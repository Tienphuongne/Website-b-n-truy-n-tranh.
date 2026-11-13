# TruyenThuVi — PHP MVC

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

5. Ảnh bìa có thể đặt trong `assets/uploads/`; lưu đường dẫn tương đối trong `products.cover_image`. Ngoài ra trang quản trị hỗ trợ tải ảnh trực tiếp (JPG/PNG/WEBP/GIF, tối đa 5MB) vào `assets/uploads/products/`, nên thư mục `assets/uploads/` cần quyền ghi của tiến trình PHP.

Nếu chưa cấu hình `DB_NAME` hoặc database chưa khả dụng, trang cửa hàng vẫn hiển thị layout và empty state. Không có dữ liệu giả được tự tạo.

## Các route

Các URL thân thiện như `/products`, `/product/detail/1`, `/cart`, `/checkout`, `/account`, `/admin` được rewrite về `index.php`. Có thể truy cập qua query `?action=products` nếu môi trường không bật rewrite.

Trang admin được bảo vệ bằng role `admin`. Các form POST dùng CSRF token; mật khẩu được lưu bằng `password_hash()`. Thanh toán hiện hỗ trợ COD và chuyển khoản thủ công, chưa tích hợp cổng thanh toán.

Các chức năng cửa hàng chính:

- Tìm kiếm theo tên truyện, tác giả, nhà xuất bản hoặc mô tả; lọc theo danh mục, thể loại, tác giả, khoảng giá và tình trạng còn hàng.
- Sắp xếp theo mới nhất, bán chạy, đánh giá cao hoặc giá; phân trang và hiển thị chip bộ lọc đang áp dụng.
- Trang chi tiết liên kết tới danh mục/tác giả/thể loại, hiển thị điểm đánh giá và gợi ý truyện liên quan theo danh mục, tác giả hoặc thể loại.

Các chức năng quản trị chính:

- Sản phẩm: thêm/sửa/xóa, tải ảnh bìa, chọn nhiều thể loại và quản lý thư viện ảnh.
- Đơn hàng: xem chi tiết khách hàng, sản phẩm, phí vận chuyển/giảm giá và cập nhật trạng thái.
- Liên hệ: theo dõi và đổi trạng thái tin nhắn từ trang liên hệ.
- Thống kê: doanh thu 7 ngày gần nhất trên dashboard và báo cáo theo khoảng ngày tại `/admin/reports` (tối đa 5 năm gần nhất).

## Kiểm tra

Chạy kiểm tra cú pháp PHP từ thư mục dự án:

```powershell
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object { php -l $_.FullName }
```
