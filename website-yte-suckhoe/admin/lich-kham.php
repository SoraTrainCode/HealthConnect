<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require dirname(__DIR__) . "/includes/admin_ui.php";
require dirname(__DIR__) . "/includes/appointments.php";
$admin = require_admin();
$error = null;
$today = date("Y-m-d");
$doctorFilter=max(0,(int)($_GET["doctor"]??0));

// Ngày đang xem (mặc định hôm nay) + bộ lọc trạng thái
$date = (string) ($_GET["date"] ?? $today);
if (!appt_valid_date($date)) {
    $date = $today;
}
$fs = array_key_exists($_GET["status"] ?? "", appt_statuses()) ? $_GET["status"] : "";
$back = http_build_query(array_filter(["date" => $date === $today ? "" : $date, "status" => $fs, "doctor" => $doctorFilter]));

if (post()) {
    check_csrf();
    [$act, $id] = array_pad(explode(":", (string) ($_POST["act"] ?? ""), 2), 2, "0");
    try {
        appt_admin_update((int) $id, $act, (int)$admin["id"]);
        flash("Đã cập nhật lịch khám.");
        redirect("lich-kham.php" . ($back ? "?" . $back : ""));
    } catch (PDOException $ex) {
        error_log((string) $ex);
        $error = "Chưa thể cập nhật lịch. Hãy thử lại sau.";
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

$counts = query(
    "SELECT status, COUNT(*) c FROM appointments WHERE appt_date=? GROUP BY status",
    [$date],
)->fetchAll(PDO::FETCH_KEY_PAIR);
$total = array_sum($counts);

$params = [$date];
$sql = "SELECT a.*, u.email, d.name doctor_name FROM appointments a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN doctors d ON d.id=a.doctor_id WHERE a.appt_date=?";
if ($fs !== "") {
    $sql .= " AND a.status=?";
    $params[] = $fs;
}
if($doctorFilter){$sql.=" AND a.doctor_id=?";$params[]=$doctorFilter;}
$rows = query($sql . " ORDER BY a.appt_time, a.id", $params)->fetchAll();

$prev = date("Y-m-d", strtotime($date . " -1 day"));
$next = date("Y-m-d", strtotime($date . " +1 day"));
$nowTime = date("H:i");

head("Lịch khám");
admin_bar("lich-kham.php");
$upcoming=query("SELECT appt_date,COUNT(*) total,SUM(status='pending') pending FROM appointments WHERE appt_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) AND status IN ('pending','confirmed') GROUP BY appt_date ORDER BY appt_date")->fetchAll();
if($upcoming){echo '<div class="hc-panel mb-4"><h2 class="h5">Lịch sắp tới trong 30 ngày</h2><div class="d-flex flex-wrap gap-2">';foreach($upcoming as $day){echo '<a class="btn btn-sm btn-outline-brand" href="?date='.e($day['appt_date']).'">'.e(date('d/m',strtotime($day['appt_date']))).' · '.(int)$day['total'].' lịch ('.(int)$day['pending'].' chờ)</a>';}echo '</div></div>';}

notice($error);
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a class="btn btn-outline-secondary btn-sm" href="?date=<?php echo e($prev); ?>">&larr; Hôm trước</a>
  <form method="get" class="d-flex flex-wrap gap-2 align-items-center m-0">
    <input type="date" name="date" value="<?php echo e($date); ?>" class="form-control" style="width:auto" onchange="this.form.submit()">
    <label class="visually-hidden" for="doctor-filter">Bác sĩ</label><select id="doctor-filter" name="doctor" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="0">Mọi bác sĩ</option><?php foreach(query('SELECT id,name FROM doctors ORDER BY name') as $doctor): ?><option value="<?php echo (int)$doctor['id']; ?>" <?php echo $doctorFilter===(int)$doctor['id']?'selected':''; ?>><?php echo e($doctor['name']); ?></option><?php endforeach; ?></select>
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
      <td><?php echo e($a["specialty"]); ?><div><?php echo e($a["doctor_name"] ?? "Tiếp nhận chung"); ?></div><a href="../phieu-hen.php?id=<?php echo (int)$a["id"]; ?>">Phiếu hẹn</a>
        <?php if ($a["reason"] !== ""): ?><div class="small text-muted"><?php echo e($a["reason"]); ?></div><?php endif; ?></td>
      <td><?php echo appt_badge($a["status"]); ?></td>
      <td class="text-nowrap">
        <?php $id = (int) $a["id"]; ?>
        <?php foreach (["confirm" => "Xác nhận", "done" => "Đã khám", "noshow" => "Không đến", "cancel" => "Hủy", "reopen" => "Mở lại"] as $action => $label): ?>
          <?php if (appt_next_status($a["status"], $action, appt_future($a["appt_date"], $a["appt_time"])) !== null): ?>
            <button name="act" value="<?php echo e($action . ":" . $id); ?>" class="btn btn-sm btn-outline-secondary"
              <?php if ($action === "cancel"): ?>onclick="return confirm('Hủy lịch này?')"<?php endif; ?>><?php echo e($label); ?></button>
          <?php endif; ?>
        <?php endforeach; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6" class="adm-empty">Không có lịch khám nào trong ngày này.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
</form>
<?php foot(); ?>
