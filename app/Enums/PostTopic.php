<?php

namespace App\Enums;

enum PostTopic: string
{
    case General = 'general';
    case Politics = 'politics';
    case Sports = 'sports';
    case Weather = 'weather';
    case Community = 'community';
    case Traffic = 'traffic';
}
