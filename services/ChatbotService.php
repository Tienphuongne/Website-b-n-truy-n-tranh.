<?php

/**
 * Xây ngữ cảnh từ dữ liệu thật của cửa hàng, gọi AI và dự phòng bằng kịch bản.
 */
class ChatbotService
{
    private const STATUS_LABELS = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Chờ lấy hàng',
        'picked_up' => 'Lấy hàng thành công',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];

    private GeminiChatClient $ai;

    public function __construct()
    {
        $this->ai = new GeminiChatClient();
    }

    /**
     * @param array<int, array{role: string, text: string}> $history
     * @return array{message: string, suggestions: array<int, string>, products: array<int, array<string, mixed>>, need_human: bool, mode: string}
     */
    public function respond(string $message, array $history): array
    {
        $context = $this->buildContext($message);
        $conversation = array_slice($history, -8);
        $conversation[] = ['role' => 'user', 'text' => $message];

        try {
            $answer = $this->ai->chat(
                $this->buildSystemPrompt($context),
                $conversation,
                array_column($context['products'], 'id')
            );

            $answer['products'] = $this->pickProducts($context['products'], $answer['product_ids']);
            unset($answer['product_ids']);
            $answer['mode'] = 'ai';
            return $answer;
        } catch (Throwable $exception) {
            error_log('[ChatbotService] ' . $exception->getMessage());
            $answer = $this->fallback($message, $context);
            $answer['mode'] = 'fallback';
            return $answer;
        }
    }

    // ------------------------------------------------------------------
    // Ngữ cảnh
    // ------------------------------------------------------------------

    private function buildContext(string $message): array
    {
        return [
            'products' => $this->findProducts($message),
            'orders' => $this->findOrders($message),
            'notes' => [],
        ];
    }

    private function findProducts(string $message): array
    {
        try {
            $products = new ProductModel();
            $found = [];

            if (preg_match('/bán chạy|phổ biến|đọc nhiều|hot nhất|best.?seller/iu', $message)) {
                $rows = $products->getBestSellers(6);
            } elseif (preg_match('/mới nhất|mới về|vừa cập nhật|recent/iu', $message)) {
                $rows = $products->getLatest(6);
            } elseif (preg_match('/giảm giá|khuyến mãi|rẻ nhất|deal/iu', $message)) {
                $rows = $products->getDiscounted(6);
            } else {
                $rows = [];
                foreach ($this->searchTerms($message) as $term) {
                    foreach ($products->search(['q' => $term], 1, 4)['items'] as $row) {
                        $found[(int) $row['id']] = $row;
                    }
                    if (count($found) >= 6) {
                        break;
                    }
                }
            }

            foreach ($rows as $row) {
                $found[(int) $row['id']] = $row;
            }

            return array_values(array_map([$this, 'toProductCard'], array_slice($found, 0, 6)));
        } catch (Throwable $exception) {
            error_log('[ChatbotService][products] ' . $exception->getMessage());
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function searchTerms(string $message): array
    {
        $normalized = mb_strtolower($message, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? '';
        $tokens = preg_split('/\s+/u', trim($normalized)) ?: [];

        $stopWords = [
            'tôi', 'mình', 'cho', 'tìm', 'mua', 'có', 'truyện', 'tranh', 'nào', 'gì', 'về', 'giá',
            'bao', 'nhiêu', 'của', 'và', 'hay', 'gợi', 'ý', 'một', 'cái', 'cuốn', 'bộ', 'nhờ',
            'bạn', 'kể', 'vui', 'lòng', 'hiện', 'đang', 'bán', 'sẵn', 'không', 'ạ', 'nhé', 'để',
            'xem', 'với', 'nếu', 'cần', 'tư', 'vấn', 'git', 'theo', 'của', 'thế', 'này', 'đó',
        ];

        $keywords = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token, 'UTF-8') < 2 || in_array($token, $stopWords, true)) {
                continue;
            }
            $keywords[] = $token;
        }

        $terms = [];
        if ($keywords) {
            $terms[] = implode(' ', $keywords);
        }
        foreach ($keywords as $keyword) {
            if (!in_array($keyword, $terms, true)) {
                $terms[] = $keyword;
            }
            if (count($terms) >= 5) {
                break;
            }
        }

        return array_slice($terms, 0, 5);
    }

    private function toProductCard(array $row): array
    {
        $id = (int) $row['id'];

        return [
            'id' => $id,
            'name' => (string) $row['name'],
            'price' => $this->money((float) $row['sale_price']),
            'stock' => (int) $row['stock_quantity'] > 0 ? 'Còn hàng' : 'Hết hàng',
            'rating' => round((float) ($row['avg_rating'] ?? 0), 1),
            'url' => url('product/detail/' . $id),
            'description' => limit_text((string) ($row['short_description'] ?? ''), 110),
        ];
    }

    private function findOrders(string $message): array
    {
        $userId = !empty($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;

        try {
            $orders = new OrderModel();

            if ($userId > 0) {
                $rows = $orders->getForUser($userId, (string) ($_SESSION['user']['email'] ?? ''));
                $result = [
                    'owner' => 'signed_in',
                    'items' => [],
                    'detail' => null,
                ];
                foreach (array_slice($rows, 0, 5) as $row) {
                    $result['items'][] = $this->toOrderLine($row);
                }

                $wantedId = $this->extractOrderId($message);
                if ($wantedId > 0) {
                    foreach ($rows as $row) {
                        if ((int) $row['id'] === $wantedId) {
                            $details = $orders->getDetailsForUser(
                                $wantedId,
                                $userId,
                                (string) ($_SESSION['user']['email'] ?? '')
                            );
                            $result['detail'] = [
                                'id' => $wantedId,
                                'items' => array_map([$this, 'toOrderItem'], $details),
                            ];
                            break;
                        }
                    }
                }

                return $result;
            }

            $wantedId = $this->extractOrderId($message);
            $email = '';
            if (preg_match('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/', $message, $emailMatch)) {
                $email = $emailMatch[0];
            }

            $digits = preg_replace('/\D+/', '', $message) ?: '';
            $phone = preg_match('/0\d{9,10}/', $digits, $phoneMatch) ? $phoneMatch[0] : '';

            if ($wantedId > 0 && ($email !== '' || $phone !== '')) {
                $lookup = $orders->findForSupport($wantedId, $email, $phone);
                if ($lookup) {
                    return [
                        'owner' => 'verified_guest',
                        'items' => [$this->toOrderLine($lookup['order'])],
                        'detail' => [
                            'id' => $wantedId,
                            'items' => array_map([$this, 'toOrderItem'], $lookup['items']),
                        ],
                    ];
                }

                return [
                    'owner' => 'not_found',
                    'items' => [],
                    'detail' => null,
                ];
            }

            return [
                'owner' => 'guest',
                'items' => [],
                'detail' => null,
            ];
        } catch (Throwable $exception) {
            error_log('[ChatbotService][orders] ' . $exception->getMessage());
            return ['owner' => 'unavailable', 'items' => [], 'detail' => null];
        }
    }

    private function extractOrderId(string $message): int
    {
        if (preg_match('/(?:#|mã|đơn|order)\s*:?\s*(\d{2,})/iu', $message, $match)) {
            return (int) $match[1];
        }
        if (preg_match('/\b\d{4,}\b/', $message, $match)) {
            return (int) $match[0];
        }

        return 0;
    }

    private function toOrderLine(array $order): string
    {
        $status = (string) ($order['status'] ?? '');

        return sprintf(
            '#%d | %s | %s | %s | %s | %d sản phẩm',
            (int) $order['id'],
            self::STATUS_LABELS[$status] ?? $status,
            $this->money((float) ($order['total_amount'] ?? 0)),
            (string) ($order['created_at'] ?? ''),
            $this->paymentLabel((string) ($order['payment_method'] ?? '')),
            (int) ($order['item_count'] ?? 0)
        );
    }

    private function toOrderItem(array $item): string
    {
        return sprintf(
            '%s x%d - %s',
            (string) ($item['product_name'] ?? ''),
            (int) ($item['quantity'] ?? 0),
            $this->money((float) ($item['unit_price'] ?? 0))
        );
    }

    private function paymentLabel(string $method): string
    {
        return $method === 'bank_transfer' ? 'Chuyển khoản' : 'COD';
    }

    // ------------------------------------------------------------------
    // Prompt
    // ------------------------------------------------------------------

    private function buildSystemPrompt(array $context): string
    {
        $lines = [
            'Bạn là trợ lý ảo của TruyenThuVi, cửa hàng truyện tranh online tại Việt Nam.',
            'Trả lời bằng tiếng Việt, thân thiện, ngắn gọn (tối đa 120 từ), xưng "em" - gọi "anh/chị".',
            'Chỉ dùng dữ liệu trong phần DỮ LIỆU CỬA HÀNG cho tên truyện, giá, tồn kho và đơn hàng.',
            'TUYỆT ĐỐI không bịa giá, mã đơn, trạng thái đơn, chính sách hay thông tin liên hệ.',
            'product_ids chỉ được chứa id có trong DANH SÁCH SẢN PHẨM; nếu không khớp thì để mảng rỗng.',
            'Không tiết lộ dữ liệu đơn của người khác. Với khách chưa đăng nhập, phải có mã đơn VÀ email hoặc số điện thoại trùng khớp mới được trả lời trạng thái.',
            'Nếu không tìm thấy sản phẩm, nói rõ và gợi ý xem danh mục truyện.',
            'Chính sách hiện có: thanh toán COD hoặc chuyển khoản; phí vận chuyển mặc định 30.000₫; giao hàng toàn quốc; đổi trả liên hệ bộ phận hỗ trợ.',
            'Hotline 1900 0000, email hello@truyenthuvi.vn (thông tin mẫu của dự án).',
            'Nếu khách yêu cầu gặp người thật, phàn nàn hoặc bạn không chắc chắn: đặt need_human=true và nói sẽ chuyển thông tin cho nhân viên.',
            'suggestions là 1-3 câu ngắn gợi ý bước tiếp theo, không trùng lặp.',
            'Chỉ trả về JSON đúng schema, không kèm markdown.',
            '',
            'DỮ LIỆU CỬA HÀNG:',
            'DANH SÁCH SẢN PHẨM (id | tên | giá | tồn | đánh giá):',
        ];

        if ($context['products']) {
            foreach ($context['products'] as $product) {
                $lines[] = sprintf(
                    '%d | %s | %s | %s | %s/5',
                    $product['id'],
                    $product['name'],
                    $product['price'],
                    $product['stock'],
                    $product['rating']
                );
            }
        } else {
            $lines[] = '(không có sản phẩm khớp hoặc dữ liệu chưa sẵn sàng)';
        }

        $lines[] = '';
        $lines[] = 'ĐƠN HÀNG:';
        $orders = $context['orders'];
        if (($orders['owner'] ?? '') === 'signed_in') {
            $lines[] = 'Khách đã đăng nhập. Các đơn của khách:';
            $lines[] = $orders['items'] ? implode("\n", $orders['items']) : '(chưa có đơn)';
            if (!empty($orders['detail']['items'])) {
                $lines[] = 'Chi tiết đơn #' . $orders['detail']['id'] . ': ' . implode('; ', $orders['detail']['items']);
            }
        } elseif (($orders['owner'] ?? '') === 'verified_guest') {
            $lines[] = 'Đã xác thực thông tin. ' . implode("\n", $orders['items']);
            if (!empty($orders['detail']['items'])) {
                $lines[] = 'Chi tiết: ' . implode('; ', $orders['detail']['items']);
            }
        } elseif (($orders['owner'] ?? '') === 'not_found') {
            $lines[] = 'Không tìm thấy đơn khớp với thông tin khách cung cấp. Không được tiết lộ thông tin đơn khác.';
        } elseif (($orders['owner'] ?? '') === 'guest') {
            $lines[] = 'Khách chưa cung cấp mã đơn và email/số điện thoại. Hãy hỏi thêm trước khi tra cứu.';
        } else {
            $lines[] = 'Dữ liệu đơn tạm thời không truy cập được. Hãy xin lỗi và hướng khách tới liên hệ hỗ trợ.';
        }

        return implode("\n", $lines);
    }

    // ------------------------------------------------------------------
    // Dự phòng (không dùng AI)
    // ------------------------------------------------------------------

    private function fallback(string $message, array $context): array
    {
        $lower = mb_strtolower($message, 'UTF-8');
        $products = $context['products'];

        if (preg_match('/nhân viên|người thật|gặp ai|khiếu nại|escalate/iu', $lower)) {
            return [
                'message' => 'Dạ, em sẽ chuyển yêu cầu của anh/chị tới nhân viên hỗ trợ. Anh/chị có thể bấm "Gặp nhân viên" bên dưới để gửi thông tin liên hệ nhé.',
                'suggestions' => ['Gặp nhân viên', 'Xem truyện bán chạy'],
                'products' => [],
                'need_human' => true,
            ];
        }

        if (preg_match('/đơn|order|trạng thái|giao hàng|ship|vận chuyển đơn/iu', $lower)) {
            $orders = $context['orders'];
            if (in_array($orders['owner'] ?? '', ['signed_in', 'verified_guest'], true) && $orders['items']) {
                return [
                    'message' => 'Dạ, các đơn liên quan của anh/chị: ' . implode('; ', $orders['items']) . '. Anh/chị cần em giải thích thêm phần nào không?',
                    'suggestions' => ['Chính sách giao hàng', 'Gặp nhân viên'],
                    'products' => [],
                    'need_human' => false,
                ];
            }
            if (($orders['owner'] ?? '') === 'not_found') {
                return [
                    'message' => 'Dạ, em chưa tìm thấy đơn khớp với mã và thông tin anh/chị cung cấp. Anh/chị kiểm tra lại hoặc bấm "Gặp nhân viên" để được hỗ trợ nhé.',
                    'suggestions' => ['Gặp nhân viên', 'Xem truyện bán chạy'],
                    'products' => [],
                    'need_human' => true,
                ];
            }

            return [
                'message' => 'Dạ, để tra cứu đơn anh/chị vui lòng cung cấp mã đơn (vd. #12) và email hoặc số điện thoại đã dùng khi đặt. Nếu đã đăng nhập, em sẽ tự hiển thị các đơn của tài khoản này.',
                'suggestions' => ['Xem truyện bán chạy', 'Gặp nhân viên'],
                'products' => [],
                'need_human' => false,
            ];
        }

        if (preg_match('/chào|hello|hi\b|xin chào/iu', $lower)) {
            return [
                'message' => 'Chào anh/chị! Em là trợ lý TruyenThuVi. Em có thể giúp tìm truyện, báo giá, tra cứu đơn hàng hoặc chuyển anh/chị tới nhân viên hỗ trợ ạ.',
                'suggestions' => ['Gợi ý truyện bán chạy', 'Tra cứu đơn hàng', 'Gặp nhân viên'],
                'products' => [],
                'need_human' => false,
            ];
        }

        if (preg_match('/vận chuyển|ship|phí|giao hàng|bao lâu/iu', $lower)) {
            return [
                'message' => 'Dạ, hiện cửa hàng áp dụng phí vận chuyển mặc định 30.000₫ và giao hàng toàn quốc. Thời gian cụ thể nhân viên sẽ xác nhận khi xử lý đơn ạ.',
                'suggestions' => ['Tra cứu đơn hàng', 'Gặp nhân viên'],
                'products' => [],
                'need_human' => false,
            ];
        }

        if (preg_match('/thanh toán|cod|chuyển khoản|trả góp|thẻ/iu', $lower)) {
            return [
                'message' => 'Dạ, cửa hàng hỗ trợ thanh toán khi nhận hàng (COD) hoặc chuyển khoản thủ công khi đặt đơn ạ.',
                'suggestions' => ['Xem truyện bán chạy', 'Tra cứu đơn hàng'],
                'products' => [],
                'need_human' => false,
            ];
        }

        if (preg_match('/đổi|trả|hoàn tiền|bảo hành/iu', $lower)) {
            return [
                'message' => 'Dạ, nếu sách/truyện nhận được có vấn đề, anh/chị bấm "Gặp nhân viên" để gửi thông tin đơn, bộ phận hỗ trợ sẽ hướng dẫn đổi trả theo chính sách cửa hàng ạ.',
                'suggestions' => ['Gặp nhân viên', 'Tra cứu đơn hàng'],
                'products' => [],
                'need_human' => true,
            ];
        }

        if ($products) {
            $lines = [];
            $ids = [];
            foreach (array_slice($products, 0, 3) as $product) {
                $lines[] = sprintf('%s (%s, %s)', $product['name'], $product['price'], $product['stock']);
                $ids[] = $product['id'];
            }

            return [
                'message' => 'Dạ, em tìm thấy một số truyện phù hợp: ' . implode('; ', $lines) . '. Anh/chị bấm vào sản phẩm bên dưới để xem chi tiết nhé.',
                'suggestions' => ['Xem thêm truyện', 'Gợi ý truyện bán chạy', 'Gặp nhân viên'],
                'products' => $this->pickProducts($products, $ids),
                'need_human' => false,
            ];
        }

        return [
            'message' => 'Dạ, em chưa tìm thấy truyện khớp với yêu cầu. Anh/chị thử gõ tên truyện, tác giả hoặc xem danh mục truyện tranh để em hỗ trợ chính xác hơn nhé.',
            'suggestions' => ['Xem truyện bán chạy', 'Tra cứu đơn hàng', 'Gặp nhân viên'],
            'products' => [],
            'need_human' => false,
        ];
    }

    private function pickProducts(array $products, array $ids): array
    {
        $byId = [];
        foreach ($products as $product) {
            $byId[(int) $product['id']] = $product;
        }

        $picked = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if (isset($byId[$id])) {
                $picked[] = $byId[$id];
            }
            if (count($picked) >= 4) {
                break;
            }
        }

        return $picked;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . '₫';
    }
}
