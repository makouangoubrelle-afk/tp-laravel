<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\IntegrityService;
use Illuminate\View\View;

class BlockchainHistoryController extends Controller
{
    public function index(IntegrityService $integrityService): View
    {
        $vehicles = Vehicle::withCount(['odometerReadings', 'maintenances', 'assignments', 'fuelRecords'])->get();

        $reports = $vehicles->map(fn (Vehicle $v) => $integrityService->vehicleIntegrityReport($v));

        $globalScore = $reports->avg('score') ?? 100;
        $tamperProof = $reports->every(fn ($r) => $r['mileage_tamper_proof']);

        return view('history.index', compact('reports', 'globalScore', 'tamperProof'));
    }

    public function show(Vehicle $vehicle, IntegrityService $integrityService): View
    {
        $report = $integrityService->vehicleIntegrityReport($vehicle);
        $timeline = $vehicle->load([
            'maintenances.mechanic',
            'odometerReadings.recorder',
            'assignments.driver',
            'documents',
            'fuelRecords.driver',
        ])->timelineEvents();

        return view('history.show', compact('report', 'vehicle', 'timeline'));
    }
}
