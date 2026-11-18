<?php

/**
 * Gọi Gemini generateContent. API key chỉ được dùng ở server.
 */
class GeminiChatClient
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    private int $lastHttpCode = 0;

    public function isConfigured(): bool
    {
        return AI_API_KEY !== '';
    }

    /**
     * @param array<int, array{role: string, text: string}> $history
     * @param array<int, int> $allowedProductIds
     * @return array{message: string, suggestions: array<int, string>, product_ids: array<int, int>, need_human: bool}
     */
    public function chat(string $systemPrompt, array $history, array $allowedProductIds = []): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình AI_API_KEY.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP chưa bật extension cURL.');
        }

        $contents = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => limit_text($text, 4000)]],
                ];
            }
        }
        if (!$contents) {
            throw new RuntimeException('Cuộc trò chuyện trống.');
        }

        $payload = [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 700,
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'message' => ['type' => 'STRING'],
                        'suggestions' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING'],
                        ],
                        'product_ids' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'INTEGER'],
                        ],
                        'need_human' => ['type' => 'BOOLEAN'],
                    ],
                    'required' => ['message', 'suggestions', 'product_ids', 'need_human'],
                ],
            ],
        ];

        $models = array_values(array_unique(array_merge([AI_MODEL], AI_MODEL_FALLBACKS)));
        $rawResponse = false;
        $decoded = null;
        $lastError = null;

        foreach ($models as $model) {
            $rawResponse = false;
            $this->lastHttpCode = 0;
            $url = sprintf(self::ENDPOINT, rawurlencode($model));
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json; charset=utf-8',
                    'x-goog-api-key: ' . AI_API_KEY,
                ],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 20,
            ]);

            $rawResponse = curl_exec($ch);
            $this->lastHttpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($rawResponse === false) {
                $lastError = new RuntimeException('Không kết nối được dịch vụ AI: ' . $curlError);
                continue;
            }

            $decoded = json_decode($rawResponse, true);
            if ($this->lastHttpCode !== 200) {
                $apiMessage = $decoded['error']['message'] ?? substr($rawResponse, 0, 200);
                error_log('[GeminiChatClient][' . $model . '] HTTP ' . $this->lastHttpCode . ': ' . $apiMessage);
                $lastError = new RuntimeException('Dịch vụ AI tạm thời không phản hồi (HTTP ' . $this->lastHttpCode . ').');
                if (!in_array($this->lastHttpCode, [429, 500, 502, 503, 504], true)) {
                    throw $lastError;
                }
                continue;
            }

            break;
        }

        if ($rawResponse === false || $this->lastHttpCode !== 200) {
            throw $lastError ?? new RuntimeException('Dịch vụ AI tạm thời không phản hồi.');
        }

        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if (!is_string($text) || trim($text) === '') {
            throw new RuntimeException('Dịch vụ AI không trả về nội dung.');
        }

        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        }
        $answer = json_decode(trim($text), true);
        if (!is_array($answer) || !is_string($answer['message'] ?? null)) {
            throw new RuntimeException('Phản hồi AI không đúng định dạng.');
        }

        $message = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $answer['message']) ?? '');
        if ($message === '') {
            throw new RuntimeException('Phản hồi AI trống.');
        }

        $suggestions = [];
        foreach ((array) ($answer['suggestions'] ?? []) as $suggestion) {
            if (is_string($suggestion)) {
                $suggestion = trim(limit_text($suggestion, 60));
                if ($suggestion !== '' && !in_array($suggestion, $suggestions, true)) {
                    $suggestions[] = $suggestion;
                }
            }
            if (count($suggestions) >= 3) {
                break;
            }
        }

        $productIds = [];
        foreach ((array) ($answer['product_ids'] ?? []) as $productId) {
            $productId = (int) $productId;
            if ($productId > 0 && in_array($productId, $allowedProductIds, true)) {
                $productIds[$productId] = $productId;
            }
            if (count($productIds) >= 4) {
                break;
            }
        }

        return [
            'message' => limit_text($message, 1400),
            'suggestions' => $suggestions,
            'product_ids' => array_values($productIds),
            'need_human' => !empty($answer['need_human']),
        ];
    }
}
