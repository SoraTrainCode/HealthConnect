<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$raw = (string) ($_GET["token"] ?? ($_POST["token"] ?? ""));
$hash = hash("sha256", $raw);
$error = null;
if (post()) {
    check_csrf();
    try {
        $pw = (string) ($_POST["password"] ?? "");
        password_valid($pw);
        if ($pw !== ($_POST["confirm"] ?? "")) {
            throw new RuntimeException("Mật khẩu nhập lại chưa khớp.");
        }
        db()->beginTransaction();
        $reset = query(
            "SELECT * FROM password_resets WHERE token_hash=? AND expires_at>NOW() FOR UPDATE",
            [$hash],
        )->fetch();
        if (!$reset) {
            throw new RuntimeException(
                "Liên kết không hợp lệ hoặc đã hết hạn.",
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
?><form method="post" style="max-width:500px"><?php echo csrf(); ?><input type="hidden" name="token" value="<?php echo e(
    $raw,
); ?>"><?php
input("password", "Mật khẩu mới", "", "password", true);
input("confirm", "Nhập lại mật khẩu", "", "password", true);
?><button class="btn btn-brand">Đổi mật khẩu</button></form><?php foot(); ?>
