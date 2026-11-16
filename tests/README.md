# Kiểm thử HealthConnect

Chạy từ gốc repo. Cần PHP 8.1+ với các extension của [ứng dụng](../website-yte-suckhoe/HUONG_DAN.md); Python/Node chỉ phục vụ kiểm thử.

## PHP và database

```sh
php tests/appointments.php
php tests/news.php
php tests/database.php
```

Hai suite đầu kiểm tra quy tắc lịch và RSS/Atom. Suite database kiểm tra schema/migration, sức chứa, quyền và concurrency; cần `proc_open` và tài khoản có quyền CREATE/DROP DATABASE thử. Mặc định `127.0.0.1:3306`, root, mật khẩu trống. Ghi đè bằng `HC_TEST_DB_HOST`, `HC_TEST_DB_PORT`, `HC_TEST_DB_USER`, `HC_TEST_DB_PASS` trong môi trường cục bộ, không commit giá trị bí mật.

## HTTP và AI cloud

```sh
python -m pip install requests
python tests/http_smoke.py
python tests/cloud_chat.py
```

- `http_smoke.py`: luồng tài khoản/quyền, sức khỏe, lịch, nội dung/ảnh, email và chat. Dùng SMTP/Ollama giả lập, nhận cùng biến DB phía trên.
- `cloud_chat.py`: guest Groq/Gemini/Ollama, Bearer/model, 429/503, thiếu key, log che key, CSRF/spam/history. Không cần DB hay key thật.
- Đặt `HC_PHP_BIN` nếu PHP chưa có trong PATH; `HC_PHP_INI` chọn php.ini riêng.

Các suite DB tạo/xóa database ngẫu nhiên `healthconnect_test_*` hoặc `healthconnect_http_*`. HTTP/AI dùng bản sao source tạm và không đọc config.local.php của ứng dụng. Không dùng tài khoản production; kết quả mock không chứng minh thư thật hoặc model thật hoạt động.

## Trình duyệt tùy chọn

Cài Node/Playwright tại gốc repo, không lưu dependency/lockfile vào dự án:

```sh
npm install --no-save --package-lock=false playwright
npx playwright install chromium
```

Ví dụ PowerShell:

```powershell
$env:HC_BROWSER_TEST = "1"
$env:HC_BROWSER_SCRIPT = (Resolve-Path .\tests\browser.cjs).Path
python tests/http_smoke.py
```

Kiểm tra desktop/mobile, menu/chat, Service Worker, loại PHP/API khỏi cache và fallback ngoại tuyến. Không thay thế cài PWA trên điện thoại thật. Trước buổi bảo vệ, thực hiện [demo và checklist](../website-yte-suckhoe/docs/DEMO.md#checklist-trước-khi-nộp) với SMTP/AI thật nếu có. Các [kết quả lưu trữ](../website-yte-suckhoe/docs/KIEM_TRA_FINAL.md) chỉ áp dụng phiên bản ghi trong tài liệu.
