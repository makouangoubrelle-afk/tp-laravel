<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    protected $fillable = [
        'vehicle_id',
        'mechanic_id',
        'service_date',
        'intervention_type',
        'description',
        'parts_replaced',
        'mileage_at_service',
        'cost',
        'document_checksum',
        'ipfs_hash',
        'blockchain_tx_hash',
        'content_hash',
    ];

    protected $casts = [
        'service_date' => 'date',
        'parts_replaced' => 'array',
        'cost' => 'decimal:2',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }
}
