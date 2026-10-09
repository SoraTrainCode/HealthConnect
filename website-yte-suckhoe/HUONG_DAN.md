# HealthConnect — bản PHP + MySQL

## 1. Đây là bản gì?

Bản chuyển từ repo SoraTrainCode/HealthConnect, commit nền `659265a`, sang xử lý PHP thật. Giữ CSS, bảng màu và Bootstrap của dự án; sắp xếp lại các biểu mẫu để dùng dữ liệu thật. PHP xử lý nghiệp vụ trên máy chủ; HTML/CSS/JavaScript vẫn cần cho giao diện, biểu đồ, trình soạn thảo và chat. Đây không phải website chỉ chứa duy nhất ngôn ngữ PHP.

Đã triển khai: bài viết/tìm kiếm/lọc/phân trang/chi tiết/lượt xem; đăng ký/đăng nhập/đăng xuất; hồ sơ/đổi mật khẩu/quên mật khẩu; BMI/lịch sử/biểu đồ theo tài khoản; admin CRUD bài viết và danh mục; khóa/mở người dùng; chatbot qua PHP gọi Ollama, lưu/đọc/tiếp tục/xóa hội thoại. Admin có soạn thảo định dạng cơ bản và upload ảnh. Ảnh đại diện hồ sơ dùng URL.

## 2. Môi trường

- PHP 8.1 trở lên. Bật các extension: pdo_mysql, mbstring, curl, dom, fileinfo.
- MySQL 8.x hoặc MariaDB 10.4 trở lên.
- Có thể dùng XAMPP trên Windows. Trong XAMPP, nút MySQL thường khởi chạy MariaDB, dùng được với script này.
- phpMyAdmin là giao diện quản lý database, không phải loại database.
- Bootstrap lấy từ CDN: cần Internet để tải CSS/JS Bootstrap.

## 3. Cài đặt trên XAMPP (Windows)

1. Giải nén, đặt thư mục `HealthConnect` vào `C:\xampp\htdocs\`.
2. Mở XAMPP Control Panel → Start **Apache** và **MySQL**.
3. Vào `http://localhost/phpmyadmin`.
4. Nếu CHƯA có database: Import `website-yte-suckhoe/database/schema.sql`. Script tạo `healthconnect_moi` và các bảng. Không import lại schema vào database đã có bảng.
5. Nếu ĐÃ import schema cũ của repo: chỉ import `database/migration_php.sql` để thêm bảng hỗ trợ đăng nhập/đặt lại mật khẩu. Không xóa database cũ.
6. Sao chép `config.example.php` thành `config.local.php` trong `website-yte-suckhoe`.
7. Sửa cấu hình database. XAMPP mặc định thường là host `127.0.0.1`, port `3306`, user `root`, password rỗng. Nếu máy bạn đã đổi mật khẩu/cổng thì nhập giá trị thực tế.
8. Đặt `setup_key` thành một chuỗi riêng ít nhất 16 ký tự. Không dùng chung mật khẩu tài khoản. `app_url` phải khớp URL thư mục của bạn.
9. Mở `http://localhost/HealthConnect/website-yte-suckhoe/setup.php`. Nhập khóa ở bước 8, họ tên/email/mật khẩu admin. Có thể tích tạo dữ liệu bài viết mẫu.
10. Đăng nhập admin và sử dụng. `setup.php` tự chặn khi đã có admin; có thể xóa file này sau khi cài xong.
11. Trang chủ: `http://localhost/HealthConnect/website-yte-suckhoe/index.php`.

Nếu Apache chạy ở cổng 8080, thay `localhost` bằng `localhost:8080`, kể cả `app_url` trong cấu hình.

KHÔNG mở file bằng nhấp đúp hoặc VS Code Live Server: hai cách đó không thực thi PHP. Các đường dẫn `.html` cũ sẽ chuyển sang `.php`.

### Chạy bằng PHP CLI (nếu không dùng Apache)

Trong thư mục `website-yte-suckhoe`:

```bash
php -S localhost:8000 tools/router.php
```

Database vẫn phải chạy riêng. Đặt `app_url` là `http://localhost:8000`. Dùng router đi kèm để chặn tải trực tiếp tài liệu/config/SQL; PHP development server không đọc `.htaccess`. Chỉ dùng development server để chạy thử tại máy.

## 4. Chatbot Ollama

Ollama phải chạy trên **máy chủ chạy PHP**. Cài Ollama rồi tải model:

```bash
ollama pull qwen2.5:3b
ollama serve
```

Nếu ứng dụng Ollama đã chạy nền thì không cần chạy thêm `ollama serve`. Trong `config.local.php`, mặc định `ollama_url` là `http://127.0.0.1:11434/api/chat`, model `qwen2.5:3b`. Nếu đặt Ollama trên máy khác, sửa URL tới máy đó.

Luồng: trình duyệt → `api/chat.php` → Ollama → PHP lưu MySQL → trả lời trình duyệt. Khách chỉ giữ ngữ cảnh trong session; người đã đăng nhập có lịch sử riêng trong `chat_sessions`/`chat_messages`. Không lưu hội thoại khi AI lỗi. Chat mới mở phiên khác, không xóa phiên cũ.

Giới hạn chủ đề được hướng dẫn bằng system prompt, không bảo đảm AI luôn tuân thủ hoàn toàn. Câu trả lời luôn được PHP gắn khuyến cáo y tế. Nội dung mẫu cần được nhóm rà soát chuyên môn trước khi sử dụng công khai.

## 5. Email quên mật khẩu

Đã có tạo token ngẫu nhiên, lưu hash token, hạn dùng 15 phút và dùng một lần. PHP gửi link qua hàm `mail()`.

**Để nhận email thật**, phải cấu hình mail transport của PHP (SMTP/sendmail, hoặc dịch vụ gửi mail phù hợp). XAMPP mặc định không tự gửi được email. Cấu hình `mail_from` là địa chỉ gửi hợp lệ và `app_url` đúng đường dẫn ứng dụng. Khi chưa cấu hình mail, các trang còn lại vẫn hoạt động; chức năng quên mật khẩu chưa thể gửi thư đến người dùng. Token không được hiển thị trên màn hình hay ghi vào log.

## 6. Cấu trúc và phân công dễ hiểu

- `includes/bootstrap.php`: kết nối PDO, session, CSRF, phân quyền, các hàm chung.
- `includes/layout.php`: header/footer, biểu mẫu, thẻ bài viết.
- Các file `.php` ở gốc: chức năng người dùng.
- `admin/*.php`: chức năng quản trị, bắt buộc role admin.
- `api/chat.php`: gọi Ollama từ PHP; `api/new-chat.php`: mở ngữ cảnh chat mới.
- `database/schema.sql`: cài mới; `migration_php.sql`: nâng schema cũ; `seed.json`: dữ liệu demo.
- `assets/js/chatbot-php.js`: giao diện chat gọi PHP; các JS mock cũ không còn được trang PHP sử dụng.
- `uploads/`: ảnh bài viết; không đưa ảnh người dùng lên Git nếu không cần.

Database hiện có 8 bảng: 6 bảng gốc và `login_attempts`, `password_resets`. Không đổi ý nghĩa các quan hệ gốc. Mật khẩu lưu bằng `password_hash`; đăng nhập dùng `password_verify`. Dữ liệu do người dùng nhập được bind bằng PDO và escape khi hiển thị. Bài rich-text chỉ giữ các thẻ định dạng cho phép.

## 7. Tự kiểm tra trước khi nộp

1. Cài mới và tạo admin, tải lại setup phải bị chặn.
2. Đăng ký 2 tài khoản A/B; email trùng phải bị từ chối.
3. A đo BMI nhiều lần và sửa hồ sơ; B không được thấy lịch sử của A.
4. Khách tính BMI được, không lưu vào database.
5. Người dùng thường mở `admin/dashboard.php` phải bị chặn.
6. Admin tạo danh mục, tạo bài nháp, sửa thành đã đăng; bài xuất hiện trên trang tin tức. Thử ảnh upload, tìm kiếm và bài liên quan.
7. Danh mục còn bài viết không xóa được. Xóa bài thử nghiệm rồi xóa danh mục đó.
8. Khóa A: A không đăng nhập được và session cũ không truy cập được phần riêng. Mở khóa để đăng nhập lại.
9. Đổi mật khẩu: mật khẩu cũ không đăng nhập được. Đặt lại qua email khi mail đã cấu hình.
10. Bật Ollama; gửi 2 câu liên tiếp, xem lịch sử, tiếp tục hội thoại rồi xóa. Đăng nhập B không thấy chat của A.
11. Tắt Ollama: widget hiện lỗi kết nối và cho phép gửi lại.

PDF do nhóm tự chuẩn bị; ảnh nộp cần có cả thanh địa chỉ theo yêu cầu thầy.
