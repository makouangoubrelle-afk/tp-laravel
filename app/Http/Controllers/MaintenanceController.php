<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Maintenance;
use App\Models\Vehicle;
use App\Services\AlertService;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(): View
    {
        $maintenances = Maintenance::with(['vehicle', 'mechanic'])
            ->latest('service_date')
            ->paginate(15);

        $vehicles = Vehicle::orderBy('plate_number')->get();
        $stats = [
            'total' => Maintenance::count(),
            'cost' => Maintenance::sum('cost'),
            'certified' => Maintenance::whereNotNull('blockchain_tx_hash')->count(),
        ];

        return view('maintenances.index', compact('maintenances', 'vehicles', 'stats'));
    }

    public function store(Request $request, Vehicle $vehicle, BlockchainService $blockchain, AlertService $alertService)
    {
        $data = $request->validate([
            'service_date' => ['required', 'date'],
            'intervention_type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'parts_replaced' => ['nullable', 'string'],
            'mileage_at_service' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $parts = $data['parts_replaced']
            ? array_map('trim', explode(',', $data['parts_replaced']))
            : [];

        $payload = json_encode([
            'vehicle_id' => $vehicle->id,
            'mechanic_id' => $request->user()->id,
            'intervention' => $data['intervention_type'],
            'mileage' => $data['mileage_at_service'],
            'parts' => $parts,
        ]);

        $record = $blockchain->record($payload, 'maintenance', $vehicle->id);

        Maintenance::create([
            'vehicle_id' => $vehicle->id,
            'mechanic_id' => $request->user()->id,
            'service_date' => $data['service_date'],
            'intervention_type' => $data['intervention_type'],
            'description' => $data['description'],
            'parts_replaced' => $parts,
            'mileage_at_service' => $data['mileage_at_service'],
            'cost' => $data['cost'],
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'content_hash' => $record['content_hash'],
            'ipfs_hash' => null,
        ]);

        $vehicle->update([
            'current_mileage' => max($vehicle->current_mileage, $data['mileage_at_service']),
            'status' => VehicleStatus::Available,
        ]);

        $alertService->syncForVehicle($vehicle);

        $redirect = $request->input('redirect') === 'maintenances.index'
            ? redirect()->route('maintenances.index')
            : back();

        return $redirect->with('success', 'Maintenance certifiée on-chain. TX : '.$record['blockchain_tx_hash']);
    }
}
