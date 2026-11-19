<?php

namespace App\Enums;

enum AssessmentPlacementStatus
{
    case MatchesAssignment;
    case DifferentClass;
    case Unknown;
    case Conflict;
    case DateUnavailable;
}
