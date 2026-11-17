(function () {
    var root = document.documentElement;
    var storageKey = 'ttv-theme';
    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function currentTheme() {
        return root.dataset.theme === 'dark' ? 'dark' : 'light';
    }

    function apply(theme, persist) {
        var dark = theme === 'dark';
        root.dataset.theme = dark ? 'dark' : 'light';

        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', dark ? '#16171b' : '#f05a35');
        }

        if (document.querySelectorAll) {
            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                var icon = button.querySelector('.theme-toggle-icon');
                if (icon) {
                    icon.textContent = dark ? '☀' : '☾';
                }
                button.setAttribute('aria-label', dark ? 'Chuyển chế độ sáng' : 'Chuyển chế độ tối');
                button.setAttribute('title', dark ? 'Chế độ sáng' : 'Chế độ tối');
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
            });
        }

        if (persist) {
            try {
                localStorage.setItem(storageKey, theme);
            } catch (error) {
                /* private mode — theme still applies for this page */
            }
        }
    }

    document.addEventListener('click', function (event) {
        if (!event.target || !event.target.closest) {
            return;
        }
        var button = event.target.closest('[data-theme-toggle]');
        if (!button) {
            return;
        }
        event.preventDefault();
        apply(currentTheme() === 'dark' ? 'light' : 'dark', true);
    });

    window.addEventListener('storage', function (event) {
        if (event.key === storageKey && (event.newValue === 'dark' || event.newValue === 'light')) {
            apply(event.newValue, false);
        }
    });

    if (media && media.addEventListener) {
        media.addEventListener('change', function (event) {
            var saved = null;
            try {
                saved = localStorage.getItem(storageKey);
            } catch (error) {
                saved = null;
            }
            if (!saved) {
                apply(event.matches ? 'dark' : 'light', false);
            }
        });
    }

    apply(currentTheme(), false);
})();
