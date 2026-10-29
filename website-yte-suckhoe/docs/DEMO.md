# Kịch bản demo và chuẩn bị bảo vệ

## Chuẩn bị

- Import/cập nhật DB và chạy doctor.php trước buổi demo.
- Một tài khoản admin và hai tài khoản user tự tạo; không trình chiếu mật khẩu/config.local.
- Tạo 8 bác sĩ giả lập thuộc 8 chuyên khoa hoặc dữ liệu giả lập tự nhập; đặt lịch tuần.
- User A có 2–3 lần đo BMI, 2 ngày nhật ký, một mục tiêu cân nặng. User B dùng kiểm tra tách dữ liệu.
- Ollama và SMTP hoạt động nếu định demo trực tiếp. Khi chưa cấu hình, nói rõ giới hạn, không giả vờ dữ liệu mock là trả lời AI/email thật.

## Thứ tự trình bày

1. **Trang chủ:** giới thiệu vấn đề và ba chức năng sức khỏe, kiến thức, lịch hẹn. Thu nhỏ màn hình để thấy giao diện điện thoại và thanh dưới.
2. **Tài khoản:** đăng ký hoặc đăng nhập user A. Mở trang Sức khỏe, nhập cân nặng/BMI, nhật ký một ngày và mục tiêu; xem biểu đồ/thống kê. Tải CSV.
3. **Đặt lịch:** xem bác sĩ, lọc chuyên khoa, chọn ngày/giờ, gửi yêu cầu. Mở phiếu hẹn và lịch sử trạng thái; giới thiệu tải ICS.
4. **Quản trị:** ở cửa sổ/trình duyệt khác đăng nhập admin. Xem ngày sắp tới, chọn lịch vừa đặt, xác nhận. User A tải lại sẽ thấy trạng thái mới.
5. **Ràng buộc:** thử đặt cùng giờ cùng bác sĩ bằng user B; giờ đầy không đặt được. User B không xem được URL phiếu hẹn của A. Thử thêm ngày nghỉ đang có lịch, admin nhận thông báo cần xử lý lịch trước.
6. **Nội dung:** admin tạo bài nháp, xuất bản; user tìm thấy. Mở tab RSS và chỉ rõ bài mở tại nguồn gốc.
7. **Chat/email:** nếu đã cấu hình, chat câu hỏi phù hợp và mở lịch sử; gửi reset vào hộp thư thử, đổi mật khẩu và kiểm tra phiên cũ bị yêu cầu đăng nhập lại.
8. **PWA:** cài từ URL HTTPS trên điện thoại/desktop hỗ trợ; mở độc lập. Tắt mạng để thấy trang kết nối lại; giải thích dữ liệu cá nhân không nằm trong cache.

## Các câu hỏi cần giải thích được

- HTML ở đâu, PHP làm gì, trình duyệt có chạy PHP không?
- Vì sao phải có session, CSRF, password_hash và prepared statements?
- Vì sao đặt lịch cần transaction và khóa, không chỉ đếm số chỗ?
- Cài mới khác nâng cấp database như thế nào?
- PWA khác giao diện responsive và APK/IPA như thế nào?
- Khi SMTP/Ollama/RSS lỗi, những phần nào vẫn hoạt động?
- Dữ liệu user A được tách với user B bằng điều kiện SQL nào?

## Minh chứng nộp

Chụp trang chủ desktop/mobile, sổ sức khỏe, form đặt lịch, phiếu hẹn, admin bác sĩ/lịch khám, bài viết, chat/reset nếu đã cấu hình. Để lộ thanh địa chỉ theo yêu cầu thầy, tránh lộ dữ liệu thật hoặc khóa bí mật. Nhóm tự dựng PDF theo mẫu môn học.
