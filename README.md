# Manga House — PHP MVC

Website bán truyện tranh dùng PHP 8+, MySQL, PDO và Bootstrap 5. Cấu trúc MVC hiện có được mở rộng trực tiếp; dữ liệu sản phẩm, người dùng, đơn hàng và danh mục được đọc/ghi qua Model, không khai báo sản phẩm mẫu trong View.

## Chạy dự án

1. Cấu hình Apache để thư mục dự án là document root và bật `mod_rewrite`/AllowOverride để dùng `.htaccess`.
2. Tạo database và các bảng bằng cách import `database/schema.sql` vào MySQL.
3. Cấu hình biến môi trường cho tiến trình PHP:

   ```text
   DB_HOST=localhost
   DB_PORT=3306
   DB_USERNAME=root
   DB_PASSWORD=
   DB_NAME=manga_house
   APP_BASE_URL=http://localhost/website-ban-truyen-tranh/
   ```

   Khi triển khai tại domain root, đặt `APP_BASE_URL` thành URL root có dấu `/` cuối. Không lưu thông tin mật khẩu thật trong Git.

4. Mở `APP_BASE_URL`, đăng ký tài khoản khách hàng và thêm sản phẩm/danh mục/tác giả từ trang quản trị sau khi cấp quyền admin cho tài khoản đó:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
   ```

5. Ảnh bìa có thể đặt trong `assets/uploads/`; lưu đường dẫn tương đối trong `products.cover_image`.

Nếu chưa cấu hình `DB_NAME` hoặc database chưa khả dụng, trang cửa hàng vẫn hiển thị layout và empty state. Không có dữ liệu giả được tự tạo.

## Các route

Các URL thân thiện như `/products`, `/product/detail/1`, `/cart`, `/checkout`, `/account`, `/admin` được rewrite về `index.php`. Có thể truy cập qua query `?action=products` nếu môi trường không bật rewrite.

Trang admin được bảo vệ bằng role `admin`. Các form POST dùng CSRF token; mật khẩu được lưu bằng `password_hash()`. Thanh toán hiện hỗ trợ COD và chuyển khoản thủ công, chưa tích hợp cổng thanh toán.

## Kiểm tra

Chạy kiểm tra cú pháp PHP từ thư mục dự án:

```powershell
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object { php -l $_.FullName }
```
