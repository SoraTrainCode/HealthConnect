# HealthConnect

Website cung cấp thông tin y tế và hỗ trợ theo dõi sức khỏe cá nhân. Đồ án môn học, xây dựng bằng HTML, CSS, JavaScript, Bootstrap ở phần giao diện, dự kiến PHP và MySQL ở phần backend.

> Trạng thái hiện tại: đã hoàn thành giao diện FrontEnd (chạy độc lập bằng dữ liệu mẫu), đã thiết kế sơ bộ CSDL. Phần BackEnd PHP kết nối MySQL đang được tiếp tục triển khai.

## 1. Giới thiệu

HealthConnect giúp người dùng phổ thông:

- Tra cứu kiến thức chăm sóc sức khỏe cơ bản theo từng chuyên mục.
- Tự tính và lưu lại lịch sử chỉ số BMI, xem xu hướng theo thời gian.
- Đặt câu hỏi sức khỏe thông thường cho một hộp thoại tư vấn tự động, luôn kèm khuyến cáo nên đến cơ sở y tế để được thăm khám chính xác.

Quản trị viên có khu vực riêng để quản lý bài viết, danh mục và người dùng của hệ thống.

## 2. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Giao diện, FrontEnd | HTML5, CSS3, JavaScript thuần, Bootstrap 5 |
| Máy chủ, BackEnd | PHP, đang triển khai |
| Cơ sở dữ liệu | MySQL, xem `database/schema.sql` |
| Font chữ | Times New Roman |

Ở giai đoạn hiện tại, FrontEnd chạy độc lập bằng dữ liệu mẫu nhúng trong JavaScript (`assets/js/mock-data.js`) và lưu tạm bằng `localStorage`, để có thể xem và nộp giao diện trước khi nối BackEnd thật.

## 3. Cấu trúc thư mục

Mã nguồn nằm trong thư mục `website-yte-suckhoe/`:

```
website-yte-suckhoe/
├── index.html                 Trang chủ
├── tin-tuc.html                Danh sách tin tức y tế, tìm kiếm, lọc danh mục
├── chi-tiet-bai-viet.html      Chi tiết một bài viết
├── bmi.html                    Công cụ tính và theo dõi chỉ số BMI
├── dang-nhap.html               Đăng nhập
├── dang-ky.html                 Đăng ký tài khoản
├── tai-khoan.html               Hồ sơ cá nhân, lịch sử đo BMI
├── admin/
│   ├── dashboard.html          Tổng quan hệ thống
│   ├── bai-viet.html           Quản lý bài viết, CRUD
│   ├── danh-muc.html           Quản lý danh mục
│   └── nguoi-dung.html         Quản lý người dùng
├── assets/
│   ├── css/style.css           Toàn bộ giao diện tùy chỉnh
│   └── js/
│       ├── mock-data.js        Dữ liệu bài viết, danh mục mẫu
│       ├── components.js       Header, footer dùng chung, tiện ích localStorage an toàn
│       ├── auth.js             Đăng ký, đăng nhập, mô phỏng
│       ├── bmi.js               Tính toán, lưu lịch sử BMI
│       ├── chatbot.js          Widget hộp thoại hỗ trợ
│       └── admin.js            Khung sườn khu quản trị
├── database/
│   └── schema.sql              Script tạo cơ sở dữ liệu MySQL
└── docs/
    └── DE-TAI-CHI-TIET.md      Tài liệu chi tiết hóa đề tài, sitemap, đặc tả chức năng
```

## 4. Chạy thử FrontEnd

Vì các trang gọi lẫn nhau bằng đường dẫn tương đối và dùng `localStorage`, cần mở qua một máy chủ local, không mở trực tiếp bằng cách nhấp đúp vào file.

Chọn một trong hai cách, chạy trong thư mục `website-yte-suckhoe`:

```bash
# Cách 1, dùng PHP có sẵn
php -S localhost:8000

# Cách 2, dùng Python
python3 -m http.server 8000
```

Sau đó mở trình duyệt tại `http://localhost:8000/index.html`.

## 5. Cơ sở dữ liệu

Script tạo cơ sở dữ liệu nằm ở `database/schema.sql`, gồm các bảng chính:

- `users`, tài khoản người dùng và quản trị viên, phân quyền qua cột `role`.
- `categories`, danh mục bài viết.
- `articles`, bài viết tin tức y tế, liên kết với `categories` và `users`.
- `bmi_logs`, lịch sử đo chỉ số BMI của từng người dùng.
- `chat_sessions` và `chat_messages`, lịch sử hội thoại với chatbot.

Import file này vào MySQL, ví dụ qua phpMyAdmin, để khởi tạo cơ sở dữ liệu trước khi viết các API PHP kết nối tới.

## 6. Việc cần làm tiếp theo

- Viết các API PHP, đăng ký, đăng nhập, CRUD bài viết, danh mục, người dùng, lưu và truy xuất lịch sử BMI.
- Thay các hàm giả lập trong `mock-data.js`, `auth.js`, `bmi.js` bằng lời gọi `fetch` tới API PHP thật.
- Tích hợp chatbot với một API bên thứ ba thông qua PHP, để giấu khóa API phía máy chủ.
- Viết file báo cáo đồ án, mô tả chức năng, cơ sở dữ liệu, phân công thành viên.

## 7. Thành viên nhóm

| Họ tên | Vai trò |
|---|---|
| ... | ... |
| ... | ... |
| ... | ... |

## 8. Lưu ý

Nội dung y tế trong website chỉ mang tính chất tham khảo, không thay thế cho chẩn đoán, tư vấn hoặc điều trị y khoa chuyên nghiệp.
