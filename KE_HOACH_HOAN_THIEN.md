# Checklist trước khi demo và nộp

Đánh dấu sau khi thực hiện trên máy trình diễn. Kết quả tự động không thay thế kiểm tra email, AI và thiết bị thật.

- [ ] Cài được từ [README](README.md), đúng thư mục/cổng/`app_url`; `doctor.php` báo đủ PHP/extensions, DB và quyền uploads.
- [ ] Cài mới đủ 16 bảng hoặc nâng cấp bản sao DB có dữ liệu; thử sao lưu/khôi phục.
- [ ] Tạo admin và hai user demo; đăng nhập đúng cổng, khóa tài khoản và kiểm tra quyền.
- [ ] Lưu BMI, nhật ký, mục tiêu và CSV; tài khoản khác không thấy dữ liệu riêng.
- [ ] Đặt bác sĩ, xác nhận/hủy, xem phiếu/ICS; thử giờ đầy, ngày nghỉ và phiếu của người khác.
- [ ] Tạo bài nháp, xuất bản, tìm kiếm, upload ảnh; kiểm tra RSS và nguồn gốc.
- [ ] Hỏi AI bằng provider/model đã cấu hình; nếu dùng mock thì ghi rõ.
- [ ] Nhận mã email thật, đổi mật khẩu; thử mã sai/dùng lại và phiên đăng nhập cũ.
- [ ] Thử giao diện điện thoại; chỉ ghi đã cài PWA sau khi thử trên thiết bị qua HTTPS.
- [ ] Chạy [tests](tests/README.md), chuẩn bị [demo](website-yte-suckhoe/docs/DEMO.md) và ảnh minh chứng không lộ bí mật.

Mỗi thành viên cần giải thích được PHP/PDO, session, CSRF, phân quyền, transaction/mutex và Service Worker. Dữ liệu demo không phải dữ liệu bệnh nhân thật; PWA không phải APK/IPA.
