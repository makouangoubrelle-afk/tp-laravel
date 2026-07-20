<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\Document;
use App\Models\Vehicle;
use Carbon\Carbon;

class AlertService
{
    public function syncForVehicle(Vehicle $vehicle): void
    {
        $checks = [
            ['field' => 'technical_inspection_due', 'type' => 'technical_inspection', 'label' => 'Contrôle technique'],
            ['field' => 'insurance_expiry', 'type' => 'insurance_renewal', 'label' => 'Assurance'],
            ['field' => 'next_oil_change', 'type' => 'oil_change', 'label' => 'Vidange'],
        ];

        foreach ($checks as $check) {
            $dueDate = $vehicle->{$check['field']};

            if (! $dueDate) {
                continue;
            }

            $daysUntil = now()->startOfDay()->diffInDays($dueDate, false);

            if ($daysUntil <= 30) {
                Alert::updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'type' => $check['type'],
                        'status' => 'pending',
                    ],
                    [
                        'message' => "{$check['label']} du véhicule {$vehicle->plate_number} — échéance le {$dueDate->format('d/m/Y')}",
                        'due_date' => $dueDate,
                        'priority' => $this->priorityFromDays($daysUntil),
                    ]
                );
            } else {
                Alert::where('vehicle_id', $vehicle->id)
                    ->where('type', $check['type'])
                    ->where('status', 'pending')
                    ->update(['status' => 'resolved']);
            }
        }

        Document::where('vehicle_id', $vehicle->id)
            ->whereNotNull('expiry_date')
            ->each(function (Document $doc) use ($vehicle) {
                $daysUntil = now()->startOfDay()->diffInDays($doc->expiry_date, false);

                if ($daysUntil <= 30) {
                    $typeMap = [
                        'insurance' => 'insurance_renewal',
                        'registration' => 'technical_inspection',
                        'inspection' => 'technical_inspection',
                    ];

                    Alert::updateOrCreate(
                        [
                            'vehicle_id' => $vehicle->id,
                            'type' => $typeMap[$doc->type] ?? 'maintenance_due',
                            'status' => 'pending',
                        ],
                        [
                            'message' => "Document « {$doc->title} » ({$vehicle->plate_number}) expire le {$doc->expiry_date->format('d/m/Y')}",
                            'due_date' => $doc->expiry_date,
                            'priority' => $this->priorityFromDays($daysUntil),
                        ]
                    );
                }
            });
    }

    public function syncAll(): int
    {
        Vehicle::all()->each(fn (Vehicle $v) => $this->syncForVehicle($v));

        return Vehicle::count();
    }

    public function stats(): array
    {
        return [
            'pending' => Alert::where('status', 'pending')->count(),
            'critical' => Alert::where('status', 'pending')->where('priority', 'critical')->count(),
            'resolved' => Alert::where('status', 'resolved')->count(),
            'overdue' => Alert::where('status', 'pending')->where('due_date', '<', now())->count(),
        ];
    }

    private function priorityFromDays(int $daysUntil): string
    {
        if ($daysUntil < 0) {
            return 'critical';
        }
        if ($daysUntil <= 7) {
            return 'high';
        }
        if ($daysUntil <= 15) {
            return 'medium';
        }

        return 'low';
    }
}
