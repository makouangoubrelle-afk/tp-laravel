<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;
use App\Services\BlockchainService;
use App\Services\DriverPlanningService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = VehicleAssignment::with(['vehicle', 'driver', 'assignedBy'])
            ->latest('assigned_at');

        if ($request->user()->hasRole(UserRole::Driver)) {
            $query->where('driver_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $active = (clone $query)->whereIn('status', ['pending_pickup', 'active'])->get();
        $history = VehicleAssignment::with(['vehicle', 'driver'])
            ->when($request->user()->hasRole(UserRole::Driver), fn ($q) => $q->where('driver_id', $request->user()->id))
            ->where('status', 'completed')
            ->latest('returned_at')
            ->limit(10)
            ->get();

        $availableVehicles = Vehicle::where('status', VehicleStatus::Available)
            ->whereNull('assigned_driver_id')
            ->get();

        $drivers = User::where('role', UserRole::Driver)->where('is_active', true)->get();

        return view('assignments.index', compact('active', 'history', 'availableVehicles', 'drivers'));
    }

    public function store(Request $request, Vehicle $vehicle, BlockchainService $blockchain, DriverPlanningService $planning)
    {
        if ($vehicle->assigned_driver_id) {
            return back()->with('error', 'Ce véhicule est déjà attribué.');
        }

        $data = $request->validate([
            'driver_id' => ['required', 'exists:users,id'],
            'start_mileage' => ['required', 'integer', 'min:'.$vehicle->current_mileage],
            'mission_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $driver = User::where('role', UserRole::Driver)->findOrFail($data['driver_id']);

        if (! $driver->wallet_address) {
            return back()->withErrors([
                'driver_id' => 'Ce chauffeur n\'a pas de wallet Web3. Attribuez-en un dans Chauffeurs → Modifier avant de créer une mission.',
            ])->withInput();
        }

        $payload = json_encode([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $data['driver_id'],
            'start_mileage' => $data['start_mileage'],
            'action' => 'assignment_start',
            'assigned_by' => $request->user()->id,
        ]);

        $record = $blockchain->record($payload, 'assignment_start', $vehicle->id);

        VehicleAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $data['driver_id'],
            'assigned_by' => $request->user()->id,
            'assigned_at' => now(),
            'start_mileage' => $data['start_mileage'],
            'mission_notes' => $data['mission_notes'] ?? null,
            'blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'content_hash' => $record['content_hash'],
            'status' => 'pending_pickup',
        ]);

        $vehicle->update([
            'assigned_driver_id' => $data['driver_id'],
            'status' => VehicleStatus::OnMission,
        ]);

        $redirect = redirect()->route('assignments.index')
            ->with('success', 'Attribution digitale créée — en attente de prise en charge par le chauffeur.');

        $scheduleWarning = $planning->assignmentScheduleWarning($driver, now());
        if ($scheduleWarning) {
            $redirect->with('error', $scheduleWarning);
        }

        return $redirect;
    }

    public function confirmPickup(Request $request, VehicleAssignment $assignment, BlockchainService $blockchain)
    {
        if ($assignment->status !== 'pending_pickup') {
            return back()->with('error', 'Cette mission ne peut plus être confirmée.');
        }

        if ($request->user()->hasRole(UserRole::Driver) && $assignment->driver_id !== $request->user()->id) {
            abort(403);
        }

        $assignment->load('driver');

        $data = $request->validate([
            'pickup_mileage' => ['required', 'integer', 'min:'.$assignment->start_mileage],
            'pickup_confirm' => ['accepted'],
        ]);

        $signaturePayload = json_encode([
            'assignment_id' => $assignment->id,
            'driver_id' => $assignment->driver_id,
            'driver_wallet' => $assignment->driver->wallet_address,
            'pickup_mileage' => $data['pickup_mileage'],
            'confirmed_at' => now()->toIso8601String(),
            'action' => 'digital_pickup',
        ]);

        $pickupRecord = $blockchain->record($signaturePayload, 'assignment_pickup', $assignment->id);
        $signature = $pickupRecord['content_hash'];

        $assignment->update([
            'pickup_confirmed_at' => now(),
            'pickup_mileage' => $data['pickup_mileage'],
            'pickup_signature_hash' => $signature,
            'pickup_blockchain_tx_hash' => $pickupRecord['blockchain_tx_hash'],
            'status' => 'active',
        ]);

        $assignment->vehicle->update(['current_mileage' => $data['pickup_mileage']]);

        return back()->with('success', 'Prise en charge digitale confirmée. Signature : '.substr($signature, 0, 16).'...');
    }

    public function returnVehicle(Request $request, VehicleAssignment $assignment, BlockchainService $blockchain)
    {
        if ($assignment->status !== 'active') {
            return back()->with('error', 'Cette mission n\'est pas active.');
        }

        $assignment->load('driver');

        $data = $request->validate([
            'end_mileage' => ['required', 'integer', 'min:'.($assignment->pickup_mileage ?? $assignment->start_mileage)],
            'return_confirm' => ['accepted'],
        ]);

        $returnPayload = json_encode([
            'assignment_id' => $assignment->id,
            'driver_wallet' => $assignment->driver->wallet_address,
            'end_mileage' => $data['end_mileage'],
            'returned_by' => $request->user()->id,
            'action' => 'assignment_end',
        ]);

        $record = $blockchain->record($returnPayload, 'assignment_end', $assignment->id);
        $returnSignature = hash('sha256', $returnPayload);

        $assignment->update([
            'returned_at' => now(),
            'end_mileage' => $data['end_mileage'],
            'return_signature_hash' => $returnSignature,
            'return_blockchain_tx_hash' => $record['blockchain_tx_hash'],
            'status' => 'completed',
        ]);

        $assignment->vehicle->update([
            'assigned_driver_id' => null,
            'status' => VehicleStatus::Available,
            'current_mileage' => $data['end_mileage'],
        ]);

        return back()->with('success', 'Restitution digitale enregistrée. TX : '.$record['blockchain_tx_hash']);
    }
}
