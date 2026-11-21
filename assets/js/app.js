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

    function showCartNotification(message, isError) {
        var notification = document.querySelector('[data-cart-notification]');
        if (!notification) {
            notification = document.createElement('div');
            notification.className = 'cart-notification';
            notification.setAttribute('role', isError ? 'alert' : 'status');
            notification.setAttribute('aria-live', isError ? 'assertive' : 'polite');
            notification.dataset.cartNotification = '1';
            document.body.appendChild(notification);
            window.requestAnimationFrame(function () {
                notification.classList.add('is-visible');
            });
        }

        window.clearTimeout(notification.hideTimer);
        window.clearTimeout(notification.removeTimer);
        notification.textContent = message;
        notification.classList.toggle('is-error', Boolean(isError));
        notification.classList.remove('is-hiding');
        notification.hideTimer = window.setTimeout(function () {
            notification.classList.add('is-hiding');
            notification.removeTimer = window.setTimeout(function () {
                notification.remove();
            }, 300);
        }, 2700);
    }

    document.querySelectorAll('form[data-cart-add-form]').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            var submitter = event.submitter;
            if (submitter && submitter.name === 'buy_now') return;

            event.preventDefault();
            if (form.dataset.submitting === '1') return;

            var button = submitter || form.querySelector('button[type="submit"]');
            var originalText = button ? button.textContent : '';
            form.dataset.submitting = '1';
            if (button) button.disabled = true;

            try {
                var response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                var result = null;
                try {
                    result = await response.json();
                } catch (parseError) {
                    result = null;
                }

                if (!response.ok || !result || !result.success) {
                    throw new Error(result && result.message ? result.message : 'Không thể thêm sản phẩm vào giỏ. Vui lòng thử lại.');
                }

                document.querySelectorAll('.cart-count').forEach(function (count) {
                    count.textContent = String(result.cart_count);
                });
                showCartNotification(result.message, false);
            } catch (error) {
                showCartNotification(error.message || 'Không thể thêm sản phẩm vào giỏ. Vui lòng thử lại.', true);
            } finally {
                delete form.dataset.submitting;
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        });
    });

    document.querySelectorAll('.product-card').forEach(function (card) {
        card.addEventListener('click', function (event) {
            if (event.button !== 0 || event.target.closest('a, button, input, select, textarea, label')) return;

            var link = card.querySelector('.product-card-link');
            if (link) window.location.assign(link.href);
        });
    });

    document.querySelectorAll('[data-cart-quantity]').forEach(function (input) {
        input.addEventListener('change', function () {
            if (!input.form) return;
            if (typeof input.form.requestSubmit === 'function') {
                input.form.requestSubmit();
            } else {
                input.form.submit();
            }
        });
    });

    document.querySelectorAll('[data-quantity-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            var inputId = button.getAttribute('aria-controls');
            var input = inputId
                ? document.getElementById(inputId)
                : button.form && button.form.querySelector('[data-cart-quantity]');
            if (!input) return;

            var min = Number(input.min) || 1;
            var max = input.max ? Number(input.max) : Infinity;
            var step = Number(input.step) || 1;
            var current = Number(input.value);
            if (!Number.isFinite(current)) current = min;

            var next = Math.min(max, Math.max(min, current + Number(button.dataset.quantityStep) * step));
            if (next === current) return;

            input.value = String(next);
            if (input.hasAttribute('data-cart-quantity')) {
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });

    function isWishlistForm(form) {
        return form.classList.contains('wishlist-form') || form.classList.contains('wishlist-detail-form');
    }

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented || isWishlistForm(form)) return;
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

    // Tim yêu thích: đổi ♡/♥ ngay lập tức, không chèn chữ "Đang xử lý" vào nút tròn.
    document.querySelectorAll('.wishlist-form, .wishlist-detail-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            if (form.dataset.wishlistPending === '1') {
                event.preventDefault();
                return;
            }

            var cardButton = form.querySelector('.wishlist-btn');
            var detailButton = form.querySelector('.btn-outline-danger');
            var willBeWished = cardButton
                ? !cardButton.classList.contains('is-active')
                : (detailButton ? detailButton.textContent.indexOf('♥') !== 0 : false);

            form.dataset.wishlistPending = '1';

            if (cardButton) {
                cardButton.classList.toggle('is-active', willBeWished);
                cardButton.textContent = willBeWished ? '♥' : '♡';
                var label = willBeWished ? 'Bỏ khỏi yêu thích' : 'Thêm vào yêu thích';
                cardButton.setAttribute('aria-label', label);
                cardButton.setAttribute('title', label);
            }
            if (detailButton) {
                detailButton.textContent = willBeWished
                    ? '♥ Đã lưu yêu thích — bỏ lưu'
                    : '♡ Lưu vào yêu thích';
            }

            var counter = document.querySelector('.wishlist-count');
            if (counter) {
                var total = (parseInt(counter.textContent, 10) || 0) + (willBeWished ? 1 : -1);
                counter.textContent = String(Math.max(0, total));
            }
        });
    });

    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
        });
        document.querySelectorAll('form[data-wishlist-pending]').forEach(function (form) {
            delete form.dataset.wishlistPending;
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

    document.querySelectorAll('[data-admin-currency-input]').forEach(function (input) {
        var condition = input.getAttribute('data-admin-currency-when');
        var unit = input.parentElement.querySelector('[data-admin-currency-unit]');

        function parseCurrency(value) {
            value = String(value).trim();
            if (value.indexOf(',') !== -1) return value.replace(/\./g, '').replace(',', '.');
            if (/^-?\d{1,3}(?:\.\d{3})+$/.test(value)) return value.replace(/\./g, '');
            return value;
        }

        function formatCurrency(value) {
            var parsed = parseCurrency(value);
            if (parsed === '' || !/^-?\d+(?:\.\d{1,2})?$/.test(parsed)) return value;
            return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(Number(parsed));
        }

        function currencyIsActive() {
            if (!condition) return true;
            var parts = condition.split('=');
            var controller = input.form.elements.namedItem(parts[0]);
            return controller && controller.value === parts[1];
        }

        function updateInputType() {
            var active = currencyIsActive();
            var value = input.value;
            if (active) {
                input.type = 'text';
                input.setAttribute('inputmode', 'decimal');
                input.value = formatCurrency(value);
                if (unit) unit.textContent = '₫';
            } else {
                input.value = parseCurrency(value);
                input.type = 'number';
                input.setAttribute('step', 'any');
                input.setAttribute('min', '0');
                input.removeAttribute('inputmode');
                if (unit) unit.textContent = '%';
            }
        }

        input.addEventListener('focus', function () {
            if (currencyIsActive()) input.value = parseCurrency(input.value);
        });
        input.addEventListener('blur', function () {
            if (currencyIsActive()) input.value = formatCurrency(input.value);
        });

        if (condition) {
            var controller = input.form.elements.namedItem(condition.split('=')[0]);
            if (controller) controller.addEventListener('change', updateInputType);
        }
        updateInputType();
    });
});
