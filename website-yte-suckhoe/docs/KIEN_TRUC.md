# Kiến trúc HealthConnect

## Frontend, backend và database nối với nhau thế nào?

```mermaid
flowchart LR
    UI["Trình duyệt: HTML/CSS/JavaScript"] <-->|"HTTP: form GET/POST hoặc fetch JSON"| PHP["Backend PHP"]
    PHP <-->|"PDO + pdo_mysql: truy vấn SQL"| DB[(MySQL / MariaDB)]
    PHP <-->|"cURL: HTTP/JSON"| AI["Groq / Gemini / Ollama"]
    PHP -->|"PHPMailer: SMTP"| MAIL["Máy chủ email"]
```

PHP chạy trên server và trả HTML/JSON về trình duyệt. Trình duyệt không kết nối database trực tiếp. `includes/bootstrap.php` nạp `config.local.php` và tạo kết nối PDO từ host/port/database/user/password; `includes/layout.php` tạo HTML chung, còn CSS/JavaScript xử lý hiển thị và tương tác.

| Thành phần | Công nghệ đang dùng |
|---|---|
| Frontend | HTML, CSS, JavaScript và Bootstrap |
| Backend | PHP thuần, không dùng Laravel |
| Frontend ↔ backend | HTTP: form GET/POST; `fetch()` nhận JSON cho chat/API |
| Backend ↔ database | PDO với driver `pdo_mysql`, truy vấn SQL tới MySQL/MariaDB |
| Web server khi dùng XAMPP | Apache chạy PHP; MySQL là dịch vụ database riêng |

Ví dụ đặt lịch: form ở `dat-lich.php` gửi POST kèm CSRF → PHP kiểm tra tài khoản/dữ liệu → `appt_book()` dùng PDO/transaction để ghi MySQL → chuyển tới `phieu-hen.php` và render kết quả. Chat dùng `fetch()` trong `assets/js/chatbot-php.js` tới `api/chat.php`; key AI chỉ được dùng phía PHP.

## Dịch vụ tích hợp

- Chat: trình duyệt → `api/chat.php` → Groq/Gemini/Ollama → thêm khuyến cáo → lưu DB hoặc guest session → JSON. `ai_provider` rỗng dùng Ollama; cloud lỗi không tự đổi provider.
- RSS: PHP tải HTTPS/XML → làm sạch → cache tệp theo TTL và khóa đồng bộ → trang tin/API JSON.
- Quên mật khẩu: PHPMailer gửi mã 6 số; DB giữ bản băm HMAC, session giữ challenge. Mã hạn 15 phút, tối đa 5 lần thử và dùng một lần.
- `api/new-chat.php` kiểm tra POST/CSRF và xóa guest history; giao diện đặt `session_id=0`. Phiên DB mới được tạo khi có đáp án hợp lệ tiếp theo.

## Thành phần

| Vị trí trong ứng dụng | Trách nhiệm |
|---|---|
| `includes/bootstrap.php`, `includes/layout.php` | Cấu hình, PDO, xác thực, CSRF, sanitize và giao diện chung |
| `includes/appointments.php` | Quy tắc đặt lịch, transaction, khóa và nhật ký trạng thái |
| `suc-khoe.php`, `bmi.php`, `xuat-du-lieu.php` | Dữ liệu sức khỏe theo chủ sở hữu |
| `bac-si.php`, `dat-lich.php`, `phieu-hen.php` | Bác sĩ, đặt lịch, phiếu/ICS |
| `admin/` | Quản trị, kiểm tra role phía PHP |
| `includes/news_sync.php`, `includes/mailer.php`, `api/` | RSS, SMTP và các endpoint JSON |
| `manifest.webmanifest`, `sw.js` | PWA và cache tĩnh |
| `database/schema.sql`, `database/upgrade_final.sql` | Cài mới và nâng cấp |

## Dữ liệu chính

```mermaid
erDiagram
    users ||--o{ bmi_logs : measures
    users ||--o{ wellness_logs : records
    users ||--o| health_goals : sets
    users ||--o{ password_resets : requests
    users ||--o{ chat_sessions : owns
    chat_sessions ||--o{ chat_messages : contains
    users ||--o{ articles : writes
    categories ||--o{ articles : groups
```

```mermaid
erDiagram
    users ||--o{ appointments : books
    doctors ||--o{ doctor_schedules : works
    doctors ||--o{ doctor_days_off : rests
    doctors o|--o{ appointments : receives
    appointments ||--o{ appointment_events : records
```

Các đường trong ERD thể hiện quan hệ dữ liệu. `appointments.user_id`, `appointments.doctor_id` và `appointment_events.actor_id` là quan hệ logic, chưa có FK trong schema. `doctor_id` cho phép NULL để giữ lịch cũ. `login_attempts` lưu bộ đếm theo khóa băm; `appointment_mutex` có hàng `id=1` dùng để khóa giao dịch.

Mọi đường ghi lịch lấy mutex bằng `SELECT ... FOR UPDATE` trước khi đếm/kiểm tra/chèn rồi commit; lỗi thì rollback. Quy mô đồ án dùng khóa chung để ưu tiên tính đúng; mở rộng tải cần đo đạc và đánh giá khóa theo bác sĩ/khung giờ.

## Bảo vệ dữ liệu

PDO prepared statements, mật khẩu băm, CSRF, role/owner checks, escape/sanitize và kiểm tra ảnh được thực hiện ở server. Đổi mật khẩu làm phiên cũ mất hiệu lực. Cấu hình/key nằm ngoài Git; log lỗi AI che key, giới hạn chống spam hiện theo session. Service Worker không cache PHP/API/uploads. Xem [thư viện và nguồn dữ liệu](THIRD_PARTY.md).
