<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdometerReading extends Model
{
    protected $fillable = [
        'vehicle_id',
        'recorded_by',
        'assignment_id',
        'mileage',
        'recorded_at',
        'blockchain_tx_hash',
        'content_hash',
        'is_locked',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VehicleAssignment::class, 'assignment_id');
    }
}
