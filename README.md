# TruyenThuVi — PHP MVC

Website bán truyện tranh dùng PHP 8+, MySQL, PDO và Bootstrap 5. Cấu trúc MVC hiện có được mở rộng trực tiếp; dữ liệu sản phẩm, người dùng, đơn hàng và danh mục được đọc/ghi qua Model, không khai báo sản phẩm mẫu trong View.

## Chạy dự án

1. Cấu hình Apache để thư mục dự án là document root và bật `mod_rewrite`/AllowOverride để dùng `.htaccess`.
2. Tạo database và các bảng bằng cách import `database/schema.sql` vào MySQL. Với website đã cài đặt, chạy thêm `database/migrations/20261121_create_return_requests.sql`, `database/migrations/20261128_create_review_media.sql` và `database/migrations/20261128_allow_return_request_cancellation.sql`.
3. Cấu hình biến môi trường cho tiến trình PHP:

   ```text
   DB_HOST=localhost
   DB_PORT=3306
   DB_USERNAME=root
   DB_PASSWORD=
   DB_NAME=manga_house
   APP_BASE_URL=http://localhost/website-ban-truyen-tranh/
   AI_API_KEY=
   AI_MODEL=gemini-3.5-flash-lite
   AI_MODEL_FALLBACKS=gemini-3.1-flash-lite,gemini-flash-latest
   ```

   Có thể khai báo trực tiếp trong file `.env` (xem `.env.example`). Các biến có sẵn trong môi trường hệ thống/Apache được ưu tiên hơn `.env`. Không đẩy API key thật lên Git.

   Khi triển khai tại domain root, đặt `APP_BASE_URL` thành URL root có dấu `/` cuối. Không lưu thông tin mật khẩu thật trong Git.

4. Mở `APP_BASE_URL`, đăng ký tài khoản khách hàng và thêm sản phẩm/danh mục/tác giả từ trang quản trị sau khi cấp quyền admin cho tài khoản đó:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'admin@example.com';
   ```

5. Ảnh bìa có thể đặt trong `assets/uploads/`; lưu đường dẫn tương đối trong `products.cover_image`. Ngoài ra trang quản trị hỗ trợ tải ảnh trực tiếp (JPG/PNG/WEBP/GIF, tối đa 5MB) vào `assets/uploads/products/`, nên thư mục `assets/uploads/` cần quyền ghi của tiến trình PHP. Để nhận video trả hàng tối đa 50MB cùng 4 ảnh, cấu hình PHP `upload_max_filesize=50M`, `post_max_size=75M` và `max_file_uploads=5` trở lên.

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
- Trả hàng/hoàn tiền: khách có thể gửi yêu cầu từ đơn đã hoàn tất, kèm lý do, mô tả bắt buộc, đúng 4 ảnh và 1 video. Đơn có yêu cầu hiển thị trong trạng thái **Trả hàng**; khách có thể hủy khi yêu cầu vẫn đang chờ duyệt. Trạng thái duyệt/từ chối được thông báo tại chi tiết đơn. Quản trị viên xem minh chứng và xử lý tại mục **Yêu cầu trả hàng**; khi duyệt, hệ thống ghi nhận trạng thái thanh toán là đã hoàn tiền. Thanh toán thực tế qua ngân hàng/cổng thanh toán vẫn cần được xử lý thủ công.
- Đánh giá đơn hàng: từ chi tiết đơn đã hoàn tất, khách hàng có thể đánh giá từng sản phẩm bằng số sao, nhận xét, tối đa 4 ảnh và 1 video. Đánh giá cần được quản trị viên duyệt trước khi hiển thị công khai.
- Liên hệ: theo dõi và đổi trạng thái tin nhắn từ trang liên hệ.
- Thống kê: doanh thu 7 ngày gần nhất trên dashboard và báo cáo theo khoảng ngày tại `/admin/reports` (tối đa 5 năm gần nhất).

## Trợ lý hỗ trợ (chatbot AI)

Widget chat xuất hiện ở góc phải màn hình trên mọi trang. Trợ lý dùng Gemini (`AI_MODEL`) để trả lời bằng tiếng Việt, với dữ liệu thật lấy từ cơ sở dữ liệu:

- Tư vấn sản phẩm: tìm truyện theo tên/tác giả, giá, tồn kho, gợi ý bán chạy/mới/giảm giá và đính kèm link chi tiết.
- Tra cứu đơn hàng: khách đã đăng nhập xem được các đơn của mình; khách vãng lai cần mã đơn **và** email hoặc số điện thoại trùng khớp. Bot không bao giờ được bịa trạng thái đơn.
- Câu hỏi chung: thanh toán COD/chuyển khoản, phí vận chuyển mặc định 30.000₫, đổi trả, thông tin liên hệ.
- Chuyển nhân viên: nút "Gặp nhân viên" gửi kèm lịch sử hội thoại vào mục Liên hệ trong trang quản trị.

API key chỉ nằm ở server (`AI_API_KEY`), không gửi xuống trình duyệt. Nếu model chính báo quá tải (429/5xx), client tự thử các model trong `AI_MODEL_FALLBACKS`. Các endpoint JSON `chatbot/message` và `chatbot/escalate` dùng CSRF qua header `X-CSRF-Token`, có giới hạn tần suất và lịch sử tối đa 10 lượt trong session. Nếu chưa có key, key sai hoặc Gemini lỗi, bot tự chuyển sang chế độ dự phòng theo kịch bản mà không làm gián đoạn cửa hàng.

## Kiểm tra

Chạy kiểm tra cú pháp PHP từ thư mục dự án:

```powershell
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object { php -l $_.FullName }
```
