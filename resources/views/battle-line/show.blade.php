<x-layouts.battle-line :title="'Battle #'.$game->id">
    <main class="mx-auto flex min-h-screen w-full max-w-[1600px] flex-col px-4 py-6 sm:px-6 lg:px-8">
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('battle-line-games.page.index') }}" class="text-sm font-semibold uppercase tracking-[0.26em] text-white/45 transition hover:text-white/70">Back to Command</a>
                <h1 class="mt-2 font-display text-4xl text-white">Battle #{{ $game->id }}</h1>
                <p class="mt-2 text-sm text-white/60">{{ $game->player_one_name }} vs {{ $game->player_two_name }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @foreach ($viewerOptions as $viewerOption)
                    <a
                        href="{{ route('battle-line-games.page.show', ['battleLineGame' => $game, 'viewer_player_id' => $viewerOption]) }}"
                        class="rounded-full border px-4 py-2 text-sm font-semibold transition {{ $viewerPlayerId === $viewerOption ? 'border-war-gold/45 bg-war-gold/15 text-war-gold' : 'border-white/10 bg-white/[0.04] text-white/70 hover:bg-white/[0.08]' }}"
                    >
                        View as {{ $viewerOption }}
                    </a>
                @endforeach
            </div>
        </header>

        <div
            data-battle-line-app
            data-viewer-player-id="{{ $viewerPlayerId }}"
            data-show-url="{{ route('battle-line-games.show', $game) }}"
            data-action-url="{{ route('battle-line-games.actions.store', $game) }}"
            class="grid flex-1 gap-6 xl:grid-cols-[320px_minmax(0,1fr)]"
        >
            <aside class="flex flex-col gap-4">
                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div data-turn></div>
                </section>

                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div data-viewer></div>
                </section>

                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div data-opponent></div>
                </section>

                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="font-display text-2xl text-white">Orders</h2>
                        <button data-refresh-button type="button" class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/65 transition hover:bg-white/[0.1]">Refresh</button>
                    </div>
                    <div data-actions class="grid gap-3"></div>
                </section>

                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div class="mb-3">
                        <p class="text-xs uppercase tracking-[0.28em] text-white/35">Field Intel</p>
                        <h2 class="font-display text-2xl text-white">What matters now</h2>
                    </div>
                    <div data-feedback class="grid gap-3"></div>
                </section>

                <section class="rounded-[1.8rem] border border-white/10 bg-white/[0.045] p-5 shadow-xl shadow-black/20 backdrop-blur-sm">
                    <div class="mb-3">
                        <p class="text-xs uppercase tracking-[0.28em] text-white/35">Selected Card</p>
                        <h2 class="font-display text-2xl text-white">Deployment preview</h2>
                    </div>
                    <div data-selection class="grid gap-3"></div>
                </section>
            </aside>

            <section class="flex min-h-0 flex-col gap-6">
                <div data-game-alert class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-white/60">
                    Waiting for battle state...
                </div>

                <section class="rounded-[2rem] border border-white/10 bg-black/20 p-4 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/35">Battlefield</p>
                            <h2 class="font-display text-3xl text-white">Nine contested flags</h2>
                        </div>
                        <p class="text-sm text-white/55">Tap a card, then a flag to deploy.</p>
                    </div>
                    <div data-board class="flex snap-x gap-4 overflow-x-auto pb-2"></div>
                </section>

                <section class="rounded-[2rem] border border-white/10 bg-white/[0.045] p-5 shadow-2xl shadow-black/20 backdrop-blur-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/35">Your Hand</p>
                            <h2 class="font-display text-3xl text-white">Choose your next formation</h2>
                        </div>
                    </div>
                    <div data-hand class="flex gap-3 overflow-x-auto pb-2"></div>
                </section>
            </section>
        </div>
    </main>
</x-layouts.battle-line>
