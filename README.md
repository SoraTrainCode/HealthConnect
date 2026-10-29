# HealthConnect — Sổ tay sức khỏe & đặt lịch khám

Đồ án web dùng **PHP 8.1+, MySQL/MariaDB, HTML, CSS và JavaScript**. Giao diện responsive và PWA có thể cài lên màn hình chính khi truy cập qua HTTPS. Không cần Node, React hoặc framework backend để chạy website.

## Có gì trong bản này?

- Đăng ký, đăng nhập theo cổng người dùng/admin, hồ sơ, đổi mật khẩu, reset qua email.
- Sổ sức khỏe riêng: BMI, biểu đồ cân nặng, mục tiêu, nhật ký ngủ/nước/vận động, xuất CSV.
- Bác sĩ/chuyên khoa, lịch tuần/ngày nghỉ, đặt lịch theo giờ trống, nhật ký trạng thái, phiếu hẹn và lịch điện thoại.
- Admin quản lý bác sĩ, lịch khám, bài viết, danh mục, tài khoản và thống kê.
- Bài viết nội bộ cùng tin RSS của nhóm; chatbot Ollama và lịch sử hội thoại.
- Giao diện điện thoại, thanh điều hướng dưới, cài PWA, trang ngoại tuyến không lưu dữ liệu riêng tư.

## Chạy nhanh trên Windows/XAMPP

1. Bật **MySQL** trong XAMPP; bật Apache nếu dùng phpMyAdmin.
2. **Máy mới:** import `website-yte-suckhoe/database/schema.sql` trong phpMyAdmin. File này tạo database `healthconnect_moi`.
3. **Máy đã có dữ liệu:** Export sao lưu trước, chọn database đang dùng rồi import **`website-yte-suckhoe/database/upgrade_final.sql`**. Không import lại schema cài mới.
4. Copy `website-yte-suckhoe/config.example.php` thành `config.local.php`. Điền database, đặt `setup_key` riêng dài ít nhất 16 ký tự nếu chưa có admin. Với server dưới đây, đặt `app_url` là `http://localhost:8000`.
5. Mở CMD tại thư mục repo, chạy:

```bat
C:\xampp\php\php.exe website-yte-suckhoe\tools\doctor.php
cd website-yte-suckhoe
C:\xampp\php\php.exe -S localhost:8000 -t . tools/router.php
```

6. Mở **http://localhost:8000/setup.php** nếu cài mới, tạo admin và chọn dữ liệu mẫu. Sau đó mở **http://localhost:8000/index.php**. Admin dùng `dang-nhap.php?role=admin`.
7. Máy nâng cấp chưa có bác sĩ: vào Quản trị → Bác sĩ → “Tạo 3 hồ sơ minh họa”, hoặc tự thêm bác sĩ và lịch làm việc.

Có thể dùng `CHAY_WINDOWS.bat` sau khi đã cấu hình database. Nếu dùng Apache trong htdocs, sửa `app_url` thành đúng URL thư mục/cổng Apache. Không mở file PHP bằng nhấp đúp hoặc Live Server.

## Cấu hình dịch vụ

- Extensions: `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`, `SimpleXML`, `openssl`.
- **Ollama:** cài trên máy PHP, chạy `ollama pull qwen2.5:3b`. Cấu hình URL/model trong config.local. Khi dịch vụ dừng, chatbot báo chưa kết nối được.
- **Email:** dùng PHPMailer đã kèm source; đặt `mail_transport=smtp`, host, port, username/password, TLS và địa chỉ gửi trong config.local. Không commit mật khẩu. Hướng dẫn chi tiết trong `HUONG_DAN.md`.
- **PWA:** HTTPS hoặc localhost; HTTP qua địa chỉ IP trong LAN thường không cài được. Cài trực tiếp từ trình duyệt, không phải APK/IPA.

## Tài liệu

- [Kế hoạch và checklist hoàn thiện, không chia ngày](KE_HOACH_HOAN_THIEN.md)
- [Hướng dẫn cài mới, nâng cấp, SMTP, Ollama, PWA](website-yte-suckhoe/HUONG_DAN.md)
- [Kiến trúc và ERD](website-yte-suckhoe/docs/KIEN_TRUC.md)
- [Kịch bản demo](website-yte-suckhoe/docs/DEMO.md)
- [Kết quả kiểm tra và giới hạn](website-yte-suckhoe/docs/KIEM_TRA_FINAL.md)
- [Chạy bộ kiểm thử](tests/README.md)
- [Đưa bản này vào repo nhóm](HUONG_DAN_GIT.md)

## Dữ liệu và mã nguồn bên thứ ba

Hồ sơ bác sĩ mẫu là giả lập. Nội dung mẫu và câu trả lời AI chỉ để tham khảo, không thay thế tư vấn chuyên môn. Không nhập dữ liệu bệnh nhân thật vào bản demo.

Bootstrap 5.3.3 (MIT) được lưu trong `assets/vendor`; PHPMailer 7.1.1 (LGPL-2.1) trong `includes/vendor/PHPMailer`, kèm giấy phép và nguồn. Python/Node chỉ dùng cho kiểm thử tùy chọn, không cần để chạy ứng dụng PHP.
