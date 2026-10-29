<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$error = null;
// Cổng đăng nhập: "user" (mặc định) hoặc "admin"
$portal = ($_POST["role"] ?? ($_GET["role"] ?? "user")) === "admin" ? "admin" : "user";
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
        // Mật khẩu đúng nhưng sai cổng: không tạo phiên, không tính là lần sai
        if ($u["role"] !== $portal) {
            throw new RuntimeException(
                $portal === "admin"
                    ? "Tài khoản này không phải quản trị viên. Hãy đăng nhập ở cổng người dùng."
                    : "Đây là tài khoản quản trị. Hãy đăng nhập ở cổng quản trị viên.",
            );
        }
        query("DELETE FROM login_attempts WHERE attempt_key=?", [$key]);
        session_regenerate_id(true);
        $_SESSION["uid"] = $u["id"];
        $_SESSION["auth_hash"] = hash("sha256", $u["password_hash"]);
        redirect(
            $u["role"] === "admin" ? "admin/dashboard.php" : "suc-khoe.php",
        );
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Chưa thể đăng nhập. Kiểm tra cấu hình database."
                : $ex->getMessage();
    }
}
head($portal === "admin" ? "Đăng nhập quản trị" : "Đăng nhập");
notice($error);
?><div class="mx-auto mb-3 d-flex gap-2" style="max-width:540px">
<a class="btn <?php echo $portal === "user" ? "btn-brand" : "btn-outline-brand"; ?> flex-fill" href="dang-nhap.php?role=user">Người dùng</a>
<a class="btn <?php echo $portal === "admin" ? "btn-brand" : "btn-outline-brand"; ?> flex-fill" href="dang-nhap.php?role=admin">Quản trị viên</a>
</div><form method="post" class="auth-card mx-auto p-4" style="max-width:540px"><?php
echo csrf();
?><input type="hidden" name="role" value="<?php echo e($portal); ?>"><?php
input("email", "Email", $_POST["email"] ?? "", "email", true);
input("password", "Mật khẩu", "", "password", true);
?><button class="btn btn-brand">Đăng nhập</button> <a href="quen-mat-khau.php">Quên mật khẩu?</a></form><?php foot(); ?>
