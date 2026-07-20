<?php

namespace App\Http\Controllers;

use App\Models\FuelRecord;
use App\Models\Vehicle;
use App\Services\FuelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelController extends Controller
{
    public function index(): View
    {
        $records = FuelRecord::with(['vehicle', 'driver'])
            ->latest('filled_at')
            ->paginate(15);

        $vehicles = Vehicle::orderBy('plate_number')->get();

        $stats = [
            'total_liters' => FuelRecord::sum('liters'),
            'total_cost' => FuelRecord::sum('cost'),
            'avg_consumption' => round(FuelRecord::whereNotNull('consumption_avg')->avg('consumption_avg') ?? 0, 2),
        ];

        return view('fuel.index', compact('records', 'vehicles', 'stats'));
    }

    public function store(Request $request, Vehicle $vehicle, FuelService $fuelService)
    {
        $data = $request->validate([
            'liters' => ['required', 'numeric', 'min:0.1'],
            'cost' => ['required', 'numeric', 'min:0'],
            'mileage_at_fill' => ['required', 'integer', 'min:0'],
            'slip_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $record = $fuelService->recordConsumption(
            $vehicle,
            $request->user()->id,
            $data['liters'],
            $data['cost'],
            $data['mileage_at_fill'],
            $data['slip_reference'] ?? null
        );

        $redirect = $request->input('redirect') === 'fuel.index'
            ? redirect()->route('fuel.index')
            : back();

        return $redirect->with('success', 'Plein enregistré. Consommation : '
            .($record->consumption_avg ? $record->consumption_avg.' L/100km' : 'N/A'));
    }
}
