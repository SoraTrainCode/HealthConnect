<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$u = require_user();
$error = null;
if (post()) {
    check_csrf();
    try {
        if (($_POST["action"] ?? "") === "password") {
            if (
                !password_verify(
                    (string) ($_POST["old_password"] ?? ""),
                    $u["password_hash"],
                )
            ) {
                throw new RuntimeException("Mật khẩu hiện tại không đúng.");
            }
            $pw = (string) ($_POST["password"] ?? "");
            password_valid($pw);
            if ($pw !== ($_POST["confirm"] ?? "")) {
                throw new RuntimeException("Mật khẩu nhập lại chưa khớp.");
            }
            query("UPDATE users SET password_hash=? WHERE id=?", [
                password_hash($pw, PASSWORD_DEFAULT),
                $u["id"],
            ]);
            $_SESSION["auth_hash"] = hash(
                "sha256",
                (string) query("SELECT password_hash FROM users WHERE id=?", [
                    $u["id"],
                ])->fetchColumn(),
            );
            session_regenerate_id(true);
        } else {
            $name = field("full_name", 120);
            $phone = field("phone", 20);
            $birth = field("birthday", 10);
            $gender = field("gender", 20);
            $height = field("default_height", 10);
            if (!$name) {
                throw new RuntimeException("Họ tên không được để trống.");
            }
            if (
                $height !== "" &&
                ((float) $height < 80 || (float) $height > 250)
            ) {
                throw new RuntimeException("Chiều cao từ 80–250 cm.");
            }
            if (
                $birth !== "" &&
                (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) ||
                    !checkdate(
                        (int) substr($birth, 5, 2),
                        (int) substr($birth, 8, 2),
                        (int) substr($birth, 0, 4),
                    ) ||
                    $birth > date("Y-m-d"))
            ) {
                throw new RuntimeException("Ngày sinh không hợp lệ.");
            }
            if (!in_array($gender, ["", "Nam", "Nữ", "Khác"], true)) {
                throw new RuntimeException("Giới tính không hợp lệ.");
            }
            $avatar = field("avatar_url", 500);
            if (
                $avatar !== "" &&
                (!filter_var($avatar, FILTER_VALIDATE_URL) ||
                    !in_array(
                        parse_url($avatar, PHP_URL_SCHEME),
                        ["http", "https"],
                        true,
                    ))
            ) {
                throw new RuntimeException(
                    "Ảnh đại diện cần là đường dẫn http/https hợp lệ.",
                );
            }
            query(
                "UPDATE users SET full_name=?,phone=?,birthday=?,gender=?,default_height=?,avatar_url=? WHERE id=?",
                [
                    $name,
                    $phone,
                    $birth ?: null,
                    $gender,
                    $height !== "" ? (float) $height : null,
                    $avatar ?: null,
                    $u["id"],
                ],
            );
        }
        flash("Đã cập nhật tài khoản.");
        redirect("tai-khoan.php");
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Chưa cập nhật được tài khoản."
                : $ex->getMessage();
    }
}
head("Tài khoản của tôi");
notice($error);
?><div class="row g-4"><div class="col-md-7"><form method="post" class="bmi-panel"><?php
echo csrf();
if ($u["avatar_url"]): ?><img class="avatar mb-3" src="<?php echo e(
    $u["avatar_url"],
); ?>" alt="Ảnh đại diện"><?php endif;
?><p>Email: <?php echo e($u["email"]); ?></p><?php
input("full_name", "Họ tên", $u["full_name"], "text", true);
input("phone", "Số điện thoại", $u["phone"]);
input("birthday", "Ngày sinh", $u["birthday"], "date");
input(
    "default_height",
    "Chiều cao mặc định (cm)",
    $u["default_height"],
    "number",
);
input("avatar_url", "Đường dẫn ảnh đại diện", $u["avatar_url"], "url");
?><label>Giới tính</label><select class="form-select" name="gender"><?php foreach (
    ["", "Nam", "Nữ", "Khác"]
    as $g
): ?><option <?php echo $u["gender"] === $g
    ? "selected"
    : ""; ?> value="<?php echo e($g); ?>"><?php echo e(
    $g ?: "Chưa chọn",
); ?></option><?php endforeach; ?></select><button class="btn btn-brand">Lưu hồ sơ</button></form></div><div class="col-md-5"><form method="post" class="bmi-panel"><?php echo csrf(); ?><input type="hidden" name="action" value="password"><h2 class="h5">Đổi mật khẩu</h2><?php
input("old_password", "Mật khẩu hiện tại", "", "password", true);
input("password", "Mật khẩu mới", "", "password", true);
input("confirm", "Nhập lại mật khẩu mới", "", "password", true);
?><button class="btn btn-brand">Đổi mật khẩu</button></form><div class="mt-4"><a class="btn btn-outline-brand" href="bmi.php">Lịch sử và biểu đồ BMI</a> <a class="btn btn-outline-brand" href="lich-su-chat.php">Lịch sử chatbot</a></div></div></div><script>document.getElementById('default_height').step='0.1';</script><?php foot(); ?>
