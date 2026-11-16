# Chạy bằng Docker

Cần Docker Engine/Desktop và Compose v2. Web dùng PHP 8.2/Apache, DB dùng MariaDB 11; DB không publish cổng ra máy chủ.

## Cấu hình

Tạo `.env` riêng tại gốc repo, không ghi đè file đã có. Điền các giá trị dưới đây; mật khẩu và khóa setup tự đặt riêng, không dùng nguyên chữ minh họa:

```dotenv
HC_DB_PASSWORD=MAT_KHAU_DB_RIENG
HC_DB_ROOT_PASSWORD=MAT_KHAU_ROOT_RIENG
HC_SETUP_KEY=KHOA_RIENG_IT_NHAT_16_KY_TU
HC_APP_URL=http://localhost:8080
HC_AI_PROVIDER=groq
HC_AI_API_KEY=KEY_CUA_BAN
HC_AI_MODEL=ID_MODEL_TAI_KHOAN_DANG_DUNG
```

AI key/model lấy từ nhà cung cấp; xem [cấu hình AI](website-yte-suckhoe/HUONG_DAN.md#chatbot). Provider trống dùng Ollama; trong container, `127.0.0.1` trỏ về chính web nên cần `HC_OLLAMA_URL` phù hợp nếu Ollama chạy nơi khác. Có thể đặt `HC_AI_URL` để ghi đè endpoint.

Entry point tạo `/var/www/html/config.local.php` từ biến môi trường lúc tạo container; image không chứa cấu hình riêng. Có thể mount file riêng chỉ đọc thay thế; file mount phải dùng DB host `db`, tên `healthconnect_moi`, user `healthconnect` và mật khẩu khớp `.env`. XAMPP không tự đọc `.env` này.

## Khởi động

```sh
docker compose up --build -d
docker compose exec web php tools/doctor.php
```

Mở `http://localhost:8080/setup.php`, dùng `HC_SETUP_KEY`, tạo admin và chọn dữ liệu mẫu. Setup tự khóa; cổng admin là `dang-nhap.php?role=admin`.

Schema chỉ chạy khi volume DB mới. Database và uploads giữ trong volume; không chạy `down -v` nếu cần dữ liệu. Sau khi sửa `.env`/source, tạo lại web:

```sh
docker compose up --build -d --force-recreate web
```

Đổi mật khẩu trong `.env` không tự đổi user MariaDB đã tồn tại. [Sao lưu/nâng cấp](website-yte-suckhoe/HUONG_DAN.md#nâng-cấp-và-sao-lưu) trước khi đổi schema; không import schema mới vào DB có dữ liệu.

## Proxy có CA riêng

Giữ proxy của môi trường và xác minh TLS/chữ ký gói. Dockerfile nhận BuildKit secret tùy chọn `ca_bundle`; CA phiên không được lưu vào image:

```sh
docker build --secret "id=ca_bundle,src=${SSL_CERT_FILE:-/etc/ssl/certs/ca-certificates.crt}" -t healthconnect-web .
docker compose up -d --no-build
```

Ví dụ trên dùng shell POSIX và project mặc định `healthconnect`; nếu dùng `-p`, tag image là `<project>-web`. Khi PHP/cURL cũng cần CA riêng lúc chạy, mount trust bundle hiện tại vào `/etc/ssl/certs/ca-certificates.crt` chỉ đọc qua override riêng máy, không commit override chứa bí mật hoặc tắt TLS.

## Hosting/VPS

Cấp DB bền vững và các biến `HC_DB_HOST`, `HC_DB_PORT`, `HC_DB_NAME`, `HC_DB_USER`, `HC_DB_PASSWORD`, `HC_APP_URL` (HTTPS), `HC_SETUP_KEY` cùng cấu hình `HC_AI_*` qua cơ chế secrets của nền tảng. SMTP dùng `HC_MAIL_TRANSPORT`, `HC_MAIL_FROM`, `HC_SMTP_*` hoặc file cấu hình mount riêng.

Import schema đúng một lần cho DB mới, lưu bền vững uploads và cấu hình backup. VPS có thể đặt reverse proxy HTTPS trước port 8080 loopback; nền tảng chạy image định tuyến vào port 80. Sau triển khai, kiểm tra home/login, AI, email, RSS và PWA. Chạy được Docker local chưa chứng minh đã có public deployment.
