<?php

namespace App\Domain\Game\Support;

use App\Domain\Game\Services\BattleLineEngine;

final class GameStateViewProjector
{
    public function __construct(
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
        private readonly BattleLineEngine $engine = new BattleLineEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function project(array $state, ?string $viewerPlayerId): array
    {
        $deserializedState = $this->serializer->deserialize($state);
        $viewer = $viewerPlayerId !== null ? ($state['players'][$viewerPlayerId] ?? null) : null;
        $opponentPlayerId = $viewerPlayerId !== null ? $deserializedState->opponentIdOf($viewerPlayerId) : null;
        $opponent = $opponentPlayerId !== null ? ($state['players'][$opponentPlayerId] ?? null) : null;

        $flags = [];

        foreach (array_values($state['flags']) as $flag) {
            $viewerCards = $viewerPlayerId !== null ? ($flag['cards_by_player'][$viewerPlayerId] ?? []) : [];
            $opponentCards = $opponentPlayerId !== null ? ($flag['cards_by_player'][$opponentPlayerId] ?? []) : [];

            $flags[] = [
                'index' => $flag['index'],
                'claimed_by' => $flag['claimed_by'],
                'claimed_by_viewer' => $flag['claimed_by'] === $viewerPlayerId,
                'claimed_by_opponent' => $flag['claimed_by'] === $opponentPlayerId,
                'viewer_cards' => $viewerCards,
                'viewer_card_count' => count($viewerCards),
                'opponent_cards' => $opponentCards,
                'opponent_card_count' => count($opponentCards),
            ];
        }

        return [
            'turn' => [
                'active_player_id' => $state['active_player_id'],
                'phase' => $state['phase'],
                'next_play_order' => $state['next_play_order'],
                'troop_deck_count' => count($state['troop_deck']),
                'winner_id' => $state['winner_id'],
                'is_viewer_active' => $viewerPlayerId !== null && $state['active_player_id'] === $viewerPlayerId,
            ],
            'viewer' => $viewer === null ? null : [
                'player_id' => $viewer['player_id'],
                'hand' => $viewer['hand'],
                'hand_count' => count($viewer['hand']),
                'claimed_flags' => $viewer['claimed_flags'],
                'claimed_flag_count' => count($viewer['claimed_flags']),
            ],
            'opponent' => $opponent === null ? null : [
                'player_id' => $opponent['player_id'],
                'hand' => null,
                'hand_count' => count($opponent['hand']),
                'claimed_flags' => $opponent['claimed_flags'],
                'claimed_flag_count' => count($opponent['claimed_flags']),
            ],
            'board' => [
                'flags' => $flags,
            ],
            'available_actions' => $viewerPlayerId === null ? null : [
                'can_play_troop' => $this->engine->playableFlagIndexes($deserializedState, $viewerPlayerId) !== []
                    && $deserializedState->activePlayerId === $viewerPlayerId
                    && $deserializedState->phase->value === 'playing_card',
                'playable_flag_indexes' => $this->engine->playableFlagIndexes($deserializedState, $viewerPlayerId),
                'claimable_flag_indexes' => $this->engine->claimableFlagIndexes($deserializedState, $viewerPlayerId),
                'can_pass' => $this->engine->canPass($deserializedState, $viewerPlayerId),
                'can_finish_turn' => $this->engine->canFinishTurn($deserializedState, $viewerPlayerId),
            ],
        ];
    }
}
