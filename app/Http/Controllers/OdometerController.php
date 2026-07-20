<?php

namespace App\Http\Controllers;

use App\Models\OdometerReading;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OdometerController extends Controller
{
    public function index(): View
    {
        $readings = OdometerReading::with(['vehicle', 'recorder'])
            ->latest('recorded_at')
            ->paginate(15);

        $vehicles = Vehicle::orderBy('plate_number')->get();

        return view('odometer.index', compact('readings', 'vehicles'));
    }

    public function store(Request $request, Vehicle $vehicle, BlockchainService $blockchain)
    {
        $data = $request->validate([
            'mileage' => ['required', 'integer', 'min:'.$vehicle->current_mileage],
            'assignment_id' => ['nullable', 'exists:vehicle_assignments,id'],
        ]);

        if ($data['mileage'] < $vehicle->current_mileage) {
            return back()->withErrors(['mileage' => 'Le kilométrage ne peut pas être inférieur au relevé précédent.']);
        }

        $recordedAt = now();
        $payload = json_encode([
            'vehicle_id' => $vehicle->id,
            'mileage' => $data['mileage'],
            'recorded_by' => $request->user()->id,
            'timestamp' => $recordedAt->toIso8601String(),
        ]);

        $record = $blockchain->record($payload, 'odometer', $vehicle->id);

        OdometerReading::create([
            'vehicle_id' => $vehicle->id,
            'recorded_by' => $request->user()->id,
            'assignment_id' => $data['assignment_id'] ?? null,
            'mileage' => $data['mileage'],
            'recorded_at' => $recordedAt,
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'content_hash' => $record['content_hash'],
            'is_locked' => true,
        ]);

        $vehicle->update(['current_mileage' => $data['mileage']]);

        $redirect = $request->input('redirect') === 'odometer.index'
            ? redirect()->route('odometer.index')
            : back();

        return $redirect->with('success', 'Compteur certifié et verrouillé on-chain. TX : '.$record['blockchain_tx_hash']);
    }

    public function update(OdometerReading $reading)
    {
        abort(403, 'Modification impossible : relevé certifié et verrouillé par la blockchain.');
    }

    public function destroy(OdometerReading $reading)
    {
        abort(403, 'Suppression impossible : relevé certifié et verrouillé par la blockchain.');
    }
}
