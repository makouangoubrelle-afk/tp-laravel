<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleAssignment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'assigned_by',
        'assigned_at',
        'pickup_confirmed_at',
        'pickup_mileage',
        'pickup_signature_hash',
        'pickup_blockchain_tx_hash',
        'returned_at',
        'start_mileage',
        'end_mileage',
        'return_signature_hash',
        'return_blockchain_tx_hash',
        'blockchain_tx_hash',
        'content_hash',
        'mission_notes',
        'status',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'pickup_confirmed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function odometerReadings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OdometerReading::class, 'assignment_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPendingPickup(): bool
    {
        return $this->status === 'pending_pickup';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending_pickup' => 'En attente de prise en charge',
            'active' => 'Mission en cours',
            'completed' => 'Terminée',
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending_pickup' => 'warning',
            'active' => 'info',
            'completed' => 'success',
            default => 'secondary',
        };
    }
}
