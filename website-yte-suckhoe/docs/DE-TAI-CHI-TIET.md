# Phạm vi đề tài HealthConnect

Website thông tin y tế, theo dõi sức khỏe cá nhân và đặt lịch khám cho môn Lập trình Web và Ứng dụng. Công nghệ: PHP thuần, MySQL/MariaDB, HTML/CSS/JavaScript, Bootstrap và PHPMailer.

## Người dùng và chức năng

| Vai trò | Chức năng |
|---|---|
| Khách | Đọc/tìm tin, xem bác sĩ, tính thử BMI, hỏi chatbot, đăng ký/đăng nhập |
| Người dùng | Hồ sơ, BMI/biểu đồ, nhật ký/mục tiêu, CSV, đặt lịch/phiếu hẹn/ICS, lịch sử chat |
| Quản trị viên | Thống kê, bài viết/danh mục, bác sĩ/lịch làm việc/ngày nghỉ, lịch khám và tài khoản |

## Quy tắc chính

- Đăng ký bằng họ tên, email duy nhất, mật khẩu/xác nhận. Mật khẩu dài 8–72 byte, lưu qua `password_hash`; hồ sơ bổ sung thông tin cá nhân sau đăng nhập.
- Đăng nhập kiểm tra vai trò, trạng thái và giới hạn lần sai. Quên mật khẩu dùng mã email 6 số, hạn 15 phút, tối đa 5 lần thử trong cùng trình duyệt.
- BMI tính bằng cân nặng/(chiều cao mét)², phân loại theo các ngưỡng trong code: 18,5 / 23 / 27,5. Chiều cao 80–250 cm, cân nặng 20–300 kg; chỉ đăng nhập mới lưu lịch sử.
- Nhật ký theo ngày và mục tiêu thuộc từng tài khoản; CSV chỉ xuất dữ liệu của chủ sở hữu.
- Mỗi bác sĩ một chỗ/giờ, đặt trong 30 ngày, đúng lịch làm việc và không trùng ngày nghỉ. Mỗi user tối đa 3 lịch sắp tới và không trùng giờ ở hai bác sĩ. Giao dịch có mutex chống tranh chỗ.
- Lịch hẹn có xác nhận, hủy, hoàn thành, vắng và mở lại theo trạng thái/thời gian; phiếu hẹn ghi lịch sử. ICS là bản chụp lúc tải, không tự đồng bộ.
- Bài nháp không công khai; ảnh tải lên có kiểm tra. Không xóa danh mục đang có bài hoặc tài khoản còn lịch khám; có thể khóa để giữ lịch sử.
- RSS lấy nguồn HTTPS, làm sạch và cache. Bài ngoài mở tại nguồn gốc.
- Chatbot chọn Groq/Gemini/Ollama phía server; giới hạn 1–2000 ký tự và 3 giây theo session, giữ khuyến cáo y tế. Tài khoản lưu DB; khách giữ tối đa 10 thông điệp trong session. API key không đưa xuống JavaScript.

## Dữ liệu và bảo vệ

Schema có 16 bảng; quan hệ và luồng xử lý ở [kiến trúc/ERD](KIEN_TRUC.md). PHP dùng PDO, session, CSRF, kiểm tra role/chủ sở hữu và escape/sanitize. PWA chỉ cache tài nguyên công khai; nghiệp vụ riêng tư cần kết nối server.

Đây là mô phỏng học tập, không phải hồ sơ bệnh án hay dịch vụ phòng khám thật. AI không thay thế chẩn đoán/kê đơn. Không dùng dữ liệu bệnh nhân thật.

## Cài đặt và nghiệm thu

Theo [hướng dẫn chạy](../HUONG_DAN.md), [bộ kiểm thử](../../tests/README.md), [demo](DEMO.md) và [checklist](../../KE_HOACH_HOAN_THIEN.md). Phân biệt kiểm thử giả lập với key AI, SMTP, RSS live, PWA thiết bị thật và public hosting; chỉ ghi đã nghiệm thu khi có bằng chứng thực tế.
