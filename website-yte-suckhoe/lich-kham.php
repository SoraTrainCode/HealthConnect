<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require dirname(__DIR__) . "/includes/admin_ui.php";
require dirname(__DIR__) . "/includes/appointments.php";
require_admin();
$error = null;
$today = date("Y-m-d");

// Ngày đang xem (mặc định hôm nay) + bộ lọc trạng thái
$date = (string) ($_GET["date"] ?? $today);
if (!appt_valid_date($date)) {
    $date = $today;
}
$fs = array_key_exists($_GET["status"] ?? "", appt_statuses()) ? $_GET["status"] : "";
$back = http_build_query(array_filter(["date" => $date === $today ? "" : $date, "status" => $fs]));

if (post()) {
    check_csrf();
    [$act, $id] = array_pad(explode(":", (string) ($_POST["act"] ?? ""), 2), 2, "0");
    $map = ["confirm" => "confirmed", "done" => "done", "noshow" => "no_show", "cancel" => "cancelled", "reopen" => "pending"];
    if (isset($map[$act]) && (int) $id > 0) {
        query("UPDATE appointments SET status=? WHERE id=?", [$map[$act], (int) $id]);
        flash("Đã cập nhật lịch khám.");
        redirect("lich-kham.php" . ($back ? "?" . $back : ""));
    }
    $error = "Thao tác không hợp lệ.";
}

$counts = query(
    "SELECT status, COUNT(*) c FROM appointments WHERE appt_date=? GROUP BY status",
    [$date],
)->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);

$params = [$date];
$sql = "SELECT a.*, u.email FROM appointments a LEFT JOIN users u ON u.id=a.user_id WHERE a.appt_date=?";
if ($fs !== "") {
    $sql .= " AND a.status=?";
    $params[] = $fs;
}
$rows = query($sql . " ORDER BY a.appt_time, a.id", $params)->fetchAll();

// Tổng quan 7 ngày tới (mọi ngày, không chỉ ngày đang xem) để không bỏ sót lịch mới đặt
$upcoming = query(
    "SELECT appt_date, COUNT(*) c, SUM(status='pending') p FROM appointments WHERE appt_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status<>'cancelled' GROUP BY appt_date ORDER BY appt_date",
)->fetchAll();
$pendingAll = (int) query(
    "SELECT COUNT(*) FROM appointments WHERE status='pending' AND appt_date>=CURDATE()",
)->fetchColumn();
$prev = date("Y-m-d", strtotime($date . " -1 day"));
$next = date("Y-m-d", strtotime($date . " +1 day"));
$nowTime = date("H:i");

head("Lịch khám");
admin_bar("lich-kham.php");
notice($error);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a class="btn btn-outline-secondary btn-sm" href="?date=<?php echo e($prev); ?>">&larr; Hôm trước</a>
  <form method="get" class="d-flex gap-2 align-items-center m-0">
    <input type="date" name="date" value="<?php echo e($date); ?>" class="form-control" style="width:auto" onchange="this.form.submit()">
    <select name="status" class="form-select" style="width:auto" onchange="this.form.submit()">
      <option value="">Mọi trạng thái</option>
      <?php foreach (appt_statuses() as $k => [$label]): ?>
        <option value="<?php echo e($k); ?>" <?php echo $fs === $k ? "selected" : ""; ?>><?php echo e($label); ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <a class="btn btn-outline-secondary btn-sm" href="?date=<?php echo e($next); ?>">Hôm sau &rarr;</a>
  <?php if ($date !== $today): ?><a class="btn btn-brand btn-sm" href="lich-kham.php">Về hôm nay</a><?php endif; ?>
</div>

<div class="adm-box">
  <h2>Lịch 7 ngày tới
    <?php if ($pendingAll): ?><span class="adm-badge draft"><?php echo $pendingAll; ?> lịch chờ xác nhận</span><?php endif; ?>
  </h2>
  <?php if ($upcoming): ?>
    <div class="d-flex flex-wrap gap-2">
    <?php foreach ($upcoming as $d): ?>
      <a class="btn btn-sm <?php echo $d["appt_date"] === $date ? "btn-brand" : "btn-outline-brand"; ?>"
         href="?date=<?php echo e($d["appt_date"]); ?>">
        <?php echo e(date("d/m", strtotime($d["appt_date"]))); ?>: <?php echo (int) $d["c"]; ?> lịch<?php echo (int) $d["p"] ? " (" . (int) $d["p"] . " chờ)" : ""; ?>
      </a>
    <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-muted">Chưa có lịch nào trong 7 ngày tới.</div>
  <?php endif; ?>
</div>

<h2 class="h5 mb-3">
  <?php echo $date === $today ? "Lịch khám hôm nay" : "Lịch khám"; ?> — <?php echo e(date("d/m/Y", strtotime($date))); ?>
</h2>

<div class="row g-3 mb-4">
  <div class="col-6 col-md"><div class="adm-stat"><div class="n"><?php echo $total; ?></div><div class="l">Tổng lượt đặt</div></div></div>
  <?php foreach (appt_statuses() as $k => [$label]): ?>
    <div class="col-6 col-md"><div class="adm-stat"><div class="n"><?php echo (int) ($counts[$k] ?? 0); ?></div><div class="l"><?php echo e($label); ?></div></div></div>
  <?php endforeach; ?>
</div>

<form method="post">
<?php echo csrf(); ?>
<div class="table-responsive">
<table class="table align-middle">
  <thead><tr><th>Giờ</th><th>Bệnh nhân</th><th>Liên hệ</th><th>Chuyên khoa / Lý do</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $a):
      $time = substr($a["appt_time"], 0, 5);
      $late = $date === $today && $a["status"] === "confirmed" && $time < $nowTime; ?>
    <tr<?php echo $late ? ' class="table-warning"' : ""; ?>>
      <td><strong><?php echo e($time); ?></strong></td>
      <td><?php echo e($a["patient_name"]); ?></td>
      <td><?php echo e($a["phone"]); ?><div class="small text-muted"><?php echo e($a["email"] ?? ""); ?></div></td>
      <td><?php echo e($a["specialty"]); ?>
        <?php if ($a["reason"] !== ""): ?><div class="small text-muted"><?php echo e($a["reason"]); ?></div><?php endif; ?></td>
      <td><?php echo appt_badge($a["status"]); ?></td>
      <td class="text-nowrap">
        <?php $id = (int) $a["id"]; ?>
        <?php if ($a["status"] === "pending"): ?>
          <button name="act" value="confirm:<?php echo $id; ?>" class="btn btn-sm btn-brand">Xác nhận</button>
          <button name="act" value="cancel:<?php echo $id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hủy lịch này?')">Hủy</button>
        <?php elseif ($a["status"] === "confirmed"): ?>
          <button name="act" value="done:<?php echo $id; ?>" class="btn btn-sm btn-brand">Đã khám</button>
          <button name="act" value="noshow:<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">Không đến</button>
          <button name="act" value="cancel:<?php echo $id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hủy lịch này?')">Hủy</button>
        <?php else: ?>
          <button name="act" value="reopen:<?php echo $id; ?>" class="btn btn-sm btn-outline-brand">Mở lại</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="adm-empty">Không có lịch khám nào trong ngày này.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
</form>
<?php foot(); ?>
