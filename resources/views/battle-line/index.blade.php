<x-layouts.battle-line :title="'Battle Line Command'">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-8 sm:px-8 lg:px-10">
        @if ($errors->has('game'))
            <div class="mb-6 rounded-[1.6rem] border border-red-400/30 bg-red-500/10 px-5 py-4 text-sm leading-6 text-red-100">
                {{ $errors->first('game') }}
            </div>
        @endif

        <header class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
            <section class="space-y-6 rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-9">
                <p class="text-sm font-semibold uppercase tracking-[0.32em] text-war-gold/75">Battle Line Online</p>
                <div class="space-y-4">
                    <h1 class="font-display text-5xl leading-none text-white sm:text-6xl">Command the line. Face a real opponent.</h1>
                    <p class="max-w-2xl text-base leading-7 text-white/70 sm:text-lg">
                        The board now uses real user accounts, authenticated seats, and joinable live matches so each commander sees only their own hand and actions.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Auth</p>
                        <p class="mt-2 text-sm text-white/70">Register once, sign in, and let Laravel session auth identify your seat.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Lobby</p>
                        <p class="mt-2 text-sm text-white/70">Open a waiting battle or join a challenge another commander has already posted.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Fair Play</p>
                        <p class="mt-2 text-sm text-white/70">Battle actions resolve from your authenticated identity, not from client-supplied player names.</p>
                    </div>
                </div>
            </section>

            @guest
                <section class="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-8">
                    <div class="mb-6 space-y-2">
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-war-gold/75">Join The Hall</p>
                        <h2 class="font-display text-3xl text-white">Sign in to start playing</h2>
                    </div>

                    <div class="grid gap-4">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl border border-war-gold/40 bg-war-gold/15 px-5 py-3 font-semibold text-war-gold transition hover:bg-war-gold/25">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/[0.05] px-5 py-3 font-semibold text-white/75 transition hover:bg-white/[0.09]">
                            Create Account
                        </a>
                    </div>

                    <div class="mt-6 rounded-[1.8rem] border border-white/10 bg-white/[0.04] p-5">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">How live play works</p>
                        <div class="mt-4 grid gap-3 text-sm leading-6 text-white/70">
                            <p>1. Register or sign in with your commander name.</p>
                            <p>2. Create a waiting battle or join one from the lobby.</p>
                            <p>3. Once two users are seated, the rules engine deals hidden hands and the match begins.</p>
                        </div>
                    </div>
                </section>
            @else
                <section class="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-8">
                    <div class="mb-6 space-y-2">
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-war-gold/75">New Match</p>
                        <h2 class="font-display text-3xl text-white">Open a challenge</h2>
                    </div>

                    <form action="{{ route('battle-line-games.store') }}" method="post" class="grid gap-5">
                        @csrf

                        <div class="rounded-[1.8rem] border border-white/10 bg-white/[0.04] p-5">
                            <p class="text-sm font-medium text-white/80">You will be seated as player one.</p>
                            <p class="mt-2 text-sm leading-6 text-white/60">
                                Another signed-in user can join from the lobby. Cards are dealt only after the second commander joins, so the board stays fair from the first turn.
                            </p>
                        </div>

                        @if ($openGame)
                            <div class="rounded-[1.8rem] border border-war-gold/30 bg-war-gold/10 p-5">
                                <p class="text-sm font-medium text-war-gold">You already have an open battle.</p>
                                <p class="mt-2 text-sm leading-6 text-white/65">
                                    Finish or close your current battle before creating another one.
                                </p>
                                <a href="{{ route('battle-line-games.page.show', $openGame) }}" class="mt-4 inline-flex rounded-full border border-war-gold/35 bg-war-gold/12 px-4 py-2 text-sm font-semibold text-war-gold transition hover:bg-war-gold/20">
                                    Return to Battle #{{ $openGame->id }}
                                </a>
                            </div>
                        @endif

                        <button
                            type="submit"
                            @disabled((bool) $openGame)
                            class="inline-flex items-center justify-center rounded-2xl border border-war-gold/40 bg-war-gold/15 px-5 py-3 font-semibold text-war-gold transition hover:bg-war-gold/25 disabled:cursor-not-allowed disabled:opacity-45"
                        >
                            Create Waiting Battle
                        </button>
                    </form>
                </section>
            @endguest
        </header>

        @auth
            <section class="mt-10 space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-white/35">Open Challenges</p>
                    <h2 class="font-display text-3xl text-white">Join another commander</h2>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @forelse ($joinableGames as $game)
                        <article class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/15 backdrop-blur-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.24em] text-white/35">Battle #{{ $game->id }}</p>
                                    <h3 class="mt-2 font-display text-2xl text-white">{{ $game->player_one_name }} needs an opponent</h3>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs uppercase tracking-[0.18em] text-white/60">
                                    waiting
                                </span>
                            </div>

                            <p class="mt-4 text-sm leading-6 text-white/65">
                                Join this battle to lock the second seat, receive your opening hand, and begin a live match.
                            </p>

                            <form action="{{ route('battle-line-games.join', $game) }}" method="post" class="mt-5">
                                @csrf
                                <button
                                    type="submit"
                                    @disabled((bool) $openGame)
                                    class="rounded-full border border-war-ember/45 bg-war-ember/10 px-4 py-2 text-sm font-semibold text-war-ash transition hover:bg-war-ember/18 disabled:cursor-not-allowed disabled:opacity-45"
                                >
                                    Join Battle
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-[1.8rem] border border-dashed border-white/10 bg-white/[0.035] p-8 text-white/55 lg:col-span-3">
                            {{ $openGame
                                ? 'You already have an open battle, so the lobby is locked until that match is finished.'
                                : 'No open challenges right now. Create one above and wait for another commander to answer.' }}
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="mt-10 space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-white/35">Your Battles</p>
                    <h2 class="font-display text-3xl text-white">Return to the front</h2>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @forelse ($myGames as $game)
                        <article class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/15 backdrop-blur-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.24em] text-white/35">Battle #{{ $game->id }}</p>
                                    <h3 class="mt-2 font-display text-2xl text-white">{{ $game->player_one_name }} vs {{ $game->player_two_name }}</h3>
                                </div>
                                <span class="rounded-full border border-white/10 bg-white/[0.04] px-3 py-1 text-xs uppercase tracking-[0.18em] text-white/60">
                                    {{ str_replace('_', ' ', $game->status) }}
                                </span>
                            </div>

                            <p class="mt-4 text-sm leading-6 text-white/65">
                                {{ $game->status === \App\Models\BattleLineGame::WaitingForOpponentStatus
                                    ? 'This battle is waiting for a second commander to join.'
                                    : 'Open the board to continue from your authenticated seat.' }}
                            </p>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <a href="{{ route('battle-line-games.page.show', $game) }}" class="rounded-full border border-war-ember/45 bg-war-ember/10 px-4 py-2 text-sm font-semibold text-war-ash transition hover:bg-war-ember/18">
                                    Open Battle
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.8rem] border border-dashed border-white/10 bg-white/[0.035] p-8 text-white/55 lg:col-span-3">
                            You have not opened or joined a battle yet.
                        </div>
                    @endforelse
                </div>
            </section>
        @endauth
    </main>
</x-layouts.battle-line>
