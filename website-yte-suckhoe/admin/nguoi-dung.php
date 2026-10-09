<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
$admin = require_admin();
$error = null;
if (post()) {
    check_csrf();
    $id = (int) ($_POST["id"] ?? 0);
    $target = query("SELECT * FROM users WHERE id=?", [$id])->fetch();
    if (!$target || $target["role"] === "admin") {
        $error = "Không khóa tài khoản quản trị tại trang này.";
    } else {
        query(
            "UPDATE users SET status=IF(status='active','locked','active') WHERE id=? AND role='user'",
            [$id],
        );
        flash("Đã thay đổi trạng thái tài khoản.");
        redirect("nguoi-dung.php");
    }
}
$q = mb_substr((string) ($_GET["q"] ?? ""), 0, 190);
$users = query(
    "SELECT id,full_name,email,role,status,created_at FROM users WHERE full_name LIKE ? OR email LIKE ? ORDER BY id DESC",
    ["%" . $q . "%", "%" . $q . "%"],
)->fetchAll();
head("Quản lý người dùng");
notice($error);
?><form method="get" class="d-flex gap-2"><input name="q" value="<?php echo e(
    $q,
); ?>" class="form-control" placeholder="Tìm tên/email"><button class="btn btn-brand mb-3">Tìm</button></form><div class="table-responsive"><table class="table"><thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach (
    $users
    as $u
): ?><tr><td><?php echo e($u["full_name"]); ?></td><td><?php echo e(
    $u["email"],
); ?></td><td><?php echo e($u["role"]); ?></td><td><?php echo $u["status"] ===
"active"
    ? "Hoạt động"
    : "Đã khóa"; ?></td><td><?php if (
    $u["role"] === "user"
): ?><form method="post"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo $u[
    "id"
]; ?>"><button class="btn btn-sm btn-outline-brand"><?php echo $u["status"] ===
"active"
    ? "Khóa"
    : "Mở khóa"; ?></button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php foot(); ?>
