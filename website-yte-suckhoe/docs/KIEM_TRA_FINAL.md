# Kết quả kiểm tra bản hoàn thiện

Ngày chốt: **29/10/2026**. Bản nền remote: `a4c67a73ff0b043eac4e99a3067b160cf5ffcaa2`. Môi trường chạy kiểm tra: PHP 8.3.6, MariaDB 10.11.14, Chromium headless. Dữ liệu giả lập nằm trong database riêng tên ngẫu nhiên, không dùng database của nhóm.

## Đã chạy đạt

| Nhóm | Kết quả | Phạm vi |
|---|---:|---|
| PHP lint | 49 file | Ứng dụng, thư viện PHP kèm theo, script test |
| Quy tắc + database + concurrency | 38 kiểm tra | Trong đó 15 quy tắc thuần; schema 16 bảng, migration lặp lại/giữ lịch cũ, bác sĩ/ngày nghỉ, sức chứa, chủ sở hữu, trạng thái, nhật ký |
| RSS/Atom | 6 kiểm tra | RSS, Atom, title/summary, namespace ảnh, URL không an toàn và XML lỗi |
| HTTP | 61 kiểm tra | Setup/seed/admin/login, CSRF/quyền, đặt/hủy/xác nhận/mở lại, nhật ký/goal/BMI/CSV, phiếu/ICS, bài viết/ảnh/danh mục, SMTP/reset và Ollama giả lập |
| Trình duyệt | Đạt | Desktop 1440px, 13 trang ở viewport 390×844; menu, khung chat, không tràn ngang toàn trang, service worker, trang ngoại tuyến, cache không có PHP/API |
| Git | Đạt | `git diff --check`; patch được kiểm tra áp dụng trên checkout đúng bản nền trước khi đóng gói |

Kiểm thử đồng thời: 4 tiến trình PHP tranh một chỗ cuối của lịch tiếp nhận chung, đúng 1 tiến trình thành công. Luồng bác sĩ dùng cùng cơ chế mutex và có test sức chứa một người/giờ, không trùng giờ giữa bác sĩ, ngày nghỉ và inactive.

HTTP kiểm tra cả thao tác quản trị cũ: bài nháp không công khai, xuất bản/tìm kiếm, làm sạch script, tải PNG và sửa bài có ảnh, ngăn xóa danh mục còn bài. Tài khoản còn lịch khám không bị xóa để tránh lịch mồ côi.

Email: PHPMailer gửi tới SMTP giả lập localhost; test đọc được link, đổi mật khẩu, vô hiệu hóa session, chặn dùng lại token và đăng nhập mật khẩu mới. Ollama: server giả lập nhận đúng payload rồi PHP lưu hội thoại và đọc lại. Hai kiểm tra này **không phải gửi mail ra hộp thư thật hoặc chạy model thật**.

Nguồn CDC mặc định và endpoint WHO tham khảo đã được truy cập thử và trả HTTP 200/XML trong môi trường phát triển. Điều này không bảo đảm nguồn luôn sẵn sàng hoặc nội dung đã được rà soát chuyên môn; chức năng có cache/fallback.

## Cần nghiệm thu trên môi trường nhóm

- XAMPP/Windows thực tế, cấu hình Apache .htaccess và các extension; phiên bản PHP 8.1/MySQL 8 do CI cấu hình nhưng chưa được chạy trực tiếp trong môi trường kiểm tra này.
- SMTP thật, nhận thư thật, app_url đúng tên miền; model Ollama thật theo cấu hình máy.
- HTTPS hosting và cài PWA trên điện thoại thật; kiểm tra headless không chứng minh đã cài lên thiết bị hay được cửa hàng app duyệt.
- Backup/restore trên bản sao dữ liệu nhóm, ảnh/PDF báo cáo và phần thuyết trình.

Bộ test có thể chạy lại theo `tests/README.md`. Các tài liệu kiểm thử phiên bản trước trong docs là lịch sử, không cộng số lượng đó vào kết quả hiện tại.
