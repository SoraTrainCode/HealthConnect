> Ghi chú bản PHP: tài liệu bên dưới là đặc tả gốc. Trạng thái triển khai hiện tại và cách chạy xem `../HUONG_DAN.md`; kết quả kiểm tra xem `KIEM_THU.md`. Các mô tả “đang làm frontend/mock” bên dưới phản ánh giai đoạn trước.

# ĐỀ TÀI CHI TIẾT: Website cung cấp thông tin y tế và hỗ trợ theo dõi sức khỏe cá nhân

## 1. Bối cảnh & mục tiêu cụ thể

Mục tiêu chung được cụ thể hóa thành 3 nhóm mục tiêu đo lường được:

| Nhóm mục tiêu | Mô tả | Tiêu chí hoàn thành |
|---|---|---|
| Tra cứu thông tin | Người dùng tìm và đọc bài viết sức khỏe theo danh mục | Tìm kiếm ra kết quả đúng trong < 2s (dữ liệu mẫu) |
| Tự theo dõi sức khỏe | Tính BMI, lưu lịch sử, xem biểu đồ xu hướng | Lưu và truy xuất lại lịch sử theo tài khoản |
| Tư vấn nhanh (chatbot) | Trả lời câu hỏi sức khỏe phổ thông, luôn khuyến cáo đi khám | Phản hồi có disclaimer y tế ở mọi câu trả lời |

## 2. Sơ đồ chức năng (Sitemap)

```
Website
├── Khách (chưa đăng nhập)
│   ├── Trang chủ
│   ├── Tin tức y tế (danh sách + tìm kiếm + lọc danh mục)
│   ├── Chi tiết bài viết
│   ├── Công cụ BMI (dùng thử, không lưu lịch sử)
│   ├── Chatbot (hỏi nhanh, không lưu lịch sử chat)
│   ├── Đăng ký / Đăng nhập
├── Người dùng (đã đăng nhập)
│   ├── (Tất cả chức năng của khách)
│   ├── Hồ sơ cá nhân (xem/sửa thông tin)
│   ├── Lịch sử đo BMI (lưu theo tài khoản, biểu đồ xu hướng)
│   ├── Lịch sử hội thoại chatbot
├── Quản trị viên
│   ├── Dashboard (thống kê nhanh)
│   ├── Quản lý bài viết (CRUD, ẩn/hiện)
│   ├── Quản lý danh mục (CRUD)
│   ├── Quản lý người dùng (xem, khóa/mở tài khoản)
```

## 3. Đặc tả chức năng chi tiết

### 3.1 Tài khoản
- **Đăng ký:** họ tên, email (unique), mật khẩu (>=8 ký tự, hash bcrypt), số điện thoại (tùy chọn), ngày sinh, giới tính.
- **Đăng nhập:** email + mật khẩu; giới hạn 5 lần sai → khóa tạm 5 phút (chống brute-force).
- **Cập nhật hồ sơ:** đổi họ tên, avatar, ngày sinh, giới tính, chiều cao mặc định (để auto-fill công cụ BMI).
- **Quên mật khẩu:** gửi link đặt lại qua email (token hết hạn 15 phút).

### 3.2 Tin tức y tế
- Danh sách bài viết: phân trang, sắp xếp theo mới nhất, lọc theo danh mục (Dinh dưỡng, Mẹo vặt sức khỏe, Bệnh thường gặp, Vận động, Tâm lý...).
- Tìm kiếm theo tiêu đề/nội dung (full-text search MySQL hoặc LIKE cho phạm vi đồ án).
- Trang chi tiết: nội dung định dạng rich-text, ảnh minh họa, thời gian đăng, lượt xem, bài viết liên quan cùng danh mục.

### 3.3 Công cụ theo dõi sức khỏe (BMI)
- Nhập chiều cao (cm), cân nặng (kg) → tính `BMI = cân nặng / (chiều cao(m))²`.
- Phân loại theo thang WHO (dành riêng ngưỡng châu Á nếu muốn nâng cao): Gầy / Bình thường / Thừa cân / Béo phì.
- Nếu đã đăng nhập: lưu lại mỗi lần đo (ngày đo, chiều cao, cân nặng, BMI, ghi chú) → hiển thị bảng lịch sử + biểu đồ đường xu hướng cân nặng/BMI theo thời gian.
- Nếu là khách: chỉ tính tạm thời, có nút "Đăng nhập để lưu lịch sử".

### 3.4 Chatbot hỗ trợ
- Giao diện khung chat nổi (floating widget) ở mọi trang.
- Gửi câu hỏi → gọi API bên thứ 3 (vd. OpenAI/Claude API) qua backend PHP (để giấu API key) → trả lời.
- **Bắt buộc:** mọi câu trả lời kèm dòng khuyến cáo "Thông tin chỉ mang tính tham khảo, vui lòng đến cơ sở y tế để được chẩn đoán chính xác."
- Chặn/lọc các câu hỏi ngoài phạm vi sức khỏe (dùng prompt hệ thống giới hạn chủ đề).
- Người dùng đã đăng nhập: lưu lịch sử hội thoại theo phiên.

### 3.5 Quản trị viên
- **Quản lý bài viết:** CRUD, upload ảnh đại diện bài viết, soạn nội dung bằng rich-text editor (vd. TinyMCE/CKEditor), ẩn/hiện (trạng thái draft/published).
- **Quản lý danh mục:** CRUD danh mục, không cho xóa danh mục đang có bài viết.
- **Quản lý người dùng:** xem danh sách, tìm kiếm, khóa/mở khóa tài khoản; không cho sửa mật khẩu người dùng khác (chỉ reset).

## 4. Thiết kế cơ sở dữ liệu (đề xuất bảng chính)

```
users(id, full_name, email, password_hash, phone, birthday, gender,
      default_height, avatar_url, role ENUM('user','admin'),
      status ENUM('active','locked'), created_at)

categories(id, name, slug, description)

articles(id, category_id FK, title, slug, summary, content, thumbnail_url,
         status ENUM('draft','published'), view_count, author_id FK users,
         created_at, updated_at)

bmi_logs(id, user_id FK, height_cm, weight_kg, bmi_value, classification,
         note, measured_at)

chat_messages(id, user_id FK nullable, session_id, role ENUM('user','bot'),
              content, created_at)
```

## 5. Kiến trúc & luồng xử lý tổng quát

```
[Trình duyệt: HTML/CSS/JS + Bootstrap]
        │  fetch() / XHR (AJAX, JSON)
        ▼
[Back-end: PHP - REST-like endpoints, ví dụ /api/articles.php]
        │
        ├──► MySQL/SQL Server (dữ liệu bài viết, người dùng, BMI logs)
        └──► Gọi API bên thứ 3 (chatbot) qua cURL từ PHP, giấu API key
              trong biến môi trường / file config không public
```

Front-end giao tiếp back-end qua AJAX (fetch API), trả JSON, để trải nghiệm mượt (không reload trang), phù hợp với các phần: tìm kiếm bài viết, lưu log BMI, gửi/nhận tin nhắn chatbot.

## 6. Kế hoạch triển khai đề xuất (5 giai đoạn)

1. **Thiết kế & làm giao diện tĩnh (front-end)**, *đang thực hiện ở bước này*: dựng toàn bộ trang HTML/CSS/JS bằng dữ liệu giả (mock data), chưa nối back-end.
2. Thiết kế & khởi tạo CSDL MySQL, viết API PHP cơ bản (CRUD bài viết, auth).
3. Nối front-end với API thật qua AJAX, thay dữ liệu giả bằng dữ liệu thật.
4. Tích hợp chatbot (API bên thứ 3) qua PHP proxy.
5. Kiểm thử, tối ưu responsive, viết báo cáo & hướng dẫn sử dụng.

## 7. Phạm vi mock data ở bước front-end hiện tại

Vì back-end PHP/MySQL chưa được nối, phần code front-end bên dưới dùng **dữ liệu mẫu (mock JSON) nhúng trong JavaScript** để giao diện chạy được độc lập ngay trong trình duyệt. Khi làm back-end, chỉ cần thay các hàm `fetch(...)` giả lập bằng lời gọi API PHP thật, giữ nguyên cấu trúc HTML/CSS.
