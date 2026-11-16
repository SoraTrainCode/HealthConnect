# HealthConnect

Đồ án môn Lập trình Web và Ứng dụng: website thông tin y tế, sổ sức khỏe và đặt lịch khám. Dùng **PHP thuần, MySQL/MariaDB, HTML, CSS và JavaScript**, kèm thư viện Bootstrap/PHPMailer; không dùng framework backend như Laravel.

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

Yêu cầu **PHP 8.1+**, MySQL 8/MariaDB 10.4+, extensions `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`, `SimpleXML`, `openssl`. Kiểm tra bằng `php tools/doctor.php` tại thư mục ứng dụng. Website không cần Node/Composer để chạy; Python/Node chỉ phục vụ kiểm thử, Docker là cách chạy tùy chọn.

## Làm việc theo nhóm

Chạy tại gốc repo. Nếu đã có bản clone thì cập nhật bản đó; không clone thêm vào bên trong:

```sh
git status
git switch main
git pull --ff-only origin main
```

Nếu có thay đổi chưa lưu, lưu công việc có chủ đích trước khi cập nhật. Để gửi thay đổi, thay tên nhánh/đường dẫn minh họa bên dưới bằng giá trị thực tế:

```sh
git switch -c ten-nhanh
git diff
git add duong-dan-file-can-gui
git diff --cached
git commit -m "Mo ta thay doi"
git push -u origin ten-nhanh
```

Chỉ stage file đã kiểm tra, mở Pull Request kèm kết quả kiểm thử và để thành viên nhóm duyệt trước khi merge. Không force-push, reset hoặc xóa `.git` để xử lý lỗi. Git không đồng bộ database, cấu hình riêng hay ảnh upload giữa các máy; xem [sao lưu/nâng cấp](website-yte-suckhoe/HUONG_DAN.md#nâng-cấp-và-sao-lưu) trước khi đổi schema.

## Tài liệu

- [Cài đặt, AI, email và xử lý lỗi](website-yte-suckhoe/HUONG_DAN.md)
- [Mô tả đề tài, kiến trúc và ERD](website-yte-suckhoe/docs/KIEN_TRUC.md)
- [Demo và checklist trước khi nộp](website-yte-suckhoe/docs/DEMO.md)
- [Kiểm thử](tests/README.md) · [Docker](DOCKER.md)
- [Thư viện và nguồn dữ liệu](website-yte-suckhoe/docs/THIRD_PARTY.md)

Chỉ điền mật khẩu/key trong cấu hình riêng; không commit `config.local.php` hay `.env`. Dữ liệu bác sĩ/lịch khám là mô phỏng; thông tin sức khỏe và AI chỉ để tham khảo.
