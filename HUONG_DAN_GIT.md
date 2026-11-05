# Làm việc với Git

Chạy tại gốc repo. Nếu đã có bản clone thì dùng bản đó, không clone thêm vào bên trong.

## Cập nhật code

```sh
git status
git switch main
git pull --ff-only origin main
```

Nếu có thay đổi chưa lưu, lưu công việc có chủ đích trước khi cập nhật. Không reset, chép đè hoặc xóa `.git` để xử lý lỗi. Git không đồng bộ database, `config.local.php`, `.env` hay ảnh upload giữa các máy; xem [hướng dẫn nâng cấp](website-yte-suckhoe/HUONG_DAN.md#nâng-cấp-và-sao-lưu) khi cần đổi schema.

## Gửi thay đổi

```sh
git switch -c ten-nhanh
git diff
git status
git add duong-dan-file-can-gui
git diff --cached
git commit -m "Mo ta thay doi"
git push -u origin ten-nhanh
```

Thay tên nhánh/đường dẫn bằng giá trị thực tế. Chỉ stage file đã kiểm tra; không commit mật khẩu, API key, cấu hình riêng, backup DB hoặc dữ liệu upload.

Mở Pull Request vào `main`, ghi thay đổi và [kết quả kiểm thử](tests/README.md), rồi để thành viên nhóm duyệt. Không force-push hoặc tự merge khi chưa được đồng ý.
