<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelRecord extends Model
{
    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'liters',
        'cost',
        'mileage_at_fill',
        'consumption_avg',
        'filled_at',
        'slip_reference',
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'cost' => 'decimal:2',
        'consumption_avg' => 'decimal:2',
        'filled_at' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
