<section class="container section-space">
    <div class="page-heading"><div><span class="eyebrow">TRUYENTHUVI</span><h1>Tài khoản của tôi</h1></div>
        <form action="<?= e(url('logout')) ?>" method="post"><?= csrf_field() ?><button class="btn btn-outline-secondary" type="submit">Đăng xuất</button></form>
    </div>
    <div class="account-layout">
        <nav class="account-nav" aria-label="Tài khoản">
            <a class="<?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e(url('account')) ?>">Thông tin cá nhân</a>
            <a class="<?= $tab === 'password' ? 'active' : '' ?>" href="<?= e(url('account?tab=password')) ?>">Đổi mật khẩu</a>
            <a class="<?= $tab === 'orders' ? 'active' : '' ?>" href="<?= e(url('account?tab=orders')) ?>">Đơn hàng</a>
            <a class="<?= $tab === 'wishlist' ? 'active' : '' ?>" href="<?= e(url('account?tab=wishlist')) ?>">Yêu thích (<?= count($wishlistProducts) ?>)</a>
            <a class="<?= $tab === 'addresses' ? 'active' : '' ?>" href="<?= e(url('account?tab=addresses')) ?>">Sổ địa chỉ</a>

            <div class="account-voucher-card" title="Số mã ưu đãi còn dùng được, đã trừ mã đã sử dụng">
                <span class="voucher-mini-icon" aria-hidden="true">✦</span>
                <div>
                    <span class="account-voucher-label">Mã ưu đãi của bạn</span>
                    <strong><?= (int) $voucherSummary['available'] ?> <small>có thể dùng</small></strong>
                    <small class="account-voucher-used">Đã dùng <?= (int) $voucherSummary['used'] ?> mã ưu đãi</small>
                </div>
            </div>
        </nav>

        <div class="account-content">
            <?php if ($tab === 'password'): ?>
                <h2>Đổi mật khẩu</h2>
                <form method="post" action="<?= e(url('account?tab=password')) ?>" class="account-form">
                    <?= csrf_field() ?><input type="hidden" name="form_type" value="password">
                    <label class="form-label" for="current-password">Mật khẩu hiện tại</label><input class="form-control mb-3" id="current-password" name="current_password" type="password" required autocomplete="current-password">
                    <label class="form-label" for="new-password">Mật khẩu mới</label><input class="form-control mb-3" id="new-password" name="new_password" type="password" required minlength="8" autocomplete="new-password">
                    <label class="form-label" for="confirm-password">Xác nhận mật khẩu mới</label><input class="form-control mb-3" id="confirm-password" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                    <button class="btn btn-primary" type="submit">Cập nhật mật khẩu</button>
                </form>
            <?php elseif ($tab === 'orders'): ?>
                <?php
                $statusLabels = [
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Chờ lấy hàng',
                    'picked_up' => 'Lấy hàng thành công',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn tất',
                    'cancelled' => 'Đã hủy',
                ];
                $paymentLabels = [
                    'cod' => 'Thanh toán khi nhận hàng (COD)',
                    'bank_transfer' => 'Chuyển khoản ngân hàng',
                    'vnpay' => 'VNPAY',
                    'momo' => 'MoMo',
                    'card' => 'Thẻ Visa / Master / JCB / Napas',
                ];
                $formatOrderDate = static function ($value) {
                    return !empty($value) ? date('d/m/Y H:i', strtotime((string) $value)) : '—';
                };
                ?>
                <div class="account-section-head">
                    <div><h2>Đơn hàng của tôi</h2><p>Theo dõi trạng thái và đánh giá các sản phẩm đã mua.</p></div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('account?tab=orders')) ?>">Làm mới</a>
                </div>
                <div class="order-status-tabs" aria-label="Lọc trạng thái đơn hàng">
                    <?php
                    $statusTabs = [
                        'pending' => 'Chờ xác nhận',
                        'pickup' => 'Chờ lấy hàng',
                        'picked_up' => 'Lấy hàng thành công',
                        'shipping' => 'Đang giao',
                        'review' => 'Hoàn tất',
                        'cancelled' => 'Đã hủy',
                    ];
                    ?>
                    <?php foreach ($statusTabs as $statusKey => $statusName): ?>
                        <a class="<?= $orderStatusFilter === $statusKey ? 'active' : '' ?>"
                           href="<?= e(url('account?tab=orders&status=' . $statusKey)) ?>">
                            <?= e($statusName) ?>
                            <span><?= (int) ($orderStatusCounts[$statusKey] ?? 0) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($orders || $selectedOrder): ?>
                    <div class="table-responsive account-orders-table">
                    <?php if ($orders): ?>
                        <table class="table align-middle">
                            <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th><th></th></tr></thead>
                            <tbody>
                            <?php foreach ($orders as $order): ?>
                                <?php
                                $orderStatus = (string) $order['status'];
                                $isSelected = $selectedOrder && (int) $selectedOrder['id'] === (int) $order['id'];
                                ?>
                                <tr class="<?= $isSelected ? 'is-selected' : '' ?>">
                                    <td>
                                        <strong>#<?= (int) $order['id'] ?></strong>
                                        <small><?= (int) $order['item_quantity'] ?> cuốn · <?= (int) $order['item_count'] ?> đầu truyện</small>
                                    </td>
                                    <td><?= e($formatOrderDate($order['created_at'])) ?></td>
                                    <td><strong><?= number_format((float) $order['total_amount'], 0, ',', '.') ?>₫</strong></td>
                                    <td><span class="order-status-badge status-<?= e($orderStatus) ?>"><?= e($statusLabels[$orderStatus] ?? $orderStatus) ?></span></td>
                                    <td><a href="<?= e(url('account?tab=orders&status=' . rawurlencode($orderStatusFilter) . '&order=' . (int) $order['id'])) ?>"><?= $isSelected ? 'Đang xem' : 'Chi tiết' ?> <span aria-hidden="true">→</span></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                    </div>

                    <?php if ($orderNotFound && !$selectedOrder): ?>
                        <div class="account-alert">Không tìm thấy đơn hàng #<?= $requestedOrderId ?> trong lịch sử của bạn.</div>
                    <?php endif; ?>

                    <?php if ($selectedOrder): ?>
                        <?php
                        $detailSubtotal = 0;
                        foreach ($orderDetails as $detail) {
                            $detailSubtotal += (float) $detail['unit_price'] * (int) $detail['quantity'];
                        }
                        $detailStatus = (string) $selectedOrder['status'];
                        $addressParts = array_filter([
                            $selectedOrder['address'] ?? '',
                            $selectedOrder['district'] ?? '',
                            $selectedOrder['city'] ?? '',
                        ]);
                        $addressLabel = $addressParts ? implode(', ', array_map('strval', $addressParts)) : '—';
                        ?>
                        <div class="order-detail-box account-order-detail">
                            <div class="order-detail-head">
                                <div><h3>Chi tiết đơn #<?= (int) $selectedOrder['id'] ?></h3><p>Đặt lúc <?= e($formatOrderDate($selectedOrder['created_at'])) ?></p></div>
                                <div class="order-detail-actions">
                                    <span class="order-status-badge status-<?= e($detailStatus) ?>"><?= e($statusLabels[$detailStatus] ?? $detailStatus) ?></span>
                                    <?php if ($detailStatus === 'completed'): ?>
                                        <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#order-review-<?= (int) $selectedOrder['id'] ?>" aria-expanded="false" aria-controls="order-review-<?= (int) $selectedOrder['id'] ?>">Đánh giá</button>
                                        <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#order-return-<?= (int) $selectedOrder['id'] ?>" aria-expanded="false" aria-controls="order-return-<?= (int) $selectedOrder['id'] ?>">Yêu cầu hoàn hàng</button>
                                    <?php endif; ?>
                                    <?php if (in_array($detailStatus, ['pending', 'confirmed'], true)): ?>
                                        <?php if (!$cancellationRequest || $cancellationRequest['status'] === 'rejected'): ?>
                                            <button class="btn btn-outline-danger btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#cancel-order-<?= (int) $selectedOrder['id'] ?>">Hủy đơn hàng</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (in_array($detailStatus, ['pending', 'confirmed'], true)): ?>
                                <?php if (!$cancellationRequest || $cancellationRequest['status'] === 'rejected'): ?>
                                    <div class="collapse" id="cancel-order-<?= (int) $selectedOrder['id'] ?>">
                                        <form method="post" action="<?= e(url('account?tab=orders&status=' . $orderStatusFilter)) ?>" class="account-order-cancel-form" data-confirm="Bạn có chắc muốn <?= $detailStatus === 'pending' ? 'hủy' : 'gửi yêu cầu hủy' ?> đơn hàng #<?= (int) $selectedOrder['id'] ?>?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_type" value="cancel_order">
                                            <input type="hidden" name="order_id" value="<?= (int) $selectedOrder['id'] ?>">
                                            <label class="form-label" for="cancel-reason-<?= (int) $selectedOrder['id'] ?>">Lý do hủy đơn</label>
                                            <select class="form-select mb-2" id="cancel-reason-<?= (int) $selectedOrder['id'] ?>" name="cancellation_reason" required>
                                                <option value="">Chọn lý do</option>
                                                <?php foreach (OrderModel::cancellationReasons() as $reason => $label): ?>
                                                    <option value="<?= e($reason) ?>"><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label" for="cancel-description-<?= (int) $selectedOrder['id'] ?>">Chi tiết thêm (không bắt buộc)</label>
                                            <textarea class="form-control mb-3" id="cancel-description-<?= (int) $selectedOrder['id'] ?>" name="cancellation_description" rows="3" maxlength="3000"></textarea>
                                            <?php if ($detailStatus === 'confirmed'): ?>
                                                <p class="small text-muted">Đơn đã chờ lấy hàng: yêu cầu hủy cần được quản trị viên phê duyệt.</p>
                                            <?php else: ?>
                                                <p class="small text-muted">Đơn đang chờ xác nhận: hủy thành công ngay sau khi gửi yêu cầu.</p>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-sm" type="submit"><?= $detailStatus === 'pending' ? 'Xác nhận hủy' : 'Gửi yêu cầu hủy' ?></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($cancellationRequest): ?>
                                <?php
                                $cancellationStatusLabels = ['pending' => 'Chờ quản trị viên phê duyệt', 'approved' => 'Đã được duyệt', 'rejected' => 'Đã bị từ chối'];
                                $cancellationReasons = OrderModel::cancellationReasons();
                                ?>
                                <div class="account-alert">
                                    <strong>Yêu cầu hủy đơn: <?= e($cancellationStatusLabels[$cancellationRequest['status']] ?? $cancellationRequest['status']) ?></strong>
                                    <p class="mb-1">Lý do: <?= e($cancellationReasons[$cancellationRequest['reason']] ?? $cancellationRequest['reason']) ?></p>
                                    <?php if (!empty($cancellationRequest['description'])): ?><p class="mb-0"><?= nl2br(e($cancellationRequest['description'])) ?></p><?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="account-order-meta">
                                <div><span>Người nhận</span><strong><?= e($selectedOrder['customer_name']) ?></strong></div>
                                <div><span>Điện thoại</span><strong><?= e($selectedOrder['phone']) ?></strong></div>
                                <div><span>Thanh toán</span><strong><?= e($paymentLabels[$selectedOrder['payment_method']] ?? $selectedOrder['payment_method']) ?></strong></div>
                                <div class="is-wide"><span>Địa chỉ giao hàng</span><strong><?= e($addressLabel) ?></strong></div>
                            </div>

                            <?php if ($orderDetails): ?>
                                <div class="table-responsive account-order-items">
                                    <table class="table align-middle">
                                        <thead><tr><th>Truyện</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($orderDetails as $detail): ?>
                                            <tr>
                                                <td><?= e($detail['product_name']) ?></td>
                                                <td><?= number_format((float) $detail['unit_price'], 0, ',', '.') ?>₫</td>
                                                <td>× <?= (int) $detail['quantity'] ?></td>
                                                <td><strong><?= number_format((float) $detail['unit_price'] * (int) $detail['quantity'], 0, ',', '.') ?>₫</strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="account-alert">Đơn hàng này chưa có thông tin sản phẩm.</div>
                            <?php endif; ?>

                            <?php if ($detailStatus === 'completed'): ?>
                                <div class="collapse" id="order-review-<?= (int) $selectedOrder['id'] ?>">
                                    <div class="account-review-box">
                                        <h3>Đánh giá sản phẩm trong đơn hàng</h3>
                                        <?php foreach ($orderDetails as $detail): ?>
                                            <?php
                                            $reviewRecord = (int) ($detail['product_id'] ?? 0) > 0
                                                ? (new ReviewModel())->getForUserProduct((int) $_SESSION['user']['id'], (int) $detail['product_id'])
                                                : null;
                                            ?>
                                            <div class="account-review-item">
                                                <strong><?= e($detail['product_name']) ?></strong>
                                                <?php if ($reviewRecord): ?>
                                                    <div class="review-stars" aria-label="<?= (int) $reviewRecord['rating'] ?> sao"><?= str_repeat('★', (int) $reviewRecord['rating']) ?><?= str_repeat('☆', 5 - (int) $reviewRecord['rating']) ?></div>
                                                    <p><?= e($reviewRecord['content'] ?: 'Bạn đã đánh giá sản phẩm này.') ?></p>
                                                    <?php foreach ($reviewRecord['media'] as $media): ?>
                                                        <?php $mediaUrl = BASE_ASSETS_UPLOADS . ltrim((string) $media['file_path'], '/\\'); ?>
                                                        <?php if ($media['media_type'] === 'image'): ?>
                                                            <a href="<?= e($mediaUrl) ?>" target="_blank" rel="noopener"><img class="review-media-image" src="<?= e($mediaUrl) ?>" alt="Ảnh đánh giá <?= e($detail['product_name']) ?>" loading="lazy"></a>
                                                        <?php else: ?>
                                                            <video class="review-media-video" controls preload="metadata"><source src="<?= e($mediaUrl) ?>">Trình duyệt không hỗ trợ phát video.</video>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <small><?= $reviewRecord['status'] === 'approved' ? 'Đã duyệt' : ($reviewRecord['status'] === 'rejected' ? 'Bị từ chối' : 'Đang chờ duyệt') ?></small>
                                                <?php else: ?>
                                                    <form method="post" enctype="multipart/form-data" action="<?= e(url('account?tab=orders&status=review')) ?>" class="account-review-form">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="form_type" value="review">
                                                        <input type="hidden" name="order_id" value="<?= (int) $selectedOrder['id'] ?>">
                                                        <input type="hidden" name="product_id" value="<?= (int) $detail['product_id'] ?>">
                                                        <label class="form-label" for="rating-<?= (int) $detail['product_id'] ?>">Số sao</label>
                                                        <select class="form-select mb-2" id="rating-<?= (int) $detail['product_id'] ?>" name="rating" required>
                                                            <option value="">Chọn đánh giá</option>
                                                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                                                <option value="<?= $star ?>"><?= $star ?> sao</option>
                                                            <?php endfor; ?>
                                                        </select>
                                                        <label class="form-label" for="review-<?= (int) $detail['product_id'] ?>">Nhận xét</label>
                                                        <textarea class="form-control mb-2" id="review-<?= (int) $detail['product_id'] ?>" name="content" rows="3" maxlength="3000" placeholder="Chia sẻ trải nghiệm của bạn"></textarea>
                                                        <label class="form-label" for="review-images-<?= (int) $detail['product_id'] ?>">Hình ảnh (tối đa 4 ảnh, 5MB mỗi ảnh)</label>
                                                        <input class="form-control mb-2" id="review-images-<?= (int) $detail['product_id'] ?>" name="review_images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                                                        <label class="form-label" for="review-video-<?= (int) $detail['product_id'] ?>">Video (tối đa 50MB)</label>
                                                        <input class="form-control mb-2" id="review-video-<?= (int) $detail['product_id'] ?>" name="review_video" type="file" accept="video/mp4,video/webm,video/quicktime">
                                                        <button class="btn btn-primary btn-sm mt-2" type="submit">Gửi đánh giá</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="account-order-totals">
                                <div><span>Tạm tính</span><strong><?= number_format($detailSubtotal, 0, ',', '.') ?>₫</strong></div>
                                <div><span>Giảm giá<?= !empty($selectedOrder['coupon_code']) ? ' (' . e($selectedOrder['coupon_code']) . ')' : '' ?></span><strong>-<?= number_format((float) $selectedOrder['discount_amount'], 0, ',', '.') ?>₫</strong></div>
                                <div><span>Phí vận chuyển</span><strong><?= number_format((float) $selectedOrder['shipping_fee'], 0, ',', '.') ?>₫</strong></div>
                                <div class="is-total"><span>Tổng thanh toán</span><strong><?= number_format((float) $selectedOrder['total_amount'], 0, ',', '.') ?>₫</strong></div>
                            </div>
                            <?php if ($detailStatus === 'completed'): ?>
                                <div class="collapse" id="order-return-<?= (int) $selectedOrder['id'] ?>">
                                    <div class="return-request-box">
                                        <h3>Yêu cầu hoàn hàng và hoàn tiền</h3>
                                        <?php if ($returnRequest): ?>
                                            <?php
                                            $returnStatusLabels = ['pending' => 'Chờ quản trị viên phê duyệt', 'approved' => 'Đã duyệt hoàn tiền', 'rejected' => 'Không được phê duyệt'];
                                            $returnReasons = OrderModel::returnReasons();
                                            ?>
                                            <div class="account-alert">
                                                <strong>Yêu cầu trả hàng: <?= e($returnStatusLabels[$returnRequest['status']] ?? $returnRequest['status']) ?></strong>
                                                <p class="mb-1">Lý do: <?= e($returnReasons[$returnRequest['reason']] ?? $returnRequest['reason']) ?></p>
                                                <p><?= nl2br(e($returnRequest['description'])) ?></p>
                                                <div class="return-media-list">
                                                    <?php foreach ($returnRequest['media'] as $media): ?>
                                                        <?php $mediaUrl = BASE_ASSETS_UPLOADS . ltrim((string) $media['file_path'], '/\\'); ?>
                                                        <?php if ($media['media_type'] === 'image'): ?>
                                                            <a href="<?= e($mediaUrl) ?>" target="_blank" rel="noopener"><img class="review-media-image" src="<?= e($mediaUrl) ?>" alt="Ảnh minh chứng hoàn hàng" loading="lazy"></a>
                                                        <?php else: ?>
                                                            <video class="return-evidence-video" controls preload="metadata"><source src="<?= e($mediaUrl) ?>">Trình duyệt không hỗ trợ phát video.</video>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!$returnRequest || $returnRequest['status'] === 'rejected'): ?>
                                            <p>Chỉ áp dụng cho đơn đã giao thành công. Gửi đủ 4 ảnh và 1 video để quản trị viên xem xét.</p>
                                            <form method="post" enctype="multipart/form-data" action="<?= e(url('account?tab=orders&order=' . (int) $selectedOrder['id'])) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="form_type" value="return_request">
                                                <input type="hidden" name="order_id" value="<?= (int) $selectedOrder['id'] ?>">
                                                <label class="form-label" for="return-reason-<?= (int) $selectedOrder['id'] ?>">Lý do hoàn hàng</label>
                                                <select class="form-select mb-3" id="return-reason-<?= (int) $selectedOrder['id'] ?>" name="reason" required>
                                                    <option value="">Chọn lý do</option>
                                                    <?php foreach (OrderModel::returnReasons() as $reason => $label): ?>
                                                        <option value="<?= e($reason) ?>"><?= e($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <label class="form-label" for="return-description-<?= (int) $selectedOrder['id'] ?>">Chi tiết yêu cầu</label>
                                                <textarea class="form-control mb-3" id="return-description-<?= (int) $selectedOrder['id'] ?>" name="description" rows="4" maxlength="3000" required></textarea>
                                                <label class="form-label" for="return-images-<?= (int) $selectedOrder['id'] ?>">4 hình ảnh hàng hóa/đơn hàng (JPG, PNG, WEBP, GIF; tối đa 5MB mỗi ảnh)</label>
                                                <input class="form-control mb-3" id="return-images-<?= (int) $selectedOrder['id'] ?>" name="evidence_images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
                                                <label class="form-label" for="return-video-<?= (int) $selectedOrder['id'] ?>">1 video minh chứng (MP4, WEBM, MOV; tối đa 50MB)</label>
                                                <input class="form-control mb-3" id="return-video-<?= (int) $selectedOrder['id'] ?>" name="evidence_video" type="file" accept="video/mp4,video/webm,video/quicktime" required>
                                                <button class="btn btn-primary" type="submit">Gửi yêu cầu hoàn hàng</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <a class="text-link" href="<?= e(url('account?tab=orders')) ?>">← Ẩn chi tiết</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="muted-panel">Bạn chưa có đơn hàng nào. <a href="<?= e(url('products')) ?>">Khám phá truyện ngay</a>.</div>
                <?php endif; ?>
            <?php elseif ($tab === 'wishlist'): ?>
                <div class="account-section-head">
                    <div><h2>Truyện yêu thích</h2><p>Những đầu truyện bạn đã lưu để đọc sau.</p></div>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('products')) ?>">Tìm thêm truyện</a>
                </div>
                <?php if ($wishlistProducts): ?>
                    <div class="product-grid wishlist-grid">
                        <?php foreach ($wishlistProducts as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="muted-panel">Bạn chưa lưu truyện nào. Bấm ♡ trên thẻ truyện để thêm vào danh sách yêu thích.</div>
                <?php endif; ?>
            <?php elseif ($tab === 'addresses'): ?>
                <div class="account-section-head">
                    <div><h2>Sổ địa chỉ</h2><p>Lưu địa chỉ để điền nhanh khi thanh toán.</p></div>
                </div>
                <?php if ($addresses): ?>
                    <div class="address-list">
                        <?php foreach ($addresses as $address): ?>
                            <?php
                            $addressLines = array_filter([
                                $address['address'] ?? '',
                                $address['ward'] ?? '',
                                $address['district'] ?? '',
                                $address['city'] ?? '',
                            ]);
                            ?>
                            <article class="address-item<?= !empty($address['is_default']) ? ' is-default' : '' ?>">
                                <div class="address-item-head">
                                    <strong><?= e($address['label']) ?></strong>
                                    <?php if (!empty($address['is_default'])): ?><span class="address-default-badge">Mặc định</span><?php endif; ?>
                                </div>
                                <p><?= e($address['full_name']) ?> · <?= e($address['phone']) ?></p>
                                <p class="address-lines"><?= e(implode(', ', $addressLines)) ?></p>
                                <div class="address-actions">
                                    <?php if (empty($address['is_default'])): ?>
                                        <form method="post" action="<?= e(url('account?tab=addresses')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="form_type" value="address_default">
                                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                            <button class="btn btn-sm btn-outline-primary" type="submit">Đặt mặc định</button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('account?tab=addresses&edit=' . (int) $address['id'])) ?>">Sửa</a>
                                    <form method="post" action="<?= e(url('account?tab=addresses')) ?>" data-confirm="Bạn có chắc muốn xóa địa chỉ này?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="form_type" value="address_delete">
                                        <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="muted-panel">Bạn chưa lưu địa chỉ nào.</div>
                <?php endif; ?>

                <h3 class="address-form-title"><?= $editingAddress ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' ?></h3>
                <form method="post" action="<?= e(url('account?tab=addresses')) ?>" class="account-form address-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_type" value="<?= $editingAddress ? 'address_update' : 'address_create' ?>">
                    <?php if ($editingAddress): ?><input type="hidden" name="address_id" value="<?= (int) $editingAddress['id'] ?>"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="address-label">Tên gọi</label>
                            <input class="form-control" id="address-label" name="label" maxlength="60" placeholder="Nhà, Văn phòng…" value="<?= e($editingAddress['label'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-phone">Số điện thoại *</label>
                            <input class="form-control" id="address-phone" name="phone" required value="<?= e($editingAddress['phone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="address-full-name">Họ và tên *</label>
                            <input class="form-control" id="address-full-name" name="full_name" required value="<?= e($editingAddress['full_name'] ?? $_SESSION['user']['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-city">Tỉnh / thành phố *</label>
                            <input class="form-control" id="address-city" name="city" required value="<?= e($editingAddress['city'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-district">Quận / huyện</label>
                            <input class="form-control" id="address-district" name="district" value="<?= e($editingAddress['district'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-ward">Phường / xã</label>
                            <input class="form-control" id="address-ward" name="ward" value="<?= e($editingAddress['ward'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="address-street">Số nhà, tên đường *</label>
                            <input class="form-control" id="address-street" name="address" required value="<?= e($editingAddress['address'] ?? '') ?>">
                        </div>
                    </div>
                    <label class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_default" value="1" <?= !empty($editingAddress['is_default']) ? 'checked' : '' ?>>
                        <span class="form-check-label">Đặt làm địa chỉ mặc định</span>
                    </label>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" type="submit">Lưu địa chỉ</button>
                        <?php if ($editingAddress): ?><a class="btn btn-outline-secondary" href="<?= e(url('account?tab=addresses')) ?>">Hủy sửa</a><?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <h2>Thông tin cá nhân</h2>
                <?php
                $birthDay = 0;
                $birthMonth = 0;
                $birthYear = 0;
                if (!empty($profile['birth_date']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $profile['birth_date'], $birthParts)) {
                    $birthYear = (int) $birthParts[1];
                    $birthMonth = (int) $birthParts[2];
                    $birthDay = (int) $birthParts[3];
                }
                ?>
                <form method="post" action="<?= e(url('account')) ?>" class="account-form">
                    <?= csrf_field() ?><input type="hidden" name="form_type" value="profile">
                    <fieldset data-profile-fields disabled>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="account-name">Họ và tên</label>
                            <input class="form-control" id="account-name" name="full_name" required value="<?= e($profile['full_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <div class="birth-date-fields">
                                <select class="form-select" id="account-birth-day" name="birth_day" aria-label="Ngày sinh">
                                    <option value="0">Ngày</option>
                                    <?php for ($day = 1; $day <= 31; $day++): ?>
                                        <option value="<?= $day ?>" <?= $birthDay === $day ? 'selected' : '' ?>><?= $day ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select class="form-select" id="account-birth-month" name="birth_month" aria-label="Tháng sinh">
                                    <option value="0">Tháng</option>
                                    <?php for ($month = 1; $month <= 12; $month++): ?>
                                        <option value="<?= $month ?>" <?= $birthMonth === $month ? 'selected' : '' ?>><?= $month ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select class="form-select" id="account-birth-year" name="birth_year" aria-label="Năm sinh">
                                    <option value="0">Năm</option>
                                    <?php for ($year = (int) date('Y'); $year >= 1900; $year--): ?>
                                        <option value="<?= $year ?>" <?= $birthYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="account-email">Email</label>
                            <input class="form-control" id="account-email" name="email" type="email" required value="<?= e($profile['email']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="account-phone">Số điện thoại</label>
                            <input class="form-control" id="account-phone" name="phone" type="tel" value="<?= e($profile['phone']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="account-city">Tỉnh / Thành phố</label>
                            <select class="form-select" id="account-city" name="city">
                                <option value="">-- Chọn tỉnh / thành phố --</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="account-district">Quận / Huyện</label>
                            <select class="form-select" id="account-district" name="district">
                                <option value="">-- Chọn quận / huyện --</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="account-address">Địa chỉ nhận hàng</label>
                            <input class="form-control" id="account-address" name="address" value="<?= e($profile['address']) ?>" autocomplete="street-address">
                        </div>
                    </div>
                    </fieldset>
                    <div class="profile-form-actions">
                        <button class="btn btn-outline-primary" type="button" data-edit-profile>Thay đổi thông tin cá nhân</button>
                        <button class="btn btn-primary" type="submit" data-save-profile hidden>Lưu thay đổi</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<script src="<?= e(PATH_JS) ?>vietnam-addresses.js?v=<?= e($addressJsVersion ?? '1') ?>"></script>
<script>
(function () {
    var profileForm = document.querySelector('.account-form form') || document.querySelector('.account-form');
    var editProfileButton = document.querySelector('[data-edit-profile]');
    var saveProfileButton = document.querySelector('[data-save-profile]');
    var profileFields = document.querySelector('[data-profile-fields]');
    if (editProfileButton && saveProfileButton && profileFields) {
        editProfileButton.addEventListener('click', function () {
            profileFields.disabled = false;
            editProfileButton.hidden = true;
            saveProfileButton.hidden = false;
            var firstInput = profileFields.querySelector('input:not([disabled]), select:not([disabled])');
            if (firstInput) firstInput.focus();
        });
    }

    var citySelect = document.getElementById('account-city');
    var districtSelect = document.getElementById('account-district');
    if (!citySelect || !districtSelect) return;

    var addressData = window.VIETNAM_ADDRESSES || {};
    var selectedCity = <?= json_encode((string) $profile['city'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var selectedDistrict = <?= json_encode((string) $profile['district'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    Object.keys(addressData).sort(function (a, b) {
        return a.localeCompare(b, 'vi');
    }).forEach(function (city) {
        var option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        if (city === selectedCity) option.selected = true;
        citySelect.appendChild(option);
    });

    if (selectedCity && !addressData[selectedCity]) {
        var customCity = document.createElement('option');
        customCity.value = selectedCity;
        customCity.textContent = selectedCity;
        customCity.selected = true;
        citySelect.appendChild(customCity);
    }

    function renderDistricts(preselect) {
        var districts = addressData[citySelect.value] || [];
        districtSelect.innerHTML = '<option value="">-- Chọn quận / huyện --</option>';
        districts.forEach(function (district) {
            var option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            if (district === preselect) option.selected = true;
            districtSelect.appendChild(option);
        });
        if (preselect && districts.indexOf(preselect) === -1) {
            var customDistrict = document.createElement('option');
            customDistrict.value = preselect;
            customDistrict.textContent = preselect;
            customDistrict.selected = true;
            districtSelect.appendChild(customDistrict);
        }
    }

    renderDistricts(selectedDistrict);
    citySelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option value="">-- Chọn quận / huyện --</option>';
        (addressData[citySelect.value] || []).forEach(function (district) {
            var option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            districtSelect.appendChild(option);
        });
    });
})();
</script>
