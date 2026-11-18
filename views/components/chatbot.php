<?php
$chatbotUserName = (string) ($_SESSION['user']['name'] ?? '');
$chatbotUserEmail = (string) ($_SESSION['user']['email'] ?? '');
$chatbotUserPhone = (string) ($_SESSION['user']['phone'] ?? '');
?>
<div
    id="chatbot"
    class="chatbot"
    data-endpoint="<?= e(url('chatbot/message')) ?>"
    data-escalate-endpoint="<?= e(url('chatbot/escalate')) ?>"
    data-contact-url="<?= e(url('contact')) ?>"
    data-csrf="<?= e(csrf_token()) ?>"
    data-user-name="<?= e($chatbotUserName) ?>"
    data-user-email="<?= e($chatbotUserEmail) ?>"
    data-user-phone="<?= e($chatbotUserPhone) ?>"
>
    <button
        id="chatbot-toggle"
        class="chatbot-toggle"
        type="button"
        aria-label="Mở trợ lý hỗ trợ"
        aria-controls="chatbot-panel"
        aria-expanded="false"
    >
        <svg viewBox="0 0 24 24" aria-hidden="true" width="26" height="26">
            <path fill="currentColor" d="M12 3C6.9 3 3 6.5 3 10.9c0 2.5 1.4 4.8 3.6 6.3L6 21l3.9-1.8c.7.2 1.4.3 2.1.3 5.1 0 9-3.5 9-7.9S17.1 3 12 3Zm-3.2 8.6a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm3.2 0a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Zm3.2 0a1.3 1.3 0 1 1 0-2.6 1.3 1.3 0 0 1 0 2.6Z"/>
        </svg>
        <span class="chatbot-toggle-label">Hỗ trợ</span>
        <span class="chatbot-unread" id="chatbot-unread" hidden>1</span>
    </button>

    <section id="chatbot-panel" class="chatbot-panel" role="dialog" aria-label="Trợ lý hỗ trợ TruyenThuVi" aria-hidden="true" hidden>
        <header class="chatbot-header">
            <div class="chatbot-avatar" aria-hidden="true">TT</div>
            <div class="chatbot-header-text">
                <strong>Trợ lý TruyenThuVi</strong>
                <span><i class="chatbot-online-dot" aria-hidden="true"></i> Sẵn sàng hỗ trợ</span>
            </div>
            <button id="chatbot-close" class="chatbot-icon-button" type="button" aria-label="Đóng trợ lý">×</button>
        </header>

        <div id="chatbot-messages" class="chatbot-messages" role="log" aria-live="polite" aria-relevant="additions"></div>

        <div class="chatbot-footer">
            <div id="chatbot-suggestions" class="chatbot-suggestions" aria-label="Gợi ý nhanh"></div>

            <form id="chatbot-form" class="chatbot-compose" autocomplete="off">
                <label class="visually-hidden" for="chatbot-input">Nhập tin nhắn</label>
                <input
                    id="chatbot-input"
                    name="message"
                    type="text"
                    maxlength="1000"
                    placeholder="Hỏi về truyện, đơn hàng..."
                    required
                >
                <button id="chatbot-send" type="submit" aria-label="Gửi tin nhắn">➤</button>
            </form>

            <div id="chatbot-actions" class="chatbot-actions">
                <button id="chatbot-human-btn" type="button">Gặp nhân viên</button>
                <a href="<?= e(url('contact')) ?>">Gửi liên hệ</a>
            </div>

            <form id="chatbot-escalate-form" class="chatbot-escalate" hidden>
                <p class="chatbot-escalate-title">Để lại thông tin, nhân viên sẽ liên hệ lại:</p>
                <label class="visually-hidden" for="chatbot-es-name">Họ tên</label>
                <input id="chatbot-es-name" type="text" maxlength="150" placeholder="Họ tên *" required>
                <div class="chatbot-es-row">
                    <label class="visually-hidden" for="chatbot-es-email">Email</label>
                    <input id="chatbot-es-email" type="email" maxlength="190" placeholder="Email *" required>
                    <label class="visually-hidden" for="chatbot-es-phone">Số điện thoại</label>
                    <input id="chatbot-es-phone" type="tel" maxlength="30" placeholder="Số điện thoại">
                </div>
                <label class="visually-hidden" for="chatbot-es-message">Nội dung</label>
                <textarea id="chatbot-es-message" rows="3" maxlength="2000" placeholder="Nội dung cần hỗ trợ *" required></textarea>
                <p id="chatbot-es-error" class="chatbot-error" role="alert" hidden></p>
                <div class="chatbot-es-actions">
                    <button id="chatbot-es-cancel" type="button">Quay lại</button>
                    <button id="chatbot-es-submit" type="submit">Gửi yêu cầu</button>
                </div>
            </form>

            <p class="chatbot-disclaimer">Trợ lý AI có thể sai sót. Vui lòng xác nhận thông tin quan trọng với nhân viên.</p>
        </div>
    </section>
</div>
