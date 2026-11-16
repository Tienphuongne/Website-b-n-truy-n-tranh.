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

    document.querySelectorAll('form[data-checkout]').forEach(function (form) {
        var subtotal = parseFloat(form.getAttribute('data-subtotal')) || 0;
        var discount = parseFloat(form.getAttribute('data-discount')) || 0;
        var freeThreshold = parseFloat(form.getAttribute('data-free-threshold')) || 0;
        var standardFee = parseFloat(form.getAttribute('data-fee-standard')) || 0;
        var expressFee = parseFloat(form.getAttribute('data-fee-express')) || 0;
        var etaStandard = form.getAttribute('data-eta-standard') || '';
        var etaExpress = form.getAttribute('data-eta-express') || '';
        var money = new Intl.NumberFormat('vi-VN');
        var shippingEl = form.querySelector('#summary-shipping');
        var totalEl = form.querySelector('#summary-total');
        var etaEl = form.querySelector('[data-delivery-eta]');
        var methodInputs = form.querySelectorAll('input[name="shipping_method"]');

        function currentMethod() {
            for (var index = 0; index < methodInputs.length; index++) {
                if (methodInputs[index].checked) {
                    return methodInputs[index].value;
                }
            }

            return 'standard';
        }

        function feeFor(method) {
            if (method === 'express') return expressFee;

            return subtotal >= freeThreshold ? 0 : standardFee;
        }

        function updateSummary() {
            var method = currentMethod();
            var fee = feeFor(method);
            if (shippingEl) {
                shippingEl.textContent = fee <= 0 ? 'Miễn phí' : money.format(fee) + '₫';
            }
            if (totalEl) {
                totalEl.textContent = money.format(Math.max(0, subtotal + fee - discount)) + '₫';
            }
            if (etaEl) {
                etaEl.textContent = method === 'express' ? etaExpress : etaStandard;
            }
            form.querySelectorAll('.delivery-option').forEach(function (option) {
                var input = option.querySelector('input[name="shipping_method"]');
                option.classList.toggle('is-selected', !!(input && input.checked));
            });
        }

        methodInputs.forEach(function (input) {
            input.addEventListener('change', updateSummary);
        });
        updateSummary();

        var savedRadios = form.querySelectorAll('[data-saved-address]');
        var newAddressRadio = form.querySelector('[data-new-address]');

        function setField(name, value) {
            var field = form.elements[name];
            if (field) field.value = value || '';
        }

        function markSelectedAddress(card) {
            form.querySelectorAll('.saved-address').forEach(function (item) {
                item.classList.toggle('is-selected', item === card);
            });
        }

        savedRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!radio.checked) return;

                var data;
                try {
                    data = JSON.parse(radio.getAttribute('data-saved-address'));
                } catch (error) {
                    return;
                }

                setField('full_name', data.full_name);
                setField('phone', data.phone);
                setField('city', data.city);
                setField('district', data.district);
                setField('ward', data.ward);
                setField('address', data.address);

                var phoneInput = form.elements.phone;
                if (phoneInput) phoneInput.classList.remove('is-invalid');
                markSelectedAddress(radio.closest('.saved-address'));
            });
        });

        if (newAddressRadio) {
            newAddressRadio.addEventListener('change', function () {
                if (!newAddressRadio.checked) return;

                ['full_name', 'phone', 'city', 'district', 'ward', 'address'].forEach(function (name) {
                    setField(name, '');
                });
                markSelectedAddress(newAddressRadio.closest('.saved-address'));

                var nameInput = form.elements.full_name;
                if (nameInput) nameInput.focus();
            });
        }

        var phoneField = form.querySelector('[data-phone-input]');
        if (phoneField) {
            phoneField.addEventListener('blur', function () {
                var digits = phoneField.value.replace(/\D+/g, '');
                if (digits.length === 11 && digits.indexOf('84') === 0) {
                    digits = '0' + digits.slice(2);
                }
                if (digits !== '') phoneField.value = digits;
                phoneField.classList.toggle('is-invalid', digits !== '' && !phoneField.checkValidity());
            });
            phoneField.addEventListener('input', function () {
                phoneField.classList.remove('is-invalid');
            });
        }

        var noteField = form.querySelector('[data-order-note]');
        form.querySelectorAll('[data-note-chip]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!noteField) return;

                var text = button.getAttribute('data-note-chip') || '';
                var current = noteField.value.trim();
                if (current.indexOf(text) !== -1) return;

                noteField.value = current === '' ? text : current + '. ' + text;
                button.classList.add('is-used');
                noteField.focus();
            });
        });

        var saveAddress = form.querySelector('[data-save-address]');
        var addressLabel = form.querySelector('[data-address-label]');
        if (saveAddress && addressLabel) {
            var syncAddressLabel = function () {
                addressLabel.disabled = !saveAddress.checked;
                var box = addressLabel.closest('.save-address-box');
                if (box) box.classList.toggle('is-off', !saveAddress.checked);
            };
            saveAddress.addEventListener('change', syncAddressLabel);
            syncAddressLabel();
        }
    });
});
