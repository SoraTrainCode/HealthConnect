# Cài đặt và cấu hình HealthConnect

## Chạy bằng Apache/XAMPP

1. Copy thư mục ứng dụng vào `C:\xampp\htdocs\HealthConnect` để `index.php` nằm trực tiếp bên trong. Nếu XAMPP ở `C:\xampppp`, đổi đường dẫn tương ứng.
2. Bật Apache. Dùng MySQL/MariaDB đang có hoặc bật MySQL của XAMPP; không chạy hai dịch vụ trên cùng cổng 3306.
3. Với DB mới, mở `database/schema.sql` trong MySQL Workbench hoặc import qua phpMyAdmin, thực thi một lần. Script tạo `healthconnect_moi` với 16 bảng. DB có dữ liệu: dùng mục [nâng cấp](#nâng-cấp-và-sao-lưu).
4. Copy toàn bộ `config.example.php` thành `config.local.php` **nếu chưa có**. Sửa bản local, không ghi đè cấu hình đã điền.

| Mục cấu hình | Giá trị cần điền |
|---|---|
| `db_host`, `db_port` | Thường là `127.0.0.1`, `3306`; khớp MySQL thực tế |
| `db_name` | `healthconnect_moi` |
| `db_user`, `db_pass` | Tài khoản/mật khẩu MySQL, khác tài khoản admin website |
| `setup_key` | Tự đặt khóa riêng ít nhất 16 ký tự để tạo admin lần đầu |
| `app_url` | `http://localhost/HealthConnect`, thêm cổng nếu Apache dùng cổng khác |

Nếu đặt cả repo trong `htdocs/HealthConnect`, URL ứng dụng là `http://localhost/HealthConnect/website-yte-suckhoe`; `app_url` phải khớp. Sửa cấu hình ở thư mục Apache đang phục vụ, cùng chỗ với `index.php`.

Chạy từ PowerShell để kiểm tra, thay đường dẫn nếu cần:

```powershell
& C:\xampp\php\php.exe C:\xampp\htdocs\HealthConnect\tools\doctor.php
```

Yêu cầu PHP 8.1+, MySQL 8/MariaDB 10.4+, extensions `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`, `SimpleXML`, `openssl`. Sửa `php.ini` thì khởi động lại Apache. Bootstrap/PHPMailer đã có trong source.

Mở `http://localhost/HealthConnect/setup.php`, nhập `setup_key`, tạo admin và chọn seed để có bài viết cùng 8 bác sĩ giả lập. Setup tự khóa khi đã có admin. Cổng admin là `dang-nhap.php?role=admin`; user có thể tự đăng ký.

## Chatbot

Sửa các mục tương ứng trong `config.local.php`. Ví dụ Groq, thay key/model bằng thông tin tài khoản của bạn:

```php
"ai_provider" => "groq",
"ai_api_key" => "KEY_CUA_BAN",
"ai_model" => "openai/gpt-oss-120b",
"ai_url" => "",
```

Lấy [Groq key](https://console.groq.com/keys) và chọn đúng ID model có quyền dùng trong Playground; model ví dụ không bảo đảm mọi tài khoản đều dùng được. `ai_url` trống dùng endpoint mặc định. `ai_model` trống dùng model trong code; gặp `404 model_not_found` thì chọn model khả dụng và điền rõ ID.

Dùng [Gemini key](https://aistudio.google.com/app/apikey): đổi provider thành `gemini`, điền key Gemini và đổi model tương ứng hoặc để `ai_model` trống. Không giữ model Groq khi chuyển provider.

Dùng Ollama: đặt provider rỗng hoặc `ollama`, chạy `ollama pull qwen2.5:3b` và `ollama serve` trên máy PHP nếu dịch vụ chưa chạy. Chế độ này dùng `ollama_url`/`ollama_model`; cloud lỗi không tự chuyển sang Ollama. Đăng nhập lưu hội thoại trong DB, khách chỉ giữ tạm trong session.

## Email quên mật khẩu

Ví dụ Gmail SMTP; thay các giá trị viết hoa trong cấu hình riêng:

```php
"mail_transport" => "smtp",
"mail_from" => "EMAIL_GMAIL_CUA_BAN",
"smtp_host" => "smtp.gmail.com",
"smtp_port" => "587",
"smtp_username" => "EMAIL_GMAIL_CUA_BAN",
"smtp_password" => "MAT_KHAU_UNG_DUNG_GOOGLE",
"smtp_security" => "tls",
"smtp_auth" => "1",
```

Google cần xác minh hai bước và tài khoản được phép tạo mật khẩu ứng dụng; không dùng mật khẩu Gmail thường. SMTP khác dùng thông số nhà cung cấp. Giữ xác minh TLS; `mail_transport=mail` cần hệ thống gửi thư cấu hình riêng.

Mã email có 6 số, nhập trong cùng trình duyệt đã yêu cầu, hạn 15 phút và tối đa 5 lần thử. Chờ 5 phút trước khi xin mã thay thế. Mã dùng một lần; đổi mật khẩu làm phiên cũ mất hiệu lực. Thông báo chung trên form không chứng minh thư đã tới hộp thư; kiểm tra cả spam.

## Cách chạy khác

- **PHP built-in:** tại thư mục ứng dụng chạy `php -S localhost:8001 -t . tools/router.php`, đặt `app_url=http://localhost:8001`; không cần Apache. Chỉ dùng để phát triển. `CHAY_WINDOWS.bat` ở gốc repo dùng cổng 8000; sửa đường dẫn PHP trong file nếu khác máy.
- **Docker:** theo [DOCKER.md](../DOCKER.md). `.env` của Docker không tự được bản XAMPP đọc.
- **Hosting:** cần PHP/MySQL, HTTPS và quyền ghi uploads. Apache cần cho phép `.htaccess`; [mẫu Nginx](docs/NGINX_EXAMPLE.conf) phải chỉnh root/socket/TLS theo máy thực tế.

## Nâng cấp và sao lưu

Sao lưu DB, uploads và cấu hình riêng trước khi cập nhật code. Giữ nguyên `config.local.php` và uploads; chọn đúng DB rồi import `database/upgrade_final.sql`. Script có thể chạy lại và giữ lịch cũ; **không import lại schema cài mới vào DB có dữ liệu**. Chạy doctor, kiểm tra dữ liệu cũ và thử khôi phục backup vào DB thử riêng. Không đặt backup/bí mật trong Git hoặc thư mục public.

## RSS, PWA và xử lý lỗi

- RSS dùng tối đa ba nguồn HTTPS trong `news_sources`, cache 30–60 phút và giữ cache cũ khi nguồn lỗi. Bản local dùng nguồn cũ thì tự cập nhật từ config mẫu. Tin mở tại báo gốc, không cần API key.
- PWA cần HTTPS hoặc localhost trên chính thiết bị. HTTP qua IP LAN thường không cài được. Service Worker chỉ cache tĩnh, không lưu PHP/API/uploads; tăng phiên bản cache trong `sw.js` khi phát hành tài nguyên đã đổi.
- DB lỗi: kiểm tra service, port, mật khẩu và đủ 16 bảng; dùng client tương thích server nếu gặp lỗi plugin xác thực.
- AI lỗi: xem dòng `Chatbot provider=... HTTP=...` trong log PHP. `401` kiểm tra key, `404` kiểm tra model/endpoint, `429` kiểm tra hạn mức, `0` kiểm tra kết nối/CA. Không gửi key hoặc tắt TLS để xử lý.
- Trước demo: chạy [tests](../tests/README.md) và [kịch bản demo](docs/DEMO.md). [Kết quả lưu trữ](docs/KIEM_TRA_FINAL.md) chỉ áp dụng phiên bản/môi trường ghi trong đó.
