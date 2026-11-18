<?php

class ChatbotController extends BaseController
{
    private const MAX_MESSAGE_LENGTH = 1000;
    private const HISTORY_LIMIT = 10;

    public function message(): void
    {
        $this->requirePost();

        if (!$this->allowRequest('_chatbot_message_hits', 15, 60)) {
            $this->jsonResponse([
                'message' => 'Bạn gửi tin nhắn hơi nhanh. Vui lòng đợi một chút rồi thử lại nhé.',
                'suggestions' => [],
                'products' => [],
                'need_human' => false,
            ], 429);
            return;
        }

        $input = $this->jsonInput();
        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '' || mb_strlen($message, 'UTF-8') > self::MAX_MESSAGE_LENGTH) {
            $this->jsonResponse(['error' => 'Tin nhắn không hợp lệ (tối đa 1000 ký tự).'], 422);
            return;
        }

        $history = $this->history();
        $answer = (new ChatbotService())->respond($message, $history);

        $history[] = ['role' => 'user', 'text' => limit_text($message, 2000)];
        $history[] = ['role' => 'assistant', 'text' => limit_text($answer['message'], 2000)];
        $_SESSION['_chatbot_history'] = array_slice($history, -self::HISTORY_LIMIT);

        $this->jsonResponse($answer);
    }

    public function escalate(): void
    {
        $this->requirePost();

        if (!$this->allowRequest('_chatbot_escalate_hits', 5, 600)) {
            $this->jsonResponse(['error' => 'Bạn đã gửi nhiều yêu cầu. Vui lòng đợi vài phút rồi thử lại.'], 429);
            return;
        }

        $input = $this->jsonInput();
        $fullName = trim((string) ($input['full_name'] ?? $_SESSION['user']['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? $_SESSION['user']['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? $_SESSION['user']['phone'] ?? ''));
        $note = trim((string) ($input['message'] ?? ''));

        if (
            $fullName === ''
            || mb_strlen($fullName, 'UTF-8') > 150
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || mb_strlen($note, 'UTF-8') > 2000
        ) {
            $this->jsonResponse(['error' => 'Vui lòng nhập họ tên, email hợp lệ và nội dung (tối đa 2000 ký tự).'], 422);
            return;
        }

        $message = "[Yêu cầu từ chatbot]\nHọ tên: {$fullName}\nEmail: {$email}\n";
        if ($phone !== '') {
            $message .= 'Điện thoại: ' . limit_text($phone, 30) . "\n";
        }
        $message .= "\nNội dung: " . $note;

        $transcript = array_slice($this->history(), -8);
        if ($transcript) {
            $lines = [];
            foreach ($transcript as $turn) {
                $speaker = $turn['role'] === 'assistant' ? 'Trợ lý' : 'Khách';
                $lines[] = $speaker . ': ' . limit_text((string) $turn['text'], 300);
            }
            $message .= "\n\n--- Lịch sử trò chuyện ---\n" . implode("\n", $lines);
        }

        try {
            (new ContactModel())->create([
                'full_name' => limit_text($fullName, 150),
                'email' => $email,
                'phone' => limit_text($phone, 30),
                'message' => limit_text($message, 8000),
            ]);
        } catch (Throwable $exception) {
            error_log('[ChatbotController][escalate] ' . $exception->getMessage());
            $this->jsonResponse(['error' => 'Chưa gửi được yêu cầu. Vui lòng thử lại hoặc dùng trang Liên hệ.'], 500);
            return;
        }

        $this->jsonResponse(['message' => 'Đã gửi yêu cầu tới nhân viên hỗ trợ. Chúng tôi sẽ liên hệ sớm.']);
    }

    private function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);

        return is_array($data) ? $data : $_POST;
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function history(): array
    {
        $history = $_SESSION['_chatbot_history'] ?? [];
        if (!is_array($history)) {
            return [];
        }

        $clean = [];
        foreach ($history as $turn) {
            if (!is_array($turn)) {
                continue;
            }
            $role = $turn['role'] ?? '';
            $text = trim((string) ($turn['text'] ?? ''));
            if (in_array($role, ['user', 'assistant'], true) && $text !== '') {
                $clean[] = ['role' => $role, 'text' => limit_text($text, 2000)];
            }
        }

        return array_slice($clean, -self::HISTORY_LIMIT);
    }

    private function allowRequest(string $key, int $limit, int $windowSeconds): bool
    {
        $now = time();
        $hits = $_SESSION[$key] ?? [];
        if (!is_array($hits)) {
            $hits = [];
        }

        $hits = array_values(array_filter($hits, static function ($timestamp) use ($now, $windowSeconds) {
            return is_numeric($timestamp) && ($now - (int) $timestamp) < $windowSeconds;
        }));

        if (count($hits) >= $limit) {
            $_SESSION[$key] = $hits;
            return false;
        }

        $hits[] = $now;
        $_SESSION[$key] = $hits;

        return true;
    }
}
