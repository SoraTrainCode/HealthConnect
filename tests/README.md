# Kiểm thử HealthConnect

Ứng dụng chỉ cần PHP/MySQL để chạy. Python/Node là công cụ phát triển tùy chọn cho bộ kiểm thử HTTP/trình duyệt.

## PHP và database

Từ gốc repo:

```sh
php tests/appointments.php
php tests/news.php
php tests/database.php
```

`appointments.php` kiểm tra ngày/giờ và chuyển trạng thái. `news.php` kiểm tra RSS/Atom, URL không an toàn, namespace ảnh và XML lỗi. `database.php` tạo database mới `healthconnect_test_<ngẫu nhiên>`, kiểm tra schema/migration lặp lại/giữ dữ liệu cũ, sức chứa, trùng giờ, lịch bác sĩ/ngày nghỉ, nhật ký và concurrency, rồi xóa DB thử.

Mặc định kết nối `127.0.0.1:3306`, root, mật khẩu trống. Ghi đè bằng `HC_TEST_DB_HOST`, `HC_TEST_DB_PORT`, `HC_TEST_DB_USER`, `HC_TEST_DB_PASS`. Dùng tài khoản test có quyền CREATE/DROP DATABASE, không dùng tài khoản production. Script không đọc config.local của ứng dụng. Concurrency cần `proc_open`.

Windows CMD ví dụ:

```bat
set HC_TEST_DB_PORT=3306
set HC_TEST_DB_USER=root
C:\xampp\php\php.exe tests\database.php
C:\xampp\php\php.exe tests\news.php
```

## HTTP, SMTP và Ollama giả lập

```sh
python -m pip install requests
python tests/http_smoke.py
```

Đặt `HC_PHP_BIN` nếu PHP chưa có trong PATH; ví dụ `set HC_PHP_BIN=C:\xampp\php\php.exe`. Có thể đặt `HC_PHP_INI` để chọn php.ini riêng. Các biến database giống phía trên.

Test tạo bản sao source và database `healthconnect_http_<ngẫu nhiên>`, chạy PHP server ở cổng localhost tạm, tạo SMTP/Ollama giả lập cục bộ và dọn khi xong. Không gửi email ra Internet. Test SMTP chứng minh luồng gửi và reset token, không chứng minh hộp thư thật đã nhận. Test Ollama chứng minh request/persistence, không đánh giá model thật.

## Trình duyệt tùy chọn

```sh
npm install --no-save playwright
npx playwright install chromium
```

Sau đó bật `HC_BROWSER_TEST=1` và đặt `HC_BROWSER_SCRIPT` là đường dẫn tuyệt đối tới `tests/browser.cjs` trước khi chạy http_smoke.py. Ví dụ Windows:

```bat
set HC_BROWSER_TEST=1
set HC_BROWSER_SCRIPT=D:\duong-dan\HealthConnect\tests\browser.cjs
python tests\http_smoke.py
```

Kiểm tra màn hình desktop/mobile, không tràn ngang toàn trang, menu, chat toggle, service worker, loại trừ cache dữ liệu riêng tư và ngoại tuyến. Không thay thế thử cài PWA trên điện thoại thật qua HTTPS. Bộ này không cần chạy để người dùng sử dụng website.

## Kiểm tra trước khi demo

- Cài mới và nâng cấp bản sao DB thật trên XAMPP.
- Ghi BMI/nhật ký bằng hai tài khoản; xác nhận tách dữ liệu.
- Đặt/xác nhận/hủy lịch; kiểm tra khung giờ đầy, ngày nghỉ và mở lại lịch.
- Thêm bài viết có ảnh tải lên, sửa lại bài, tìm kiếm và xem công khai.
- Cấu hình SMTP thật, Ollama thật và URL HTTPS; thử từ điện thoại.
- Đối chiếu kết quả trong `docs/KIEM_TRA_FINAL.md`; các báo cáo phiên bản cũ chỉ là lịch sử.
