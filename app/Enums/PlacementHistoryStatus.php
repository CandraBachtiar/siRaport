<?php

namespace App\Enums;

enum PlacementHistoryStatus
{
    case Verified;
    case DifferentClass;
    case Unknown;
    case Conflict;
}
