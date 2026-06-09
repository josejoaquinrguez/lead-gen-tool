document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-search-form]');
    const icons = {
        search: '<svg viewBox="0 0 24 24" fill="none"><path d="m21 21-4.3-4.3M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        refresh: '<svg viewBox="0 0 24 24" fill="none"><path d="M20 11a8.1 8.1 0 0 0-15.5-3M4 4v4h4M4 13a8.1 8.1 0 0 0 15.5 3M20 20v-4h-4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        download: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3v11m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        external: '<svg viewBox="0 0 24 24" fill="none"><path d="M14 4h6v6m0-6-9 9M20 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        instagram: '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor"/><path d="M15.5 11.4a3.6 3.6 0 1 1-7.2 0 3.6 3.6 0 0 1 7.2 0ZM17.3 7.4h.01" stroke="currentColor" stroke-linecap="round"/></svg>',
        map: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21s7-5.3 7-11a7 7 0 1 0-14 0c0 5.7 7 11 7 11Z" stroke="currentColor" stroke-linejoin="round"/><path d="M12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" stroke="currentColor"/></svg>',
        database: '<svg viewBox="0 0 24 24" fill="none"><path d="M5 7c0-2 14-2 14 0v10c0 2-14 2-14 0V7Zm14 5c0 2-14 2-14 0m14-5c0 2-14 2-14 0" stroke="currentColor"/></svg>',
        target: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0-2.2a2.3 2.3 0 1 0 0-4.6 2.3 2.3 0 0 0 0 4.6Z" stroke="currentColor"/></svg>',
        activity: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 13h4l2-7 4 14 2-7h4" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        globe: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-8-9h16M12 3c2.2 2.4 3.2 5.4 3.2 9S14.2 18.6 12 21c-2.2-2.4-3.2-5.4-3.2-9S9.8 5.4 12 3Z" stroke="currentColor"/></svg>',
        dashboard: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 5a1 1 0 0 1 1-1h6v8H4V5Zm13-1h2a1 1 0 0 1 1 1v4h-6V4h3ZM4 15h6v5H5a1 1 0 0 1-1-1v-4Zm10-2h6v6a1 1 0 0 1-1 1h-5v-7Z" stroke="currentColor" stroke-linejoin="round"/></svg>',
        leads: '<svg viewBox="0 0 24 24" fill="none"><path d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4 21a8 8 0 0 1 16 0M18 8h3m-1.5-1.5v3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        filters: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 7h10M18 7h2M4 17h2M10 17h10M8 5v4M16 15v4" stroke="currentColor" stroke-linecap="round"/></svg>',
        export: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 14V3m0 11 4-4m-4 4-4-4M5 17v3h14v-3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        moon: '<svg viewBox="0 0 24 24" fill="none"><path d="M20.5 15.2A8.5 8.5 0 0 1 8.8 3.5a8.5 8.5 0 1 0 11.7 11.7Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        sun: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 17a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1" stroke="currentColor" stroke-linecap="round"/></svg>',
    };

    const refreshThemeToggle = () => {
        const toggle = document.querySelector('[data-theme-toggle]');
        const label = document.querySelector('[data-theme-label]');
        const isLight = document.documentElement.dataset.theme === 'light';

        if (toggle) {
            toggle.dataset.buttonIcon = isLight ? 'sun' : 'moon';
            const existing = toggle.querySelector('svg');
            if (existing) {
                existing.remove();
            }
            toggle.insertAdjacentHTML('afterbegin', icons[toggle.dataset.buttonIcon] || '');
        }

        if (label) {
            label.textContent = isLight ? 'Modo claro' : 'Modo oscuro';
        }
    };

    document.querySelectorAll('[data-icon]').forEach((node) => {
        node.innerHTML = icons[node.dataset.icon] || '';
    });

    document.querySelectorAll('[data-button-icon]').forEach((node) => {
        if (!node.querySelector('svg')) {
            node.insertAdjacentHTML('afterbegin', icons[node.dataset.buttonIcon] || '');
        }
    });

    document.querySelectorAll('[data-nav-icon]').forEach((node) => {
        if (!node.querySelector('svg')) {
            node.insertAdjacentHTML('afterbegin', icons[node.dataset.navIcon] || '');
        }
    });

    refreshThemeToggle();

    document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
        document.documentElement.dataset.theme = nextTheme;
        localStorage.setItem('leadgen-theme', nextTheme);
        refreshThemeToggle();
    });

    document.querySelectorAll('.score-ring').forEach((ring) => {
        ring.style.setProperty('--score-final', ring.style.getPropertyValue('--score') || '0');
        ring.style.setProperty('--score', '0');
        requestAnimationFrame(() => ring.classList.add('is-ready'));
    });

    if (!form) {
        return;
    }

    form.addEventListener('submit', () => {
        form.classList.add('is-loading');
        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.disabled = true;
            const label = button.querySelector('span:first-child');
            if (label) {
                label.textContent = 'Analizando...';
            }
        }
    });
});
