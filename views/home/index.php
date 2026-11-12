<section class="hero-section">
    <div class="container hero-content">
        <div class="hero-copy">
            <span class="eyebrow">MỞ TRANG SÁCH, MỞ THẾ GIỚI</span>
            <h1>Mỗi câu chuyện<br><span>là một cuộc phiêu lưu.</span></h1>
            <p>Chọn cuốn truyện tiếp theo cho bộ sưu tập của bạn — từ những hành trình kỳ ảo đến câu chuyện đời thường đầy cảm xúc.</p>
            <a class="btn btn-primary btn-lg" href="<?= e(url('products')) ?>">Khám phá ngay <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="hero-spark spark-one">✦</div><div class="hero-spark spark-two">✧</div>
            <div class="hero-book book-back"></div><div class="hero-book book-front"><span>TRUYEN<br>THUVI<br>STORY</span></div>
            <div class="hero-orbit"></div>
        </div>
        <div class="hero-caption">GÓC ĐỌC CỦA BẠN</div>
    </div>
</section>

<section class="container section-space">
    <div class="section-heading">
        <div><span class="eyebrow">TÌM THEO SỞ THÍCH</span><h2>Danh mục truyện</h2></div>
        <a class="text-link" href="<?= e(url('products')) ?>">Tất cả truyện <span aria-hidden="true">→</span></a>
    </div>
    <?php if (!empty($categories)): ?>
        <div class="category-grid">
            <?php foreach ($categories as $index => $category): ?>
                <a class="category-tile category-tone-<?= $index % 4 ?>" href="<?= e(url('products?category=' . (int) $category['id'])) ?>">
                    <span class="category-symbol" aria-hidden="true"><?= ['漫', '✦', '◈', '♡'][$index % 4] ?></span>
                    <span><?= e($category['name']) ?></span><span class="category-arrow">↗</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="muted-panel">Danh mục truyện sẽ được cập nhật khi cửa hàng kết nối dữ liệu.</div>
    <?php endif; ?>
</section>

<section class="section-tint section-space">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">ĐƯỢC YÊU THÍCH</span><h2>Truyện nổi bật</h2></div>
            <a class="text-link" href="<?= e(url('products?sort=best_selling')) ?>">Xem tất cả <span aria-hidden="true">→</span></a>
        </div>
        <?php if (!empty($featured)): ?>
            <div class="product-grid">
                <?php foreach ($featured as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?>
            </div>
        <?php else: ?>
            <div class="muted-panel">Chưa có truyện nổi bật. Sản phẩm sẽ xuất hiện tại đây sau khi được thêm vào cơ sở dữ liệu.</div>
        <?php endif; ?>
    </div>
</section>

<section class="container section-space">
    <div class="section-heading">
        <div><span class="eyebrow">ĐƯỢC ĐỘC GIẢ LỰA CHỌN</span><h2>Truyện bán chạy</h2></div>
        <a class="text-link" href="<?= e(url('products?sort=best_selling')) ?>">Xem tất cả <span aria-hidden="true">→</span></a>
    </div>
    <?php if (!empty($bestSellers)): ?>
        <div class="product-grid"><?php foreach ($bestSellers as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?></div>
    <?php else: ?><div class="muted-panel">Danh sách bán chạy sẽ hiển thị sau khi có dữ liệu đơn hàng.</div><?php endif; ?>
</section>

<section class="section-tint section-space">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">ƯU ĐÃI DÀNH CHO BẠN</span><h2>Truyện đang giảm giá</h2></div>
            <a class="text-link" href="<?= e(url('products')) ?>">Khám phá thêm <span aria-hidden="true">→</span></a>
        </div>
        <?php if (!empty($discounted)): ?>
            <div class="product-grid"><?php foreach ($discounted as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?></div>
        <?php else: ?><div class="muted-panel">Ưu đãi mới sẽ được cập nhật tại đây.</div><?php endif; ?>
    </div>
</section>

<section class="container section-space">
    <div class="section-heading">
        <div><span class="eyebrow">CẢM NHẬN TỪ ĐỘC GIẢ</span><h2>Truyện được yêu thích</h2></div>
        <a class="text-link" href="<?= e(url('products')) ?>">Xem bộ sưu tập <span aria-hidden="true">→</span></a>
    </div>
    <?php if (!empty($mostLoved)): ?>
        <div class="product-grid"><?php foreach ($mostLoved as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?></div>
    <?php else: ?><div class="muted-panel">Truyện được yêu thích sẽ hiển thị khi có đánh giá được duyệt.</div><?php endif; ?>
</section>

<section class="container section-space">
    <div class="section-heading">
        <div><span class="eyebrow">VỪA LÊN KỆ</span><h2>Truyện mới cập nhật</h2></div>
        <a class="text-link" href="<?= e(url('products?sort=newest')) ?>">Khám phá thêm <span aria-hidden="true">→</span></a>
    </div>
    <?php if (!empty($latest)): ?>
        <div class="product-grid">
            <?php foreach ($latest as $product): require PATH_VIEW . 'components/product-card.php'; endforeach; ?>
        </div>
    <?php else: ?>
        <div class="muted-panel">Hiện chưa có sản phẩm. Hãy quay lại sau nhé!</div>
    <?php endif; ?>
</section>

<section class="container newsletter-panel section-space">
    <div><span class="eyebrow">TRUYENTHUVI</span><h2>Chuyện hay đang chờ bạn.</h2><p>Khám phá bộ sưu tập truyện tranh được tuyển chọn cho mọi tâm trạng.</p></div>
    <a class="btn btn-light" href="<?= e(url('products')) ?>">Bắt đầu khám phá <span aria-hidden="true">→</span></a>
</section>
