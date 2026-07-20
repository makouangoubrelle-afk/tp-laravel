<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alertService): View
    {
        $alertService->syncAll();
        $stats = $alertService->stats();

        $alerts = Alert::with('vehicle')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('due_date')
            ->paginate(15);

        return view('alerts.index', compact('alerts', 'stats'));
    }

    public function resolve(Alert $alert)
    {
        $alert->update(['status' => 'resolved']);

        return back()->with('success', 'Alerte marquée comme résolue.');
    }

    public function sync(AlertService $alertService)
    {
        $alertService->syncAll();

        return back()->with('success', 'Alertes synchronisées automatiquement.');
    }
}
