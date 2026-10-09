<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$error = null;
if (post()) {
    check_csrf();
    try {
        $email = mb_strtolower(field("email", 190));
        $key = hash("sha256", $email);
        $ipKey = hash("sha256", "ip:" . ($_SERVER["REMOTE_ADDR"] ?? ""));
        foreach ([$key, $ipKey] as $k) {
            $attempt = query(
                "SELECT * FROM login_attempts WHERE attempt_key=?",
                [$k],
            )->fetch();
            if (
                $attempt &&
                strtotime($attempt["locked_until"] ?? "1970-01-01") > time()
            ) {
                throw new RuntimeException(
                    "Đăng nhập sai nhiều lần. Vui lòng thử lại sau 5 phút.",
                );
            }
        }
        $u = query("SELECT * FROM users WHERE email=?", [$email])->fetch();
        if (
            !$u ||
            !password_verify(
                (string) ($_POST["password"] ?? ""),
                $u["password_hash"],
            ) ||
            $u["status"] !== "active"
        ) {
            foreach ([$key, $ipKey] as $k) {
                query(
                    "INSERT INTO login_attempts(attempt_key,failures,last_attempt) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE failures=IF(last_attempt<DATE_SUB(NOW(),INTERVAL 5 MINUTE),1,failures+1),last_attempt=NOW()",
                    [$k],
                );
                query(
                    "UPDATE login_attempts SET locked_until=DATE_ADD(NOW(),INTERVAL 5 MINUTE) WHERE attempt_key=? AND failures>=?",
                    [$k, $k === $ipKey ? 30 : 5],
                );
            }
            throw new RuntimeException(
                "Email/mật khẩu không đúng hoặc tài khoản đã bị khóa.",
            );
        }
        query("DELETE FROM login_attempts WHERE attempt_key=?", [$key]);
        session_regenerate_id(true);
        $_SESSION["uid"] = $u["id"];
        $_SESSION["auth_hash"] = hash("sha256", $u["password_hash"]);
        redirect(
            $u["role"] === "admin" ? "admin/dashboard.php" : "tai-khoan.php",
        );
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Chưa thể đăng nhập. Kiểm tra cấu hình database."
                : $ex->getMessage();
    }
}
head("Đăng nhập");
notice($error);
?><form method="post" class="auth-card mx-auto p-4" style="max-width:540px"><?php
echo csrf();
input("email", "Email", $_POST["email"] ?? "", "email", true);
input("password", "Mật khẩu", "", "password", true);
?><button class="btn btn-brand">Đăng nhập</button> <a href="quen-mat-khau.php">Quên mật khẩu?</a></form><?php foot(); ?>
