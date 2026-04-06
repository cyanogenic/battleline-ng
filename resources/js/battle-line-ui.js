const troopColorClasses = {
    red: 'from-red-500/90 to-red-700/95 text-red-50 ring-red-300/40',
    orange: 'from-orange-400/90 to-orange-700/95 text-orange-50 ring-orange-300/40',
    yellow: 'from-amber-300/95 to-yellow-600/95 text-stone-950 ring-yellow-200/50',
    green: 'from-emerald-400/90 to-emerald-700/95 text-emerald-50 ring-emerald-300/40',
    blue: 'from-sky-400/90 to-blue-700/95 text-blue-50 ring-sky-300/40',
    purple: 'from-violet-400/90 to-purple-700/95 text-violet-50 ring-violet-300/40',
};

const sidebarLayoutClasses = [
    'xl:grid-cols-[280px_minmax(0,1fr)_320px]',
    'xl:grid-cols-[88px_minmax(0,1fr)_320px]',
    'xl:grid-cols-[280px_minmax(0,1fr)_88px]',
    'xl:grid-cols-[88px_minmax(0,1fr)_88px]',
];

document.addEventListener('DOMContentLoaded', () => {
    setupBattleLineBoard();
});

function setupBattleLineBoard() {
    const app = document.querySelector('[data-battle-line-app]');

    if (! app) {
        return;
    }

    const state = {
        selectedCardId: null,
        draggingCardId: null,
        hoverFlagIndex: null,
        pendingDeployment: null,
        game: null,
        busy: false,
        pollTimer: null,
        lastMessage: null,
        collapsedSidebars: loadSidebarState(),
    };

    const elements = {
        layout: app,
        alert: document.querySelector('[data-game-alert]'),
        dismissAlert: document.querySelector('[data-dismiss-alert]'),
        board: app.querySelector('[data-board]'),
        hand: app.querySelector('[data-hand]'),
        turn: app.querySelector('[data-turn]'),
        viewer: app.querySelector('[data-viewer]'),
        opponent: app.querySelector('[data-opponent]'),
        actions: app.querySelector('[data-actions]'),
        feedback: document.querySelector('[data-feedback]'),
        refresh: app.querySelector('[data-refresh-button]'),
        feedbackModal: document.querySelector('[data-feedback-modal]'),
        feedbackModalPanel: document.querySelector('[data-feedback-modal-panel]'),
        openFeedbackModal: document.querySelector('[data-open-feedback-modal]'),
        closeFeedbackModal: document.querySelector('[data-close-feedback-modal]'),
        playerPanelShells: {
            viewer: app.querySelector('[data-player-panel-shell="viewer"]'),
            opponent: app.querySelector('[data-player-panel-shell="opponent"]'),
        },
        sidebars: {
            left: app.querySelector('[data-sidebar="left"]'),
            right: app.querySelector('[data-sidebar="right"]'),
        },
        sidebarShells: {
            left: app.querySelector('[data-sidebar-shell="left"]'),
            right: app.querySelector('[data-sidebar-shell="right"]'),
        },
        sidebarExpandedHeaders: {
            left: app.querySelector('[data-sidebar-expanded-header="left"]'),
            right: app.querySelector('[data-sidebar-expanded-header="right"]'),
        },
        sidebarCollapsedTriggers: {
            left: app.querySelector('[data-sidebar-collapsed-trigger="left"]'),
            right: app.querySelector('[data-sidebar-collapsed-trigger="right"]'),
        },
        sidebarPanels: {
            left: app.querySelector('[data-sidebar-panels="left"]'),
            right: app.querySelector('[data-sidebar-panels="right"]'),
        },
        sidebarToggles: app.querySelectorAll('[data-toggle-sidebar]'),
    };

    const viewerPlayerId = app.dataset.viewerPlayerId;
    const showUrl = new URL(app.dataset.showUrl, window.location.origin);

    const render = () => {
        if (! state.game) {
            return;
        }

        if (! state.game.state) {
            renderWaitingState(elements, state.game);

            return;
        }

        renderTurn(elements.turn, state.game.state.turn);
        renderPlayerPanel(elements.viewer, state.game.state.viewer, 'Commander', state.game.state.turn.is_viewer_active);
        renderPlayerPanel(elements.opponent, state.game.state.opponent, 'Opponent', ! state.game.state.turn.is_viewer_active && ! state.game.state.turn.winner_id);
        renderBoard(elements.board, state, postAction, elements);
        renderHand(elements.hand, state, postAction, elements);
        renderActions(elements.actions, state, postAction, elements);
        renderFeedback(elements.feedback, state);
        syncPlayerPanelState(elements, state.game.state.turn);
        syncInteractionState(elements, state);
    };

    const loadGame = async (silent = false) => {
        if (state.busy) {
            return;
        }

        if (! silent) {
            setAlert(elements.alert, '');
        }

        try {
            const response = await fetch(showUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();

            if (! response.ok) {
                throw new Error(firstValidationMessage(data) ?? 'Unable to load the battle.');
            }

            state.game = data.data;
            state.hoverFlagIndex = null;
            state.draggingCardId = null;

            if (
                state.selectedCardId
                && state.game.state?.viewer
                && ! state.game.state.viewer.hand.some((card) => card.id === state.selectedCardId)
            ) {
                state.selectedCardId = null;
            }

            if (
                state.pendingDeployment
                && (
                    ! state.game.state?.viewer?.hand.some((card) => card.id === state.pendingDeployment.cardId)
                    || ! state.game.state.available_actions.playable_flag_indexes.includes(state.pendingDeployment.flagIndex)
                )
            ) {
                state.pendingDeployment = null;
            }

            render();
        } catch (error) {
            setAlert(elements.alert, error.message, 'error');
        }
    };

    const postAction = async (payload) => {
        if (state.busy) {
            return;
        }

        state.busy = true;
        setAlert(elements.alert, 'Dispatching order to the front...', 'info');

        try {
            const response = await fetch(app.dataset.actionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json();

            if (! response.ok) {
                throw new Error(firstValidationMessage(data) ?? 'The action could not be completed.');
            }

            state.game = data.data;
            state.pendingDeployment = null;
            state.hoverFlagIndex = null;
            state.draggingCardId = null;

            if (payload.type === 'play_troop') {
                state.selectedCardId = null;
            }

            state.lastMessage = actionSuccessMessage(payload);
            setAlert(elements.alert, state.lastMessage, 'success');
            render();
        } catch (error) {
            setAlert(elements.alert, error.message, 'error');
        } finally {
            state.busy = false;
        }
    };

    elements.refresh.addEventListener('click', () => {
        loadGame();
    });

    elements.dismissAlert?.addEventListener('click', () => {
        setAlert(elements.alert, '');
    });

    setupImmersiveInteractions();
    setupSidebarToggles(elements, state);
    setupFeedbackModal(elements);
    loadGame();

    state.pollTimer = window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            loadGame(true);
        }
    }, 5000);
}

function renderWaitingState(elements, game) {
    const viewerName = game.viewer_player_id === 'player_two' ? game.player_two_name : game.player_one_name;
    const opponentName = game.viewer_player_id === 'player_two' ? game.player_one_name : game.player_two_name;

    setAlert(elements.alert, 'Waiting for another commander to join this battle. The board will unlock automatically once they arrive.', 'info');

    elements.turn.innerHTML = `
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-war-gold/70">Turn</p>
            <p class="font-display text-2xl text-war-ash">Awaiting Challenger</p>
            <p class="text-sm leading-6 text-white/60">Cards will be dealt and initiative chosen as soon as the second player joins.</p>
        </div>
    `;

    elements.viewer.innerHTML = waitingPlayerPanel('Commander', viewerName, 'Your seat is locked in and ready.');
    elements.opponent.innerHTML = waitingPlayerPanel('Opponent', opponentName, 'This seat is still open in the lobby.');
    elements.actions.innerHTML = `
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-4 text-sm leading-6 text-white/60">
            No battle actions are available yet. Keep this page open or refresh from the hall until another commander joins.
        </div>
    `;
    elements.feedback.innerHTML = `
        <div class="rounded-2xl border border-war-gold/35 bg-war-gold/10 px-4 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-war-gold/75">Holding Pattern</p>
            <p class="mt-2 font-display text-2xl text-white">Challenge posted.</p>
            <p class="mt-2 text-sm leading-6 text-white/68">Once a second authenticated user joins from the lobby, the game state will appear here automatically.</p>
        </div>
    `;
    elements.hand.innerHTML = `
        <p class="rounded-3xl border border-white/10 bg-white/[0.04] px-5 py-8 text-center text-sm text-white/55">
            No hand yet. Waiting for another commander to join.
        </p>
    `;
    elements.board.innerHTML = Array.from({ length: 9 }, (_, index) => `
        <article class="flex basis-[min(15rem,calc((100%-2rem)/3))] shrink-0 snap-start flex-col rounded-3xl border border-white/10 bg-white/[0.045] p-3 shadow-2xl shadow-black/20 backdrop-blur-sm sm:p-4">
            <p class="text-xs uppercase tracking-[0.24em] text-white/35">Flag ${index + 1}</p>
            <div class="mt-3 flex min-h-48 items-center justify-center rounded-2xl border border-dashed border-white/8 bg-black/15 px-3 text-[0.7rem] uppercase tracking-[0.2em] text-white/25 sm:mt-4 sm:min-h-56 sm:text-xs sm:tracking-[0.24em]">
                Waiting For First Deployment
            </div>
        </article>
    `).join('');
}

function waitingPlayerPanel(label, name, body) {
    return `
        <div class="space-y-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/40">${escapeHtml(label)}</p>
                <h2 class="mt-2 font-display text-2xl text-white">${escapeHtml(name)}</h2>
            </div>
            <p class="text-sm leading-6 text-white/60">${escapeHtml(body)}</p>
        </div>
    `;
}

function renderTurn(container, turn) {
    container.innerHTML = `
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-war-gold/70">Turn</p>
                <p class="font-display text-2xl text-war-ash">${escapeHtml(humanizePhase(turn.phase))}</p>
                <p class="text-sm text-white/60">Deck remaining: ${turn.troop_deck_count} cards</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80">Play order: ${turn.next_play_order}</span>
                ${turn.winner_name ? `<span class="rounded-full border border-war-gold/40 bg-war-gold/15 px-3 py-1 font-semibold text-war-gold">Winner: ${escapeHtml(turn.winner_name)}</span>` : ''}
            </div>
        </div>
    `;
}

function renderPlayerPanel(container, player, label, isActive = false) {
    container.innerHTML = `
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/40">${escapeHtml(label)}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <h2 class="font-display text-2xl text-white">${escapeHtml(player.player_name)}</h2>
                    ${isActive ? '<span class="rounded-full border border-war-gold/40 bg-war-gold/12 px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-war-gold">Active Turn</span>' : ''}
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-white/60">Hand ${player.hand_count}</p>
                <p class="text-sm text-white/60">Flags ${player.claimed_flag_count}</p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            ${player.claimed_flags.length > 0
                ? player.claimed_flags.map((flag) => `<span class="rounded-full bg-war-gold/15 px-3 py-1 text-xs font-semibold text-war-gold">Flag ${flag + 1}</span>`).join('')
                : '<span class="rounded-full bg-white/5 px-3 py-1 text-xs text-white/50">No flags claimed yet</span>'}
        </div>
    `;
}

function syncPlayerPanelState(elements, turn) {
    const viewerIsActive = Boolean(turn.is_viewer_active && ! turn.winner_id);
    const opponentIsActive = Boolean(! turn.is_viewer_active && ! turn.winner_id);

    syncPlayerPanelShell(elements.playerPanelShells.viewer, viewerIsActive);
    syncPlayerPanelShell(elements.playerPanelShells.opponent, opponentIsActive);
}

function syncPlayerPanelShell(shell, isActive) {
    if (! shell) {
        return;
    }

    shell.classList.toggle('border-war-gold/35', isActive);
    shell.classList.toggle('bg-war-gold/10', isActive);
    shell.classList.toggle('ring-2', isActive);
    shell.classList.toggle('ring-war-gold/35', isActive);
    shell.classList.toggle('shadow-lg', isActive);
    shell.classList.toggle('shadow-war-gold/10', isActive);

    shell.classList.toggle('border-white/10', ! isActive);
    shell.classList.toggle('bg-black/15', ! isActive);
}

function renderBoard(container, state, postAction, elements) {
    const flags = state.game.state.board.flags;
    const claimable = new Set(state.game.state.available_actions?.claimable_flag_indexes ?? []);
    const playable = new Set(state.game.state.available_actions?.playable_flag_indexes ?? []);

    container.innerHTML = flags.map((flag) => {
        const canPlayHere = canDropOnFlag(state, flag.index);
        const canClaimHere = claimable.has(flag.index);
        const isPendingHere = state.pendingDeployment?.flagIndex === flag.index;
        const usesFocusedDropHighlight = isPendingHere;
        const pendingPreviewCard = isPendingHere
            ? state.game.state.viewer.hand.find((card) => card.id === state.pendingDeployment.cardId) ?? null
            : null;
        const viewerCards = pendingPreviewCard
            ? [...flag.viewer_cards, { card: pendingPreviewCard, isPendingPreview: true }]
            : flag.viewer_cards;
        const viewerCardCount = flag.viewer_card_count + (pendingPreviewCard ? 1 : 0);
        const status = flagFeedback(flag, state, { canPlayHere, canClaimHere, playable });

        return `
            <article
                data-flag-target="${flag.index}"
                class="group relative flex basis-[min(15rem,calc((100%-2rem)/3))] shrink-0 snap-start flex-col rounded-3xl border ${status.borderClass} bg-white/[0.045] p-3 shadow-2xl shadow-black/20 ring-inset backdrop-blur-sm transition sm:p-4 ${flag.claimed_by_viewer ? 'ring-2 ring-war-gold/60' : ''} ${canPlayHere ? 'cursor-pointer' : ''} ${usesFocusedDropHighlight ? 'ring-2 ring-war-gold/45' : ''}"
            >
                <div class="mb-3 flex flex-wrap items-start justify-between gap-3 sm:mb-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Flag ${flag.index + 1}</p>
                        <p class="font-display text-base text-white sm:text-lg">${flag.claimed_by_name ? `Claimed by ${escapeHtml(flag.claimed_by_name)}` : 'Contested'}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                        <span class="rounded-full ${status.badgeClass} px-2.5 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.18em] sm:px-3 sm:text-[0.68rem] sm:tracking-[0.2em]">${status.label}</span>
                        ${canClaimHere ? `<button data-claim-flag="${flag.index}" class="rounded-full border border-war-gold/40 bg-war-gold/15 px-2.5 py-1 text-[0.68rem] font-semibold text-war-gold transition hover:bg-war-gold/25 sm:px-3 sm:text-xs">Claim</button>` : ''}
                    </div>
                </div>
                <div class="grid gap-3 sm:gap-4">
                    <section class="rounded-2xl border border-white/8 bg-black/15 p-2.5 sm:p-3">
                        <div class="mb-2 flex items-center justify-between text-[0.68rem] uppercase tracking-[0.2em] text-white/35 sm:text-xs sm:tracking-[0.24em]">
                            <span>Opponent Front</span>
                            <span>${flag.opponent_card_count}/3</span>
                        </div>
                        <div class="flex min-h-24 gap-2 sm:min-h-28">
                            ${renderPlacedCards(flag.opponent_cards, false)}
                        </div>
                    </section>
                    <button
                        type="button"
                        data-play-flag-hint="${flag.index}"
                        aria-disabled="${canPlayHere ? 'false' : 'true'}"
                        class="rounded-2xl border border-dashed px-2.5 py-2 text-left text-xs transition sm:px-3 sm:text-sm ${usesFocusedDropHighlight ? 'border-war-gold/55 bg-war-gold/10 text-war-gold' : canPlayHere ? 'border-war-ember/70 bg-war-ember/12 text-war-ash hover:bg-war-ember/18' : 'border-white/8 bg-white/[0.03] text-white/45'}"
                    >
                        <span class="block text-[0.72rem] font-semibold sm:text-sm">${isPendingHere ? 'Deployment queued here' : status.hint}</span>
                        <span class="mt-1 block text-[0.68rem] text-white/40 sm:text-xs">${isPendingHere ? 'Confirm or cancel from Orders before the troop is committed.' : status.detail}</span>
                    </button>
                    <section class="rounded-2xl border border-white/8 bg-black/15 p-2.5 sm:p-3">
                        <div class="mb-2 flex items-center justify-between text-[0.68rem] uppercase tracking-[0.2em] text-white/35 sm:text-xs sm:tracking-[0.24em]">
                            <span>Your Line</span>
                            <span>${viewerCardCount}/3</span>
                        </div>
                        <div class="flex min-h-24 gap-2 sm:min-h-28">
                            ${renderPlacedCards(viewerCards, true)}
                        </div>
                    </section>
                </div>
            </article>
        `;
    }).join('');

    container.querySelectorAll('[data-flag-target]').forEach((flagTarget) => {
        const flagIndex = Number(flagTarget.dataset.flagTarget);

        flagTarget.addEventListener('click', (event) => {
            if (event.target.closest('[data-claim-flag]')) {
                return;
            }

            if (! state.selectedCardId) {
                if (state.game.state.available_actions.can_play_troop) {
                    setAlert(document.querySelector('[data-game-alert]'), 'Choose a card from your hand first.', 'error');
                }

                return;
            }

            stagePlayCardToFlag(state, elements, postAction, flagIndex, state.selectedCardId);
        });

        flagTarget.addEventListener('dragenter', (event) => {
            if (! canDropOnFlag(state, flagIndex)) {
                return;
            }

            event.preventDefault();
            state.hoverFlagIndex = flagIndex;
            syncInteractionState(elements, state);
        });

        flagTarget.addEventListener('dragover', (event) => {
            if (! canDropOnFlag(state, flagIndex)) {
                return;
            }

            event.preventDefault();
            state.hoverFlagIndex = flagIndex;

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            syncInteractionState(elements, state);
        });

        flagTarget.addEventListener('dragleave', (event) => {
            const nextTarget = event.relatedTarget;

            if (nextTarget instanceof Node && flagTarget.contains(nextTarget)) {
                return;
            }

            if (state.hoverFlagIndex === flagIndex) {
                state.hoverFlagIndex = null;
                syncInteractionState(elements, state);
            }
        });

        flagTarget.addEventListener('drop', (event) => {
            if (! canDropOnFlag(state, flagIndex)) {
                return;
            }

            event.preventDefault();

            const draggedCardId = event.dataTransfer?.getData('text/plain') || state.draggingCardId || state.selectedCardId;

            state.hoverFlagIndex = null;
            stagePlayCardToFlag(state, elements, postAction, flagIndex, draggedCardId);
        });
    });

    container.querySelectorAll('[data-claim-flag]').forEach((button) => {
        button.addEventListener('click', () => {
            postAction({
                type: 'claim_flag',
                flag_index: Number(button.dataset.claimFlag),
            });
        });
    });
}

function renderHand(container, state, postAction, elements) {
    const hand = state.game.state.viewer.hand;
    const canDrag = state.game.state.available_actions?.can_play_troop ?? false;

    container.innerHTML = hand.length === 0
        ? '<p class="rounded-3xl border border-white/10 bg-white/[0.04] px-5 py-8 text-center text-sm text-white/55">Your hand is empty.</p>'
        : hand.map((card) => {
            const selected = state.selectedCardId === card.id;

            return `
                <button
                    type="button"
                    data-select-card="${card.id}"
                    draggable="${canDrag ? 'true' : 'false'}"
                    class="group relative flex h-36 w-24 shrink-0 flex-col items-center justify-between overflow-hidden rounded-[1.6rem] border border-white/12 bg-gradient-to-b ${troopColorClasses[card.color] ?? troopColorClasses.red} p-3 text-left shadow-lg shadow-black/25 ring-1 transition ${selected ? 'border-white/25 ring-2 ring-white/80 shadow-2xl shadow-black/35' : 'ring-black/15 hover:ring-white/35 hover:shadow-xl hover:shadow-black/30'} ${canDrag ? 'cursor-grab active:cursor-grabbing' : 'cursor-default opacity-80'}"
                >
                    <span class="w-full text-[0.65rem] font-bold uppercase tracking-[0.28em] opacity-80">${escapeHtml(card.color)}</span>
                    <span class="font-display text-5xl leading-none">${card.strength}</span>
                    <span class="w-full text-right text-[0.65rem] font-semibold uppercase tracking-[0.28em] opacity-80">Troop</span>
                </button>
            `;
        }).join('');

    container.querySelectorAll('[data-select-card]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextSelectedCardId = state.selectedCardId === button.dataset.selectCard
                ? null
                : button.dataset.selectCard;
            state.selectedCardId = nextSelectedCardId;

            if (state.pendingDeployment?.cardId !== nextSelectedCardId) {
                state.pendingDeployment = null;
            }

            renderHand(container, state, postAction, elements);
            renderActions(document.querySelector('[data-actions]'), state, postAction, elements);
            renderFeedback(document.querySelector('[data-feedback]'), state);
            renderBoard(document.querySelector('[data-board]'), state, postAction, elements);
        });

        button.addEventListener('dragstart', (event) => {
            if (! canDrag) {
                event.preventDefault();

                return;
            }

            state.selectedCardId = button.dataset.selectCard;
            state.draggingCardId = button.dataset.selectCard;
            state.hoverFlagIndex = null;
            state.pendingDeployment = null;

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', button.dataset.selectCard);
            }

            renderActions(document.querySelector('[data-actions]'), state, postAction, elements);
            renderFeedback(document.querySelector('[data-feedback]'), state);
            syncInteractionState(elements, state);
        });

        button.addEventListener('dragend', () => {
            state.draggingCardId = null;
            state.hoverFlagIndex = null;
            syncInteractionState(elements, state);
        });
    });
}

function renderActions(container, state, postAction, elements) {
    const actions = state.game.state.available_actions;
    const pendingDeployment = state.pendingDeployment;
    const selectedCard = state.game.state.viewer.hand.find((card) => card.id === pendingDeployment?.cardId) ?? null;

    if (pendingDeployment && selectedCard) {
        container.innerHTML = `
            <button type="button" data-cancel-deployment class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm font-semibold text-white/72 transition hover:bg-white/[0.08]">Cancel Deployment</button>
            <button type="button" data-confirm-deployment class="${actionButtonClasses(true, true)}">Confirm Deployment</button>
            <div class="rounded-2xl border border-war-gold/35 bg-war-gold/10 px-4 py-3 text-sm text-white/68">
                Queued troop: ${escapeHtml(selectedCard.color)} ${selectedCard.strength}
                <br>
                Target flag: ${pendingDeployment.flagIndex + 1}
            </div>
        `;

        container.querySelector('[data-cancel-deployment]')?.addEventListener('click', () => {
            cancelPendingDeployment(state, elements, postAction);
        });

        container.querySelector('[data-confirm-deployment]')?.addEventListener('click', () => {
            confirmPendingDeployment(state, postAction);
        });

        return;
    }

    container.innerHTML = `
        <button type="button" data-pass ${actions.can_pass ? '' : 'disabled'} class="${actionButtonClasses(actions.can_pass)}">Pass</button>
        <button type="button" data-finish ${actions.can_finish_turn ? '' : 'disabled'} class="${actionButtonClasses(actions.can_finish_turn, true)}">Finish Turn</button>
        <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 text-sm text-white/60">
            Playable flags: ${actions.playable_flag_indexes.length > 0 ? actions.playable_flag_indexes.map((index) => index + 1).join(', ') : 'none'}
            <br>
            Claimable flags: ${actions.claimable_flag_indexes.length > 0 ? actions.claimable_flag_indexes.map((index) => index + 1).join(', ') : 'none'}
        </div>
    `;

    container.querySelector('[data-pass]').addEventListener('click', () => {
        if (! actions.can_pass) {
            return;
        }

        postAction({ type: 'pass' });
    });

    container.querySelector('[data-finish]').addEventListener('click', () => {
        if (! actions.can_finish_turn) {
            return;
        }

        postAction({ type: 'finish_turn' });
    });
}

function renderFeedback(container, state) {
    const feedback = deriveFeedback(state);
    const battlefieldIntel = deriveBattlefieldIntel(state);

    container.innerHTML = `
        <div class="grid gap-4 xl:grid-cols-[minmax(0,340px)_minmax(0,1fr)]">
            <div class="space-y-4">
                <div class="rounded-[1.6rem] border ${feedback.borderClass} ${feedback.panelClass} px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] ${feedback.eyebrowClass}">${feedback.eyebrow}</p>
                    <p class="mt-2 font-display text-2xl text-white">${feedback.title}</p>
                    <p class="mt-2 text-sm leading-6 text-white/68">${feedback.body}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    ${feedback.checklist.map((item) => `
                        <div class="rounded-[1.4rem] border border-white/10 bg-black/15 px-4 py-3">
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-white/35">${item.label}</p>
                            <p class="mt-1 text-sm text-white/70">${item.value}</p>
                        </div>
                    `).join('')}
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    ${battlefieldIntel.summary.map((item) => `
                        <div class="rounded-[1.4rem] border border-white/10 bg-white/[0.03] px-4 py-3">
                            <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-white/35">${item.label}</p>
                            <p class="mt-2 font-display text-2xl text-white">${item.value}</p>
                            <p class="mt-1 text-xs leading-5 text-white/50">${item.note}</p>
                        </div>
                    `).join('')}
                </div>
            </div>

            <section class="rounded-[1.6rem] border border-white/10 bg-black/15 p-4 sm:p-5">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/35">Nine-Flag Overview</p>
                        <h3 class="mt-2 font-display text-2xl text-white">Current line pressure</h3>
                        <p class="mt-2 text-sm leading-6 text-white/60">Scan every contested front before you commit the next troop.</p>
                    </div>
                    <span class="rounded-full border ${battlefieldIntel.controlClass} px-3 py-1 text-[0.72rem] font-semibold uppercase tracking-[0.2em] ${battlefieldIntel.controlTextClass}">
                        ${battlefieldIntel.controlLabel}
                    </span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    ${battlefieldIntel.flags.map((flag) => `
                        <article class="rounded-[1.4rem] border ${flag.cardClass} bg-black/20 px-4 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-white/35">Flag ${flag.index + 1}</p>
                                    <p class="mt-2 font-display text-xl text-white">${flag.headline}</p>
                                </div>
                                <span class="rounded-full border ${flag.badgeClass} px-2.5 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.18em]">
                                    ${flag.label}
                                </span>
                            </div>

                            <p class="mt-3 text-sm leading-6 text-white/62">${flag.note}</p>

                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <div class="rounded-xl border border-white/8 bg-white/[0.03] px-3 py-2">
                                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-white/35">You</p>
                                    <p class="mt-1 text-sm text-white/78">${flag.viewerCardCount}/3</p>
                                </div>
                                <div class="rounded-xl border border-white/8 bg-white/[0.03] px-3 py-2">
                                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-white/35">Enemy</p>
                                    <p class="mt-1 text-sm text-white/78">${flag.opponentCardCount}/3</p>
                                </div>
                            </div>
                        </article>
                    `).join('')}
                </div>
            </section>
        </div>
    `;
}

function deriveBattlefieldIntel(state) {
    const flags = state.game.state.board.flags;
    const actions = state.game.state.available_actions;
    const viewerClaimedFlags = state.game.state.viewer.claimed_flag_count;
    const opponentClaimedFlags = state.game.state.opponent.claimed_flag_count;
    const claimableFlags = new Set(actions.claimable_flag_indexes ?? []);
    const playableFlags = new Set(actions.playable_flag_indexes ?? []);
    const contestedFlags = flags.filter((flag) => ! flag.claimed_by_viewer && ! flag.claimed_by_opponent).length;
    const pressuredFlags = flags.filter((flag) => ! flag.claimed_by_viewer && ! flag.claimed_by_opponent && flag.opponent_card_count > flag.viewer_card_count).length;
    const lead = viewerClaimedFlags - opponentClaimedFlags;

    let controlLabel = `Deadlocked ${viewerClaimedFlags}-${opponentClaimedFlags}`;
    let controlClass = 'border-white/10 bg-white/[0.04]';
    let controlTextClass = 'text-white/70';

    if (lead > 0) {
        controlLabel = `You Lead ${viewerClaimedFlags}-${opponentClaimedFlags}`;
        controlClass = 'border-war-gold/35 bg-war-gold/12';
        controlTextClass = 'text-war-gold';
    } else if (lead < 0) {
        controlLabel = `Enemy Leads ${opponentClaimedFlags}-${viewerClaimedFlags}`;
        controlClass = 'border-red-400/30 bg-red-500/10';
        controlTextClass = 'text-red-100';
    }

    return {
        controlLabel,
        controlClass,
        controlTextClass,
        summary: [
            { label: 'Your Flags', value: `${viewerClaimedFlags}`, note: 'Banners under your control.' },
            { label: 'Enemy Flags', value: `${opponentClaimedFlags}`, note: 'Ground already lost to the opposing line.' },
            { label: 'Contested', value: `${contestedFlags}`, note: 'Flags still open to influence.' },
            { label: 'Pressured', value: `${pressuredFlags}`, note: 'Lines where the enemy currently has more troops committed.' },
        ],
        flags: flags.map((flag) => summarizeFlagForIntel(flag, state, { claimableFlags, playableFlags })),
    };
}

function summarizeFlagForIntel(flag, state, context) {
    const isPending = state.pendingDeployment?.flagIndex === flag.index;
    const isClaimable = context.claimableFlags.has(flag.index);
    const isPlayable = state.selectedCardId && context.playableFlags.has(flag.index) && ! isPending;
    const openSlots = 3 - flag.viewer_card_count;

    if (flag.claimed_by_viewer) {
        return {
            index: flag.index,
            headline: 'Secured',
            label: 'Yours',
            note: `Held by ${escapeHtml(flag.claimed_by_name ?? 'you')}. No further troops may enter this line.`,
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-war-gold/30 bg-war-gold/10',
            badgeClass: 'border-war-gold/35 bg-war-gold/12 text-war-gold',
        };
    }

    if (flag.claimed_by_opponent) {
        return {
            index: flag.index,
            headline: 'Lost',
            label: 'Enemy',
            note: `Held by ${escapeHtml(flag.claimed_by_name ?? 'the enemy')}. Shift pressure elsewhere to recover momentum.`,
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-red-400/22 bg-red-500/8',
            badgeClass: 'border-red-400/30 bg-red-500/10 text-red-100',
        };
    }

    if (isPending) {
        return {
            index: flag.index,
            headline: 'Queued',
            label: 'Pending',
            note: 'A troop is staged here and waiting for your confirmation from Orders.',
            viewerCardCount: flag.viewer_card_count + 1,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-war-gold/28 bg-war-gold/10',
            badgeClass: 'border-war-gold/35 bg-war-gold/12 text-war-gold',
        };
    }

    if (isClaimable) {
        return {
            index: flag.index,
            headline: 'Claim Ready',
            label: 'Claim',
            note: 'This front can be seized immediately before the turn passes.',
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-war-gold/28 bg-war-gold/8',
            badgeClass: 'border-war-gold/35 bg-war-gold/12 text-war-gold',
        };
    }

    if (isPlayable) {
        return {
            index: flag.index,
            headline: 'Deployable',
            label: 'Open',
            note: 'Your selected troop can still be committed to this line right now.',
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-war-ember/25 bg-war-ember/8',
            badgeClass: 'border-war-ember/35 bg-war-ember/12 text-war-ash',
        };
    }

    if (flag.viewer_card_count === 3) {
        return {
            index: flag.index,
            headline: 'Formed',
            label: 'Full',
            note: 'Your formation here is already complete. Watch for a claim window.',
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-sky-400/20 bg-sky-500/8',
            badgeClass: 'border-sky-400/25 bg-sky-500/10 text-sky-100',
        };
    }

    if (flag.opponent_card_count > flag.viewer_card_count) {
        return {
            index: flag.index,
            headline: 'Pressured',
            label: 'Threat',
            note: 'Enemy commitment is currently ahead on this flag.',
            viewerCardCount: flag.viewer_card_count,
            opponentCardCount: flag.opponent_card_count,
            cardClass: 'border-red-400/18 bg-red-500/6',
            badgeClass: 'border-red-400/25 bg-red-500/10 text-red-100',
        };
    }

    return {
        index: flag.index,
        headline: 'Open',
        label: 'Contested',
        note: `${openSlots} slot${openSlots === 1 ? '' : 's'} still open on your side of this line.`,
        viewerCardCount: flag.viewer_card_count,
        opponentCardCount: flag.opponent_card_count,
        cardClass: 'border-white/10 bg-white/[0.03]',
        badgeClass: 'border-white/10 bg-white/[0.05] text-white/65',
    };
}

function renderPlacedCards(cards, isViewerSide) {
    if (! cards.length) {
        return '<div class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-white/8 px-2 text-[0.68rem] uppercase tracking-[0.2em] text-white/25 sm:text-xs sm:tracking-[0.24em]">Open Ground</div>';
    }

    return cards.map((entry) => `
        <div class="flex h-20 min-w-0 basis-0 flex-1 flex-col justify-between rounded-[1rem] border ${entry.isPendingPreview ? 'border-war-gold/45' : 'border-white/10'} bg-gradient-to-b ${troopColorClasses[entry.card.color] ?? troopColorClasses.red} p-1.5 shadow-lg ${entry.isPendingPreview ? 'shadow-war-gold/20 ring-2 ring-war-gold/60' : 'shadow-black/20 ring-1 ring-black/15'} sm:h-24 sm:max-w-18 sm:rounded-[1.2rem] sm:p-2">
            <span class="text-[0.48rem] font-bold uppercase tracking-[0.2em] opacity-80 sm:text-[0.55rem] sm:tracking-[0.24em]">${escapeHtml(entry.card.color)}</span>
            <span class="font-display text-2xl leading-none sm:text-3xl">${entry.card.strength}</span>
            <span class="text-right text-[0.48rem] font-semibold uppercase tracking-[0.2em] opacity-70 sm:text-[0.55rem] sm:tracking-[0.24em]">${entry.isPendingPreview ? 'Preview' : isViewerSide ? 'Yours' : 'Enemy'}</span>
        </div>
    `).join('');
}

function actionButtonClasses(enabled, accent = false) {
    if (! enabled) {
        return 'rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm font-semibold text-white/35';
    }

    return accent
        ? 'rounded-2xl border border-war-gold/50 bg-war-gold/15 px-4 py-3 text-sm font-semibold text-war-gold transition hover:bg-war-gold/25'
        : 'rounded-2xl border border-war-ember/55 bg-war-ember/12 px-4 py-3 text-sm font-semibold text-war-ash transition hover:bg-war-ember/22';
}

function setAlert(element, message, type = 'info') {
    if (! element) {
        return;
    }

    const styles = {
        info: 'border-white/10 bg-white/[0.04] text-white/60',
        success: 'border-emerald-400/30 bg-emerald-500/10 text-emerald-100',
        error: 'border-red-400/30 bg-red-500/10 text-red-100',
    };

    const alertMessage = element.querySelector('[data-game-alert-message]');
    const hasMessage = message.trim().length > 0;

    if (alertMessage) {
        alertMessage.textContent = message;
    }

    element.className = `pointer-events-auto w-full max-w-2xl rounded-2xl border px-4 py-3 text-sm shadow-2xl shadow-black/30 backdrop-blur-md transition duration-200 ${styles[type] ?? styles.info} ${hasMessage ? 'translate-y-0 opacity-100' : 'pointer-events-none -translate-y-2 opacity-0'}`;
    element.setAttribute('aria-hidden', hasMessage ? 'false' : 'true');
}

function deriveFeedback(state) {
    const turn = state.game.state.turn;
    const actions = state.game.state.available_actions;
    const selectedCard = state.game.state.viewer.hand.find((card) => card.id === state.selectedCardId) ?? null;
    const pendingDeployment = state.pendingDeployment;

    if (turn.winner_id) {
        return {
            eyebrow: 'Outcome',
            title: turn.winner_id === state.game.viewer_player_id ? 'You hold the line.' : `${turn.winner_name} wins the battle.`,
            body: 'The match is complete. Review the final board or return to the command hall to launch the next battle.',
            borderClass: 'border-war-gold/35',
            panelClass: 'bg-war-gold/10',
            eyebrowClass: 'text-war-gold/75',
            checklist: [
                { label: 'Claimed Flags', value: `${state.game.state.viewer.claimed_flag_count}` },
                { label: 'Opponent Flags', value: `${state.game.state.opponent.claimed_flag_count}` },
            ],
        };
    }

    if (pendingDeployment && selectedCard) {
        return {
            eyebrow: 'Confirm Deployment',
            title: `Ready for flag ${pendingDeployment.flagIndex + 1}`,
            body: `You have staged ${selectedCard.color} ${selectedCard.strength} for this line. Use Orders to confirm it, or cancel there to reposition without losing your turn.`,
            borderClass: 'border-war-gold/35',
            panelClass: 'bg-war-gold/10',
            eyebrowClass: 'text-war-gold/75',
            checklist: [
                { label: 'Queued Flag', value: `${pendingDeployment.flagIndex + 1}` },
                { label: 'Card', value: `${selectedCard.color} ${selectedCard.strength}` },
            ],
        };
    }

    if (selectedCard && actions.can_play_troop) {
        return {
            eyebrow: 'Selected Card',
            title: `Deploy ${selectedCard.color} ${selectedCard.strength}`,
            body: 'The board is highlighting legal destinations. Drag the troop onto a glowing flag or click anywhere on a highlighted battle line to stage the deployment, then confirm it from Orders.',
            borderClass: 'border-war-ember/35',
            panelClass: 'bg-war-ember/10',
            eyebrowClass: 'text-war-ember/75',
            checklist: [
                { label: 'Playable Flags', value: actions.playable_flag_indexes.length > 0 ? actions.playable_flag_indexes.map((index) => index + 1).join(', ') : 'none' },
                { label: 'Phase', value: humanizePhase(turn.phase) },
            ],
        };
    }

    if (actions.claimable_flag_indexes.length > 0) {
        return {
            eyebrow: 'Claim Window',
            title: 'You can seize a flag right now.',
            body: `Use the gold claim buttons on flags ${actions.claimable_flag_indexes.map((index) => index + 1).join(', ')} before ending your turn.`,
            borderClass: 'border-war-gold/35',
            panelClass: 'bg-war-gold/10',
            eyebrowClass: 'text-war-gold/75',
            checklist: [
                { label: 'Claimable', value: actions.claimable_flag_indexes.map((index) => index + 1).join(', ') },
                { label: 'Phase', value: humanizePhase(turn.phase) },
            ],
        };
    }

    if (actions.can_finish_turn) {
        return {
            eyebrow: 'Decision Point',
            title: 'Your claim step is complete.',
            body: 'No urgent claims are available. Review the board, then finish the turn to pass initiative.',
            borderClass: 'border-sky-400/25',
            panelClass: 'bg-sky-500/8',
            eyebrowClass: 'text-sky-200/70',
            checklist: [
                { label: 'Your Flags', value: `${state.game.state.viewer.claimed_flag_count}` },
                { label: 'Opponent Flags', value: `${state.game.state.opponent.claimed_flag_count}` },
            ],
        };
    }

    if (turn.is_viewer_active) {
        return {
            eyebrow: 'Your Move',
            title: 'Choose the next troop to commit.',
            body: actions.can_play_troop
                ? 'Drag a card from your hand onto a highlighted battle line, or click a card and then choose any highlighted flag area. Orders will hold the confirm button before the troop is committed.'
                : 'No legal troop deployment is available on the board. If the rules allow it, use Pass.',
            borderClass: 'border-emerald-400/25',
            panelClass: 'bg-emerald-500/8',
            eyebrowClass: 'text-emerald-200/75',
            checklist: [
                { label: 'Playable Flags', value: actions.playable_flag_indexes.length > 0 ? actions.playable_flag_indexes.map((index) => index + 1).join(', ') : 'none' },
                { label: 'Pass Available', value: actions.can_pass ? 'yes' : 'no' },
            ],
        };
    }

    return {
        eyebrow: 'Stand Fast',
        title: `Waiting for ${turn.active_player_name}`,
        body: 'The board will refresh automatically every few seconds while the opposing commander decides.',
        borderClass: 'border-white/10',
        panelClass: 'bg-white/[0.04]',
        eyebrowClass: 'text-white/45',
        checklist: [
            { label: 'Deck', value: `${turn.troop_deck_count} cards remain` },
            { label: 'Last Notice', value: state.lastMessage ?? 'no recent action' },
        ],
    };
}

function flagFeedback(flag, state, context) {
    if (flag.claimed_by_viewer) {
        return {
            label: 'Secured',
            hint: 'Your banner already flies here',
            detail: 'No more cards can be deployed to this flag.',
            badgeClass: 'border border-war-gold/35 bg-war-gold/12 text-war-gold',
            borderClass: 'border-war-gold/25',
        };
    }

    if (flag.claimed_by_opponent) {
        return {
            label: 'Lost',
            hint: 'The enemy controls this ground',
            detail: 'Use the remaining flags to recover momentum.',
            badgeClass: 'border border-red-400/25 bg-red-500/10 text-red-100',
            borderClass: 'border-red-400/20',
        };
    }

    if (context.canClaimHere) {
        return {
            label: 'Claim Ready',
            hint: 'A claim is available on this flag',
            detail: 'Resolve the claim before ending your turn if you want the banner now.',
            badgeClass: 'border border-war-gold/35 bg-war-gold/12 text-war-gold',
            borderClass: 'border-war-gold/30',
        };
    }

    if (context.canPlayHere) {
        return {
            label: 'Deploy',
            hint: 'This entire battle line can receive the selected troop',
            detail: `Your line has ${3 - flag.viewer_card_count} slot${3 - flag.viewer_card_count === 1 ? '' : 's'} left.`,
            badgeClass: 'border border-war-ember/35 bg-war-ember/12 text-war-ash',
            borderClass: 'border-war-ember/28',
        };
    }

    if (flag.viewer_card_count === 3) {
        return {
            label: 'Formed',
            hint: 'Your line is already full here',
            detail: 'Watch for a future claim opportunity in the claim phase.',
            badgeClass: 'border border-sky-400/25 bg-sky-500/10 text-sky-100',
            borderClass: 'border-white/10',
        };
    }

    if (state.selectedCardId && context.playable.has(flag.index) === false) {
        return {
            label: 'Unavailable',
            hint: 'The selected card cannot go here',
            detail: flag.viewer_card_count >= 3 ? 'Your side of this flag is full.' : 'Pick one of the highlighted flags instead.',
            badgeClass: 'border border-white/10 bg-white/[0.05] text-white/55',
            borderClass: 'border-white/10',
        };
    }

    const openSlots = 3 - flag.viewer_card_count;

    return {
        label: flag.opponent_card_count > flag.viewer_card_count ? 'Pressured' : 'Open',
        hint: 'Select a troop from your hand to continue this line',
        detail: `You have ${openSlots} open slot${openSlots === 1 ? '' : 's'} on this flag.`,
        badgeClass: 'border border-white/10 bg-white/[0.05] text-white/60',
        borderClass: 'border-white/10',
    };
}

function actionSuccessMessage(payload) {
    if (payload.type === 'play_troop') {
        return `Troop deployed to flag ${payload.flag_index + 1}.`;
    }

    if (payload.type === 'claim_flag') {
        return `Flag ${payload.flag_index + 1} claimed.`;
    }

    if (payload.type === 'pass') {
        return 'Pass accepted. Move to the claim phase.';
    }

    return 'Turn completed. Awaiting the next command.';
}

function canDropOnFlag(state, flagIndex) {
    const actions = state.game?.state?.available_actions;
    const hasCard = Boolean(state.draggingCardId || state.selectedCardId);
    const pendingDeployment = state.pendingDeployment;

    if (pendingDeployment && pendingDeployment.flagIndex !== flagIndex) {
        return false;
    }

    return Boolean(
        actions?.can_play_troop
        && hasCard
        && actions.playable_flag_indexes.includes(flagIndex)
    );
}

function tryPlayCardToFlag(state, postAction, flagIndex, cardId) {
    if (! cardId || ! canDropOnFlag(state, flagIndex)) {
        return;
    }

    postAction({
        type: 'play_troop',
        card_id: cardId,
        flag_index: flagIndex,
    });
}

function stagePlayCardToFlag(state, elements, postAction, flagIndex, cardId) {
    if (! cardId) {
        setAlert(document.querySelector('[data-game-alert]'), 'Choose a card from your hand first.', 'error');

        return;
    }

    if (! canDropOnFlag(state, flagIndex)) {
        setAlert(document.querySelector('[data-game-alert]'), `Flag ${flagIndex + 1} cannot receive this troop right now.`, 'error');

        return;
    }

    state.selectedCardId = cardId;
    state.pendingDeployment = {
        cardId,
        flagIndex,
    };

    setAlert(document.querySelector('[data-game-alert]'), `Flag ${flagIndex + 1} selected. Use Orders to confirm the troop.`, 'info');
    renderBoard(elements.board, state, postAction, elements);
    renderActions(elements.actions, state, postAction, elements);
    renderFeedback(elements.feedback, state);
    syncInteractionState(elements, state);
}

function confirmPendingDeployment(state, postAction) {
    if (! state.pendingDeployment) {
        return;
    }

    tryPlayCardToFlag(
        state,
        postAction,
        state.pendingDeployment.flagIndex,
        state.pendingDeployment.cardId,
    );
}

function setupFeedbackModal(elements) {
    elements.openFeedbackModal?.addEventListener('click', () => {
        setFeedbackModalVisibility(elements, true);
    });

    elements.closeFeedbackModal?.addEventListener('click', () => {
        setFeedbackModalVisibility(elements, false);
    });

    elements.feedbackModal?.addEventListener('click', (event) => {
        if (event.target === elements.feedbackModal) {
            setFeedbackModalVisibility(elements, false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setFeedbackModalVisibility(elements, false);
        }
    });
}

function setupImmersiveInteractions() {
    document.addEventListener('contextmenu', (event) => {
        event.preventDefault();
    });
}

function setFeedbackModalVisibility(elements, visible) {
    if (! elements.feedbackModal || ! elements.feedbackModalPanel) {
        return;
    }

    elements.feedbackModal.classList.toggle('pointer-events-none', ! visible);
    elements.feedbackModal.classList.toggle('opacity-0', ! visible);
    elements.feedbackModal.classList.toggle('opacity-100', visible);
    elements.feedbackModalPanel.classList.toggle('translate-y-6', ! visible);
    elements.feedbackModalPanel.classList.toggle('translate-y-0', visible);
    elements.feedbackModalPanel.classList.toggle('scale-[0.98]', ! visible);
    elements.feedbackModalPanel.classList.toggle('scale-100', visible);
    elements.feedbackModalPanel.classList.toggle('opacity-0', ! visible);
    elements.feedbackModalPanel.classList.toggle('opacity-100', visible);
    elements.feedbackModal.setAttribute('aria-hidden', visible ? 'false' : 'true');
    elements.openFeedbackModal?.setAttribute('aria-expanded', visible ? 'true' : 'false');
}

function cancelPendingDeployment(state, elements, postAction) {
    state.pendingDeployment = null;
    state.selectedCardId = null;
    state.draggingCardId = null;
    state.hoverFlagIndex = null;

    setAlert(document.querySelector('[data-game-alert]'), 'Deployment canceled. Select a troop again to continue.', 'info');
    renderHand(elements.hand, state, postAction, elements);
    renderBoard(elements.board, state, postAction, elements);
    renderActions(elements.actions, state, postAction, elements);
    renderFeedback(document.querySelector('[data-feedback]'), state);
    syncInteractionState(elements, state);
}

function setupSidebarToggles(elements, state) {
    elements.sidebarToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const side = button.dataset.toggleSidebar;

            if (! ['left', 'right'].includes(side)) {
                return;
            }

            state.collapsedSidebars[side] = ! state.collapsedSidebars[side];
            persistSidebarState(state.collapsedSidebars);
            applySidebarState(elements, state);
        });
    });

    applySidebarState(elements, state);
}

function applySidebarState(elements, state) {
    const leftCollapsed = state.collapsedSidebars.left;
    const rightCollapsed = state.collapsedSidebars.right;

    elements.layout.classList.remove(...sidebarLayoutClasses);

    if (leftCollapsed && rightCollapsed) {
        elements.layout.classList.add('xl:grid-cols-[88px_minmax(0,1fr)_88px]');
    } else if (leftCollapsed) {
        elements.layout.classList.add('xl:grid-cols-[88px_minmax(0,1fr)_320px]');
    } else if (rightCollapsed) {
        elements.layout.classList.add('xl:grid-cols-[280px_minmax(0,1fr)_88px]');
    } else {
        elements.layout.classList.add('xl:grid-cols-[280px_minmax(0,1fr)_320px]');
    }

    ['left', 'right'].forEach((side) => {
        const collapsed = state.collapsedSidebars[side];
        const shell = elements.sidebarShells[side];
        const expandedHeader = elements.sidebarExpandedHeaders[side];
        const collapsedTrigger = elements.sidebarCollapsedTriggers[side];
        const panels = elements.sidebarPanels[side];

        elements.sidebars[side]?.setAttribute('data-collapsed', collapsed ? 'true' : 'false');
        expandedHeader?.classList.toggle('hidden', collapsed);
        panels?.classList.toggle('hidden', collapsed);
        collapsedTrigger?.classList.toggle('hidden', ! collapsed);
        collapsedTrigger?.classList.toggle('flex', collapsed);
        shell?.classList.toggle('items-center', collapsed);
        shell?.classList.toggle('justify-center', collapsed);
    });

    elements.sidebarToggles.forEach((button) => {
        const side = button.dataset.toggleSidebar;
        const collapsed = state.collapsedSidebars[side];

        button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
}

function syncInteractionState(elements, state) {
    syncHandState(elements.hand, state);
    syncBoardState(elements.board, state);
}

function syncHandState(container, state) {
    if (! container) {
        return;
    }

    container.querySelectorAll('[data-select-card]').forEach((button) => {
        const isSelected = state.selectedCardId === button.dataset.selectCard;
        const isDragging = state.draggingCardId === button.dataset.selectCard;

        button.classList.toggle('ring-2', isSelected);
        button.classList.toggle('ring-white/80', isSelected);
        button.classList.toggle('border-white/25', isSelected);
        button.classList.toggle('shadow-2xl', isSelected);
        button.classList.toggle('shadow-black/35', isSelected);
        button.classList.toggle('opacity-35', isDragging);
        button.classList.toggle('cursor-grabbing', isDragging);
    });
}

function syncBoardState(container, state) {
    if (! container || ! state.game?.state) {
        return;
    }

    container.querySelectorAll('[data-flag-target]').forEach((flagTarget) => {
        const flagIndex = Number(flagTarget.dataset.flagTarget);
        const canDrop = canDropOnFlag(state, flagIndex);
        const isHovered = state.hoverFlagIndex === flagIndex;
        const isPending = state.pendingDeployment?.flagIndex === flagIndex;
        const usesDefaultDropHighlight = canDrop && ! isHovered && ! isPending;
        const usesFocusedDropHighlight = isHovered || isPending;
        const playHint = flagTarget.querySelector('[data-play-flag-hint]');

        flagTarget.classList.toggle('ring-4', canDrop || usesFocusedDropHighlight);
        flagTarget.classList.toggle('ring-war-ember/25', usesDefaultDropHighlight);
        flagTarget.classList.toggle('ring-war-gold/25', usesFocusedDropHighlight);
        flagTarget.classList.toggle('shadow-war-ember/15', usesDefaultDropHighlight);
        flagTarget.classList.toggle('shadow-war-gold/15', usesFocusedDropHighlight);
        flagTarget.classList.toggle('cursor-pointer', canDrop);

        if (! playHint) {
            return;
        }

        playHint.classList.toggle('border-war-ember/70', usesDefaultDropHighlight);
        playHint.classList.toggle('bg-war-ember/12', usesDefaultDropHighlight);
        playHint.classList.toggle('text-war-ash', usesDefaultDropHighlight);
        playHint.classList.toggle('hover:bg-war-ember/18', usesDefaultDropHighlight);
        playHint.classList.toggle('border-war-gold/55', usesFocusedDropHighlight);
        playHint.classList.toggle('bg-war-gold/10', usesFocusedDropHighlight);
        playHint.classList.toggle('text-war-gold', usesFocusedDropHighlight);
        playHint.classList.toggle('border-white/8', ! canDrop);
        playHint.classList.toggle('bg-white/[0.03]', ! canDrop);
        playHint.classList.toggle('text-white/45', ! canDrop);
        playHint.classList.toggle('ring-2', canDrop);
        playHint.classList.toggle('ring-war-gold/35', usesFocusedDropHighlight);
        playHint.classList.toggle('ring-war-ember/25', usesDefaultDropHighlight);
    });
}

function humanizePhase(phase) {
    return phase
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function firstValidationMessage(payload) {
    const errors = payload.errors ?? {};
    const firstErrorList = Object.values(errors)[0];

    return Array.isArray(firstErrorList) ? firstErrorList[0] : null;
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function loadSidebarState() {
    try {
        const storedState = window.localStorage.getItem('battle-line-sidebars');

        if (! storedState) {
            return { left: false, right: false };
        }

        const parsedState = JSON.parse(storedState);

        return {
            left: Boolean(parsedState.left),
            right: Boolean(parsedState.right),
        };
    } catch {
        return { left: false, right: false };
    }
}

function persistSidebarState(sidebarState) {
    try {
        window.localStorage.setItem('battle-line-sidebars', JSON.stringify(sidebarState));
    } catch {
        // Ignore storage failures and keep the in-memory layout state.
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}
