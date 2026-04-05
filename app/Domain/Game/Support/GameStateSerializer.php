<?php

namespace App\Domain\Game\Support;

use App\Domain\Game\Entities\FlagState;
use App\Domain\Game\Entities\GameState;
use App\Domain\Game\Entities\PlayerState;
use App\Domain\Game\Enums\GamePhase;
use App\Domain\Game\Enums\TroopColor;
use App\Domain\Game\ValueObjects\PlacedTroopCard;
use App\Domain\Game\ValueObjects\TroopCard;

final class GameStateSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(GameState $state): array
    {
        return [
            'player_order' => $state->playerOrder,
            'active_player_id' => $state->activePlayerId,
            'phase' => $state->phase->value,
            'next_play_order' => $state->nextPlayOrder,
            'winner_id' => $state->winnerId,
            'troop_deck' => array_map($this->serializeCard(...), $state->troopDeck),
            'players' => array_map(
                fn (PlayerState $player): array => [
                    'player_id' => $player->playerId,
                    'hand' => array_map($this->serializeCard(...), $player->hand),
                    'claimed_flags' => $player->claimedFlags,
                ],
                $state->players,
            ),
            'flags' => array_map(
                fn (FlagState $flag): array => [
                    'index' => $flag->index,
                    'claimed_by' => $flag->claimedBy,
                    'cards_by_player' => array_map(
                        fn (array $cards): array => array_map(
                            fn (PlacedTroopCard $card): array => [
                                'play_order' => $card->playOrder,
                                'card' => $this->serializeCard($card->card),
                            ],
                            $cards,
                        ),
                        $flag->cardsByPlayer,
                    ),
                ],
                $state->flags,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deserialize(array $payload): GameState
    {
        return new GameState(
            playerOrder: $payload['player_order'],
            activePlayerId: $payload['active_player_id'],
            phase: GamePhase::from($payload['phase']),
            players: array_map(
                fn (array $player): PlayerState => new PlayerState(
                    playerId: $player['player_id'],
                    hand: array_map(fn (array $card): TroopCard => $this->deserializeCard($card), $player['hand']),
                    claimedFlags: $player['claimed_flags'],
                ),
                $payload['players'],
            ),
            flags: array_map(
                fn (array $flag): FlagState => new FlagState(
                    index: $flag['index'],
                    claimedBy: $flag['claimed_by'],
                    cardsByPlayer: array_map(
                        fn (array $cards): array => array_map(
                            fn (array $card): PlacedTroopCard => new PlacedTroopCard(
                                card: $this->deserializeCard($card['card']),
                                playOrder: $card['play_order'],
                            ),
                            $cards,
                        ),
                        $flag['cards_by_player'],
                    ),
                ),
                $payload['flags'],
            ),
            troopDeck: array_map(fn (array $card): TroopCard => $this->deserializeCard($card), $payload['troop_deck']),
            nextPlayOrder: $payload['next_play_order'],
            winnerId: $payload['winner_id'],
        );
    }

    /**
     * @return array{id: string, color: string, strength: int}
     */
    private function serializeCard(TroopCard $card): array
    {
        return [
            'id' => $card->id,
            'color' => $card->color->value,
            'strength' => $card->strength,
        ];
    }

    /**
     * @param  array{id: string, color: string, strength: int}  $payload
     */
    private function deserializeCard(array $payload): TroopCard
    {
        return new TroopCard(
            id: $payload['id'],
            color: TroopColor::from($payload['color']),
            strength: $payload['strength'],
        );
    }
}
