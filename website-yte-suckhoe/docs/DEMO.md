# Kịch bản demo

## Chuẩn bị

- Chạy doctor và [tests](../../tests/README.md); một admin, hai user demo, bác sĩ có lịch tuần.
- User A có vài lần đo BMI, nhật ký và mục tiêu; user B dùng kiểm tra tách dữ liệu.
- Thử provider AI và SMTP trước. Nếu dùng mock hoặc dịch vụ chưa hoạt động, ghi rõ; không trình chiếu key/mật khẩu hay dữ liệu thật.

## Trình bày

1. Trang chủ và bố cục điện thoại; giới thiệu sức khỏe, kiến thức và lịch hẹn.
2. Đăng nhập A, lưu BMI/nhật ký/mục tiêu, xem biểu đồ và tải CSV; B không thấy dữ liệu của A.
3. Chọn bác sĩ/ngày/giờ, đặt lịch, xem phiếu và tải ICS. Admin ở cửa sổ khác xác nhận; A tải lại thấy trạng thái mới.
4. Thử giờ đầy, ngày nghỉ và URL phiếu của người khác; kiểm tra từ chối đúng.
5. Admin tạo bài nháp, xuất bản, upload ảnh; user tìm thấy. RSS mở bài ở nguồn gốc khi feed khả dụng.
6. Hỏi chatbot, xem lịch sử; gửi mã quên mật khẩu tới email thử và đổi mật khẩu trong cùng trình duyệt.
7. Nếu có HTTPS/thiết bị phù hợp, thử cài PWA và ngoại tuyến; giải thích PHP/API riêng tư cần mạng.

Chuẩn bị ảnh trang chủ, sức khỏe, đặt lịch/phiếu, quản trị và AI/email theo phạm vi đã thử. Giải thích được PDO, session, CSRF, phân quyền, transaction/mutex, nâng cấp DB và PWA. Đối chiếu [checklist](../../KE_HOACH_HOAN_THIEN.md) trước khi nộp.
