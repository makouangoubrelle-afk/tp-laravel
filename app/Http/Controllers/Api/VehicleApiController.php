<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleApiController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::with(['assignedDriver', 'maintenances', 'odometerReadings'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->get()
            ->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'brand' => $v->brand,
                'model' => $v->model,
                'plate_number' => $v->plate_number,
                'status' => $v->status->value,
                'current_mileage' => $v->current_mileage,
                'blockchain_hash' => $v->blockchain_hash,
                'driver' => $v->assignedDriver?->name,
            ]);

        return response()->json(['data' => $vehicles]);
    }

    public function show(Vehicle $vehicle)
    {
        $vehicle->load(['maintenances', 'odometerReadings', 'documents', 'fuelRecords']);

        return response()->json([
            'data' => $vehicle,
            'timeline' => $vehicle->timelineEvents(),
        ]);
    }

    public function publicHistory(Vehicle $vehicle)
    {
        $vehicle->load(['maintenances', 'odometerReadings']);

        return response()->json([
            'plate_number' => $vehicle->plate_number,
            'certified_history' => $vehicle->timelineEvents(),
            'blockchain_hash' => $vehicle->blockchain_hash,
        ]);
    }
}
