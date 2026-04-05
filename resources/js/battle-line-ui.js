const troopColorClasses = {
    red: 'from-red-500/90 to-red-700/95 text-red-50 ring-red-300/40',
    orange: 'from-orange-400/90 to-orange-700/95 text-orange-50 ring-orange-300/40',
    yellow: 'from-amber-300/95 to-yellow-600/95 text-stone-950 ring-yellow-200/50',
    green: 'from-emerald-400/90 to-emerald-700/95 text-emerald-50 ring-emerald-300/40',
    blue: 'from-sky-400/90 to-blue-700/95 text-blue-50 ring-sky-300/40',
    purple: 'from-violet-400/90 to-purple-700/95 text-violet-50 ring-violet-300/40',
};

document.addEventListener('DOMContentLoaded', () => {
    setupCreateGameForm();
    setupBattleLineBoard();
});

function setupCreateGameForm() {
    const form = document.querySelector('[data-create-game-form]');

    if (! form) {
        return;
    }

    const submitButton = form.querySelector('[type="submit"]');
    const errorBox = form.querySelector('[data-form-error]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        submitButton.disabled = true;
        errorBox.textContent = '';

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.viewer_player_id = payload[payload.viewer_player_id];
        payload.starting_player_name = payload[payload.starting_player_name];

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (! response.ok) {
                throw new Error(firstValidationMessage(data) ?? 'Unable to create a new battle.');
            }

            window.location.href = `${form.dataset.redirectBase}/${data.data.id}?viewer_player_id=${encodeURIComponent(data.data.viewer_player_id)}`;
        } catch (error) {
            errorBox.textContent = error.message;
        } finally {
            submitButton.disabled = false;
        }
    });
}

function setupBattleLineBoard() {
    const app = document.querySelector('[data-battle-line-app]');

    if (! app) {
        return;
    }

    const state = {
        selectedCardId: null,
        game: null,
        busy: false,
        pollTimer: null,
        lastMessage: null,
    };

    const elements = {
        alert: app.querySelector('[data-game-alert]'),
        board: app.querySelector('[data-board]'),
        hand: app.querySelector('[data-hand]'),
        turn: app.querySelector('[data-turn]'),
        viewer: app.querySelector('[data-viewer]'),
        opponent: app.querySelector('[data-opponent]'),
        actions: app.querySelector('[data-actions]'),
        feedback: app.querySelector('[data-feedback]'),
        selection: app.querySelector('[data-selection]'),
        refresh: app.querySelector('[data-refresh-button]'),
    };

    const viewerPlayerId = app.dataset.viewerPlayerId;
    const showUrl = new URL(app.dataset.showUrl, window.location.origin);
    showUrl.searchParams.set('viewer_player_id', viewerPlayerId);

    const render = () => {
        if (! state.game) {
            return;
        }

        renderTurn(elements.turn, state.game.state.turn);
        renderPlayerPanel(elements.viewer, state.game.state.viewer, 'Commander');
        renderPlayerPanel(elements.opponent, state.game.state.opponent, 'Opponent');
        renderBoard(elements.board, state, postAction);
        renderHand(elements.hand, state, postAction);
        renderActions(elements.actions, state, postAction);
        renderFeedback(elements.feedback, state);
        renderSelection(elements.selection, state);
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

            if (state.selectedCardId && ! state.game.state.viewer.hand.some((card) => card.id === state.selectedCardId)) {
                state.selectedCardId = null;
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
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    player_id: viewerPlayerId,
                    ...payload,
                }),
            });
            const data = await response.json();

            if (! response.ok) {
                throw new Error(firstValidationMessage(data) ?? 'The action could not be completed.');
            }

            state.game = data.data;

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

    loadGame();

    state.pollTimer = window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            loadGame(true);
        }
    }, 5000);
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
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80">Active: ${escapeHtml(turn.active_player_id)}</span>
                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-white/80">Play order: ${turn.next_play_order}</span>
                ${turn.winner_id ? `<span class="rounded-full border border-war-gold/40 bg-war-gold/15 px-3 py-1 font-semibold text-war-gold">Winner: ${escapeHtml(turn.winner_id)}</span>` : ''}
            </div>
        </div>
    `;
}

function renderPlayerPanel(container, player, label) {
    container.innerHTML = `
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-white/40">${escapeHtml(label)}</p>
                <h2 class="font-display text-2xl text-white">${escapeHtml(player.player_id)}</h2>
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

function renderBoard(container, state, postAction) {
    const flags = state.game.state.board.flags;
    const claimable = new Set(state.game.state.available_actions?.claimable_flag_indexes ?? []);
    const playable = new Set(state.game.state.available_actions?.playable_flag_indexes ?? []);
    const canPlay = state.game.state.available_actions?.can_play_troop ?? false;

    container.innerHTML = flags.map((flag) => {
        const canPlayHere = canPlay && state.selectedCardId && playable.has(flag.index);
        const canClaimHere = claimable.has(flag.index);
        const status = flagFeedback(flag, state, { canPlayHere, canClaimHere, playable });

        return `
            <article class="group relative flex min-w-70 shrink-0 snap-start flex-col rounded-3xl border ${status.borderClass} bg-white/[0.045] p-4 shadow-2xl shadow-black/20 backdrop-blur-sm transition ${flag.claimed_by_viewer ? 'ring-2 ring-war-gold/60' : ''}">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.24em] text-white/35">Flag ${flag.index + 1}</p>
                        <p class="font-display text-lg text-white">${flag.claimed_by ? `Claimed by ${escapeHtml(flag.claimed_by)}` : 'Contested'}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full ${status.badgeClass} px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.2em]">${status.label}</span>
                        ${canClaimHere ? `<button data-claim-flag="${flag.index}" class="rounded-full border border-war-gold/40 bg-war-gold/15 px-3 py-1 text-xs font-semibold text-war-gold transition hover:bg-war-gold/25">Claim</button>` : ''}
                    </div>
                </div>
                <div class="grid gap-4">
                    <section class="rounded-2xl border border-white/8 bg-black/15 p-3">
                        <div class="mb-2 flex items-center justify-between text-xs uppercase tracking-[0.24em] text-white/35">
                            <span>Opponent Front</span>
                            <span>${flag.opponent_card_count}/3</span>
                        </div>
                        <div class="flex min-h-28 gap-2">
                            ${renderPlacedCards(flag.opponent_cards, false)}
                        </div>
                    </section>
                    <button
                        type="button"
                        data-play-flag="${flag.index}"
                        class="rounded-2xl border border-dashed px-3 py-2 text-left text-sm transition ${canPlayHere ? 'border-war-ember/70 bg-war-ember/12 text-war-ash hover:bg-war-ember/18' : 'border-white/8 bg-white/[0.03] text-white/45'}"
                        ${canPlayHere ? '' : 'disabled'}
                    >
                        <span class="block font-semibold">${canPlayHere ? 'Deploy selected card to this flag' : status.hint}</span>
                        <span class="mt-1 block text-xs text-white/40">${status.detail}</span>
                    </button>
                    <section class="rounded-2xl border border-white/8 bg-black/15 p-3">
                        <div class="mb-2 flex items-center justify-between text-xs uppercase tracking-[0.24em] text-white/35">
                            <span>Your Line</span>
                            <span>${flag.viewer_card_count}/3</span>
                        </div>
                        <div class="flex min-h-28 gap-2">
                            ${renderPlacedCards(flag.viewer_cards, true)}
                        </div>
                    </section>
                </div>
            </article>
        `;
    }).join('');

    container.querySelectorAll('[data-play-flag]').forEach((button) => {
        button.addEventListener('click', () => {
            const flagIndex = Number(button.dataset.playFlag);

            if (! state.selectedCardId) {
                setAlert(document.querySelector('[data-game-alert]'), 'Choose a card from your hand first.', true);

                return;
            }

            if (! playable.has(flagIndex)) {
                return;
            }

            postAction({
                type: 'play_troop',
                card_id: state.selectedCardId,
                flag_index: flagIndex,
            });
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

function renderHand(container, state, postAction) {
    const hand = state.game.state.viewer.hand;

    container.innerHTML = hand.length === 0
        ? '<p class="rounded-3xl border border-white/10 bg-white/[0.04] px-5 py-8 text-center text-sm text-white/55">Your hand is empty.</p>'
        : hand.map((card) => {
            const selected = state.selectedCardId === card.id;

            return `
                <button
                    type="button"
                    data-select-card="${card.id}"
                    class="group relative flex h-36 w-24 shrink-0 flex-col items-center justify-between rounded-[1.6rem] border border-white/12 bg-gradient-to-b ${troopColorClasses[card.color] ?? troopColorClasses.red} p-3 text-left shadow-lg shadow-black/25 ring-1 transition ${selected ? 'scale-[1.02] -translate-y-1 ring-2 ring-white/80' : 'ring-black/15 hover:-translate-y-1 hover:ring-white/35'}"
                >
                    <span class="w-full text-[0.65rem] font-bold uppercase tracking-[0.28em] opacity-80">${escapeHtml(card.color)}</span>
                    <span class="font-display text-5xl leading-none">${card.strength}</span>
                    <span class="w-full text-right text-[0.65rem] font-semibold uppercase tracking-[0.28em] opacity-80">Troop</span>
                </button>
            `;
        }).join('');

    container.querySelectorAll('[data-select-card]').forEach((button) => {
        button.addEventListener('click', () => {
            state.selectedCardId = state.selectedCardId === button.dataset.selectCard
                ? null
                : button.dataset.selectCard;

            renderHand(container, state, postAction);
            renderSelection(document.querySelector('[data-selection]'), state);
            renderFeedback(document.querySelector('[data-feedback]'), state);
            renderBoard(document.querySelector('[data-board]'), state, postAction);
        });
    });
}

function renderActions(container, state, postAction) {
    const actions = state.game.state.available_actions;

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

    container.innerHTML = `
        <div class="rounded-2xl border ${feedback.borderClass} ${feedback.panelClass} px-4 py-4">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] ${feedback.eyebrowClass}">${feedback.eyebrow}</p>
            <p class="mt-2 font-display text-2xl text-white">${feedback.title}</p>
            <p class="mt-2 text-sm leading-6 text-white/68">${feedback.body}</p>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            ${feedback.checklist.map((item) => `
                <div class="rounded-2xl border border-white/10 bg-black/15 px-4 py-3">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.24em] text-white/35">${item.label}</p>
                    <p class="mt-1 text-sm text-white/70">${item.value}</p>
                </div>
            `).join('')}
        </div>
    `;
}

function renderSelection(container, state) {
    const hand = state.game.state.viewer.hand;
    const selectedCard = hand.find((card) => card.id === state.selectedCardId) ?? null;
    const playableFlags = state.game.state.available_actions.playable_flag_indexes;

    if (! selectedCard) {
        container.innerHTML = `
            <div class="rounded-2xl border border-dashed border-white/10 bg-black/10 px-4 py-5 text-sm text-white/58">
                Choose a troop card from your hand to preview where it can be deployed this turn.
            </div>
        `;

        return;
    }

    container.innerHTML = `
        <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-black/15 p-4">
            <div class="flex h-24 w-18 shrink-0 flex-col justify-between rounded-[1.2rem] border border-white/10 bg-gradient-to-b ${troopColorClasses[selectedCard.color] ?? troopColorClasses.red} p-2 shadow-lg shadow-black/20">
                <span class="text-[0.55rem] font-bold uppercase tracking-[0.24em] opacity-80">${escapeHtml(selectedCard.color)}</span>
                <span class="font-display text-3xl leading-none">${selectedCard.strength}</span>
                <span class="text-right text-[0.55rem] font-semibold uppercase tracking-[0.24em] opacity-70">Troop</span>
            </div>
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-war-gold/70">Card locked in</p>
                <p class="font-display text-2xl text-white">${escapeHtml(selectedCard.color)} ${selectedCard.strength}</p>
                <p class="text-sm leading-6 text-white/68">
                    ${playableFlags.length > 0
                        ? `Deploy this troop to flags ${playableFlags.map((index) => index + 1).join(', ')}.`
                        : 'There is no legal flag for this troop right now.'}
                </p>
            </div>
        </div>
    `;
}

function renderPlacedCards(cards, isViewerSide) {
    if (! cards.length) {
        return '<div class="flex flex-1 items-center justify-center rounded-2xl border border-dashed border-white/8 text-xs uppercase tracking-[0.24em] text-white/25">Open Ground</div>';
    }

    return cards.map((entry) => `
        <div class="flex h-24 w-18 shrink-0 flex-col justify-between rounded-[1.2rem] border border-white/10 bg-gradient-to-b ${troopColorClasses[entry.card.color] ?? troopColorClasses.red} p-2 shadow-lg shadow-black/20 ring-1 ring-black/15">
            <span class="text-[0.55rem] font-bold uppercase tracking-[0.24em] opacity-80">${escapeHtml(entry.card.color)}</span>
            <span class="font-display text-3xl leading-none">${entry.card.strength}</span>
            <span class="text-right text-[0.55rem] font-semibold uppercase tracking-[0.24em] opacity-70">${isViewerSide ? 'Yours' : 'Enemy'}</span>
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

    element.textContent = message;
    element.className = `rounded-2xl border px-4 py-3 text-sm ${styles[type] ?? styles.info}`;
}

function deriveFeedback(state) {
    const turn = state.game.state.turn;
    const actions = state.game.state.available_actions;
    const selectedCard = state.game.state.viewer.hand.find((card) => card.id === state.selectedCardId) ?? null;

    if (turn.winner_id) {
        return {
            eyebrow: 'Outcome',
            title: turn.winner_id === state.game.viewer_player_id ? 'You hold the line.' : `${turn.winner_id} wins the battle.`,
            body: 'The match is complete. You can switch viewer seats above to inspect the final board from either perspective.',
            borderClass: 'border-war-gold/35',
            panelClass: 'bg-war-gold/10',
            eyebrowClass: 'text-war-gold/75',
            checklist: [
                { label: 'Claimed Flags', value: `${state.game.state.viewer.claimed_flag_count}` },
                { label: 'Opponent Flags', value: `${state.game.state.opponent.claimed_flag_count}` },
            ],
        };
    }

    if (selectedCard && actions.can_play_troop) {
        return {
            eyebrow: 'Selected Card',
            title: `Deploy ${selectedCard.color} ${selectedCard.strength}`,
            body: `The board is highlighting legal destinations. Place it on one of the glowing flags to commit the troop.`,
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
                ? 'Select a card from your hand and then tap a highlighted flag to deploy it.'
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
        title: `Waiting for ${turn.active_player_id}`,
        body: 'The board will refresh automatically every few seconds. You can still switch viewpoint or review each flag while the opponent thinks.',
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
            hint: 'Selected troop can be committed here',
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

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
