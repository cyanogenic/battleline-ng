<?php

namespace App\Http\Controllers;

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Http\Requests\JoinBattleLineGameRequest;
use App\Http\Requests\StoreBattleLineGameRequest;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BattleLineGamePageController extends Controller
{
    public function __construct(
        private readonly BattleLineEngine $engine = new BattleLineEngine,
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
    ) {}

    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = $request->user();
        $myGames = collect();
        $joinableGames = collect();
        $openGame = null;

        if ($user !== null) {
            $openGame = BattleLineGame::query()
                ->openForUser($user)
                ->latest('updated_at')
                ->first();

            $myGames = BattleLineGame::query()
                ->forUser($user)
                ->latest()
                ->take(6)
                ->get();

            $joinableGames = BattleLineGame::query()
                ->joinableFor($user)
                ->latest()
                ->take(6)
                ->get();
        }

        return view('battle-line.index', [
            'myGames' => $myGames,
            'joinableGames' => $joinableGames,
            'openGame' => $openGame,
        ]);
    }

    public function store(StoreBattleLineGameRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (BattleLineGame::query()->openForUser($user)->exists()) {
            throw ValidationException::withMessages([
                'game' => ['You already have an open battle. Finish it before creating a new one.'],
            ]);
        }

        $game = BattleLineGame::query()->create([
            'player_one_user_id' => $user->getKey(),
            'player_one_name' => $user->name,
            'player_two_name' => 'Awaiting challenger',
            'status' => BattleLineGame::WaitingForOpponentStatus,
            'state' => [],
        ]);

        return redirect()->route('battle-line-games.page.show', $game);
    }

    /**
     * @throws ValidationException
     */
    public function join(JoinBattleLineGameRequest $request, BattleLineGame $battleLineGame): RedirectResponse
    {
        /** @var User $joiningUser */
        $joiningUser = $request->user();

        $game = DB::transaction(function () use ($battleLineGame, $joiningUser): BattleLineGame {
            /** @var BattleLineGame $lockedGame */
            $lockedGame = BattleLineGame::query()->lockForUpdate()->findOrFail($battleLineGame->getKey());

            if (BattleLineGame::query()->openForUser($joiningUser)->exists()) {
                throw ValidationException::withMessages([
                    'game' => ['You already have an open battle. Finish it before joining another one.'],
                ]);
            }

            if (! $lockedGame->canBeJoinedBy($joiningUser)) {
                throw ValidationException::withMessages([
                    'game' => ['This battle is no longer available to join.'],
                ]);
            }

            $shuffledDeck = collect(TroopCard::standardDeck())->shuffle()->values()->all();
            $firstPlayerHand = array_slice($shuffledDeck, 0, 7);
            $secondPlayerHand = array_slice($shuffledDeck, 7, 7);
            $remainingDeck = array_values(array_slice($shuffledDeck, 14));
            $startingPlayerId = random_int(0, 1) === 0
                ? BattleLineGame::PlayerOneSeat
                : BattleLineGame::PlayerTwoSeat;

            $state = $this->engine->startGame(
                firstPlayerId: BattleLineGame::PlayerOneSeat,
                secondPlayerId: BattleLineGame::PlayerTwoSeat,
                firstPlayerHand: $firstPlayerHand,
                secondPlayerHand: $secondPlayerHand,
                troopDeck: $remainingDeck,
                startingPlayerId: $startingPlayerId,
            );

            $lockedGame->forceFill([
                'player_two_user_id' => $joiningUser->getKey(),
                'player_two_name' => $joiningUser->name,
                'status' => $state->phase->value,
                'state' => $this->serializer->serialize($state),
            ])->save();

            return $lockedGame->refresh();
        });

        return redirect()->route('battle-line-games.page.show', $game);
    }

    public function show(Request $request, BattleLineGame $battleLineGame): View
    {
        $this->authorize('view', $battleLineGame);

        return view('battle-line.show', [
            'game' => $battleLineGame,
            'viewerPlayerId' => $battleLineGame->seatFor($request->user()),
        ]);
    }
}
