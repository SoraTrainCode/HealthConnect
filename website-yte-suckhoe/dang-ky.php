<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$error = null;
if (post()) {
    check_csrf();
    try {
        $name = field("full_name", 120);
        $email = mb_strtolower(field("email", 190));
        $pw = (string) ($_POST["password"] ?? "");
        password_valid($pw);
        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Họ tên hoặc email không hợp lệ.");
        }
        if ($pw !== ($_POST["confirm"] ?? "")) {
            throw new RuntimeException("Mật khẩu nhập lại chưa khớp.");
        }
        if (query("SELECT id FROM users WHERE email=?", [$email])->fetch()) {
            throw new RuntimeException("Email đã được đăng ký.");
        }
        query(
            "INSERT INTO users(full_name,email,password_hash) VALUES(?,?,?)",
            [$name, $email, password_hash($pw, PASSWORD_DEFAULT)],
        );
        $newId = (int) db()->lastInsertId();
        $_SESSION["auth_hash"] = hash(
            "sha256",
            (string) query("SELECT password_hash FROM users WHERE email=?", [
                $email,
            ])->fetchColumn(),
        );
        session_regenerate_id(true);
        $_SESSION["uid"] = $newId;
        redirect("tai-khoan.php");
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Không thể tạo tài khoản. Hãy kiểm tra email hoặc thử lại."
                : $ex->getMessage();
    }
}
head("Đăng ký");
notice($error);
?><form method="post" class="auth-card mx-auto p-4" style="max-width:540px"><?php
echo csrf();
input("full_name", "Họ tên", $_POST["full_name"] ?? "", "text", true);
input("email", "Email", $_POST["email"] ?? "", "email", true);
input("password", "Mật khẩu (8–72 byte)", "", "password", true);
input("confirm", "Nhập lại mật khẩu", "", "password", true);
?><button class="btn btn-brand">Tạo tài khoản</button></form><?php foot(); ?>
