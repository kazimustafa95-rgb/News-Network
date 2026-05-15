<?php

namespace App\Support\Enums;

use BackedEnum;
use Illuminate\Support\Str;

class EnumOptions
{
    public static function for(string $enumClass): array
    {
        return collect($enumClass::cases())
            ->mapWithKeys(fn (BackedEnum $case): array => [
                $case->value => Str::headline(str_replace('_', ' ', $case->value)),
            ])
            ->all();
    }
}
