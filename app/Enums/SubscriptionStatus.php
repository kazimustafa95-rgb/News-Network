<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Grace = 'grace';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Revoked = 'revoked';
}
