# Demo và checklist trước khi nộp

## Chuẩn bị

Chuẩn bị một admin, hai user A/B và bác sĩ có lịch tuần. A có vài lần đo BMI, nhật ký và mục tiêu; B dùng kiểm tra tách dữ liệu. Thử provider AI và SMTP trước; ghi rõ nếu dùng mock hoặc dịch vụ chưa hoạt động.

## Trình bày

1. Trang chủ và bố cục điện thoại; giới thiệu sức khỏe, kiến thức và lịch hẹn.
2. Đăng nhập A, lưu BMI/nhật ký/mục tiêu, xem biểu đồ và tải CSV; B không thấy dữ liệu của A.
3. Chọn bác sĩ/ngày/giờ, đặt lịch, xem phiếu và tải ICS. Admin ở cửa sổ khác xác nhận; A tải lại thấy trạng thái mới.
4. Thử giờ đầy, ngày nghỉ và URL phiếu của người khác; kiểm tra từ chối đúng.
5. Admin tạo bài nháp, xuất bản, upload ảnh; user tìm thấy. RSS mở bài ở nguồn gốc khi feed khả dụng.
6. Hỏi chatbot, xem lịch sử; gửi mã quên mật khẩu tới email thử và đổi mật khẩu trong cùng trình duyệt.
7. Nếu có HTTPS/thiết bị phù hợp, thử cài PWA và ngoại tuyến; giải thích PHP/API riêng tư cần mạng.

## Checklist trước khi nộp

Đánh dấu sau khi thực hiện trên máy trình diễn. Kết quả tự động cần được bổ sung bằng kiểm tra AI, email và thiết bị thật.

- [ ] Cài theo [README](../../README.md), đúng thư mục/cổng/`app_url`; doctor báo đủ PHP/extensions, DB và quyền uploads.
- [ ] Cài mới đủ 16 bảng hoặc nâng cấp bản sao DB có dữ liệu; thử sao lưu/khôi phục.
- [ ] Tạo admin và hai user demo; đăng nhập đúng cổng, khóa tài khoản và kiểm tra quyền.
- [ ] Lưu BMI, nhật ký, mục tiêu và CSV; tài khoản khác không thấy dữ liệu riêng.
- [ ] Đặt bác sĩ, xác nhận/hủy, xem phiếu/ICS; thử giờ đầy, ngày nghỉ và phiếu của người khác.
- [ ] Tạo bài nháp, xuất bản, tìm kiếm, upload ảnh; kiểm tra RSS và liên kết nguồn gốc.
- [ ] Hỏi AI bằng provider/model đã cấu hình; ghi rõ nếu dùng mock.
- [ ] Nhận mã email thật, đổi mật khẩu; thử mã sai/dùng lại và phiên đăng nhập cũ.
- [ ] Thử giao diện điện thoại; chỉ ghi đã cài PWA sau khi thử trên thiết bị qua HTTPS.
- [ ] Chạy [tests](../../tests/README.md), tập các bước demo và chuẩn bị ảnh minh chứng theo phạm vi đã thử.

Dùng dữ liệu giả lập, che key/mật khẩu trong ảnh và khi trình chiếu. Mỗi thành viên cần giải thích được PHP/PDO, session, CSRF, phân quyền, transaction/mutex, nâng cấp DB và Service Worker; nêu rõ PWA được cài từ trình duyệt.
