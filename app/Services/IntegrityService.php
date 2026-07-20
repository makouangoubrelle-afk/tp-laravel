<?php

namespace App\Services;

use App\Models\Maintenance;
use App\Models\OdometerReading;
use App\Models\Vehicle;
use App\Models\VehicleAssignment;

class IntegrityService
{
    public function __construct(private BlockchainService $blockchain) {}

    public function verifyOdometer(OdometerReading $reading): bool
    {
        if (! $reading->content_hash) {
            return false;
        }

        $payload = json_encode([
            'vehicle_id' => $reading->vehicle_id,
            'mileage' => $reading->mileage,
            'recorded_by' => $reading->recorded_by,
            'timestamp' => $reading->recorded_at->toIso8601String(),
        ]);

        return $this->blockchain->verify($payload, $reading->content_hash);
    }

    public function verifyMaintenance(Maintenance $maintenance): bool
    {
        if (! $maintenance->content_hash) {
            return false;
        }

        $payload = json_encode([
            'vehicle_id' => $maintenance->vehicle_id,
            'mechanic_id' => $maintenance->mechanic_id,
            'intervention' => $maintenance->intervention_type,
            'mileage' => $maintenance->mileage_at_service,
            'parts' => $maintenance->parts_replaced ?? [],
        ]);

        return $this->blockchain->verify($payload, $maintenance->content_hash);
    }

    public function verifyAssignment(VehicleAssignment $assignment): bool
    {
        if (! $assignment->content_hash) {
            return (bool) $assignment->blockchain_tx_hash;
        }

        $payload = json_encode([
            'vehicle_id' => $assignment->vehicle_id,
            'driver_id' => $assignment->driver_id,
            'start_mileage' => $assignment->start_mileage,
            'action' => 'assignment_start',
            'assigned_by' => $assignment->assigned_by,
        ]);

        return $this->blockchain->verify($payload, $assignment->content_hash);
    }

    public function vehicleIntegrityReport(Vehicle $vehicle): array
    {
        $vehicle->load(['odometerReadings', 'maintenances', 'assignments']);

        $odometerChecks = $vehicle->odometerReadings->map(fn ($r) => [
            'id' => $r->id,
            'mileage' => $r->mileage,
            'date' => $r->recorded_at,
            'valid' => $this->verifyOdometer($r),
            'tx_hash' => $r->blockchain_tx_hash,
        ]);

        $maintenanceChecks = $vehicle->maintenances->map(fn ($m) => [
            'id' => $m->id,
            'type' => $m->intervention_type,
            'date' => $m->service_date,
            'valid' => $this->verifyMaintenance($m),
            'tx_hash' => $m->blockchain_tx_hash,
        ]);

        $assignmentChecks = $vehicle->assignments->map(fn ($a) => [
            'id' => $a->id,
            'driver' => $a->driver?->name,
            'date' => $a->assigned_at,
            'valid' => $this->verifyAssignment($a),
            'tx_hash' => $a->blockchain_tx_hash,
        ]);

        $total = $odometerChecks->count() + $maintenanceChecks->count() + $assignmentChecks->count();
        $valid = $odometerChecks->where('valid', true)->count()
            + $maintenanceChecks->where('valid', true)->count()
            + $assignmentChecks->where('valid', true)->count();

        return [
            'vehicle' => $vehicle,
            'odometer' => $odometerChecks,
            'maintenances' => $maintenanceChecks,
            'assignments' => $assignmentChecks,
            'score' => $total > 0 ? round(($valid / $total) * 100) : 0,
            'total_records' => $total,
            'valid_records' => $valid,
            'mileage_tamper_proof' => $odometerChecks->isEmpty() || $odometerChecks->every(fn ($c) => $c['valid']),
        ];
    }
}
