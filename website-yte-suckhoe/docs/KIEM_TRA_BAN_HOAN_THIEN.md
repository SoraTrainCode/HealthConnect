# Kết quả kiểm tra nhánh hoàn thiện

Bản nền: `5062dfe`. Kiểm tra ngày 18/10/2026 UTC bằng PHP 8.3.6 và MariaDB 10.11.14 trên môi trường tạm riêng.

## Đã chạy đạt

- Kiểm tra cú pháp toàn bộ 34 file PHP gồm ứng dụng và các script test.
- 15 kiểm tra quy tắc lịch khám.
- 12 kiểm tra database/tính đồng thời, tổng 27 khi chạy `php tests/database.php` (script có chạy lại 15 kiểm tra quy tắc).
- 22 kiểm tra HTTP trên một bản sao ứng dụng và database rỗng: form setup, tạo admin + seed, khóa setup, đăng nhập admin, đăng ký 2 user, chặn user vào admin, CSRF, đặt lịch, ngăn hủy lịch người khác, xác nhận, hiển thị trạng thái, chặn hoàn thành trước giờ, hủy/mở lại, redirect URL cũ, chặn truy cập config/database/includes/tools.
- `git diff --check`.

Kiểm thử đồng thời tạo 4 tiến trình PHP tranh 1 chỗ cuối, chỉ 1 tiến trình thành công; tổng số lịch vẫn là 3. Test database tạo DB tên ngẫu nhiên rồi xóa DB đó, không dùng dữ liệu ứng dụng.

## Chưa xác nhận trong lần kiểm tra này

- MySQL 8 và PHP 8.1: workflow CI đã cấu hình, cần xem kết quả chạy trên GitHub sau khi push.
- Giao diện trực quan trên trình duyệt/điện thoại và XAMPP Windows.
- Sinh câu trả lời qua Ollama thật và gửi email reset thật.
- Hosting HTTPS, backup/restore trên máy nhóm, ứng dụng Android/iOS.

Các kết quả kiểm tra của phiên bản cũ trong tài liệu khác là lịch sử, không phải chứng nhận cho toàn bộ code mới. Nhóm dùng checklist trong `tests/README.md` và kế hoạch hoàn thiện để nghiệm thu tiếp.
