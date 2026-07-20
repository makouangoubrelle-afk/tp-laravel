<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Alert;
use App\Models\Document;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\AlertService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AlertService $alertService): View
    {
        $alertService->syncAll();

        $stats = [
            'total' => Vehicle::count(),
            'available' => Vehicle::where('status', VehicleStatus::Available)->count(),
            'on_mission' => Vehicle::where('status', VehicleStatus::OnMission)->count(),
            'in_repair' => Vehicle::where('status', VehicleStatus::InRepair)->count(),
        ];

        $alertStats = $alertService->stats();

        $alerts = Alert::with('vehicle')
            ->where('status', 'pending')
            ->orderByPriority()
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $activeAssignments = VehicleAssignment::with(['vehicle', 'driver'])
            ->whereIn('status', ['pending_pickup', 'active'])
            ->latest('assigned_at')
            ->limit(5)
            ->get();

        $documentStats = [
            'total' => Document::count(),
            'expiring' => Document::whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->count(),
        ];

        $certifiedVehicles = Vehicle::whereNotNull('blockchain_hash')->count();
        $totalVehicles = Vehicle::count();
        $integrityScore = $totalVehicles > 0 ? round(($certifiedVehicles / $totalVehicles) * 100) : 100;

        $recentVehicles = Vehicle::with('assignedDriver')->latest()->limit(5)->get();
        $blockchainConfig = [
            'enabled' => config('blockchain.mode') === 'polygon',
            'network' => config('blockchain.network'),
            'contract' => config('blockchain.contract_address'),
            'explorer' => config('blockchain.explorer_url'),
        ];

        return view('dashboard', compact(
            'stats', 'alerts', 'alertStats', 'activeAssignments',
            'documentStats', 'integrityScore', 'recentVehicles', 'blockchainConfig'
        ));
    }
}
