(function () {
    'use strict';

    var root = document.getElementById('chatbot');
    if (!root) {
        return;
    }

    var toggle = document.getElementById('chatbot-toggle');
    var panel = document.getElementById('chatbot-panel');
    var closeButton = document.getElementById('chatbot-close');
    var messages = document.getElementById('chatbot-messages');
    var suggestions = document.getElementById('chatbot-suggestions');
    var form = document.getElementById('chatbot-form');
    var input = document.getElementById('chatbot-input');
    var sendButton = document.getElementById('chatbot-send');
    var unread = document.getElementById('chatbot-unread');
    var actions = document.getElementById('chatbot-actions');
    var humanButton = document.getElementById('chatbot-human-btn');
    var escalateForm = document.getElementById('chatbot-escalate-form');
    var escalateCancel = document.getElementById('chatbot-es-cancel');
    var escalateError = document.getElementById('chatbot-es-error');
    var escalateSubmit = document.getElementById('chatbot-es-submit');

    var endpoint = root.getAttribute('data-endpoint');
    var escalateEndpoint = root.getAttribute('data-escalate-endpoint');
    var csrfToken = root.getAttribute('data-csrf');

    var defaultSuggestions = ['Gợi ý truyện bán chạy', 'Tra cứu đơn hàng', 'Gặp nhân viên'];
    var greeting = 'Chào anh/chị! Em là trợ lý TruyenThuVi. Em có thể giúp tìm truyện, báo giá, tra cứu đơn hàng hoặc chuyển anh/chị tới nhân viên hỗ trợ ạ.';
    var greeted = false;
    var busy = false;

    function scrollToBottom() {
        messages.scrollTop = messages.scrollHeight;
    }

    function addMessage(role, text, products) {
        var row = document.createElement('div');
        row.className = 'chatbot-message ' + (role === 'user' ? 'user' : 'bot');

        var bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.textContent = String(text || '');
        row.appendChild(bubble);

        if (role !== 'user' && Array.isArray(products) && products.length) {
            var list = document.createElement('div');
            list.className = 'chatbot-products';

            products.slice(0, 4).forEach(function (product) {
                if (!product || !product.name) {
                    return;
                }

                var link = document.createElement('a');
                var href = safeUrl(product.url);
                if (href) {
                    link.href = href;
                } else {
                    link.setAttribute('role', 'text');
                }
                link.className = 'chatbot-product';

                var name = document.createElement('span');
                name.className = 'chatbot-product-name';
                name.textContent = String(product.name);
                link.appendChild(name);

                var meta = document.createElement('span');
                meta.className = 'chatbot-product-meta';
                meta.textContent = [product.price, product.stock].filter(Boolean).join(' · ');
                link.appendChild(meta);

                list.appendChild(link);
            });

            if (list.childNodes.length) {
                bubble.appendChild(list);
            }
        }

        messages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function safeUrl(value) {
        try {
            var parsed = new URL(String(value || ''), window.location.href);
            return parsed.origin === window.location.origin ? parsed.href : null;
        } catch (error) {
            return null;
        }
    }

    function addTyping() {
        var row = document.createElement('div');
        row.className = 'chatbot-message bot';
        var bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble chatbot-typing';
        bubble.setAttribute('aria-label', 'Trợ lý đang soạn tin');
        for (var i = 0; i < 3; i += 1) {
            bubble.appendChild(document.createElement('span'));
        }
        row.appendChild(bubble);
        messages.appendChild(row);
        scrollToBottom();
        return row;
    }

    function setSuggestions(items) {
        suggestions.textContent = '';
        if (!Array.isArray(items)) {
            return;
        }
        items.slice(0, 3).forEach(function (item) {
            if (typeof item !== 'string' || item.trim() === '') {
                return;
            }
            var button = document.createElement('button');
            button.type = 'button';
            button.textContent = item.trim();
            suggestions.appendChild(button);
        });
    }

    function openPanel() {
        panel.hidden = false;
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Đóng trợ lý hỗ trợ');
        unread.hidden = true;

        if (!greeted) {
            addMessage('bot', greeting);
            setSuggestions(defaultSuggestions);
            greeted = true;
        }
        input.focus();
    }

    function closePanel() {
        panel.hidden = true;
        panel.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Mở trợ lý hỗ trợ');
        toggle.focus();
    }

    function showEscalateForm() {
        actions.hidden = true;
        escalateForm.hidden = false;
        escalateError.hidden = true;
        document.getElementById('chatbot-es-name').value = root.getAttribute('data-user-name') || '';
        document.getElementById('chatbot-es-email').value = root.getAttribute('data-user-email') || '';
        document.getElementById('chatbot-es-phone').value = root.getAttribute('data-user-phone') || '';
        document.getElementById('chatbot-es-name').focus();
    }

    function hideEscalateForm() {
        escalateForm.hidden = true;
        actions.hidden = false;
    }

    function postJson(url, payload) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok) {
                    var error = new Error(data.error || data.message || 'Yêu cầu không thành công.');
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        });
    }

    function sendMessage(text) {
        var message = String(text || '').trim();
        if (!message || busy) {
            return;
        }

        busy = true;
        sendButton.disabled = true;
        input.value = '';
        setSuggestions([]);
        addMessage('user', message);

        var typing = addTyping();
        postJson(endpoint, { message: message }).then(function (data) {
            typing.remove();
            addMessage('bot', data.message || 'Xin lỗi, em chưa trả lời được. Vui lòng thử lại.', data.products);
            setSuggestions(data.suggestions);
        }).catch(function (error) {
            typing.remove();
            var textError = error.status === 429
                ? 'Bạn gửi tin nhắn hơi nhanh. Vui lòng đợi một chút rồi thử lại nhé.'
                : 'Hiện em chưa kết nối được dịch vụ hỗ trợ. Anh/chị thử lại hoặc bấm "Gặp nhân viên" nhé.';
            addMessage('bot', textError);
            setSuggestions(['Thử lại', 'Gặp nhân viên']);
        }).then(function () {
            busy = false;
            sendButton.disabled = false;
            input.focus();
        });
    }

    toggle.addEventListener('click', function () {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel();
        }
    });

    closeButton.addEventListener('click', closePanel);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden && escalateForm.hidden) {
            closePanel();
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage(input.value);
    });

    suggestions.addEventListener('click', function (event) {
        var button = event.target.closest('button');
        if (!button) {
            return;
        }
        var text = button.textContent.trim();
        if (text === 'Gặp nhân viên') {
            showEscalateForm();
            return;
        }
        if (text === 'Thử lại') {
            input.focus();
            return;
        }
        sendMessage(text);
    });

    humanButton.addEventListener('click', showEscalateForm);
    escalateCancel.addEventListener('click', hideEscalateForm);

    escalateForm.addEventListener('submit', function (event) {
        event.preventDefault();
        escalateError.hidden = true;
        escalateSubmit.disabled = true;

        postJson(escalateEndpoint, {
            full_name: document.getElementById('chatbot-es-name').value.trim(),
            email: document.getElementById('chatbot-es-email').value.trim(),
            phone: document.getElementById('chatbot-es-phone').value.trim(),
            message: document.getElementById('chatbot-es-message').value.trim()
        }).then(function (data) {
            hideEscalateForm();
            addMessage('bot', data.message || 'Đã gửi yêu cầu tới nhân viên hỗ trợ.');
            setSuggestions(['Xem truyện bán chạy']);
            document.getElementById('chatbot-es-message').value = '';
        }).catch(function (error) {
            escalateError.textContent = error.message || 'Không gửi được yêu cầu. Vui lòng thử lại.';
            escalateError.hidden = false;
        }).then(function () {
            escalateSubmit.disabled = false;
        });
    });
})();
