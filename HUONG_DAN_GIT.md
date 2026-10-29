# Đưa bản hoàn thiện vào GitHub nhóm

Bản này dựa trên `main` commit `a4c67a7` và đã giữ phần RSS mới của nhóm. Chưa push tự động lên GitHub. Gói ZIP có toàn bộ source cùng `HealthConnect-final.patch` nằm ngoài thư mục source.

## Cách khuyến nghị: áp dụng patch vào repo đang clone

Giải nén ZIP ở một thư mục riêng, ví dụ `D:\Downloads\HealthConnect-Final`. Không thay/xóa thư mục `.git` trong repo cũ.

Mở CMD:

```bat
cd /d "D:\Thực Hành\HealthConnect\HealthConnect"
git status
```

Chỉ tiếp tục khi `working tree clean`. Nếu có code nhóm đang sửa dở, commit riêng hoặc sao lưu có chủ đích trước; không dùng restore/reset để xóa thay đổi.

```bat
git fetch origin
git switch -c trong/healthconnect-final origin/main
git apply --check "D:\Downloads\HealthConnect-Final\HealthConnect-final.patch"
git apply "D:\Downloads\HealthConnect-Final\HealthConnect-final.patch"
git status
```

Đổi đường dẫn patch theo nơi thực tế cậu giải nén. Nếu `--check` báo lỗi, **dừng trước lệnh apply**: main có thể đã thay đổi hoặc đã có một phần bản sửa; gửi lỗi để ghép đúng. Không chép đè toàn bộ bản cũ lên code mới của bạn cùng nhóm.

Tiếp theo:

1. Sao lưu database; import `website-yte-suckhoe/database/upgrade_final.sql` vào đúng DB. Máy mới dùng schema.sql.
2. Chạy doctor.php, các test cần thiết và demo chức năng chính theo README.
3. Xem `git diff --stat` và đảm bảo không có config.local, mật khẩu, SQL backup hay uploads người dùng trong commit.

```bat
git add .
git commit -m "Complete HealthConnect health tracking, doctor booking and mobile PWA"
git push -u origin trong/healthconnect-final
```

Trên GitHub chọn Compare & pull request, base `main`, compare `trong/healthconnect-final`. Hai người xem thay đổi và kết quả CI rồi merge. Không force push lên main.

## Source đầy đủ trong ZIP dùng để làm gì?

Thư mục `HealthConnect/` đủ để chạy riêng theo README. Nó không chứa `.git`, config.local hoặc dữ liệu cá nhân. Nếu chỉ muốn xem code/chạy thử, dùng thư mục này và tạo config riêng.

Nếu muốn đưa code vào repo đã có lịch sử, ưu tiên patch phía trên. Sau merge, người còn lại cập nhật repo và chạy cùng migration. Git không tự đồng bộ database hoặc config.local giữa hai máy.
