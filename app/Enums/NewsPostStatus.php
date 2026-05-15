<?php

namespace App\Enums;

enum NewsPostStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';
    case Archived = 'archived';
    case Rejected = 'rejected';
}
