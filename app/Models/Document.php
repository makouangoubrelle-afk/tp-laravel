<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'vehicle_id',
        'uploaded_by',
        'type',
        'title',
        'file_path',
        'checksum',
        'content_hash',
        'blockchain_tx_hash',
        'ipfs_hash',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'registration' => 'Carte grise',
            'insurance' => 'Assurance',
            'invoice' => 'Facture',
            'inspection' => 'Contrôle technique',
            'contract' => 'Contrat',
            default => 'Autre',
        };
    }

    public function typeIcon(): string
    {
        return match ($this->type) {
            'registration' => 'bi-card-heading',
            'insurance' => 'bi-shield-check',
            'invoice' => 'bi-receipt',
            'inspection' => 'bi-clipboard-check',
            'contract' => 'bi-file-earmark-text',
            default => 'bi-file-earmark',
        };
    }
}
