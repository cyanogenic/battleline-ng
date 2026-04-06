<x-layouts.battle-line :title="'Battle #'.$game->id">
    <x-slot:topbar>
        <div class="flex min-w-0 justify-center">
            <button
                data-open-feedback-modal
                type="button"
                aria-expanded="false"
                aria-controls="battle-field-intel-modal"
                class="group min-w-0 rounded-[1.6rem] border border-white/10 bg-white/[0.04] px-5 py-3 text-center transition hover:border-war-gold/35 hover:bg-war-gold/10"
            >
                <p class="font-display text-xl text-white transition group-hover:text-war-gold sm:text-2xl">Battle #{{ $game->id }}</p>
                <p class="truncate text-xs uppercase tracking-[0.18em] text-white/45 transition group-hover:text-white/65 sm:text-[0.68rem]">{{ $game->player_one_name }} vs {{ $game->player_two_name }}</p>
            </button>
        </div>
    </x-slot:topbar>

    <main class="flex min-h-screen w-full flex-col px-4 py-4 sm:px-6 sm:py-5 lg:px-8">
        <div class="pointer-events-none fixed inset-x-0 top-24 z-50 flex justify-center px-4 sm:px-6 lg:px-8">
            <div data-game-alert class="pointer-events-auto w-full max-w-2xl rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-white/60 shadow-2xl shadow-black/30 backdrop-blur-md transition duration-200">
                <div class="flex items-start gap-3">
                    <p data-game-alert-message class="min-w-0 flex-1 leading-6">Waiting for battle state...</p>
                    <button
                        data-dismiss-alert
                        type="button"
                        aria-label="Dismiss notification"
                        class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-white/65 transition hover:bg-white/[0.1] hover:text-white/85"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <div
            data-battle-line-app
            data-viewer-player-id="{{ $viewerPlayerId }}"
            data-show-url="{{ route('battle-line-games.show', $game) }}"
            data-action-url="{{ route('battle-line-games.actions.store', $game) }}"
            class="grid flex-1 gap-6 xl:min-h-[calc(100vh-11rem)] xl:grid-cols-[280px_minmax(0,1fr)_320px] xl:items-stretch"
        >
            <aside data-sidebar="left" class="min-h-0 xl:h-full">
                <div data-sidebar-shell="left" class="flex h-full min-h-0 flex-col rounded-[2rem] border border-white/10 bg-white/[0.045] p-4 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-5">
                    <div data-sidebar-expanded-header="left" class="grid gap-3">
                        <div class="flex items-start justify-between gap-4">
                            <p class="min-w-0 flex-1 text-xs uppercase tracking-[0.28em] text-white/35">Command Stack</p>
                            <button
                                data-toggle-sidebar="left"
                                type="button"
                                aria-controls="battle-left-panels"
                                aria-expanded="true"
                                class="shrink-0 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/65 transition hover:bg-white/[0.1]"
                            >
                                Hide
                            </button>
                        </div>
                        <h2 class="block w-full font-display text-3xl text-white">Orders</h2>
                        <p class="text-sm leading-6 text-white/55">Track initiative and commit the next command without leaving the board.</p>
                    </div>

                    <button
                        data-toggle-sidebar="left"
                        data-sidebar-collapsed-trigger="left"
                        type="button"
                        aria-controls="battle-left-panels"
                        aria-expanded="true"
                        class="hidden flex-1 flex-col items-center justify-center gap-3 rounded-[1.6rem] border border-white/10 bg-black/15 px-2 text-white/70 transition hover:bg-black/20"
                    >
                        <span class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-white/35">Expand</span>
                        <span class="[writing-mode:vertical-rl] rotate-180 font-display text-2xl text-white">Orders</span>
                    </button>

                    <div id="battle-left-panels" data-sidebar-panels="left" class="mt-4 grid content-start gap-4">
                        <section class="rounded-[1.8rem] border border-white/10 bg-black/15 p-5 transition">
                            <div data-turn></div>
                        </section>

                        <section class="rounded-[1.8rem] border border-white/10 bg-black/15 p-5">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <h2 class="font-display text-2xl text-white">Orders</h2>
                                <button data-refresh-button type="button" class="rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/65 transition hover:bg-white/[0.1]">Refresh</button>
                            </div>
                            <div data-actions class="grid gap-3"></div>
                        </section>
                    </div>
                </div>
            </aside>

            <section class="flex min-h-0 flex-col gap-6 xl:h-full">
                <section class="rounded-[2rem] border border-white/10 bg-black/20 p-4 shadow-2xl shadow-black/20 backdrop-blur-md sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/35">Battlefield</p>
                            <h2 class="font-display text-3xl text-white">Nine contested flags</h2>
                        </div>
                        <p class="text-sm text-white/55">Choose a card, choose a flag, then confirm the deployment.</p>
                    </div>
                    <div data-board class="flex snap-x gap-4 overflow-x-auto px-1 py-1 pb-3"></div>
                </section>

                <section class="rounded-[2rem] border border-white/10 bg-white/[0.045] p-5 shadow-2xl shadow-black/20 backdrop-blur-sm">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/35">Your Hand</p>
                            <h2 class="font-display text-3xl text-white">Choose your next formation</h2>
                        </div>
                    </div>
                    <div data-hand class="flex gap-3 overflow-x-auto px-1 py-1 pb-3"></div>
                </section>
            </section>

            <aside data-sidebar="right" class="min-h-0 xl:h-full">
                <div data-sidebar-shell="right" class="flex h-full min-h-0 flex-col rounded-[2rem] border border-white/10 bg-white/[0.045] p-4 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-5">
                    <div data-sidebar-expanded-header="right" class="grid gap-3">
                        <div class="flex items-start justify-between gap-4">
                            <p class="min-w-0 flex-1 text-xs uppercase tracking-[0.28em] text-white/35">Seat Rail</p>
                            <button
                                data-toggle-sidebar="right"
                                type="button"
                                aria-controls="battle-right-panels"
                                aria-expanded="true"
                                class="shrink-0 rounded-full border border-white/10 bg-white/[0.05] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/65 transition hover:bg-white/[0.1]"
                            >
                                Hide
                            </button>
                        </div>
                        <h2 class="block w-full font-display text-3xl text-white">Seats</h2>
                        <p class="text-sm leading-6 text-white/55">Keep both commanders within easy sight while the battle unfolds.</p>
                    </div>

                    <button
                        data-toggle-sidebar="right"
                        data-sidebar-collapsed-trigger="right"
                        type="button"
                        aria-controls="battle-right-panels"
                        aria-expanded="true"
                        class="hidden flex-1 flex-col items-center justify-center gap-3 rounded-[1.6rem] border border-white/10 bg-black/15 px-2 text-white/70 transition hover:bg-black/20"
                    >
                        <span class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] text-white/35">Expand</span>
                        <span class="[writing-mode:vertical-rl] rotate-180 font-display text-2xl text-white">Seats</span>
                    </button>

                    <div id="battle-right-panels" data-sidebar-panels="right" class="mt-4 grid content-start gap-4">
                        <section data-player-panel-shell="viewer" class="rounded-[1.8rem] border border-white/10 bg-black/15 p-5 transition">
                            <div data-viewer></div>
                        </section>

                        <section data-player-panel-shell="opponent" class="rounded-[1.8rem] border border-white/10 bg-black/15 p-5 transition">
                            <div data-opponent></div>
                        </section>
                    </div>
                </div>
            </aside>

            <div
                id="battle-field-intel-modal"
                data-feedback-modal
                aria-hidden="true"
                class="pointer-events-none fixed inset-0 z-[70] flex items-center justify-center bg-black/50 px-4 opacity-0 backdrop-blur-sm transition duration-300 ease-out sm:px-6"
            >
                <div
                    data-feedback-modal-panel
                    class="w-full max-w-6xl translate-y-6 scale-[0.98] opacity-0 rounded-[2rem] border border-white/10 bg-stone-950/95 p-5 shadow-2xl shadow-black/40 transition duration-300 ease-out sm:p-6"
                >
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.28em] text-white/35">Field Intel</p>
                            <h2 class="font-display text-3xl text-white">What matters now</h2>
                            <p class="mt-2 text-sm leading-6 text-white/55">Live tactical read for all nine lines. Click outside the panel or use Close to return to the field.</p>
                        </div>
                        <button
                            data-close-feedback-modal
                            type="button"
                            class="shrink-0 rounded-full border border-white/10 bg-white/[0.05] px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-white/70 transition hover:bg-white/[0.1]"
                        >
                            Close
                        </button>
                    </div>

                    <div data-feedback class="grid gap-4"></div>
                </div>
            </div>
        </div>
    </main>
</x-layouts.battle-line>
