<?php

namespace App\Enums;

enum CountyStatus: string
{
    case Active = 'active';
    case ComingSoon = 'coming_soon';
    case Inactive = 'inactive';
}
