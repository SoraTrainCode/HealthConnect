# HealthConnect

Đồ án môn Lập trình Web và Ứng dụng: website thông tin y tế, sổ sức khỏe và đặt lịch khám. Dùng **PHP thuần, MySQL/MariaDB, HTML, CSS và JavaScript**, kèm Bootstrap/PHPMailer.

## Chức năng

- Tài khoản, phân quyền người dùng/admin và mã quên mật khẩu qua email.
- BMI, nhật ký sức khỏe, mục tiêu, biểu đồ và xuất CSV.
- Bác sĩ, giờ trống, đặt lịch, xác nhận/hủy và phiếu hẹn/ICS.
- Bài viết, tin RSS, quản trị và chatbot Groq/Gemini/Ollama.
- Giao diện responsive, PWA và trang ngoại tuyến.

## Chạy nhanh bằng XAMPP

1. Copy thư mục `website-yte-suckhoe` thành `C:\xampp\htdocs\HealthConnect`; bên trong phải có ngay `index.php`.
2. Bật Apache và dùng một MySQL/MariaDB đang chạy. Import `database/schema.sql` **một lần cho database mới** (`healthconnect_moi`, 16 bảng). DB có dữ liệu: sao lưu rồi dùng `database/upgrade_final.sql`.
3. Copy `config.example.php` thành `config.local.php` nếu chưa có. Điền DB, đặt `setup_key` riêng ít nhất 16 ký tự và `app_url=http://localhost/HealthConnect`.
4. Mở `http://localhost/HealthConnect/setup.php`, nhập khóa setup, tạo admin và chọn dữ liệu mẫu. Sau đó vào trang chủ; cổng admin là `dang-nhap.php?role=admin`.

Nếu XAMPP cài ở nơi khác hoặc Apache dùng cổng khác, chỉnh đường dẫn/URL tương ứng. Không mở PHP bằng Live Server hay nhấp đúp vào file.

Yêu cầu **PHP 8.1+**, MySQL 8/MariaDB 10.4+, extensions `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`, `SimpleXML`, `openssl`. Kiểm tra bằng `php tools/doctor.php` tại thư mục ứng dụng. Website không cần Node/Composer để chạy.

## Tài liệu

- [Cài đặt, AI, email và xử lý lỗi](website-yte-suckhoe/HUONG_DAN.md)
- [Docker](DOCKER.md) · [Kiểm thử](tests/README.md) · [Quy trình Git](HUONG_DAN_GIT.md)
- [Phạm vi đề tài](website-yte-suckhoe/docs/DE-TAI-CHI-TIET.md) · [Kiến trúc và ERD](website-yte-suckhoe/docs/KIEN_TRUC.md)
- [Demo](website-yte-suckhoe/docs/DEMO.md) · [Checklist trước khi nộp](KE_HOACH_HOAN_THIEN.md)
- [Thư viện và nguồn dữ liệu](website-yte-suckhoe/docs/THIRD_PARTY.md)

Chỉ điền mật khẩu/key trong cấu hình riêng; không commit `config.local.php` hay `.env`. Dữ liệu bác sĩ/lịch khám là mô phỏng; thông tin sức khỏe và AI chỉ để tham khảo.
