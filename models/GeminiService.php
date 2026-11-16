<?php

/**
 * Client gọi Google Gemini API (generateContent) bằng cURL.
 * API key chỉ tồn tại ở server, không bao giờ gửi xuống trình duyệt.
 */
class GeminiService
{
    private $apiKey;
    private $model;

    public function __construct($apiKey, $model)
    {
        $this->apiKey = (string) $apiKey;
        $this->model = (string) $model;
    }

    /**
     * @param string $systemPrompt Hướng dẫn vai trò + ngữ cảnh cửa hàng.
     * @param array  $contents     Lịch sử hội thoại dạng Gemini contents.
     * @return string
     */
    public function generate($systemPrompt, array $contents)
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Chưa cấu hình AI_API_KEY.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP chưa bật extension curl.');
        }

        $url = AI_API_BASE . '/models/'
            . rawurlencode($this->model)
            . ':generateContent?key=' . urlencode($this->apiKey);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => (string) $systemPrompt]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.6,
                'topP' => 0.9,
                'maxOutputTokens' => 900,
            ],
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Không thể tạo dữ liệu gửi Gemini.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'TruyenThuVi-AI/1.0',
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Không kết nối được Gemini: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('Gemini trả về dữ liệu không hợp lệ (HTTP ' . $status . ').');
        }

        if ($status >= 400) {
            $message = $data['error']['message'] ?? ('HTTP ' . $status);
            throw new RuntimeException('Gemini API lỗi: ' . $message);
        }

        $text = '';
        foreach (($data['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }
        }
        $text = trim($text);

        if ($text === '') {
            $reason = $data['promptFeedback']['blockReason']
                ?? ($data['candidates'][0]['finishReason'] ?? 'không rõ');
            throw new RuntimeException('Gemini không trả về nội dung (' . $reason . ').');
        }

        return $text;
    }
}
