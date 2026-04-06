import './bootstrap';
import './battle-line-ui';

document.addEventListener('DOMContentLoaded', () => {
    setupAccountMenus();
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
