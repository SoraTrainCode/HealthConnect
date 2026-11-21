# HealthConnect — Website thông tin y tế, sổ sức khỏe và đặt lịch khám

Đồ án môn **Lập trình Web và Ứng dụng (503073)**. HealthConnect là website y tế chạy hoàn chỉnh trên nền web thật, gồm cổng thông tin sức khỏe, hồ sơ sức khỏe cá nhân, đặt lịch khám với bác sĩ và trợ lý AI tư vấn sức khỏe.

🌐 **Địa chỉ truy cập (đang hoạt động): https://healthconnect172.rf.gd**

- Mã nguồn: https://github.com/SoraTrainCode/HealthConnect
- Công nghệ: **PHP thuần 8.1+** (không dùng framework backend), **MySQL/MariaDB**, HTML/CSS/JavaScript, Bootstrap, PHPMailer.

---

## 1. Tính năng

### Dành cho người dùng
- **Trang chủ & bài viết y tế:** bài viết theo danh mục do quản trị viên đăng, trang chi tiết bài viết.
- **Tin tức sức khỏe tự động:** tổng hợp tin mới mỗi ngày từ 3 nguồn báo chí y tế (Tuổi Trẻ Sức khỏe, Thanh Niên Sức khỏe, Sức khỏe & Đời sống) qua RSS.
- **Danh sách bác sĩ:** xem bác sĩ theo chuyên khoa, lịch làm việc theo ngày và các khung giờ còn trống.
- **Đặt lịch khám trực tuyến:** chọn bác sĩ → chọn ngày → chọn giờ trống → nhập thông tin → nhận phiếu hẹn (có thể tải file lịch .ICS về máy). Xem lại, hủy lịch tại mục Lịch khám của tôi.
- **Trợ lý AI (chatbot):** hỏi đáp sức khỏe bằng ngôn ngữ tự nhiên, có ghi nhớ ngữ cảnh hội thoại và xem lại lịch sử trò chuyện.

### Sổ sức khỏe cá nhân (yêu cầu đăng nhập)
- **Công cụ BMI:** tính chỉ số BMI, phân loại thể trạng theo mốc châu Á, lưu lịch sử đo và vẽ biểu đồ xu hướng.
- **Nhật ký thói quen hằng ngày:** ghi giờ ngủ, lượng nước uống, phút vận động theo ngày; thống kê tổng hợp 7 ngày gần nhất.
- **Mục tiêu cân nặng:** đặt cân nặng mục tiêu, hệ thống tính chênh lệch với lần đo gần nhất.
- **Biểu đồ cân nặng** theo các lần đo và **xuất dữ liệu ra CSV**.

### Tài khoản & bảo mật
- Đăng ký / đăng nhập / đăng xuất, phân quyền **người dùng** và **quản trị viên (admin)**.
- Mật khẩu được băm bằng `password_hash` (bcrypt), không lưu mật khẩu thô.
- Quên mật khẩu bằng mã xác nhận gửi qua email (PHPMailer/SMTP).
- Chống CSRF bằng token cho mọi form, ghi nhận các lần đăng nhập sai để hạn chế dò mật khẩu, phiên đăng nhập tự vô hiệu khi mật khẩu thay đổi.

### Quản trị (admin)
- Bảng điều khiển thống kê tổng quan.
- Quản lý người dùng, bác sĩ (lịch làm việc, ngày nghỉ), danh mục, bài viết.
- Quản lý lịch khám: xác nhận / hủy lịch, mọi thay đổi trạng thái đều được ghi vào nhật ký sự kiện của lịch hẹn.

### Khác
- Giao diện responsive trên điện thoại/máy tính.
- **PWA:** cài được như ứng dụng trên điện thoại, có trang ngoại tuyến khi mất mạng.

---

## 2. Cách hoạt động

### Kiến trúc tổng thể

HealthConnect chạy theo mô hình **client–server** cổ điển của web động:

```
Trình duyệt (HTML/CSS/JS, PWA)
        │  HTTP request
        ▼
Máy chủ PHP (Apache/XAMPP hoặc hosting)
  ├─ includes/bootstrap.php  → khởi tạo session, kết nối CSDL (PDO),
  │                            hàm dùng chung: CSRF, phân quyền, BMI...
  ├─ Các trang *.php         → xử lý nghiệp vụ + render HTML phía server
  ├─ api/tro-ly.php          → endpoint chatbot (JSON)
  └─ includes/news_sync.php  → đồng bộ tin RSS
        │
        ├─► MySQL (16 bảng) — dữ liệu người dùng, lịch hẹn, sức khỏe...
        ├─► Groq API        — mô hình AI cho chatbot
        └─► RSS các báo     — nguồn tin tức sức khỏe
```

Mỗi trang PHP khi được gọi sẽ: nạp `bootstrap.php` (mở session, nối CSDL bằng PDO kèm prepared statement) → kiểm tra quyền → truy vấn dữ liệu → render HTML trả về trình duyệt. Không có bước build frontend; sửa file PHP là trang thay đổi ngay.

### Luồng đặt lịch khám (chống trùng lịch)

1. Người dùng chọn bác sĩ và ngày; hệ thống lấy khung giờ từ bảng `doctor_schedules` theo thứ trong tuần, loại ngày bác sĩ nghỉ (`doctor_days_off`) và các giờ đã có người đặt.
2. Khi gửi đặt lịch, toàn bộ kiểm tra chạy trong **một transaction có khóa hàng mutex** (`appointment_mutex ... FOR UPDATE`) để hai yêu cầu đồng thời không thể cùng lấy một giờ: kiểm tra tài khoản đang hoạt động, giới hạn số lịch sắp tới, trùng lịch của chính người dùng, và giờ đó còn trống hay không.
3. Lịch hẹn có vòng đời trạng thái rõ ràng: `pending` (chờ xác nhận) → `confirmed` (đã xác nhận) / `cancelled` (đã hủy); mỗi lần đổi trạng thái ghi một dòng vào `appointment_events` để truy vết.

### Luồng chatbot AI

1. Người dùng gửi câu hỏi → JavaScript gọi `api/tro-ly.php`.
2. PHP lấy **10 tin nhắn gần nhất** của phiên chat (lưu trong `chat_messages` nếu đã đăng nhập, hoặc trong session nếu là khách) làm ngữ cảnh, kèm một *system prompt* quy định trợ lý chỉ tư vấn sức khỏe tham khảo.
3. PHP gọi **Groq API** (mô hình `openai/gpt-oss-120b`, giao diện tương thích OpenAI Chat Completions) bằng cURL — khóa API chỉ nằm ở cấu hình phía máy chủ, không lộ ra trình duyệt.
4. Câu trả lời được lưu lại vào CSDL (với người dùng đã đăng nhập), tự động chèn dòng miễn trừ "thông tin chỉ mang tính tham khảo..."; nếu API chạm giới hạn miễn phí (HTTP 429) hoặc lỗi, hệ thống trả thông báo thân thiện thay vì lộ lỗi kỹ thuật.
   Hệ thống cấu hình được 3 nhà cung cấp AI: **Groq** (đang dùng trên bản online), Gemini hoặc Ollama chạy local.

### Luồng tin tức RSS

1. `news_sync.php` tải RSS (chỉ HTTPS, giới hạn 2 MB) từ 3 nguồn báo, phân tích XML bằng SimpleXML, gộp tối đa 100 tin mới nhất.
2. Kết quả được **cache ra file JSON** với thời gian sống 30–60 phút (có khóa file chống nhiều yêu cầu tải trùng cùng lúc) để trang tải nhanh và không phụ thuộc tốc độ của báo nguồn.
3. Nếu nguồn lỗi, hệ thống dùng lại cache cũ (đánh dấu stale) thay vì để trang tin trống.

### Công cụ BMI

- Công thức: `BMI = cân nặng (kg) / chiều cao² (m)`, làm tròn 1 chữ số thập phân; kiểm tra miền giá trị hợp lệ (cao 80–250 cm, nặng 20–300 kg).
- Phân loại theo mốc dành cho người châu Á: **< 18,5** thiếu cân · **18,5–22,9** bình thường · **23–27,4** thừa cân · **≥ 27,5** béo phì.
- Người dùng đã đăng nhập được lưu mỗi lần đo vào `bmi_logs`, xem lại 100 lần đo gần nhất và biểu đồ xu hướng 20 lần đo vẽ bằng Canvas.

---

## 3. Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Ngôn ngữ backend | PHP 8.1+ thuần (PDO + prepared statements) |
| Cơ sở dữ liệu | MySQL 8 / MariaDB 10.4+ (16 bảng) |
| Giao diện | HTML, CSS, JavaScript, Bootstrap |
| Chatbot AI | Groq API — mô hình `openai/gpt-oss-120b` (cấu hình được Gemini/Ollama) |
| Tin tức | RSS + SimpleXML, cache file JSON |
| Email | PHPMailer qua SMTP (mã đặt lại mật khẩu) |
| PWA | Web App Manifest + Service Worker, trang ngoại tuyến |
| Triển khai | Hosting InfinityFree (Apache + MySQL), tên miền phụ `healthconnect172.rf.gd` |

---

## 4. Cấu trúc thư mục chính

```
website-yte-suckhoe/
├─ index.php, tin-tuc.php, bac-si.php   Trang chủ, tin tức, danh sách bác sĩ
├─ dat-lich.php, lich-kham.php         Đặt lịch, lịch của tôi, phiếu hẹn
├─ bmi.php, suc-khoe.php               BMI, sổ sức khỏe, xuất CSV
├─ dang-ky.php, dang-nhap.php, ...     Tài khoản, quên/đặt lại mật khẩu
├─ lich-su-tro-chuyen.php              Lịch sử chat với trợ lý AI
├─ admin/                              Khu vực quản trị
├─ api/                                Endpoint chatbot và tin tức
├─ includes/                           bootstrap, đặt lịch, RSS, mailer, layout
├─ assets/                             CSS, JS (trợ lý chat), biểu tượng PWA
├─ database/                           schema.sql (16 bảng), dữ liệu mẫu
├─ sw.js, manifest.webmanifest         PWA
└─ config.local.php                    Cấu hình riêng từng máy (không commit)
```

## 5. Cơ sở dữ liệu (16 bảng)

`users`, `categories`, `articles`, `doctors`, `doctor_schedules`, `doctor_days_off`, `appointments`, `appointment_mutex`, `appointment_events`, `bmi_logs`, `wellness_logs`, `health_goals`, `chat_sessions`, `chat_messages`, `login_attempts`, `password_resets`.

## 6. Chạy thử trên máy cá nhân (XAMPP)

Yêu cầu: PHP 8.1+, MySQL 8/MariaDB 10.4+, các extension `pdo_mysql`, `mbstring`, `curl`, `dom`, `fileinfo`, `SimpleXML`, `openssl` (kiểm tra nhanh bằng `php tools/doctor.php` trong thư mục ứng dụng).

1. Copy thư mục `website-yte-suckhoe` vào `C:\xampp\htdocs\HealthConnect` (bên trong phải có ngay `index.php`).
2. Bật Apache và MySQL trong XAMPP Control Panel. Import `database/schema.sql` **một lần cho database mới** (tạo đủ 16 bảng). Nếu DB đã có dữ liệu, dùng `database/upgrade_final.sql` thay thế.
3. Copy `config.example.php` thành `config.local.php`, điền thông tin DB, đặt `setup_key` riêng ít nhất 16 ký tự và `app_url=http://localhost/HealthConnect`. Muốn chatbot hoạt động, điền thêm khóa AI (Groq) vào cấu hình này.
4. Mở `http://localhost/HealthConnect/setup.php`, nhập khóa setup, tạo tài khoản admin và chọn nạp dữ liệu mẫu (bác sĩ, bài viết). Cổng đăng nhập admin: `dang-nhap.php?role=admin`.

> Không mở file PHP bằng Live Server hay nhấp đúp file — phải chạy qua Apache theo địa chỉ `http://localhost/...` ở trên.

## 7. Triển khai online

Bản đang chạy tại **https://healthconnect172.rf.gd** (InfinityFree, Apache + MySQL). Khi đưa lên hosting cần lưu ý:

- Cấu hình DB trên hosting khác local (host, tên DB, tài khoản do nhà cung cấp cấp) — chỉ sửa trong `config.local.php` trên máy chủ, không commit file này lên GitHub.
- WAF của InfinityFree chặn các URL chứa chữ `chat`, nên trên hosting các file chatbot được đổi tên: `tro-ly.js`, `api/tro-ly.php`, `api/tro-ly-moi.php`, `lich-su-tro-chuyen.php`. Khi sửa tính năng chat phải giữ các tên này, nếu không chatbot sẽ bị chặn 403.

## 8. Nhóm thực hiện

Đồ án môn Lập trình Web và Ứng dụng (503073):

- **Lê Bá Khánh Bình** — MSSV 52400172
- **Vũ Đức Trọng** — MSSV 52400249

- **TÀI KHOẢN ADMIN DEMO (cho giảng viên đăng nhập kiểm tra):**
- **Email: vuductrong098@gmail.com — Mật khẩu: 123123123**

Repo quản lý bằng Git/GitHub theo nhánh và Pull Request, không force-push, không commit `config.local.php` hay khóa API lên kho mã nguồn.
