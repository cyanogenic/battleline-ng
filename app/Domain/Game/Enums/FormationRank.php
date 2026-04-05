<?php

namespace App\Domain\Game\Enums;

enum FormationRank: int
{
    case Host = 1;
    case SkirmishLine = 2;
    case BattalionOrder = 3;
    case Phalanx = 4;
    case Wedge = 5;
}
