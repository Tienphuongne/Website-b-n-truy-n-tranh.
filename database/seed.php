<?php

/**
 * Nạp dữ liệu mẫu từ data/products.json vào MySQL.
 *
 * Cách chạy (từ thư mục gốc dự án):
 *   C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe database\seed.php
 *
 * Script chỉ xoá và nạp lại các bảng danh mục sản phẩm
 * (categories, genres, authors, publishers, products, product_genres).
 * Dữ liệu người dùng, đơn hàng, đánh giá được giữ nguyên.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy được bằng dòng lệnh.');
}

// Cho phép chạy trực tiếp mà không cần đặt biến môi trường.
$defaults = [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_USERNAME' => 'root',
    'DB_PASSWORD' => '',
    'DB_NAME' => 'manga_house',
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false || getenv($key) === '') {
        putenv($key . '=' . $value);
    }
}

require dirname(__DIR__) . '/configs/env.php';

$dataFile = PATH_ROOT . 'data/products.json';
if (!is_readable($dataFile)) {
    fwrite(STDERR, "Không tìm thấy file dữ liệu: $dataFile\n");
    exit(1);
}

$data = json_decode(file_get_contents($dataFile), true);
if (!is_array($data)) {
    fwrite(STDERR, "File JSON không hợp lệ: " . json_last_error_msg() . "\n");
    exit(1);
}

if (DB_NAME === '') {
    fwrite(STDERR, "Chưa cấu hình DB_NAME.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
$pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, DB_OPTIONS);
$pdo->exec('SET NAMES utf8mb4');

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['product_genres', 'product_images', 'products', 'categories', 'genres', 'authors', 'publishers'] as $table) {
    $pdo->exec("TRUNCATE TABLE $table");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$pdo->beginTransaction();

try {
    $insertCategory = $pdo->prepare(
        'INSERT INTO categories (id, name, slug, description, is_active) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($data['categories'] ?? [] as $row) {
        $insertCategory->execute([
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['description'] ?? null,
            (int) ($row['is_active'] ?? 1),
        ]);
    }

    $insertGenre = $pdo->prepare('INSERT INTO genres (id, name, slug) VALUES (?, ?, ?)');
    foreach ($data['genres'] ?? [] as $row) {
        $insertGenre->execute([(int) $row['id'], $row['name'], $row['slug']]);
    }

    $insertAuthor = $pdo->prepare('INSERT INTO authors (id, name, biography) VALUES (?, ?, ?)');
    foreach ($data['authors'] ?? [] as $row) {
        $insertAuthor->execute([(int) $row['id'], $row['name'], $row['biography'] ?? null]);
    }

    $insertPublisher = $pdo->prepare('INSERT INTO publishers (id, name) VALUES (?, ?)');
    foreach ($data['publishers'] ?? [] as $row) {
        $insertPublisher->execute([(int) $row['id'], $row['name']]);
    }

    $insertProduct = $pdo->prepare(
        'INSERT INTO products
            (id, category_id, author_id, publisher_id, name, slug, short_description, description,
             cover_image, sale_price, original_price, stock_quantity, sold_quantity, is_featured, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $insertProductGenre = $pdo->prepare(
        'INSERT INTO product_genres (product_id, genre_id) VALUES (?, ?)'
    );

    foreach ($data['products'] ?? [] as $row) {
        $insertProduct->execute([
            (int) $row['id'],
            isset($row['category_id']) ? (int) $row['category_id'] : null,
            isset($row['author_id']) ? (int) $row['author_id'] : null,
            isset($row['publisher_id']) ? (int) $row['publisher_id'] : null,
            $row['name'],
            $row['slug'],
            $row['short_description'] ?? null,
            $row['description'] ?? null,
            $row['cover_image'] ?? null,
            (float) ($row['sale_price'] ?? 0),
            isset($row['original_price']) ? (float) $row['original_price'] : null,
            (int) ($row['stock_quantity'] ?? 0),
            (int) ($row['sold_quantity'] ?? 0),
            (int) ($row['is_featured'] ?? 0),
            $row['status'] ?? 'draft',
            $row['created_at'] ?? date('Y-m-d H:i:s'),
        ]);

        foreach ($row['genre_ids'] ?? [] as $genreId) {
            $insertProductGenre->execute([(int) $row['id'], (int) $genreId]);
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    fwrite(STDERR, 'Nạp dữ liệu thất bại: ' . $exception->getMessage() . "\n");
    exit(1);
}

$counts = [];
foreach (['categories', 'genres', 'authors', 'publishers', 'products', 'product_genres'] as $table) {
    $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}

echo "Đã nạp dữ liệu mẫu vào database " . DB_NAME . ":\n";
foreach ($counts as $table => $count) {
    echo "  - $table: $count\n";
}
