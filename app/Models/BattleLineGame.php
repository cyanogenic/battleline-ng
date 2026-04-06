<?php

namespace App\Models;

use App\Domain\Game\Enums\GamePhase;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'player_one_user_id',
    'player_two_user_id',
    'player_one_name',
    'player_two_name',
    'status',
    'winner_user_id',
    'winner_name',
    'state',
])]
class BattleLineGame extends Model
{
    public const string PlayerOneSeat = 'player_one';

    public const string PlayerTwoSeat = 'player_two';

    public const string WaitingForOpponentStatus = 'waiting_for_opponent';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'player_one_user_id' => 'integer',
            'player_two_user_id' => 'integer',
            'winner_user_id' => 'integer',
            'state' => 'array',
        ];
    }

    public function playerOneUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_one_user_id');
    }

    public function playerTwoUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_two_user_id');
    }

    public function winnerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('player_one_user_id', $user->getKey())
                ->orWhere('player_two_user_id', $user->getKey());
        });
    }

    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', GamePhase::GameOver->value);
    }

    public function scopeOpenForUser(Builder $query, User $user): void
    {
        $query->open()->forUser($user);
    }

    public function scopeJoinableFor(Builder $query, User $user): void
    {
        $query
            ->where('status', self::WaitingForOpponentStatus)
            ->whereNull('player_two_user_id')
            ->where('player_one_user_id', '!=', $user->getKey());
    }

    public function isOpen(): bool
    {
        return $this->status !== GamePhase::GameOver->value;
    }

    public function hasStarted(): bool
    {
        return $this->player_one_user_id !== null
            && $this->player_two_user_id !== null
            && $this->status !== self::WaitingForOpponentStatus
            && $this->state !== [];
    }

    public function isParticipant(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return in_array(
            $user->getKey(),
            [$this->player_one_user_id, $this->player_two_user_id],
            true,
        );
    }

    public function canBeJoinedBy(User $user): bool
    {
        return $this->status === self::WaitingForOpponentStatus
            && $this->player_one_user_id !== null
            && $this->player_two_user_id === null
            && $this->player_one_user_id !== $user->getKey();
    }

    public function seatFor(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return match ($user->getKey()) {
            $this->player_one_user_id => self::PlayerOneSeat,
            $this->player_two_user_id => self::PlayerTwoSeat,
            default => null,
        };
    }

    public function nameForSeat(string $seat): ?string
    {
        return match ($seat) {
            self::PlayerOneSeat => $this->player_one_name,
            self::PlayerTwoSeat => $this->player_two_name,
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public function seatNameMap(): array
    {
        return array_filter([
            self::PlayerOneSeat => $this->player_one_name,
            self::PlayerTwoSeat => $this->player_two_name,
        ], static fn (?string $name): bool => $name !== null);
    }
}
