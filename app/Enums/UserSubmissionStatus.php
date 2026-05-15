<?php

namespace App\Enums;

enum UserSubmissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processing = 'processing';
}
