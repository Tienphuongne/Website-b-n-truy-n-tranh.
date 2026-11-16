# TruyenThuVi — PHP MVC

Website bán truyện tranh dùng PHP 8+, MySQL, PDO và Bootstrap 5. Cấu trúc MVC hiện có được mở rộng trực tiếp; dữ liệu sản phẩm, người dùng, đơn hàng và danh mục được đọc/ghi qua Model, không khai báo sản phẩm mẫu trong View.

## Chạy dự án

1. Cấu hình Apache để thư mục dự án là document root và bật `mod_rewrite`/AllowOverride để dùng `.htaccess`.
2. Tạo database và các bảng bằng cách import `database/schema.sql` vào MySQL. Nếu database đã được tạo từ phiên bản trước, chạy thêm migration sổ địa chỉ / phương thức giao hàng một lần:

   ```powershell
   mysql -u root manga_house < database/migrations/2026_11_16_smart_checkout.sql
   ```
3. Cấu hình biến môi trường cho tiến trình PHP. Có thể copy `.env.example` thành `.env` (file `.env` đã được gitignore) rồi điền giá trị:

   ```text
   DB_HOST=localhost
   DB_PORT=3306
   DB_USERNAME=root
   DB_PASSWORD=
   DB_NAME=manga_house
   APP_BASE_URL=http://localhost/website-ban-truyen-tranh/

   AI_API_KEY=
   AI_MODEL=gemini-3.5-flash-lite
   ```

   Khi triển khai tại domain root, đặt `APP_BASE_URL` thành URL root có dấu `/` cuối. Không lưu thông tin mật khẩu thật trong Git. Nếu không dùng `.env`, có thể đặt cùng các biến này bằng `SetEnv` trong Apache.

4. Mở `APP_BASE_URL`, đăng ký tài khoản khách hàng và thêm sản phẩm/danh mục/tác giả từ trang quản trị sau khi cấp quyền admin cho tài khoản đó:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
   ```

5. Ảnh bìa có thể đặt trong `assets/uploads/`; lưu đường dẫn tương đối trong `products.cover_image`. Ngoài ra trang quản trị hỗ trợ tải ảnh trực tiếp (JPG/PNG/WEBP/GIF, tối đa 5MB) vào `assets/uploads/products/`, nên thư mục `assets/uploads/` cần quyền ghi của tiến trình PHP.

Nếu chưa cấu hình `DB_NAME` hoặc database chưa khả dụng, trang cửa hàng vẫn hiển thị layout và empty state. Không có dữ liệu giả được tự tạo.

## Trợ lý AI (Google Gemini)

Widget chat nổi ở góc phải trang cửa hàng, tư vấn truyện theo danh mục thật trong database và trả lời các câu hỏi về giao hàng, thanh toán, đổi trả, tra cứu đơn.

1. Lấy API key miễn phí tại <https://aistudio.google.com/app/apikey>.
2. Điền vào `.env` (hoặc biến môi trường của Apache/PHP):

   ```text
   AI_API_KEY=your-key-here
   AI_MODEL=gemini-3.5-flash-lite
   ```

3. Tải lại trang. Nếu `AI_API_KEY` để trống, widget tự ẩn và endpoint `ai/chat` trả lỗi 503. Nếu Google báo model không khả dụng với tài khoản của bạn, đổi `AI_MODEL` sang model khác đang hỗ trợ `generateContent` (ví dụ `gemini-3.8-flash` hoặc `gemini-flash-latest`).

Endpoint `ai/chat` chỉ nhận POST, kiểm tra CSRF, giới hạn 1.000 ký tự mỗi tin nhắn, 10 lượt ngữ cảnh và 20 tin nhắn / 10 phút cho mỗi phiên. API key chỉ nằm ở server, không gửi xuống trình duyệt. Nội dung trả lời do AI sinh ra nên có thể sai; widget luôn kèm lời nhắc liên hệ cửa hàng.

## Các route

Các URL thân thiện như `/products`, `/product/detail/1`, `/cart`, `/checkout`, `/account`, `/admin` được rewrite về `index.php`. Có thể truy cập qua query `?action=products` nếu môi trường không bật rewrite.

Trang admin được bảo vệ bằng role `admin`. Các form POST dùng CSRF token; mật khẩu được lưu bằng `password_hash()`. Thanh toán hiện hỗ trợ COD và chuyển khoản thủ công, chưa tích hợp cổng thanh toán.

Các chức năng cửa hàng chính:

- Tìm kiếm theo tên truyện, tác giả, nhà xuất bản hoặc mô tả; lọc theo danh mục, thể loại, tác giả, khoảng giá và tình trạng còn hàng.
- Sắp xếp theo mới nhất, bán chạy, đánh giá cao hoặc giá; phân trang và hiển thị chip bộ lọc đang áp dụng.
- Trang chi tiết liên kết tới danh mục/tác giả/thể loại, hiển thị điểm đánh giá và gợi ý truyện liên quan theo danh mục, tác giả hoặc thể loại.
- Thanh toán thông minh: sổ địa chỉ cho tài khoản đã đăng nhập, tự điền từ địa chỉ mặc định hoặc đơn gần nhất, chuẩn hóa số điện thoại, chọn tỉnh/thành cùng phường/xã, gợi ý ghi chú và lưu địa chỉ cho lần sau. Khách vãng lai được ghi nhớ thông tin trong phiên hiện tại.
- Giao hàng: giao tiêu chuẩn 30.000₫ (miễn phí từ 300.000₫) hoặc giao nhanh 45.000₫, kèm khoảng ngày giao dự kiến. Mức phí và ngưỡng miễn phí nằm trong `configs/shipping.php`.

Các chức năng quản trị chính:

- Sản phẩm: thêm/sửa/xóa, tải ảnh bìa, chọn nhiều thể loại và quản lý thư viện ảnh.
- Đơn hàng: xem chi tiết khách hàng, địa chỉ đầy đủ (phường/xã), phương thức giao hàng, sản phẩm, phí vận chuyển/giảm giá và cập nhật trạng thái.
- Liên hệ: theo dõi và đổi trạng thái tin nhắn từ trang liên hệ.
- Thống kê: doanh thu 7 ngày gần nhất trên dashboard và báo cáo theo khoảng ngày tại `/admin/reports` (tối đa 5 năm gần nhất).

## Kiểm tra

Chạy kiểm tra cú pháp PHP từ thư mục dự án:

```powershell
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object { php -l $_.FullName }
```
