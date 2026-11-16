(function () {
    'use strict';

    var root = document.getElementById('ai-chat');
    if (!root) return;

    var endpoint = root.getAttribute('data-endpoint');
    var csrf = root.getAttribute('data-csrf');
    var toggle = root.querySelector('[data-ai-toggle]');
    var panel = root.querySelector('#ai-chat-panel');
    var closeButton = root.querySelector('[data-ai-close]');
    var log = root.querySelector('[data-ai-log]');
    var chips = root.querySelector('[data-ai-chips]');
    var form = root.querySelector('[data-ai-form]');
    var input = root.querySelector('[data-ai-input]');
    var sendButton = root.querySelector('[data-ai-send]');

    if (!endpoint || !csrf || !toggle || !panel || !log || !form || !input || !sendButton) {
        return;
    }

    var history = [];
    var pending = false;
    var botCount = 0;

    function setOpen(open) {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        root.classList.toggle('is-open', open);

        if (open) {
            window.setTimeout(function () {
                input.focus();
                scrollToBottom();
            }, 40);
        } else {
            toggle.focus();
        }
    }

    function scrollToBottom() {
        log.scrollTop = log.scrollHeight;
    }

    function linkify(container, text) {
        var pattern = /(https?:\/\/[^\s<>"']+)/g;
        var parts = String(text).split(pattern);

        parts.forEach(function (part) {
            if (!part) return;

            if (/^https?:\/\//.test(part)) {
                var trailing = '';
                var match = part.match(/[.,;:!?)]+$/);
                if (match) {
                    trailing = match[0];
                    part = part.slice(0, -trailing.length);
                }

                var link = document.createElement('a');
                link.href = part;
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.textContent = part;
                container.appendChild(link);
                if (trailing) container.appendChild(document.createTextNode(trailing));
            } else {
                container.appendChild(document.createTextNode(part));
            }
        });
    }

    function appendMessage(role, text) {
        var message = document.createElement('div');
        message.className = 'ai-chat-msg ai-chat-msg-' + (role === 'user' ? 'user' : 'bot');

        if (role === 'user') {
            message.textContent = text;
        } else {
            linkify(message, text);
            botCount += 1;
        }

        log.appendChild(message);
        scrollToBottom();

        return message;
    }

    function showTyping() {
        var typing = document.createElement('div');
        typing.className = 'ai-chat-msg ai-chat-msg-bot ai-chat-typing';
        typing.setAttribute('aria-label', 'Miu đang trả lời');
        typing.innerHTML = '<span></span><span></span><span></span>';
        log.appendChild(typing);
        scrollToBottom();

        return typing;
    }

    function setPending(value) {
        pending = value;
        sendButton.disabled = value;
        input.disabled = value;
        root.classList.toggle('is-pending', value);
    }

    function pushHistory(role, content) {
        history.push({ role: role, content: content });
        if (history.length > 10) {
            history = history.slice(-10);
        }
    }

    function replyFromServer(data, response) {
        if (!response.ok) {
            var error = new Error(data && data.message ? data.message : 'Không gửi được câu hỏi.');
            error.status = response.status;
            throw error;
        }
        if (!data || typeof data.reply !== 'string' || data.reply.trim() === '') {
            throw new Error('Trợ lý chưa có câu trả lời. Bạn thử lại nhé.');
        }

        return data.reply.trim();
    }

    function send(text) {
        var message = String(text || '').trim();
        if (message === '' || pending) return;

        appendMessage('user', message);
        pushHistory('user', message);
        input.value = '';
        resizeInput();
        if (chips) chips.hidden = true;
        setPending(true);

        var typing = showTyping();
        var controller = window.AbortController ? new AbortController() : null;
        var timer = window.setTimeout(function () {
            if (controller) controller.abort();
        }, 45000);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            signal: controller ? controller.signal : undefined,
            body: JSON.stringify({ _csrf_token: csrf, messages: history })
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return {};
                }).then(function (data) {
                    return { data: data, response: response };
                });
            })
            .then(function (result) {
                var reply = replyFromServer(result.data, result.response);
                typing.remove();
                appendMessage('bot', reply);
                pushHistory('assistant', reply);
            })
            .catch(function (error) {
                typing.remove();
                var fallback = error && error.name === 'AbortError'
                    ? 'Trợ lý phản hồi hơi lâu. Bạn thử lại hoặc gọi hotline 1900 0000 nhé.'
                    : (error && error.message ? error.message : 'Có lỗi khi kết nối trợ lý. Bạn thử lại sau nhé.');
                appendMessage('bot', fallback);
            })
            .then(function () {
                window.clearTimeout(timer);
                setPending(false);
                input.focus();
            });
    }

    function resizeInput() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    toggle.addEventListener('click', function () {
        setOpen(panel.hidden);
    });

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            setOpen(false);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
        }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        send(input.value);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            send(input.value);
        }
    });

    input.addEventListener('input', resizeInput);

    root.querySelectorAll('[data-ai-suggest]').forEach(function (button) {
        button.addEventListener('click', function () {
            send(button.textContent);
        });
    });
})();
