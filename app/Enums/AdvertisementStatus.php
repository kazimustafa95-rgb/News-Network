<?php

namespace App\Enums;

enum AdvertisementStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
}
