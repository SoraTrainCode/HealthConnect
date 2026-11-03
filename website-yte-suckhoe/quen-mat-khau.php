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
                $code = sprintf('%06d', random_int(0, 999999));
                $secret = bin2hex(random_bytes(32));
                $hash = hash_hmac('sha256', $code, $secret);
                query("DELETE FROM password_resets WHERE user_id=?", [
                    $u["id"],
                ]);
                query(
                    "INSERT INTO password_resets(user_id,token_hash,expires_at) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 15 MINUTE))",
                    [$u["id"], $hash],
                );
                $sent = hc_send_mail($email, "HealthConnect - Mã đặt lại mật khẩu", "Mã đặt lại mật khẩu của bạn: " . $code . ". Mã có hiệu lực 15 phút. Không chia sẻ mã này.");
                if ($sent) {
                    $_SESSION['reset_challenge'] = ['secret'=>$secret, 'uid'=>(int)$u['id'], 'until'=>time()+900, 'tries'=>5];
                } else {
                    query("DELETE FROM password_resets WHERE token_hash=?", [$hash]);
                    error_log(
                        "HealthConnect: mail transport unavailable. Configure PHP SMTP/sendmail.",
                    );
                }
            }
        }
        $message =
            "Nếu email có tài khoản đang hoạt động, hệ thống sẽ gửi mã gồm 6 chữ số. Nhập mã trên cùng trình duyệt đã yêu cầu. Hãy kiểm tra hộp thư và thư rác.";
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
    echo '<div class="alert alert-info">' . e($message) . '</div><p><a class="btn btn-brand" href="dat-lai-mat-khau.php">Nhập mã đặt lại mật khẩu</a></p>';
}
?><form method="post" style="max-width:500px"><?php
echo csrf();
input("email", "Email đăng ký", "", "email", true);
?><button class="btn btn-brand">Gửi mã qua email</button></form><?php foot(); ?>
