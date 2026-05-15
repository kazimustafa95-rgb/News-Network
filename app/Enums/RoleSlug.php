<?php

namespace App\Enums;

enum RoleSlug: string
{
    case User = 'user';
    case Subscriber = 'subscriber';
    case Moderator = 'moderator';
    case Editor = 'editor';
    case SuperAdmin = 'super_admin';
}
