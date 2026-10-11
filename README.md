# HealthConnect --- PHP + MySQL

HealthConnect là website thông tin y tế và theo dõi sức khỏe cá nhân,
được chuyển từ giao diện tĩnh của repository
[SoraTrainCode/HealthConnect](https://github.com/SoraTrainCode/HealthConnect)
sang xử lý nghiệp vụ bằng PHP và MySQL.

Dự án vẫn sử dụng HTML, CSS, JavaScript và Bootstrap cho giao diện, biểu
đồ, trình soạn thảo và chatbot. PHP xử lý nghiệp vụ phía máy chủ; đây
không phải website chỉ sử dụng riêng PHP.

## Chức năng chính

-   Tin tức y tế: danh sách bài viết, tìm kiếm, lọc danh mục, phân
    trang, xem chi tiết và lượt xem.
-   Tài khoản: đăng ký, đăng nhập, đăng xuất, chỉnh sửa hồ sơ, đổi mật
    khẩu và quy trình quên mật khẩu.
-   Công cụ BMI: tính BMI, lưu lịch sử và xem biểu đồ theo tài khoản.
-   Quản trị viên: quản lý bài viết và danh mục, khóa/mở tài khoản người
    dùng, soạn thảo nội dung định dạng cơ bản và tải ảnh bài viết lên.
-   Chatbot sức khỏe: PHP gọi Ollama, hỗ trợ hội thoại mới, xem/tiếp
    tục/xóa lịch sử hội thoại. Người dùng đã đăng nhập có lịch sử riêng.
-   Bảo mật cơ bản: mật khẩu được băm bằng `password_hash`, xác minh
    bằng `password_verify`, truy vấn PDO có bind tham số và kiểm tra
    quyền truy cập.

> Nội dung sức khỏe chỉ mang tính tham khảo, không thay thế chẩn đoán
> hoặc điều trị của bác sĩ. Nội dung mẫu cần được rà soát chuyên môn
> trước khi sử dụng công khai.

## Công nghệ và yêu cầu

-   PHP 8.1 trở lên.
-   MySQL 8.x hoặc MariaDB 10.4 trở lên.
-   PHP extensions: `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`.
-   Có thể chạy trên XAMPP cho Windows.
-   Bootstrap được tải từ CDN nên cần Internet để tải CSS/JavaScript.
-   Chatbot cần Ollama và model được cài trên máy chạy PHP.
-   Chức năng quên mật khẩu qua email cần cấu hình SMTP/sendmail hoặc
    dịch vụ gửi email tương ứng. XAMPP mặc định thường chưa được cấu
    hình để gửi email thật.

## Cài đặt nhanh bằng XAMPP

Hướng dẫn chi tiết nằm trong
[`website-yte-suckhoe/HUONG_DAN.md`](website-yte-suckhoe/HUONG_DAN.md).

1.  Đặt thư mục `HealthConnect` trong `C:\xampp\htdocs\`.
2.  Khởi động Apache và MySQL trong XAMPP Control Panel.
3.  Mở phpMyAdmin và import `website-yte-suckhoe/database/schema.sql`
    nếu cài mới. Không import lại schema vào database đã có bảng.
4.  Sao chép `website-yte-suckhoe/config.example.php` thành
    `website-yte-suckhoe/config.local.php` rồi cập nhật cấu hình
    database và `app_url`.
5.  Mở `setup.php` theo URL được ghi trong hướng dẫn, tạo tài khoản
    admin và dữ liệu mẫu nếu cần.
6.  Đăng nhập và kiểm tra các chức năng. Sau khi cài đặt thành công, nên
    xóa hoặc vô hiệu hóa `setup.php`.

Không mở các trang PHP bằng cách nhấp đúp vào file hoặc dùng VS Code
Live Server; PHP cần được chạy qua Apache hoặc PHP development server.

## Chatbot Ollama

Trên máy chạy PHP, cài Ollama và tải model ví dụ:

``` bash
ollama pull qwen2.5:3b
ollama serve
```

Nếu Ollama đã chạy nền thì không cần chạy `ollama serve` lần nữa. Mặc
định ứng dụng gọi API tại `http://127.0.0.1:11434/api/chat` với model
`qwen2.5:3b`. Có thể thay đổi trong `config.local.php`.

Luồng xử lý: trình duyệt → `api/chat.php` → Ollama → PHP lưu dữ liệu vào
MySQL → trả lời trình duyệt. Khi Ollama không hoạt động, chatbot sẽ
không thể tạo câu trả lời.

## Cấu trúc thư mục

-   `website-yte-suckhoe/includes/bootstrap.php`: kết nối PDO, session,
    CSRF, phân quyền và hàm dùng chung.
-   `website-yte-suckhoe/includes/layout.php`: header/footer và các
    thành phần giao diện dùng chung.
-   Các file PHP ở thư mục `website-yte-suckhoe/`: chức năng dành cho
    người dùng.
-   `website-yte-suckhoe/admin/`: chức năng quản trị.
-   `website-yte-suckhoe/api/chat.php`: gửi yêu cầu đến Ollama.
-   `website-yte-suckhoe/api/new-chat.php`: tạo hội thoại mới.
-   `website-yte-suckhoe/database/schema.sql`: tạo schema cho cài đặt
    mới.
-   `website-yte-suckhoe/database/migration_php.sql`: migration cho
    database cũ theo hướng dẫn.
-   `website-yte-suckhoe/database/seed.json`: dữ liệu mẫu.
-   `website-yte-suckhoe/assets/js/chatbot-php.js`: giao diện chatbot
    gọi PHP.
-   `website-yte-suckhoe/uploads/`: ảnh bài viết được tải lên.

## Kiểm thử trước khi nộp

Tham khảo checklist đầy đủ trong phần **Tự kiểm tra trước khi nộp** của
[`website-yte-suckhoe/HUONG_DAN.md`](website-yte-suckhoe/HUONG_DAN.md).

Khi chụp ảnh minh chứng, cần để **hiển thị cả thanh địa chỉ trình
duyệt** theo yêu cầu của giảng viên. Nhóm tự chuẩn bị file PDF báo cáo
nếu môn học yêu cầu; repository này không kèm PDF báo cáo.

## Lưu ý bảo mật

-   Không commit `config.local.php`, mật khẩu thật, khóa bí mật hoặc dữ
    liệu người dùng.
-   Chỉ commit `config.example.php` khi file này không chứa thông tin bí
    mật.
-   Không đưa ảnh người dùng hoặc dữ liệu cá nhân lên GitHub nếu không
    cần thiết.
-   Sau khi cài đặt xong, xóa hoặc vô hiệu hóa `setup.php`.
-   Chỉ sử dụng PHP development server cho mục đích phát triển tại máy,
    không dùng làm máy chủ production.
