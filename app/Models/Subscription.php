<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_product_id',
        'provider_transaction_id',
        'plan_code',
        'status',
        'started_at',
        'ends_at',
        'cancelled_at',
        'verified_at',
        'auto_renew',
        'receipt_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'verified_at' => 'datetime',
            'auto_renew' => 'boolean',
            'receipt_payload' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
