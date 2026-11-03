<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$error = null;
if (post()) {
    check_csrf();
    try {
        $pw = (string) ($_POST["password"] ?? "");
        password_valid($pw);
        if ($pw !== ($_POST["confirm"] ?? "")) {
            throw new RuntimeException("Mật khẩu nhập lại chưa khớp.");
        }
        $challenge = $_SESSION['reset_challenge'] ?? [];
        if (($challenge['tries'] ?? 0) < 1 || ($challenge['until'] ?? 0) <= time()) {
            unset($_SESSION['reset_challenge']);
            throw new RuntimeException('Mã không hợp lệ hoặc đã hết hạn. Hãy yêu cầu mã mới.');
        }
        $_SESSION['reset_challenge']['tries']--;
        $code = trim((string)($_POST['code'] ?? ''));
        if (!preg_match('/^[0-9]{6}$/D', $code)) throw new RuntimeException('Nhập đủ 6 chữ số.');
        $hash = hash_hmac('sha256', $code, $challenge['secret']);
        db()->beginTransaction();
        $reset = query(
            "SELECT * FROM password_resets WHERE user_id=? AND token_hash=? AND expires_at>NOW() FOR UPDATE",
            [$challenge['uid'], $hash],
        )->fetch();
        if (!$reset) {
            throw new RuntimeException(
                "Mã không hợp lệ hoặc đã hết hạn.",
            );
        }
        query("UPDATE users SET password_hash=? WHERE id=?", [
            password_hash($pw, PASSWORD_DEFAULT),
            $reset["user_id"],
        ]);
        query("DELETE FROM password_resets WHERE user_id=?", [
            $reset["user_id"],
        ]);
        db()->commit();
        unset($_SESSION['reset_challenge']);
        flash("Đã đổi mật khẩu. Hãy đăng nhập lại.");
        redirect("dang-nhap.php");
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error =
            $ex instanceof PDOException
                ? "Chưa đặt lại được mật khẩu."
                : $ex->getMessage();
    }
}
head("Đặt lại mật khẩu");
notice($error);
?><form method="post" style="max-width:500px"><?php echo csrf(); ?><label for="code">Mã xác nhận từ email</label><input class="form-control" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required><p class="small">Mã có hiệu lực 15 phút, tối đa 5 lần thử. <a href="quen-mat-khau.php">Yêu cầu mã mới</a></p><?php
input("password", "Mật khẩu mới", "", "password", true);
input("confirm", "Nhập lại mật khẩu", "", "password", true);
?><button class="btn btn-brand">Đổi mật khẩu</button></form><?php foot(); ?>
