<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AlertService;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $query = Vehicle::with('assignedDriver');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()->hasRole(UserRole::Driver)) {
            $query->where('assigned_driver_id', $request->user()->id);
        }

        $vehicles = $query->latest()->paginate(10);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->get();
        $existingPlates = Vehicle::orderBy('plate_number')->pluck('plate_number');

        return view('vehicles.create', compact('drivers', 'existingPlates'));
    }

    public function store(Request $request, BlockchainService $blockchain, AlertService $alertService)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'technical_inspection_due' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
            'next_oil_change' => ['nullable', 'date'],
            'assigned_driver_id' => ['nullable', 'exists:users,id'],
        ], [
            'plate_number.unique' => 'Cette immatriculation est déjà enregistrée dans la flotte.',
            'plate_number.required' => 'L\'immatriculation est obligatoire.',
        ]);

        $record = $blockchain->record(json_encode([
            'plate' => $data['plate_number'],
            'mileage' => $data['current_mileage'],
            'action' => 'vehicle_registration',
        ]), 'vehicle_registration');

        $vehicle = Vehicle::create([
            ...$data,
            'status' => $data['assigned_driver_id'] ? VehicleStatus::OnMission : VehicleStatus::Available,
            'blockchain_hash' => $record['content_hash'],
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
        ]);

        $alertService->syncForVehicle($vehicle);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Véhicule enregistré. Hash blockchain : '.$record['blockchain_tx_hash']);
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load([
            'assignedDriver',
            'maintenances.mechanic',
            'odometerReadings.recorder',
            'assignments.driver',
            'documents.uploader',
            'fuelRecords.driver',
            'alerts',
        ]);

        $timeline = $vehicle->timelineEvents();
        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->get();
        $mechanics = User::where('role', UserRole::Mechanic)->where('is_active', true)->get();

        return view('vehicles.show', compact('vehicle', 'timeline', 'drivers', 'mechanics'));
    }

    public function edit(Vehicle $vehicle): View
    {
        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->get();

        return view('vehicles.edit', compact('vehicle', 'drivers'));
    }

    public function update(Request $request, Vehicle $vehicle, AlertService $alertService)
    {
        $data = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number,'.$vehicle->id],
            'status' => ['required', 'in:available,on_mission,in_repair'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'technical_inspection_due' => ['nullable', 'date'],
            'insurance_expiry' => ['nullable', 'date'],
            'next_oil_change' => ['nullable', 'date'],
            'assigned_driver_id' => ['nullable', 'exists:users,id'],
        ]);

        $vehicle->update($data);
        $alertService->syncForVehicle($vehicle);

        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Véhicule mis à jour.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('success', 'Véhicule archivé.');
    }
}
