<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverTrip extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'trip_at',
        'origin',
        'destination',
        'distance_km',
        'notes',
    ];

    protected $casts = [
        'trip_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
