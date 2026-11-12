<section class="container section-space">
    <div class="page-heading">
        <div><span class="eyebrow">BỘ SƯU TẬP</span><h1>Tất cả truyện tranh</h1><p>Tìm câu chuyện phù hợp với bạn.</p></div>
        <span class="result-count"><?= (int) ($result['total'] ?? 0) ?> sản phẩm</span>
    </div>
    <div class="catalog-layout">
        <aside class="filter-panel">
            <h2>Bộ lọc</h2>
            <form method="get" action="<?= e(url('products')) ?>">
                <label class="form-label" for="filter-search">Tên truyện</label>
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
                    <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                </select>
                <button class="btn btn-primary w-100" type="submit">Áp dụng bộ lọc</button>
                <a class="filter-reset" href="<?= e(url('products')) ?>">Xóa bộ lọc</a>
            </form>
        </aside>
        <div class="catalog-results">
            <?php if (!empty($result['items'])): ?>
                <div class="product-grid">
                    <?php foreach ($result['items'] as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?>
                </div>
                <?php $pages = (int) ceil($result['total'] / $result['per_page']); ?>
                <?php if ($pages > 1): ?>
                    <nav class="pagination-wrap" aria-label="Phân trang">
                        <?php for ($number = 1; $number <= $pages; $number++): ?>
                            <?php $query = array_merge($_GET, ['page' => $number]); ?>
                            <a class="page-number <?= $number === (int) $result['page'] ? 'active' : '' ?>" href="<?= e(url('products?' . http_build_query($query))) ?>"><?= $number ?></a>
                        <?php endfor; ?>
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
