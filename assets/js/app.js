document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-toggle-password'));
            if (!input) return;
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Ẩn' : 'Hiện';
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            // Tránh gửi trùng nhưng không vô hiệu hóa nút submit: nút có name/value
            // (ví dụ "Mua ngay" hoặc "Áp dụng mã") vẫn phải được gửi kèm form.
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            var button = event.submitter || form.querySelector('button[type="submit"]');
            if (!button || (!button.formNoValidate && !form.checkValidity())) return;
            form.dataset.submitting = '1';
            button.dataset.originalText = button.textContent;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Đang xử lý';
        });
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
            form.querySelectorAll('button[data-original-text]').forEach(function (button) {
                button.textContent = button.dataset.originalText;
                delete button.dataset.originalText;
            });
        });
    });

    document.querySelectorAll('[data-search-suggest]').forEach(function (input) {
        var form = input.closest('form');
        var box = form ? form.querySelector('[data-search-suggest-results]') : null;
        var endpoint = input.getAttribute('data-suggest-url');
        if (!box || !endpoint) return;

        var timer = null;
        var controller = null;
        var lastQuery = null;
        var numberFormat = new Intl.NumberFormat('vi-VN');

        function hideSuggestions() {
            box.hidden = true;
            box.innerHTML = '';
        }

        function renderSuggestions(items) {
            box.innerHTML = '';
            if (!items.length) {
                var empty = document.createElement('div');
                empty.className = 'search-suggest-empty';
                empty.textContent = 'Không tìm thấy truyện phù hợp';
                box.appendChild(empty);
                box.hidden = false;
                return;
            }

            items.forEach(function (item) {
                var link = document.createElement('a');
                link.className = 'search-suggest-item';
                link.href = item.url;

                var name = document.createElement('span');
                name.className = 'search-suggest-name';
                name.textContent = item.name;

                var meta = document.createElement('small');
                meta.textContent = (item.author ? item.author + ' · ' : '')
                    + numberFormat.format(item.price) + '₫';

                link.appendChild(name);
                link.appendChild(meta);
                box.appendChild(link);
            });
            box.hidden = false;
        }

        function loadSuggestions() {
            var query = input.value.trim();
            if (query === '') {
                lastQuery = null;
                hideSuggestions();
                return;
            }
            if (query === lastQuery) return;
            lastQuery = query;

            if (controller) controller.abort();
            controller = window.AbortController ? new AbortController() : null;

            fetch(endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller ? controller.signal : undefined
            })
                .then(function (response) { return response.ok ? response.json() : { items: [] }; })
                .then(function (data) {
                    if (input.value.trim() === query) renderSuggestions(data.items || []);
                })
                .catch(function (error) {
                    if (!error || error.name !== 'AbortError') hideSuggestions();
                });
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(loadSuggestions, 180);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') hideSuggestions();
        });
        input.addEventListener('focus', function () {
            if (input.value.trim() !== '') {
                lastQuery = null;
                loadSuggestions();
            }
        });
        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) hideSuggestions();
        });
    });

    document.querySelectorAll('[data-admin-search]').forEach(function (input) {
        input.addEventListener('input', function () {
            var query = input.value.toLocaleLowerCase();
            document.querySelectorAll('.admin-panel tbody tr').forEach(function (row) {
                row.hidden = row.textContent.toLocaleLowerCase().indexOf(query) === -1;
            });
        });
    });
});
