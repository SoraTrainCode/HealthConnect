# HealthConnect — phạm vi hoàn thiện và nghiệm thu

Bản nền Git: `a4c67a7` (RSS của nhóm) cùng các sửa lỗi cài đặt/đặt lịch từ bản trước. Kế hoạch được tổ chức theo hạng mục, không chia ngày.

## 1. Cài đặt và tính đúng của dữ liệu

- [x] Gỡ dòng debug chặn tạo admin; yêu cầu khóa setup riêng, tự khóa setup sau khi có admin.
- [x] Schema cài mới đủ 16 bảng; script nâng cấp có thể chạy lại, giữ tài khoản và lịch hẹn cũ.
- [x] Công cụ CLI kiểm tra PHP, extensions, database, bảng/cột bắt buộc và quyền ghi uploads.
- [x] Sửa URL đăng nhập và lịch khám cũ để chuyển đúng trang.
- [x] Dùng transaction và mutex khi đặt/hủy/cập nhật lịch; chặn trùng lịch và vượt sức chứa.
- [ ] Nhóm chạy nâng cấp trên bản sao database máy mình và kiểm tra dữ liệu cũ trước khi thay bản đang demo.

## 2. Sổ sức khỏe cá nhân

- [x] Dashboard riêng: BMI gần nhất, cân nặng, số lịch sắp tới và số ngày đã ghi nhật ký.
- [x] Biểu đồ 30 lần đo cân nặng, lịch sử BMI, xóa lần đo nhập nhầm.
- [x] Mục tiêu cân nặng do người dùng nhập; sửa hoặc bỏ mục tiêu.
- [x] Nhật ký giấc ngủ, lượng nước, vận động và ghi chú theo ngày; chỉnh sửa/xóa theo chủ sở hữu.
- [x] Thống kê 7 ngày dựa trên dữ liệu thực đã nhập.
- [x] Xuất CSV BMI/nhật ký, chỉ xuất dữ liệu đúng tài khoản và vô hiệu hóa công thức CSV.

## 3. Bác sĩ và lịch khám

- [x] Danh sách bác sĩ, lọc chuyên khoa, giới thiệu, phòng khám.
- [x] Admin tạo/sửa bác sĩ, lịch tuần, ngày nghỉ, ngừng nhận lịch mới.
- [x] Không bỏ giờ làm việc/ngày làm việc đang có lịch hẹn chưa xử lý.
- [x] Đặt lịch trong 30 ngày, chọn đúng giờ bác sĩ nhận; một người/giờ/bác sĩ.
- [x] Một tài khoản không đặt trùng giờ ở hai bác sĩ; tối đa 3 lịch sắp tới.
- [x] Admin xác nhận/hủy/hoàn thành/không đến theo trạng thái và thời gian.
- [x] Mở lại lịch hủy phải kiểm tra lại tài khoản, bác sĩ, giờ làm việc, chỗ trống và giới hạn.
- [x] Nhật ký thay đổi trạng thái, phiếu hẹn, in và tải lịch `.ics`.
- [x] Lịch cũ chưa có bác sĩ vẫn xem/hủy/xử lý được theo quy tắc tiếp nhận chung trước đây.
- [x] Tạo dữ liệu bác sĩ minh họa khi cài mới; máy cũ có nút tạo mẫu trong admin nếu danh sách trống.

## 4. Giao diện điện thoại và PWA

- [x] Giao diện đồng bộ cho trang chủ, sức khỏe, bác sĩ, lịch khám, tài khoản và quản trị.
- [x] Menu thu gọn, thanh điều hướng dưới, form phù hợp màn hình nhỏ, bảng cuộn trong vùng riêng.
- [x] Manifest, icon 192/512, service worker, nút cài khi trình duyệt hỗ trợ.
- [x] Trang ngoại tuyến; chỉ cache danh sách tài nguyên tĩnh công khai, không cache PHP/API/dữ liệu riêng tư.
- [x] Bootstrap được đóng gói tại chỗ, không phụ thuộc CDN để hiển thị giao diện.
- [ ] Nhóm cài thử lên điện thoại thật qua HTTPS và xác nhận phù hợp yêu cầu của giảng viên. PWA không phải APK/IPA hay bản phát hành trên cửa hàng ứng dụng.

## 5. Tin tức, chatbot và email

- [x] Giữ RSS của nhóm; đọc RSS/Atom, ảnh media namespace, cache và dữ liệu dự phòng khi nguồn lỗi.
- [x] Không cho khách ép làm mới liên tục; khóa khi tải lại để tránh nhiều request cùng gọi nguồn.
- [x] Phân trang bài nội bộ bằng SQL; tab tin RSS riêng, trang chủ vẫn có thể trộn hai nguồn.
- [x] Giữ chatbot Ollama, lịch sử hội thoại và kiểm tra quyền; thông báo kết nối rõ hơn.
- [x] Bổ sung PHPMailer SMTP, khóa bí mật cấu hình ngoài Git; reset token hết hạn và chỉ dùng một lần.
- [ ] Nhóm cấu hình tài khoản SMTP thật và thử nhận email thật; cấu hình Ollama/model trên máy chạy PHP và thử câu hỏi thật.

## 6. Bảo vệ dữ liệu, kiểm thử và bàn giao

- [x] CSRF, prepared statements, mật khẩu băm, kiểm tra quyền/chủ sở hữu, không đưa config.local vào gói.
- [x] Trang giải thích dữ liệu, quyền truy cập và giới hạn của bản demo.
- [x] Bộ kiểm thử quy tắc, migration, concurrency, RSS/Atom, HTTP và kiểm tra trình duyệt.
- [x] README chạy nhanh, hướng dẫn database/SMTP/Ollama/PWA, ERD, kịch bản demo và hướng dẫn Git.
- [ ] Nhóm kiểm tra môi trường XAMPP thực tế, sao lưu/khôi phục database, chụp ảnh minh chứng và làm PDF theo yêu cầu thầy.

## Tiêu chí chốt bản nộp

1. Cả hai thành viên cài được dự án từ README mà không cần thư mục runtime của người phát triển.
2. Demo được toàn luồng: đăng ký → ghi sức khỏe → đặt bác sĩ → admin xác nhận → xem phiếu hẹn.
3. Demo tình huống sai: khung giờ đầy, ngày nghỉ, truy cập dữ liệu người khác, CSRF thiếu, nguồn RSS/Ollama không sẵn sàng.
4. Chạy thử điện thoại và cài PWA trên URL HTTPS. Không dùng dữ liệu bệnh nhân thật.
5. Mỗi người giải thích được session, CSRF, PDO, transaction, migration và service worker.
6. Đọc `website-yte-suckhoe/docs/KIEM_TRA_FINAL.md` để phân biệt đã kiểm thử tự động và phần cần cấu hình/nghiệm thu trên máy nhóm. Không coi điểm số hoặc vận hành phòng khám thật là điều đã được chứng nhận.
