import './bootstrap';
import './battle-line-ui';

document.addEventListener('DOMContentLoaded', () => {
    setupAccountMenus();
    setupBattleLineViewportScaling();
});

function setupAccountMenus() {
    const accountMenus = document.querySelectorAll('[data-account-menu]');

    if (accountMenus.length === 0) {
        return;
    }

    document.addEventListener('click', (event) => {
        accountMenus.forEach((menu) => {
            if (! menu.open) {
                return;
            }

            if (! menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        accountMenus.forEach((menu) => {
            menu.open = false;
        });
    });
}

function setupBattleLineViewportScaling() {
    const page = document.querySelector('[data-battle-line-page]');
    const app = document.querySelector('[data-battle-line-app]');

    if (! page || ! app) {
        return;
    }

    const landscapeMedia = window.matchMedia('(max-width: 1023px) and (orientation: landscape)');
    const topbar = document.querySelector('[data-battle-line-topbar]');
    const scalableSections = [
        document.querySelector('[data-battlefield-panel]'),
        document.querySelector('[data-hand-shell]'),
        document.querySelector('[data-sidebar-shell="left"]'),
        document.querySelector('[data-sidebar-shell="right"]'),
    ].filter(Boolean);
    let frameId = null;

    const syncLayout = () => {
        frameId = null;

        const viewportHeight = window.visualViewport?.height ?? window.innerHeight;
        const pageRect = page.getBoundingClientRect();
        const pageStyle = window.getComputedStyle(page);
        const bottomPadding = Number.parseFloat(pageStyle.paddingBottom) || 0;
        const availableHeight = Math.max(viewportHeight - pageRect.top - bottomPadding, 280);

        page.style.setProperty('--battle-line-available-height', `${availableHeight}px`);

        if (! landscapeMedia.matches) {
            page.style.setProperty('--battle-line-mobile-scale', '1');
            page.style.setProperty('--battle-line-mobile-controls-height', 'auto');

            return;
        }

        const baseScale = Math.max(0.72, Math.min(1, availableHeight / 430));
        const baseControlsHeight = Math.max(Math.min(availableHeight * 0.36, 184), 118);

        page.style.setProperty('--battle-line-mobile-scale', baseScale.toFixed(3));
        page.style.setProperty('--battle-line-mobile-controls-height', `${baseControlsHeight}px`);

        window.requestAnimationFrame(() => {
            const sectionScale = scalableSections.reduce((smallestScale, section) => {
                const sectionOverflowScale = Math.min(1, section.clientHeight / Math.max(section.scrollHeight, 1));

                return Math.min(smallestScale, sectionOverflowScale);
            }, 1);
            const overflowScale = Math.min(1, availableHeight / Math.max(app.scrollHeight, 1), sectionScale);
            const fittedScale = Math.max(0.64, Math.min(baseScale, overflowScale));
            const fittedControlsHeight = Math.max(Math.min(availableHeight * 0.36 * fittedScale, 184), 104);

            page.style.setProperty('--battle-line-mobile-scale', fittedScale.toFixed(3));
            page.style.setProperty('--battle-line-mobile-controls-height', `${fittedControlsHeight}px`);
        });
    };

    const requestSync = () => {
        if (frameId) {
            window.cancelAnimationFrame(frameId);
        }

        frameId = window.requestAnimationFrame(syncLayout);
    };

    const resizeObserver = new ResizeObserver(() => {
        requestSync();
    });

    resizeObserver.observe(app);
    scalableSections.forEach((section) => {
        resizeObserver.observe(section);
    });

    if (topbar) {
        resizeObserver.observe(topbar);
    }

    landscapeMedia.addEventListener('change', requestSync);
    window.addEventListener('resize', requestSync);
    window.addEventListener('orientationchange', requestSync);
    window.addEventListener('battle-line:layout-change', requestSync);
    window.visualViewport?.addEventListener('resize', requestSync);

    requestSync();
}
