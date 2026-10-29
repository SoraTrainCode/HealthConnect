<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
require __DIR__ . "/includes/mailer.php";
$message = null;
$error = null;
if (post()) {
    check_csrf();
    try {
        if (time() - (int)($_SESSION['reset_request_at']??0) < 30) throw new RuntimeException('Vui lòng đợi 30 giây trước khi gửi lại.');
        $_SESSION['reset_request_at']=time();
        $email = mb_strtolower(field("email", 190));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Email không hợp lệ.");
        }
        $u = query("SELECT id FROM users WHERE email=? AND status='active'", [
            $email,
        ])->fetch();
        if ($u) {
            $recent = query(
                "SELECT COUNT(*) FROM password_resets WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 5 MINUTE)",
                [$u["id"]],
            )->fetchColumn();
            if (!$recent) {
                $raw = bin2hex(random_bytes(32));
                query("DELETE FROM password_resets WHERE user_id=?", [
                    $u["id"],
                ]);
                query(
                    "INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 15 MINUTE))",
                    [$u["id"], hash("sha256", $raw)],
                );
                $link =
                    rtrim(cfg("app_url"), "/") .
                    "/dat-lai-mat-khau.php?token=" .
                    $raw;
                $sent = hc_send_mail($email, "HealthConnect - Đặt lại mật khẩu", "Mở liên kết sau trong 15 phút:\n" . $link);
                if (!$sent) {
                    query("DELETE FROM password_resets WHERE token_hash=?", [hash('sha256',$raw)]);
                    error_log(
                        "HealthConnect: mail transport unavailable. Configure PHP SMTP/sendmail.",
                    );
                }
            }
        }
        $message =
            "Nếu email có tài khoản đang hoạt động, hệ thống sẽ gửi liên kết đặt lại mật khẩu. Hãy kiểm tra hộp thư và thư rác.";
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Không gửi được yêu cầu."
                : $ex->getMessage();
    }
}
head("Quên mật khẩu");
notice($error);
if ($message) {
    echo '<div class="alert alert-info">' . e($message) . "</div>";
}
?><form method="post" style="max-width:500px"><?php
echo csrf();
input("email", "Email đăng ký", "", "email", true);
?><button class="btn btn-brand">Gửi liên kết</button></form><?php foot(); ?>
