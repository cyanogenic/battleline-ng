<x-layouts.battle-line :title="'Battle Line Command'">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-5 py-8 sm:px-8 lg:px-10">
        <header class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
            <section class="space-y-6 rounded-[2rem] border border-white/10 bg-white/[0.04] p-7 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-9">
                <p class="text-sm font-semibold uppercase tracking-[0.32em] text-war-gold/75">Battle Line Online</p>
                <div class="space-y-4">
                    <h1 class="font-display text-5xl leading-none text-white sm:text-6xl">Command the line. Seize the flags.</h1>
                    <p class="max-w-2xl text-base leading-7 text-white/70 sm:text-lg">
                        Create a duel, choose your seat, and step straight onto a live Battle Line board backed by the Laravel rules engine.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Rules</p>
                        <p class="mt-2 text-sm text-white/70">Nine flags, hidden hands, strongest formation wins.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Engine</p>
                        <p class="mt-2 text-sm text-white/70">Server authoritative turn flow, claims, and victory tracking.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-black/15 p-4">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Interface</p>
                        <p class="mt-2 text-sm text-white/70">Player-specific views with hidden opposing hands and live actions.</p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-white/10 bg-black/20 p-6 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-8">
                <div class="mb-6 space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-war-gold/75">New Match</p>
                    <h2 class="font-display text-3xl text-white">Set the field</h2>
                </div>

                <form
                    action="{{ route('battle-line-games.store') }}"
                    method="post"
                    data-create-game-form
                    data-redirect-base="{{ url('/battle-line-games') }}"
                    class="grid gap-5"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-white/75">Player one</span>
                            <input
                                type="text"
                                name="player_one_name"
                                value="alice"
                                data-player-slot="player_one_name"
                                class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            >
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-white/75">Player two</span>
                            <input
                                type="text"
                                name="player_two_name"
                                value="bob"
                                data-player-slot="player_two_name"
                                class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none transition placeholder:text-white/30 focus:border-war-gold/50 focus:bg-white/[0.09]"
                            >
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-white/75">Viewer seat</span>
                            <select name="viewer_player_id" class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none focus:border-war-gold/50">
                                <option value="player_one_name">Player one</option>
                                <option value="player_two_name">Player two</option>
                            </select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-sm font-medium text-white/75">Starting player</span>
                            <select name="starting_player_name" class="rounded-2xl border border-white/10 bg-white/[0.06] px-4 py-3 text-white outline-none focus:border-war-gold/50">
                                <option value="player_one_name">Player one</option>
                                <option value="player_two_name">Player two</option>
                            </select>
                        </label>
                    </div>

                    <p data-form-error class="min-h-6 text-sm text-red-200"></p>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-2xl border border-war-gold/40 bg-war-gold/15 px-5 py-3 font-semibold text-war-gold transition hover:bg-war-gold/25 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Start a New Battle
                    </button>
                </form>
            </section>
        </header>

        <section class="mt-10 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-white/35">Recent Games</p>
                    <h2 class="font-display text-3xl text-white">Continue the campaign</h2>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                @forelse ($recentGames as $game)
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
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('battle-line-games.page.show', ['battleLineGame' => $game, 'viewer_player_id' => $game->player_one_name]) }}" class="rounded-full border border-war-ember/45 bg-war-ember/10 px-4 py-2 text-sm font-semibold text-war-ash transition hover:bg-war-ember/18">
                                View as {{ $game->player_one_name }}
                            </a>
                            <a href="{{ route('battle-line-games.page.show', ['battleLineGame' => $game, 'viewer_player_id' => $game->player_two_name]) }}" class="rounded-full border border-white/10 bg-white/[0.04] px-4 py-2 text-sm font-semibold text-white/70 transition hover:bg-white/[0.08]">
                                View as {{ $game->player_two_name }}
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.8rem] border border-dashed border-white/10 bg-white/[0.035] p-8 text-white/55 lg:col-span-3">
                        No battles yet. Start one from the command panel above.
                    </div>
                @endforelse
            </div>
        </section>
    </main>
</x-layouts.battle-line>
