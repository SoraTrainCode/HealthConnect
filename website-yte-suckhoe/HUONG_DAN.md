# Hướng dẫn cài đặt và kiểm thử HealthConnect

Tài liệu này hướng dẫn chạy HealthConnect bằng XAMPP trên Windows. Dự án
sử dụng PHP và MySQL/MariaDB để xử lý dữ liệu; không chạy trực tiếp bằng
cách mở file PHP hoặc dùng VS Code Live Server.

## 1. Yêu cầu môi trường

-   PHP 8.1 trở lên.
-   MySQL 8.x hoặc MariaDB 10.4 trở lên.
-   PHP extensions cần bật: `pdo_mysql`, `mbstring`, `curl`, `dom`,
    `fileinfo`.
-   XAMPP trên Windows có thể đáp ứng các yêu cầu trên. MySQL trong
    XAMPP thường là MariaDB và có thể dùng với script của dự án.
-   Internet để tải Bootstrap từ CDN.
-   Ollama nếu muốn sử dụng chatbot.
-   Cấu hình gửi email nếu muốn sử dụng chức năng quên mật khẩu qua
    email.

phpMyAdmin là giao diện quản lý database, không phải một hệ quản trị cơ
sở dữ liệu riêng.

## 2. Đặt thư mục dự án

1.  Giải nén hoặc clone dự án.

2.  Đặt thư mục `HealthConnect` tại:

    `C:\xampp\htdocs\HealthConnect`

3.  Đảm bảo đường dẫn dự án có file:

    `C:\xampp\htdocs\HealthConnect\website-yte-suckhoe\setup.php`

Nếu thư mục thực tế khác, hãy điều chỉnh URL và `app_url` theo đúng vị
trí.

## 3. Khởi động XAMPP

1.  Mở XAMPP Control Panel.
2.  Nhấn **Start** ở Apache.
3.  Nhấn **Start** ở MySQL.
4.  Mở phpMyAdmin tại `http://localhost/phpmyadmin`.

Nếu Apache dùng cổng 8080, hãy dùng `http://localhost:8080` thay cho
`http://localhost` trong các URL của ứng dụng.

## 4. Tạo hoặc nâng cấp database

### Cài đặt mới

Chỉ khi chưa có database/schema của dự án, mở phpMyAdmin → **Import** và
chọn:

`website-yte-suckhoe/database/schema.sql`

Script tạo database `healthconnect_moi` cùng các bảng cần thiết.

**Không import lại schema vào database đã có bảng.** Việc import lại có
thể gây lỗi hoặc ảnh hưởng dữ liệu hiện có.

### Đã có database từ phiên bản cũ

Nếu đã import schema cũ của repository, chỉ import:

`website-yte-suckhoe/database/migration_php.sql`

Chỉ thực hiện migration khi phù hợp với trạng thái database hiện tại.
Không xóa database cũ để thử sửa lỗi; hãy sao lưu trước khi thay đổi
schema.

## 5. Cấu hình ứng dụng

Trong thư mục `website-yte-suckhoe`, sao chép:

`config.example.php`

thành:

`config.local.php`

Mở `config.local.php` và kiểm tra các thiết lập database. Với XAMPP mặc
định, các giá trị thường là:

-   Host: `127.0.0.1`
-   Port: `3306`
-   Database: `healthconnect_moi`
-   Username: `root`
-   Password: để trống, nếu bạn chưa tự đặt mật khẩu MySQL

Nếu máy đã đổi username, password hoặc port, hãy nhập giá trị thực tế
của máy đó.

### Khóa cài đặt và URL

-   Đặt `setup_key` thành chuỗi riêng dài ít nhất 16 ký tự. Không dùng
    mật khẩu đăng nhập làm khóa này.
-   Đặt `app_url` đúng với URL thư mục ứng dụng.
-   Nếu Apache chạy cổng 8080, ví dụ `app_url` là
    `http://localhost:8080/HealthConnect/website-yte-suckhoe`.
-   Không đưa `config.local.php` lên GitHub. Chỉ commit file cấu hình
    mẫu khi không có mật khẩu hoặc khóa bí mật thật.

## 6. Tạo tài khoản quản trị

Mở URL cài đặt theo cổng Apache của bạn:

-   Apache cổng 80:
    `http://localhost/HealthConnect/website-yte-suckhoe/setup.php`
-   Apache cổng 8080:
    `http://localhost:8080/HealthConnect/website-yte-suckhoe/setup.php`

Nhập khóa `setup_key`, họ tên, email và mật khẩu quản trị. Có thể chọn
tạo dữ liệu bài viết và danh mục mẫu để thử giao diện.

Khi hoàn tất, đăng nhập tại:

-   Cổng 80:
    `http://localhost/HealthConnect/website-yte-suckhoe/dang-nhap.php`
-   Cổng 8080:
    `http://localhost:8080/HealthConnect/website-yte-suckhoe/dang-nhap.php`

Trang chủ:

-   Cổng 80:
    `http://localhost/HealthConnect/website-yte-suckhoe/index.php`
-   Cổng 8080:
    `http://localhost:8080/HealthConnect/website-yte-suckhoe/index.php`

Sau khi tạo admin thành công, `setup.php` sẽ tự khóa khi phát hiện đã có
admin. Để tăng an toàn, hãy xóa hoặc vô hiệu hóa file `setup.php` sau
khi cài đặt xong.

## 7. Chạy bằng PHP CLI (tùy chọn)

Nếu không dùng Apache, mở terminal tại thư mục `website-yte-suckhoe` và
chạy:

``` bash
php -S localhost:8000 tools/router.php
```

Database vẫn phải chạy riêng. Khi dùng cách này, đặt `app_url` thành
`http://localhost:8000`.

Router đi kèm giúp chặn truy cập trực tiếp một số tài liệu/config/SQL.
PHP development server chỉ dành cho phát triển và kiểm thử tại máy,
không dùng làm máy chủ production.

## 8. Cấu hình chatbot Ollama

Ollama phải chạy trên máy chủ đang chạy PHP.

1.  Cài Ollama.

2.  Tải model ví dụ:

    ``` bash
    ollama pull qwen2.5:3b
    ```

3.  Nếu Ollama chưa tự chạy nền, mở terminal và chạy:

    ``` bash
    ollama serve
    ```

4.  Kiểm tra cấu hình trong `config.local.php`:

    -   URL API mặc định: `http://127.0.0.1:11434/api/chat`
    -   Model mặc định: `qwen2.5:3b`

Nếu Ollama chạy trên máy khác, hãy thay URL bằng địa chỉ của máy đó và
bảo đảm PHP có thể kết nối tới máy chủ Ollama.

Luồng hoạt động: trình duyệt → `api/chat.php` → Ollama → PHP lưu vào
MySQL → trả lời trình duyệt.

Khách chỉ giữ ngữ cảnh trong session. Người đăng nhập có lịch sử hội
thoại riêng trong `chat_sessions` và `chat_messages`. Tạo chat mới mở
một phiên mới, không tự xóa phiên cũ. Khi AI lỗi, ứng dụng không lưu hội
thoại lỗi như một câu trả lời thành công.

Giới hạn chủ đề được hướng dẫn bằng system prompt nhưng không bảo đảm AI
luôn tuân thủ hoàn toàn. Nội dung chatbot liên quan sức khỏe chỉ mang
tính tham khảo và cần được rà soát chuyên môn trước khi công khai.

## 9. Cấu hình email quên mật khẩu

Ứng dụng có quy trình tạo token ngẫu nhiên, lưu hash token, thời hạn 15
phút và sử dụng một lần. PHP gửi liên kết qua `mail()`.

XAMPP mặc định thường chưa được cấu hình để gửi email thật. Muốn dùng
chức năng này, cần cấu hình mail transport của PHP (SMTP/sendmail hoặc
dịch vụ phù hợp), đặt `mail_from` thành địa chỉ gửi hợp lệ và bảo đảm
`app_url` chính xác.

Khi chưa cấu hình email, các chức năng khác vẫn có thể hoạt động nhưng
người dùng sẽ không nhận được thư đặt lại mật khẩu.

## 10. Chức năng chính

-   Tin tức y tế: xem danh sách, tìm kiếm, lọc danh mục, phân trang, xem
    chi tiết và lượt xem.
-   Tài khoản: đăng ký, đăng nhập, đăng xuất, hồ sơ, đổi mật khẩu và
    quên mật khẩu.
-   BMI: tính chỉ số BMI, lưu lịch sử theo tài khoản và xem biểu đồ.
-   Quản trị: CRUD bài viết/danh mục, khóa hoặc mở khóa người dùng, soạn
    thảo nội dung và tải ảnh bài viết.
-   Chatbot: gửi câu hỏi qua PHP tới Ollama, tạo hội thoại mới, xem/tiếp
    tục/xóa lịch sử.

## 11. Checklist tự kiểm tra trước khi nộp

1.  Cài mới, tạo admin; tải lại `setup.php` phải bị chặn sau khi admin
    đã tồn tại.
2.  Đăng ký hai tài khoản A và B; email trùng phải bị từ chối.
3.  A đo BMI nhiều lần và sửa hồ sơ; B không được thấy lịch sử BMI của
    A.
4.  Khách có thể tính BMI nhưng kết quả không được lưu vào database theo
    tài khoản.
5.  Người dùng thường mở `admin/dashboard.php` phải bị chặn.
6.  Admin tạo danh mục, tạo bài nháp rồi chuyển thành đã đăng; bài viết
    xuất hiện trên trang tin tức.
7.  Kiểm tra sửa bài viết, tải ảnh, tìm kiếm và bài viết liên quan.
8.  Danh mục đang có bài viết không được xóa. Xóa bài thử nghiệm rồi mới
    kiểm tra xóa danh mục.
9.  Khóa tài khoản A: A không thể đăng nhập và session cũ không thể tiếp
    tục truy cập khu vực riêng. Mở khóa để kiểm tra đăng nhập lại.
10. Đổi mật khẩu: mật khẩu cũ không đăng nhập được. Kiểm tra đặt lại mật
    khẩu qua email khi mail đã cấu hình.
11. Bật Ollama; gửi hai câu liên tiếp, xem lịch sử, tiếp tục hội thoại
    rồi xóa.
12. Đăng nhập B và xác nhận B không thấy hội thoại riêng của A.
13. Tắt Ollama; chatbot phải hiển thị lỗi kết nối và cho phép thử gửi
    lại.

## 12. Ảnh minh chứng và báo cáo

-   Khi chụp ảnh minh chứng, phải để **hiển thị cả thanh địa chỉ trình
    duyệt** theo yêu cầu của giảng viên.
-   Chụp các màn hình thể hiện chức năng thực tế, không chỉ chụp trang
    chủ.
-   Nhóm tự chuẩn bị file PDF báo cáo nếu giảng viên yêu cầu. Repository
    này không kèm PDF báo cáo.
-   Không chụp hoặc công khai mật khẩu, khóa cài đặt, thông tin cá nhân
    hay dữ liệu người dùng thật.

## 13. Xử lý lỗi thường gặp

### Trang PHP không chạy

Không mở file PHP bằng cách nhấp đúp hoặc dùng VS Code Live Server. Hãy
chạy Apache trong XAMPP và truy cập qua `http://localhost/...` hoặc
`http://localhost:8080/...`.

### Không kết nối được database

Kiểm tra MySQL đang chạy, tên database, host, port, username và password
trong `config.local.php`. Kiểm tra database đã được tạo/import đúng cách
chưa.

### Lỗi khi import schema

Nếu database đã có bảng, không import lại `schema.sql`. Xác định đây là
cài mới hay nâng cấp từ schema cũ; sao lưu database trước khi chạy
migration.

### Chatbot không phản hồi

Kiểm tra Ollama đang chạy trên máy chủ PHP, model đã được tải và
`ollama_url`/model trong cấu hình đúng chưa.

### Email quên mật khẩu không đến

Kiểm tra cấu hình SMTP/sendmail của PHP, `mail_from`, `app_url` và thư
mục spam. XAMPP mặc định thường chưa gửi được email thật.

## 14. Bảo mật sau khi cài đặt

-   Xóa hoặc vô hiệu hóa `setup.php` sau khi tạo admin.
-   Không commit `config.local.php`, `.env`, mật khẩu hoặc khóa bí mật.
-   Không đưa ảnh người dùng hoặc dữ liệu cá nhân lên GitHub nếu không
    cần thiết.  
-   Chỉ sử dụng PHP development server trong môi trường phát triển.
-   Kiểm tra nội dung y tế mẫu trước khi sử dụng công khai.
