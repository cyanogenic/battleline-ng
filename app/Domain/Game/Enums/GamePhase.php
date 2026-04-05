<?php

namespace App\Domain\Game\Enums;

enum GamePhase: string
{
    case PlayingCard = 'playing_card';
    case ClaimingFlags = 'claiming_flags';
    case GameOver = 'game_over';
}
