# Chạy HealthConnect bằng Docker

Yêu cầu Docker Engine và Docker Compose v2. Image dùng PHP 8.2/Apache với các extension cần thiết; MariaDB 11 chạy riêng và không mở cổng database ra máy chủ. Không cần cài Ollama nếu dùng Groq/Gemini. Các URL `.php` hiện tại không cần quy tắc rewrite.

## Cấu hình riêng của máy chạy

Từ gốc repository, tạo `.env` cục bộ với quyền riêng tư. Lệnh sau dùng chế độ tạo mới, không ghi đè tệp đã có và không in giá trị bí mật:

```sh
python3 - <<'PY'
from pathlib import Path
import os, secrets
path = Path('.env')
fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'w') as output:
    output.write('HC_DB_PASSWORD=' + secrets.token_hex(32) + '\n')
    output.write('HC_DB_ROOT_PASSWORD=' + secrets.token_hex(32) + '\n')
    output.write('HC_SETUP_KEY=' + secrets.token_hex(32) + '\n')
    output.write('HC_APP_URL=http://localhost:8080\n')
    output.write('HC_AI_PROVIDER=\nHC_AI_API_KEY=\n')
PY
```

Chỉnh riêng `.env` trên máy chạy: đặt `HC_AI_PROVIDER=groq` hoặc `gemini`, rồi điền key của bạn ở `HC_AI_API_KEY`. Có thể thêm `HC_AI_MODEL`/`HC_AI_URL` nếu cần ghi đè model/endpoint. Không gửi key qua chat, không commit `.env` hay `config.local.php`. Khi chưa chọn provider, ứng dụng vẫn gọi Ollama như trước. Trong container, địa chỉ loopback chỉ trỏ về chính container web; để dùng Ollama ở nơi khác, đặt `HC_OLLAMA_URL` thành địa chỉ truy cập được từ container.

Compose truyền các giá trị vào tiến trình web. Khi khởi động container mới, entrypoint tạo `/var/www/html/config.local.php` từ config mẫu và biến môi trường; tệp đó không nằm trong image. Nếu cần quản lý cấu hình trực tiếp thay vì biến môi trường, mount một `config.local.php` riêng vào đúng đường dẫn, chỉ đọc; entrypoint giữ nguyên tệp mount. Database host khi dùng Compose là `db`, database `healthconnect_moi`, user `healthconnect`; dùng mật khẩu khớp `.env`. Không lấy cấu hình cục bộ ngoài Docker có `db_host=127.0.0.1` để mount nguyên vào Compose.

## Khởi động và cài dữ liệu mẫu

```sh
docker compose up --build -d
docker compose exec web php tools/doctor.php
```

Mở trang chủ trên cổng 8080 ở máy chạy. Vào `setup.php`, lấy khóa `HC_SETUP_KEY` trong trình soạn thảo riêng ở máy bạn, tạo admin và chọn dữ liệu mẫu theo `website-yte-suckhoe/HUONG_DAN.md`. Không in khóa ra log hoặc báo cáo. Sau khi tạo admin, trang setup tự khóa. Đăng nhập admin tại `dang-nhap.php?role=admin`; tài khoản thường dùng `dang-nhap.php`.

Schema chỉ chạy tự động khi volume MariaDB hoàn toàn mới. Các lần `up` sau giữ database và ảnh upload. Không chạy lại schema trên dữ liệu có sẵn. Khi nâng cấp, sao lưu trước và dùng `upgrade_final.sql` theo hướng dẫn repository. Không dùng `docker compose down -v` nếu cần giữ dữ liệu.

Sau khi sửa `.env` hoặc nhận source mới, tạo lại container web để cập nhật cấu hình mà vẫn giữ các volume:

```sh
docker compose up --build -d --force-recreate web
```

Đổi mật khẩu database trong `.env` không tự đổi user của database đã tồn tại; cần đổi trong MariaDB và cập nhật cấu hình tương ứng. Đừng xóa volume để xử lý việc đổi mật khẩu.

## Cloud có proxy và CA riêng

Dockerfile hỗ trợ BuildKit secret tùy chọn `ca_bundle` để apt dùng trust bundle của phiên, vẫn xác minh TLS và chữ ký gói. CA không được lưu vào image. Trong môi trường Codex, trước tiên build image cho service web bằng lệnh sau; Compose `up --no-build` sẽ dùng image vừa build:

```sh
docker build --secret "id=ca_bundle,src=${SSL_CERT_FILE:-/etc/ssl/certs/ca-certificates.crt}" -t healthconnect-web .
docker compose up -d --no-build
```

Nếu đổi project name bằng `-p`, tag build tương ứng là `<project>-web`. Giữ proxy do nền tảng cấp. Lúc chạy, nếu proxy yêu cầu CA riêng cho HTTPS của PHP/cURL, mount trust bundle công khai hiện tại vào `/etc/ssl/certs/ca-certificates.crt` (chỉ đọc) bằng override Compose cục bộ. Không copy CA của phiên vào image hoặc tắt xác minh TLS. Những override riêng máy phải nằm ngoài repository hoặc được Git bỏ qua.

## Deploy lên hosting/VPS

1. Cấp một dịch vụ web có thể build Dockerfile và một MariaDB/MySQL có lưu trữ bền vững. Không dùng server PHP tích hợp cho public hosting.
2. Cấp các biến `HC_DB_HOST`, `HC_DB_PORT`, `HC_DB_NAME`, `HC_DB_USER`, `HC_DB_PASSWORD`, `HC_APP_URL` (URL HTTPS thật), `HC_SETUP_KEY`, `HC_AI_PROVIDER`, `HC_AI_API_KEY` qua hệ thống secrets của nền tảng. Chọn database mới `healthconnect_moi` nếu import nguyên schema. Không đưa giá trị bí mật vào Dockerfile hay build arguments.
3. Import schema đúng một lần cho database mới; với dữ liệu cũ dùng quy trình nâng cấp. Lưu bền vững `/var/www/html/uploads`, cấu hình backup database và volume upload.
4. Với VPS dùng Compose, cấu hình reverse proxy HTTPS vào web port 8080 trên loopback. Với nền tảng chỉ chạy image, định tuyến HTTP vào cổng 80 của container. Hoàn tất setup/admin và dữ liệu mẫu.
5. Kiểm tra trang chủ, đăng nhập, chatbot với một câu hỏi sức khỏe, các nguồn RSS và cấu hình HTTPS/PWA. SMTP thật cần cấu hình riêng bằng `HC_MAIL_TRANSPORT`, `HC_MAIL_FROM`, các biến `HC_SMTP_*` hoặc tệp mount riêng.

Docker chạy ở máy cloud chưa có nghĩa ứng dụng đã deploy public. Chỉ công bố URL public sau khi đã triển khai và kiểm tra thật. Quay lại Ollama bằng `HC_AI_PROVIDER=` (hoặc `ai_provider` rỗng trong tệp cấu hình riêng), rồi tạo lại container web nếu dùng biến môi trường.
