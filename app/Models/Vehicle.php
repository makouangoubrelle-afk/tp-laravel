<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'brand',
        'model',
        'plate_number',
        'status',
        'current_mileage',
        'technical_inspection_due',
        'insurance_expiry',
        'next_oil_change',
        'blockchain_hash',
        'blockchain_tx_hash',
        'assigned_driver_id',
    ];

    protected $casts = [
        'status' => VehicleStatus::class,
        'technical_inspection_due' => 'date',
        'insurance_expiry' => 'date',
        'next_oil_change' => 'date',
    ];

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function odometerReadings(): HasMany
    {
        return $this->hasMany(OdometerReading::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function fuelRecords(): HasMany
    {
        return $this->hasMany(FuelRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model}";
    }

    public function timelineEvents(): array
    {
        $events = collect();

        $this->maintenances->each(function (Maintenance $m) use ($events) {
            $events->push([
                'date' => $m->service_date,
                'type' => 'maintenance',
                'title' => "Maintenance : {$m->intervention_type}",
                'description' => $m->description,
                'certified' => (bool) $m->blockchain_tx_hash,
                'hash' => $m->blockchain_tx_hash,
            ]);
        });

        $this->odometerReadings->each(function (OdometerReading $o) use ($events) {
            $events->push([
                'date' => $o->recorded_at,
                'type' => 'odometer',
                'title' => "Kilométrage certifié : {$o->mileage} km",
                'description' => null,
                'certified' => (bool) $o->blockchain_tx_hash,
                'hash' => $o->blockchain_tx_hash,
            ]);
        });

        $this->assignments->each(function (VehicleAssignment $a) use ($events) {
            $events->push([
                'date' => $a->assigned_at,
                'type' => 'assignment',
                'title' => 'Prise en charge véhicule',
                'description' => "Départ : {$a->start_mileage} km",
                'certified' => (bool) $a->blockchain_tx_hash,
                'hash' => $a->blockchain_tx_hash,
            ]);
        });

        $this->documents->each(function (Document $d) use ($events) {
            $events->push([
                'date' => $d->created_at,
                'type' => 'document',
                'title' => "Document : {$d->title}",
                'description' => "Type : {$d->type}",
                'certified' => (bool) $d->blockchain_tx_hash,
                'hash' => $d->blockchain_tx_hash,
            ]);
        });

        $this->fuelRecords->each(function (FuelRecord $f) use ($events) {
            $events->push([
                'date' => $f->filled_at,
                'type' => 'fuel',
                'title' => "Plein : {$f->liters} L",
                'description' => $f->consumption_avg ? "Consommation : {$f->consumption_avg} L/100km" : null,
                'certified' => false,
                'hash' => null,
            ]);
        });

        return $events->sortByDesc('date')->values()->all();
    }
}
