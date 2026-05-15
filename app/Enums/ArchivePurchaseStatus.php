<?php

namespace App\Enums;

enum ArchivePurchaseStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Revoked = 'revoked';
}
