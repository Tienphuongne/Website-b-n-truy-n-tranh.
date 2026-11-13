<section class="container section-space">
    <div class="page-heading">
        <div><span class="eyebrow">BỘ SƯU TẬP</span><h1><?= e($heading ?? 'Tất cả truyện tranh') ?></h1><p><?= e($subheading ?? 'Tìm câu chuyện phù hợp với bạn.') ?></p></div>
        <span class="result-count"><?= (int) ($result['total'] ?? 0) ?> sản phẩm</span>
    </div>
    <div class="catalog-layout">
        <aside class="filter-panel">
            <h2>Bộ lọc</h2>
            <form method="get" action="<?= e(url('products')) ?>">
                <label class="form-label" for="filter-search">Tên truyện, tác giả</label>
                <input class="form-control mb-3" id="filter-search" name="q" value="<?= e($filters['q']) ?>" placeholder="Tìm truyện...">
                <label class="form-label" for="filter-category">Danh mục</label>
                <select class="form-select mb-3" id="filter-category" name="category">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" <?= (string) $filters['category'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="filter-genre">Thể loại</label>
                <select class="form-select mb-3" id="filter-genre" name="genre">
                    <option value="">Tất cả thể loại</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?= (int) $genre['id'] ?>" <?= (string) $filters['genre'] === (string) $genre['id'] ? 'selected' : '' ?>><?= e($genre['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label" for="filter-author">Tác giả</label>
                <select class="form-select mb-3" id="filter-author" name="author">
                    <option value="">Tất cả tác giả</option>
                    <?php foreach ($authors as $author): ?>
                        <option value="<?= (int) $author['id'] ?>" <?= (string) $filters['author'] === (string) $author['id'] ? 'selected' : '' ?>><?= e($author['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label" for="min-price">Giá từ</label><input class="form-control" id="min-price" type="number" min="0" name="min_price" value="<?= e($filters['min_price']) ?>"></div>
                    <div class="col-6"><label class="form-label" for="max-price">Đến</label><input class="form-control" id="max-price" type="number" min="0" name="max_price" value="<?= e($filters['max_price']) ?>"></div>
                </div>
                <label class="form-check mt-3 mb-3"><input class="form-check-input" type="checkbox" name="in_stock" value="1" <?= $filters['in_stock'] ? 'checked' : '' ?>> <span class="form-check-label">Chỉ xem còn hàng</span></label>
                <label class="form-label" for="filter-sort">Sắp xếp</label>
                <select class="form-select mb-3" id="filter-sort" name="sort">
                    <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                    <option value="best_selling" <?= $filters['sort'] === 'best_selling' ? 'selected' : '' ?>>Bán chạy</option>
                    <option value="rating" <?= $filters['sort'] === 'rating' ? 'selected' : '' ?>>Đánh giá cao</option>
                    <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                </select>
                <button class="btn btn-primary w-100" type="submit">Áp dụng bộ lọc</button>
                <a class="filter-reset" href="<?= e(url('products')) ?>">Xóa bộ lọc</a>
            </form>
        </aside>
        <div class="catalog-results">
            <?php if (!empty($activeFilters)): ?>
                <div class="filter-chips">
                    <?php foreach ($activeFilters as $chip): ?>
                        <a class="filter-chip" href="<?= e(url($chip['url'])) ?>" title="Bỏ lọc này"><?= e($chip['label']) ?> <span aria-hidden="true">×</span></a>
                    <?php endforeach; ?>
                    <a class="filter-chip filter-chip-clear" href="<?= e(url('products')) ?>">Xóa tất cả</a>
                </div>
            <?php endif; ?>
            <?php if (!empty($result['items'])): ?>
                <div class="product-grid">
                    <?php foreach ($result['items'] as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?>
                </div>
                <?php $pages = (int) ceil($result['total'] / $result['per_page']); ?>
                <?php if ($pages > 1): ?>
                    <?php
                    $currentPage = (int) $result['page'];
                    $windowStart = max(1, $currentPage - 2);
                    $windowEnd = min($pages, $currentPage + 2);
                    $pageUrl = function ($number) {
                        $query = array_merge($_GET, ['page' => (int) $number]);
                        unset($query['action']);

                        return url('products?' . http_build_query($query));
                    };
                    ?>
                    <nav class="pagination-wrap" aria-label="Phân trang">
                        <a class="page-number <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= e($pageUrl(max(1, $currentPage - 1))) ?>" aria-label="Trang trước">‹</a>
                        <?php if ($windowStart > 1): ?>
                            <a class="page-number" href="<?= e($pageUrl(1)) ?>">1</a>
                            <?php if ($windowStart > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
                        <?php endif; ?>
                        <?php for ($number = $windowStart; $number <= $windowEnd; $number++): ?>
                            <a class="page-number <?= $number === $currentPage ? 'active' : '' ?>" href="<?= e($pageUrl($number)) ?>" <?= $number === $currentPage ? 'aria-current="page"' : '' ?>><?= $number ?></a>
                        <?php endfor; ?>
                        <?php if ($windowEnd < $pages): ?>
                            <?php if ($windowEnd < $pages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                            <a class="page-number" href="<?= e($pageUrl($pages)) ?>"><?= $pages ?></a>
                        <?php endif; ?>
                        <a class="page-number <?= $currentPage >= $pages ? 'disabled' : '' ?>" href="<?= e($pageUrl(min($pages, $currentPage + 1))) ?>" aria-label="Trang sau">›</a>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <?php
                $emptyTitle = $databaseError ? 'Chưa thể hiển thị sản phẩm' : 'Chưa tìm thấy truyện phù hợp';
                $emptyMessage = $databaseError ?: 'Thử thay đổi từ khóa hoặc bộ lọc. Sản phẩm mới sẽ được cập nhật thường xuyên.';
                $emptyLink = 'products';
                $emptyAction = 'Xóa bộ lọc';
                require PATH_VIEW . 'components/empty-state.php';
                ?>
            <?php endif; ?>
        </div>
    </div>
</section>
