<?php

namespace App\Models;

use App\Enums\ArchivePurchaseStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivePurchase extends Model
{
    protected $fillable = [
        'user_id',
        'county_id',
        'archive_date',
        'provider',
        'provider_transaction_id',
        'amount_cents',
        'currency',
        'status',
        'purchased_at',
        'verified_at',
        'refunded_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'archive_date' => 'date',
            'status' => ArchivePurchaseStatus::class,
            'purchased_at' => 'datetime',
            'verified_at' => 'datetime',
            'refunded_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }
}
