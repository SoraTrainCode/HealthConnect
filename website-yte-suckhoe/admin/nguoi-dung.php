<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require dirname(__DIR__) . "/includes/admin_ui.php";
require dirname(__DIR__).'/includes/appointments.php';
$admin = require_admin();
$error = null;

if (post()) {
    check_csrf();
    $id = (int) ($_POST["id"] ?? 0);
    $do = (string) ($_POST["do"] ?? "toggle");
    $target = query("SELECT * FROM users WHERE id=?", [$id])->fetch();
    if (!$target || $target["role"] === "admin") {
        $error = "Không thao tác với tài khoản quản trị tại trang này.";
    } else {
        try {
            appt_begin();
            if ($do === "delete") {
                if (query('SELECT id FROM appointments WHERE user_id=? LIMIT 1',[$id])->fetchColumn()) {
                    throw new RuntimeException('Tài khoản còn lịch khám. Hãy khóa tài khoản để giữ lịch sử và dữ liệu lịch hẹn.');
                }
                query("DELETE FROM users WHERE id=? AND role='user'", [$id]);
                flash("Đã xóa tài khoản.");
            } else {
                query(
                    "UPDATE users SET status=IF(status='active','locked','active') WHERE id=? AND role='user'",
                    [$id],
                );
                flash("Đã thay đổi trạng thái tài khoản.");
            }
            db()->commit();
            redirect("nguoi-dung.php" . (!empty($_POST["back"]) ? "?" . $_POST["back"] : ""));
        } catch (Throwable $ex) {
            if(db()->inTransaction()) db()->rollBack();
            $error = $ex instanceof PDOException ? "Không xóa được: tài khoản còn dữ liệu liên quan. Hãy khóa tài khoản thay vì xóa." : $ex->getMessage();
        }
    }
}

// --- Bộ lọc ---
$q = mb_substr(trim((string) ($_GET["q"] ?? "")), 0, 190);
$fr = in_array($_GET["role"] ?? "", ["user", "admin"], true) ? $_GET["role"] : "";
$fs = in_array($_GET["status"] ?? "", ["active", "locked"], true) ? $_GET["status"] : "";
$where = ["(full_name LIKE ? OR email LIKE ?)"];
$params = ["%" . $q . "%", "%" . $q . "%"];
if ($fr !== "") {
    $where[] = "role=?";
    $params[] = $fr;
}
if ($fs !== "") {
    $where[] = "status=?";
    $params[] = $fs;
}
$whereSql = implode(" AND ", $where);

// --- Xuất CSV (phải chạy trước khi in HTML) ---
if (isset($_GET["export"])) {
    $all = query(
        "SELECT id,full_name,email,role,status,created_at FROM users WHERE $whereSql ORDER BY id DESC",
        $params,
    )->fetchAll();
    header("Content-Type: text/csv; charset=utf-8");
    header('Content-Disposition: attachment; filename="nguoi-dung-' . date("Ymd") . '.csv"');
    $out = fopen("php://output", "w");
    fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng tiếng Việt
    fputcsv($out, ["ID", "Họ tên", "Email", "Vai trò", "Trạng thái", "Ngày tạo"]);
    foreach ($all as $r) {
        // Chống CSV injection khi mở bằng Excel
        foreach (["full_name", "email"] as $k) {
            if (preg_match('/^[=+\-@\t\r]/', (string) $r[$k])) {
                $r[$k] = "'" . $r[$k];
            }
        }
        fputcsv($out, $r);
    }
    fclose($out);
    exit();
}

$perPage = 20;
$totalRows = (int) query("SELECT COUNT(*) FROM users WHERE $whereSql", $params)->fetchColumn();
$pages = max(1, (int) ceil($totalRows / $perPage));
$page = min($pages, max(1, (int) ($_GET["page"] ?? 1)));
$offset = ($page - 1) * $perPage;
$users = query(
    "SELECT id,full_name,email,role,status,created_at FROM users WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset",
    $params,
)->fetchAll();

$sum = query(
    "SELECT COUNT(*) total, SUM(status='locked') locked, SUM(role='admin') admins FROM users",
)->fetch();
$back = http_build_query(array_filter(["q" => $q, "role" => $fr, "status" => $fs, "page" => $page > 1 ? $page : ""]));

head("Quản lý người dùng");
admin_bar("nguoi-dung.php");
notice($error);
?>
<div class="row g-3 mb-3">
  <div class="col-4"><div class="adm-stat"><div class="n"><?php echo (int) $sum["total"]; ?></div><div class="l">Tổng tài khoản</div></div></div>
  <div class="col-4"><div class="adm-stat"><div class="n"><?php echo (int) $sum["locked"]; ?></div><div class="l">Đang bị khóa</div></div></div>
  <div class="col-4"><div class="adm-stat"><div class="n"><?php echo (int) $sum["admins"]; ?></div><div class="l">Quản trị viên</div></div></div>
</div>

<form method="get" class="adm-filter">
  <input name="q" value="<?php echo e($q); ?>" class="form-control" placeholder="Tìm tên hoặc email">
  <select name="role" class="form-select">
    <option value="">Mọi vai trò</option>
    <option value="user" <?php echo $fr === "user" ? "selected" : ""; ?>>Người dùng</option>
    <option value="admin" <?php echo $fr === "admin" ? "selected" : ""; ?>>Quản trị</option>
  </select>
  <select name="status" class="form-select">
    <option value="">Mọi trạng thái</option>
    <option value="active" <?php echo $fs === "active" ? "selected" : ""; ?>>Hoạt động</option>
    <option value="locked" <?php echo $fs === "locked" ? "selected" : ""; ?>>Đã khóa</option>
  </select>
  <button class="btn btn-brand">Lọc</button>
  <a class="btn btn-outline-secondary" href="nguoi-dung.php">Xóa lọc</a>
  <a class="btn btn-outline-brand ms-auto" href="?<?php echo e(http_build_query(array_merge($_GET, ["export" => 1]))); ?>">Xuất CSV</a>
</form>

<div class="table-responsive">
<table class="table align-middle">
  <thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?php echo e($u["full_name"]); ?></td>
      <td><?php echo e($u["email"]); ?></td>
      <td><?php echo $u["role"] === "admin"
          ? '<span class="adm-badge admin">Quản trị</span>'
          : "Người dùng"; ?></td>
      <td><?php echo $u["status"] === "active"
          ? '<span class="adm-badge ok">Hoạt động</span>'
          : '<span class="adm-badge lock">Đã khóa</span>'; ?></td>
      <td><?php echo e(date("d/m/Y H:i", strtotime($u["created_at"]))); ?></td>
      <td class="text-nowrap">
        <?php if ($u["role"] === "user"): ?>
          <form method="post" class="d-inline"><?php echo csrf(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $u["id"]; ?>">
            <input type="hidden" name="back" value="<?php echo e($back); ?>">
            <button name="do" value="toggle" class="btn btn-sm btn-outline-brand"><?php echo $u["status"] === "active" ? "Khóa" : "Mở khóa"; ?></button>
          </form>
          <form method="post" class="d-inline" onsubmit="return confirm('Xóa vĩnh viễn tài khoản này?')"><?php echo csrf(); ?>
            <input type="hidden" name="id" value="<?php echo (int) $u["id"]; ?>">
            <input type="hidden" name="back" value="<?php echo e($back); ?>">
            <button name="do" value="delete" class="btn btn-sm btn-outline-danger">Xóa</button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$users): ?><tr><td colspan="6" class="adm-empty">Không có tài khoản nào khớp bộ lọc.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php admin_pager($page, $pages); ?>
<?php foot(); ?>
