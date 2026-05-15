<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_name',
        'otp_code',
        'attempts',
        'last_sent_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
