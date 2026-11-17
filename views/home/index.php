<?php
$heroSlides = array_slice(!empty($featured) ? $featured : $latest, 0, 4);
$splitHeroTitle = static function ($name) {
    $name = trim((string) $name);
    $separator = mb_strpos($name, ' - ');
    if ($separator === false) {
        return [$name, ''];
    }

    return [
        trim(mb_substr($name, 0, $separator)),
        trim(mb_substr($name, $separator + 3)),
    ];
};
$promoProduct = null;
if (!empty($discounted)) {
    $promoProduct = $discounted[0];
} elseif (!empty($featured)) {
    $promoProduct = $featured[0];
}
$lovedFallback = !empty($bestSellers) ? $bestSellers : $featured;
$lovedTop = !empty($mostLoved) ? array_slice($mostLoved, 0, 3) : array_slice($lovedFallback, 0, 3);
?>
<section class="hero-section hero-carousel" data-hero-carousel data-interval="6500" aria-roledescription="carousel" aria-label="Truyện nổi bật của tuần">
    <div class="container hero-content">
        <?php if (!empty($heroSlides)): ?>
            <div class="hero-slides">
                <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                    <?php
                    [$slideSeries, $slideVolume] = $splitHeroTitle($slide['name'] ?? '');
                    $slideActive = $slideIndex === 0;
                    $slideStock = (int) ($slide['stock_quantity'] ?? 0);
                    $slideHasDiscount = !empty($slide['original_price']) && $slide['original_price'] > $slide['sale_price'];
                    $slideDiscount = $slideHasDiscount
                        ? (int) round((1 - $slide['sale_price'] / $slide['original_price']) * 100)
                        : 0;
                    $slideSave = $slideHasDiscount ? (float) $slide['original_price'] - (float) $slide['sale_price'] : 0;
                    $slideDescription = (string) ($slide['short_description'] ?? '');
                    if (mb_strlen($slideDescription) > 150) {
                        $slideDescription = rtrim(mb_substr($slideDescription, 0, 147), " .,;:") . '…';
                    }
                    $slideUrl = url('product/detail/' . (int) $slide['id']);
                    ?>
                    <article class="hero-slide<?= $slideActive ? ' is-active' : '' ?>" data-hero-slide role="group" aria-roledescription="slide" aria-label="<?= $slideIndex + 1 ?> / <?= count($heroSlides) ?>: <?= e($slide['name']) ?>"<?= $slideActive ? '' : ' aria-hidden="true"' ?>>
                        <div class="hero-copy">
                            <span class="eyebrow"><?= e($slide['category_name'] ?? 'Truyện nổi bật') ?><?php if (!empty($slide['author_name'])): ?> · <?= e($slide['author_name']) ?><?php endif; ?></span>
                            <h1><?= e($slideSeries) ?><?php if ($slideVolume !== ''): ?><br><span><?= e($slideVolume) ?></span><?php endif; ?></h1>
                            <?php if ($slideDescription !== ''): ?><p><?= e($slideDescription) ?></p><?php endif; ?>
                            <div class="hero-price">
                                <strong><?= number_format((float) $slide['sale_price'], 0, ',', '.') ?>₫</strong>
                                <?php if ($slideHasDiscount): ?>
                                    <del><?= number_format((float) $slide['original_price'], 0, ',', '.') ?>₫</del>
                                    <span class="hero-save">Tiết kiệm <?= number_format($slideSave, 0, ',', '.') ?>₫</span>
                                <?php endif; ?>
                            </div>
                            <div class="hero-actions">
                                <a class="btn btn-primary btn-lg" href="<?= e($slideUrl) ?>">Xem chi tiết <span aria-hidden="true">→</span></a>
                                <span class="hero-stock<?= $slideStock < 1 ? ' is-out' : '' ?>"><?= $slideStock < 1 ? 'Tạm hết hàng' : 'Còn ' . $slideStock . ' cuốn' ?></span>
                            </div>
                        </div>
                        <div class="hero-art">
                            <div class="hero-spark spark-one" aria-hidden="true">✦</div><div class="hero-spark spark-two" aria-hidden="true">✧</div>
                            <div class="hero-orbit" aria-hidden="true"></div>
                            <div class="hero-book book-back" aria-hidden="true"></div>
                            <a class="hero-book-cover" href="<?= e($slideUrl) ?>" aria-label="Xem chi tiết <?= e($slide['name']) ?>">
                                <?php if (!empty($slide['cover_image'])): ?>
                                    <img src="<?= e(product_image_url($slide['cover_image'])) ?>" alt="Bìa truyện <?= e($slide['name']) ?>" <?= $slideActive ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                                <?php else: ?>
                                    <span class="hero-cover-placeholder"><span>✦</span><small>TRUYENTHUVI</small></span>
                                <?php endif; ?>
                                <?php if ($slideDiscount > 0): ?><span class="hero-cover-badge">-<?= $slideDiscount ?>%</span><?php endif; ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($heroSlides) > 1): ?>
                <div class="hero-controls">
                    <button class="hero-arrow" type="button" data-hero-prev aria-label="Truyện trước"><span aria-hidden="true">←</span></button>
                    <div class="hero-dots">
                        <?php foreach ($heroSlides as $slideIndex => $slide): ?>
                            <button class="hero-dot<?= $slideIndex === 0 ? ' is-active' : '' ?>" type="button" data-hero-dot="<?= $slideIndex ?>" aria-label="Xem truyện <?= $slideIndex + 1 ?>: <?= e($slide['name']) ?>"<?= $slideIndex === 0 ? ' aria-current="true"' : '' ?>></button>
                        <?php endforeach; ?>
                    </div>
                    <button class="hero-arrow" type="button" data-hero-next aria-label="Truyện tiếp theo"><span aria-hidden="true">→</span></button>
                    <span class="hero-count"><span data-hero-count>01 / <?= str_pad((string) count($heroSlides), 2, '0', STR_PAD_LEFT) ?></span></span>
                </div>
                <div class="hero-progress" aria-hidden="true"><span></span></div>
            <?php endif; ?>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-grid">
        <div class="trust-item"><span class="trust-icon">✦</span><span><strong>Truyện chính hãng</strong><small>Tuyển chọn kỹ từng đầu sách</small></span></div>
        <div class="trust-item"><span class="trust-icon">➜</span><span><strong>Giao hàng toàn quốc</strong><small>Đóng gói cẩn thận, nhanh chóng</small></span></div>
        <div class="trust-item"><span class="trust-icon">↺</span><span><strong>Đổi trả dễ dàng</strong><small>Hỗ trợ khi có lỗi sản phẩm</small></span></div>
        <div class="trust-item"><span class="trust-icon">♡</span><span><strong>Cộng đồng độc giả</strong><small>Gợi ý truyện theo sở thích</small></span></div>
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

<section class="container home-promo">
    <div class="promo-banner">
        <span class="promo-shine" aria-hidden="true"></span>
        <span class="promo-glow glow-a" aria-hidden="true"></span>
        <span class="promo-glow glow-b" aria-hidden="true"></span>
        <div class="promo-copy">
            <span class="eyebrow">ƯU ĐÃI CUỐI TUẦN</span>
            <h2>Đọc nhiều hơn,<br><span>chi phí ít hơn.</span></h2>
            <p>Những đầu truyện đang giảm giá sâu — cơ hội thêm cuốn mới cho kệ sách của bạn.</p>
            <a class="btn btn-light" href="<?= e(url('products')) ?>">Xem ưu đãi <span aria-hidden="true">→</span></a>
        </div>
        <div class="promo-art">
            <div class="promo-ring" aria-hidden="true"></div>
            <?php if ($promoProduct): ?>
                <a class="promo-cover" href="<?= e(url('product/detail/' . (int) $promoProduct['id'])) ?>" aria-label="Xem ưu đãi <?= e($promoProduct['name']) ?>">
                    <?php if (!empty($promoProduct['cover_image'])): ?>
                        <img src="<?= e(product_image_url($promoProduct['cover_image'])) ?>" alt="Bìa truyện <?= e($promoProduct['name']) ?>" loading="lazy" onerror="showCoverFallback(this)">
                    <?php else: ?>
                        <span class="cover-placeholder"><span>✦</span><small>TRUYENTHUVI</small></span>
                    <?php endif; ?>
                    <span class="promo-glare" aria-hidden="true"></span>
                    <span class="promo-badge">Giá tốt</span>
                </a>
            <?php else: ?>
                <div class="promo-cover promo-cover-empty" aria-hidden="true"><span>✦</span><small>TRUYENTHUVI</small><span class="promo-glare"></span></div>
            <?php endif; ?>
            <span class="promo-spark spark-a" aria-hidden="true">✦</span>
            <span class="promo-spark spark-b" aria-hidden="true">✧</span>
            <span class="promo-spark spark-c" aria-hidden="true">✦</span>
            <span class="promo-spark spark-d" aria-hidden="true">✧</span>
            <span class="promo-spark spark-e" aria-hidden="true">✦</span>
        </div>
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

<section class="container section-space">
    <div class="story-panel">
        <div class="story-art" aria-hidden="true">
            <div class="story-orbit"></div>
            <div class="story-book story-book-back"></div>
            <div class="story-book story-book-front"><span>TRUYEN<br>THUVI<br>STORY</span></div>
            <span class="story-quote">“</span>
        </div>
        <div class="story-copy">
            <span class="eyebrow">VÌ SAO CHỌN TRUYENTHUVI</span>
            <h2>Mỗi cuốn truyện là<br><span>một thế giới chờ khám phá.</span></h2>
            <p>Chúng tôi tuyển chọn những bộ truyện đáng đọc nhất, đóng gói cẩn thận và gửi đến tay bạn cùng niềm vui được đọc.</p>
            <div class="story-stats">
                <div><strong>30+</strong><small>Đầu truyện</small></div>
                <div><strong>4.9/5</strong><small>Đánh giá độc giả</small></div>
                <div><strong>100%</strong><small>Bìa chính hãng</small></div>
            </div>
            <a class="text-link" href="<?= e(url('about')) ?>">Tìm hiểu câu chuyện của chúng tôi <span aria-hidden="true">→</span></a>
        </div>
    </div>
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
    <?php if ($lovedTop): ?>
        <div class="loved-grid">
            <?php foreach ($lovedTop as $index => $product): ?>
                <?php
                $lovedUrl = url('product/detail/' . (int) $product['id']);
                $lovedRating = (float) ($product['avg_rating'] ?? 0);
                $lovedReviews = (int) ($product['review_count'] ?? 0);
                ?>
                <a class="loved-card" href="<?= e($lovedUrl) ?>">
                    <span class="loved-rank"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="loved-cover">
                        <?php if (!empty($product['cover_image'])): ?>
                            <img src="<?= e(product_image_url($product['cover_image'])) ?>" alt="Bìa truyện <?= e($product['name']) ?>" loading="lazy" onerror="showCoverFallback(this)">
                        <?php else: ?>
                            <span class="cover-placeholder"><span>✦</span><small>TRUYENTHUVI</small></span>
                        <?php endif; ?>
                    </span>
                    <span class="loved-info">
                        <strong><?= e($product['name']) ?></strong>
                        <small><?= e($product['author_name'] ?? 'Đang cập nhật') ?></small>
                        <span class="loved-meta"><?php if ($lovedReviews > 0): ?>★ <?= number_format($lovedRating, 1) ?> · <?= $lovedReviews ?> đánh giá<?php else: ?>✦ Đang cập nhật đánh giá<?php endif; ?></span>
                        <b><?= number_format((float) $product['sale_price'], 0, ',', '.') ?>₫</b>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="muted-panel">Truyện được yêu thích sẽ hiển thị khi có đánh giá được duyệt.</div>
    <?php endif; ?>
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
