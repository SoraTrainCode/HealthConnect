<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
if (query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()) {
    http_response_code(403);
    exit("Đã có admin. Trang cài đặt đã khóa.");
}
$error = null;
if (post()) {
    check_csrf();
    try {
        if (
            strlen(cfg("setup_key")) < 16 ||
            !hash_equals(cfg("setup_key"), (string) ($_POST["setup_key"] ?? ""))
        ) {
            throw new RuntimeException(
                "Cấu hình setup_key riêng (tối thiểu 16 ký tự) trong config.local.php và nhập đúng khóa đó.",
            );
        }
        $email = mb_strtolower(field("email", 190));
        $name = field("full_name", 120);
        $pw = (string) ($_POST["password"] ?? "");
        password_valid($pw);
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Họ tên/email không hợp lệ.");
        }
        db()->beginTransaction();
        query(
            "INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,'admin')",
            [$name, $email, password_hash($pw, PASSWORD_DEFAULT)],
        );
        $uid = (int) db()->lastInsertId();
        if (
            isset($_POST["seed"]) &&
            (int) query("SELECT COUNT(*) FROM articles")->fetchColumn() === 0
        ) {
            $seed = json_decode(
                file_get_contents(__DIR__ . "/database/seed.json"),
                true,
            );
            $map = [];
            foreach ($seed["categories"] as $c) {
                query(
                    "INSERT INTO categories(name,slug) VALUES(?,?) ON DUPLICATE KEY UPDATE name=VALUES(name)",
                    [$c["name"], $c["slug"]],
                );
                $map[$c["id"]] = query(
                    "SELECT id FROM categories WHERE slug=?",
                    [$c["slug"]],
                )->fetchColumn();
            }
            foreach ($seed["articles"] as $a) {
                query(
                    "INSERT INTO articles(category_id,author_id,title,slug,summary,content,status,view_count,created_at) VALUES(?,?,?,?,?,?,'published',?,?)",
                    [
                        $map[$a["categoryId"]],
                        $uid,
                        $a["title"],
                        "bai-viet-" . $a["id"],
                        $a["summary"],
                        $a["content"],
                        $a["views"],
                        $a["date"] . " 08:00:00",
                    ],
                );
            }
        }
        db()->commit();
        flash("Đã tạo admin và dữ liệu khởi tạo. Hãy đăng nhập.");
        redirect("dang-nhap.php");
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error =
            $ex instanceof PDOException
                ? "Không tạo được admin. Email có thể đã tồn tại."
                : $ex->getMessage();
    }
}
head("Khởi tạo HealthConnect");
notice($error);
?><p>Import SQL và cấu hình config.local.php trước khi dùng. Trang này tự khóa khi đã có admin.</p><form method="post" style="max-width:600px"><?php
echo csrf();
input("setup_key", "Khóa cài đặt", "", "password", true);
input("full_name", "Tên quản trị viên", "", "text", true);
input("email", "Email quản trị", "", "email", true);
input("password", "Mật khẩu quản trị", "", "password", true);
?><label class="d-block mb-3"><input type="checkbox" name="seed" checked> Tạo bài viết và danh mục mẫu để thử giao diện</label><button class="btn btn-brand">Khởi tạo</button></form><?php foot(); ?>
