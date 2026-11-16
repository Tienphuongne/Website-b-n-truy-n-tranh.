<?php if (defined('AI_ENABLED') && AI_ENABLED): ?>
<div class="ai-chat" id="ai-chat" data-endpoint="<?= e(url('ai/chat')) ?>" data-csrf="<?= e(csrf_token()) ?>">
    <button class="ai-chat-toggle" type="button" data-ai-toggle aria-expanded="false" aria-controls="ai-chat-panel" aria-label="Mở trợ lý AI">
        <span class="ai-chat-toggle-icon" aria-hidden="true">✦</span>
        <span class="ai-chat-toggle-label">Hỗ trợ</span>
    </button>

    <section class="ai-chat-panel" id="ai-chat-panel" role="dialog" aria-labelledby="ai-chat-title" hidden>
        <header class="ai-chat-head">
            <span class="ai-chat-avatar" aria-hidden="true">✦</span>
            <div class="ai-chat-head-text">
                <strong id="ai-chat-title">Miu · Trợ lý TruyenThuVi</strong>
                <span>Tư vấn truyện &amp; chính sách</span>
            </div>
            <button class="ai-chat-close" type="button" data-ai-close aria-label="Đóng trợ lý">×</button>
        </header>

        <div class="ai-chat-log" data-ai-log role="log" aria-live="polite" aria-relevant="additions">
            <div class="ai-chat-msg ai-chat-msg-bot">
                Xin chào 👋 Mình là Miu. Mình có thể gợi ý truyện theo sở thích, giải đáp phí giao hàng, thanh toán và đổi trả. Bạn cần tìm truyện gì hôm nay?
            </div>
            <div class="ai-chat-chips" data-ai-chips>
                <button type="button" data-ai-suggest>Truyện nào đang bán chạy?</button>
                <button type="button" data-ai-suggest>Phí giao hàng thế nào?</button>
                <button type="button" data-ai-suggest>Gợi ý truyện cho bé</button>
                <button type="button" data-ai-suggest>Kiểm tra đơn hàng</button>
            </div>
        </div>

        <form class="ai-chat-form" data-ai-form>
            <label class="visually-hidden" for="ai-chat-input">Câu hỏi cho trợ lý</label>
            <textarea id="ai-chat-input" class="ai-chat-input" rows="1" maxlength="1000" placeholder="Hỏi Miu về truyện, giao hàng..." data-ai-input required></textarea>
            <button class="ai-chat-send" type="submit" data-ai-send aria-label="Gửi câu hỏi">➤</button>
        </form>

        <p class="ai-chat-note">Trợ lý AI có thể trả lời chưa chính xác. <a href="<?= e(url('contact')) ?>">Liên hệ cửa hàng</a> nếu cần hỗ trợ trực tiếp.</p>
    </section>
</div>
<script src="<?= e(PATH_JS) ?>ai-chat.js" defer></script>
<?php endif; ?>
