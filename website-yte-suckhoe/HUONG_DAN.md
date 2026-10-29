# Hướng dẫn chạy HealthConnect

## 1. Yêu cầu

PHP 8.1 trở lên; MySQL 8 hoặc MariaDB 10.4 trở lên; các extension pdo_mysql, mbstring, curl, dom, fileinfo, SimpleXML, openssl. XAMPP có thể cung cấp PHP/Apache/MariaDB. Bootstrap và PHPMailer đã nằm trong source, không cần npm/composer để chạy web.

Mọi trang chính chạy qua PHP. HTML/CSS/JS là giao diện được PHP trả về trình duyệt; không chạy một frontend server riêng. Các file `.html` cũ chỉ chuyển sang trang PHP tương ứng.

## 2. Cài mới

1. Giải nén source vào thư mục riêng hoặc dùng repo đã clone. Không tạo thêm repo lồng vào repo hiện có.
2. Bật MySQL và Apache trong XAMPP, vào phpMyAdmin.
3. Import `database/schema.sql` một lần. Database mặc định tên `healthconnect_moi`, có 16 bảng. Nếu đã có DB cùng tên và có dữ liệu, dùng quy trình nâng cấp ở mục 3.
4. Copy `config.example.php` thành `config.local.php`. Chỉnh db_host, db_port, db_name, db_user, db_pass theo XAMPP.
5. Đặt setup_key thành chuỗi riêng dài ít nhất 16 ký tự. Đặt app_url đúng cách chạy; ví dụ server PHP bên dưới là `http://localhost:8000`.
6. Mở CMD tại thư mục `website-yte-suckhoe`:

```bat
C:\xampp\php\php.exe tools\doctor.php
C:\xampp\php\php.exe -S localhost:8000 -t . tools/router.php
```

7. Mở `http://localhost:8000/setup.php`. Nhập khóa vừa đặt và tài khoản admin tự chọn. Chọn dữ liệu mẫu để có bài viết và 3 bác sĩ giả lập với lịch tuần.
8. Setup tự khóa khi đã có admin. Có thể xóa/vô hiệu hóa setup.php trên hosting. Đăng nhập admin ở `dang-nhap.php?role=admin`, user ở `dang-nhap.php?role=user`.
9. Mở `index.php`, đăng ký một tài khoản người dùng để demo. Không có mật khẩu admin dùng chung/hardcode.

Chạy bằng Apache: đặt source trong `C:\xampp\htdocs\HealthConnect`, mở `http://localhost/HealthConnect/website-yte-suckhoe/`; app_url phải trỏ đúng địa chỉ đó. Nếu Apache dùng 8080, thêm `:8080`. Với cách này không cần chạy `php -S`.

## 3. Nâng cấp bản đã có dữ liệu

1. Export database đang dùng trong phpMyAdmin, giữ file sao lưu ngoài thư mục public và ngoài Git.
2. Áp dụng bản code mới theo `HUONG_DAN_GIT.md`; giữ nguyên config.local.php và ảnh uploads của máy cậu.
3. Trong phpMyAdmin **chọn đúng database của dự án**, import `database/upgrade_final.sql`. Script không chọn tên DB cố định, không DROP/TRUNCATE dữ liệu; có thể chạy lại.
4. Script thêm bác sĩ, lịch tuần, ngày nghỉ, nhật ký lịch hẹn, nhật ký sức khỏe, mục tiêu và cột doctor_id. Lịch cũ giữ doctor_id=NULL và hiển thị “Tiếp nhận chung”.
5. Chạy `php tools/doctor.php`. Nếu chưa có bác sĩ, đăng nhập admin → Bác sĩ → “Tạo 3 hồ sơ minh họa”, hoặc nhập thủ công. Chỉ tạo mẫu khi danh sách bác sĩ đang trống.
6. Kiểm tra tài khoản, bài viết và lịch cũ trước khi tiếp tục demo. Nếu lỗi nâng cấp, không import schema cài mới đè lên DB hiện tại; kiểm tra thông báo SQL và bản sao lưu.

## 4. Sử dụng các chức năng

**Người dùng:** đăng ký/đăng nhập → Sức khỏe → ghi BMI, nhật ký theo ngày, mục tiêu; xem lịch sắp tới; tải CSV. Nhật ký cùng ngày được cập nhật thay vì tạo trùng. Có thể sửa/xóa các bản ghi của mình.

**Đặt lịch:** xem Bác sĩ → chọn ngày trong 30 ngày → chọn giờ còn chỗ → thông tin người khám → gửi. Mỗi bác sĩ có một chỗ/khung giờ; mỗi user tối đa 3 lịch tương lai. Một user không đặt trùng giờ ở hai bác sĩ. Ngày nghỉ không nhận lịch. Yêu cầu ban đầu là “Chờ xác nhận”.

**Admin:** Quản trị → Bác sĩ để cấu hình ngày/giờ và ngày nghỉ; Lịch khám để xem tổng quan ngày sắp tới, lọc ngày/trạng thái/bác sĩ, xác nhận hoặc hủy. Không thay lịch tuần nếu bỏ giờ đã có lịch tương lai chưa xử lý. Ngừng nhận lịch mới giữ nguyên lịch đã đặt; cần chủ động xử lý chúng.

**Trạng thái:** pending → confirmed/cancelled; confirmed → done/no_show/cancelled. done/no_show chỉ sau giờ hẹn. cancelled → pending chỉ nếu còn ở tương lai và qua lại đầy đủ kiểm tra. done/no_show không mở lại. User chỉ hủy lịch của mình khi chưa qua giờ và còn pending/confirmed.

**Phiếu hẹn:** xem chi tiết/lịch sử, in, tải ICS. File ICS là bản chụp lúc tải, không tự đồng bộ với web; cần xem trạng thái web trước khi đến. Lịch khám, bác sĩ và cơ sở trong dữ liệu mẫu là mô phỏng.

## 5. Email reset bằng SMTP

PHPMailer đã kèm trong `includes/vendor/PHPMailer`. Sửa config.local, ví dụ cấu trúc:

```php
'mail_transport' => 'smtp',
'mail_from' => 'dia-chi-gui-cua-ban@example.com',
'smtp_host' => 'smtp.nha-cung-cap-cua-ban',
'smtp_port' => '587',
'smtp_username' => 'tai-khoan-smtp',
'smtp_password' => 'mat-khau-hoac-app-password-rieng',
'smtp_security' => 'tls',
'smtp_auth' => '1',
'app_url' => 'https://ten-mien-cua-ban/duong-dan-web',
```

Đây là giá trị minh họa, phải thay theo dịch vụ email của cậu. `tls` dùng STARTTLS, thường ở cổng 587; `ssl` dùng SMTPS, thường ở 465. Không tắt xác minh TLS. App password nếu có phụ thuộc nhà cung cấp.

Nếu dùng `mail_transport=mail`, PHP cần sendmail/SMTP được cấu hình; XAMPP mặc định thường chưa gửi được. Trang quên mật khẩu trả thông báo chung để không lộ email có tài khoản hay không. Token chỉ lưu bản băm, hết hạn sau 15 phút, chỉ dùng một lần. Đổi mật khẩu vô hiệu hóa phiên đăng nhập cũ.

Bộ kiểm thử dùng SMTP giả lập trên localhost, không gửi thư thật. Nhóm cần tự thử hộp thư thật sau khi điền cấu hình. Không commit config.local hoặc mật khẩu.

## 6. Chatbot Ollama

Trên máy chạy PHP:

```sh
ollama pull qwen2.5:3b
ollama serve
```

Nếu Ollama đã chạy nền thì không chạy thêm serve. Cấu hình `ollama_url` mặc định `http://127.0.0.1:11434/api/chat` và `ollama_model=qwen2.5:3b`. PHP gọi Ollama phía server; điện thoại không cần cài model. Máy yếu có thể phản hồi chậm; backend giới hạn thời gian chờ.

Đăng nhập để lưu lịch sử theo tài khoản. Lịch sử chat có xem/tiếp tục/xóa. Khi Ollama không hoạt động, web báo chưa kết nối được; các chức năng khác vẫn sử dụng bình thường. Nội dung chatbot chỉ tham khảo và không được giới thiệu như công cụ chẩn đoán/kê đơn.

## 7. Tin RSS

Nguồn trong config mẫu là 3 nguồn tiếng Việt: Tuổi Trẻ Sức khỏe, Thanh Niên Sức khỏe và Sức khỏe & Đời sống. `config.local.php` là file riêng của từng máy và không được Git theo dõi; sau khi cập nhật source, người chạy cần tự sửa mảng `news_sources` trong config.local.php thành đúng 3 nguồn này nếu file local vẫn đang dùng cấu hình cũ. Mỗi lượt tối đa 3 nguồn. Endpoint nguồn phải trả XML qua HTTPS trực tiếp; redirect không được theo tự động. Cần curl + SimpleXML.

Cache trong thư mục tạm của PHP, 30–60 phút theo news_cache_ttl. Làm mới theo yêu cầu truy cập khi hết hạn, có khóa tránh nhiều request tải cùng lúc. Nếu nguồn lỗi, giữ tin cũ và báo trạng thái cũ. API `api/tin-tuc.php` chỉ trả cache/theo TTL, tham số refresh không bỏ qua giới hạn.

Trang chủ trộn tin mới nhất; trang tin có tab bài viết nội bộ và tab RSS. Bài RSS mở website gốc, không nhập thành bài do nhóm sở hữu. Danh mục nội bộ không áp cho tin RSS. Nội dung tiêu đề và tóm tắt lấy từ feed gốc, không dịch máy hay sao chép toàn văn.

## 8. Giao diện điện thoại và cài PWA

Thu nhỏ trình duyệt hoặc dùng điện thoại: giao diện tự đổi bố cục, có thanh điều hướng dưới. Để cài biểu tượng lên màn hình chính, xem `huong-dan-app.php`.

- PWA cần HTTPS (hoặc localhost trên chính thiết bị đang chạy trình duyệt).
- Điện thoại mở `http://IP-may-tinh:8000` qua LAN có thể xem responsive nhưng thường không cài PWA. Nếu demo LAN, PHP server phải bind địa chỉ LAN và firewall cho phép; chỉ mở trên mạng tin cậy, không dùng server phát triển để public Internet.
- Chrome/Android: menu cài ứng dụng hoặc nút cài khi trình duyệt cho phép. iPhone/Safari: Chia sẻ → Thêm vào màn hình chính.
- Manifest dùng đường dẫn tương đối, chạy được cả thư mục con trên Apache.
- Service worker chỉ cache các file tĩnh trong danh sách rõ ràng. Không cache trang PHP, API, uploads hoặc tài khoản. Mất mạng hiển thị trang kết nối lại, không cho đặt lịch ngoại tuyến.
- Khi sửa tài nguyên tĩnh đã cache cho lần phát hành sau, tăng phiên bản CACHE trong sw.js.

## 9. Hosting, backup và nghiệm thu

Hosting cần PHP/MySQL, HTTPS, các extension yêu cầu và quyền ghi uploads. Cấu hình app_url theo URL thật; dùng tài khoản DB riêng. Apache cần cho phép .htaccess; Nginx phải tự chặn config/includes/database/docs/tools và thực thi PHP trong uploads. Xem `docs/NGINX_EXAMPLE.conf` chỉ như mẫu để người quản trị điều chỉnh root/PHP socket.

Backup gồm SQL database + uploads + config.local riêng tư. Khôi phục vào database thử riêng, sửa db_name trong một bản copy config, đăng nhập và đối chiếu dữ liệu. Không đặt file backup SQL trong thư mục public.

Xem `docs/KIEM_TRA_FINAL.md`, `docs/DEMO.md` và `tests/README.md`. Nhóm tự làm PDF và chụp cả thanh địa chỉ theo yêu cầu môn học.
