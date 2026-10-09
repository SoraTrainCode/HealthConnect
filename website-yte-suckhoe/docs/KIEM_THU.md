# Kết quả kiểm tra bản PHP

- PHP 8.3.6, MariaDB 10.11.14; chạy trên database thử nghiệm riêng.
- 46 kiểm tra HTTP/database đạt. Chi tiết trong `KET_QUA_KIEM_TRA.json`.
- Kiểm tra cú pháp toàn bộ 22 file PHP trong gói: đạt.
- Đã kiểm tra tạo admin, seed, đăng ký, đăng nhập, CSRF, quyền admin, BMI theo tài khoản, hồ sơ, CRUD bài viết/danh mục, rich-text lọc HTML, khóa tài khoản, đổi mật khẩu, token reset dùng một lần, cô lập lịch sử chat, upload PNG và từ chối file giả ảnh.
- Chatbot dùng HTTP server mô phỏng Ollama để kiểm tra payload, lưu/đọc/xóa lịch sử và thông báo khi dịch vụ offline. Chưa kiểm tra chất lượng trả lời của model thật.
- Chưa kiểm tra giao thư email thật; cần cấu hình mail transport.
- Chưa kiểm tra trực quan bằng trình duyệt vì tải Chromium thất bại. Cần mở bằng XAMPP để rà soát bố cục desktop/mobile, CDN Bootstrap và thao tác trình soạn thảo.
- Không có tài khoản hoặc dữ liệu kiểm thử riêng trong gói ZIP. Admin do người cài tự tạo.
