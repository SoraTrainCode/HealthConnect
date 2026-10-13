<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
require __DIR__ . "/includes/appointments.php";
$u = require_user();
$error = null;
$today = date("Y-m-d");
$maxDate = date("Y-m-d", strtotime("+" . APPT_MAX_DAYS . " days"));

if (post()) {
    check_csrf();
    try {
        if (($_POST["act"] ?? "") === "cancel") {
            $n = query(
                "UPDATE appointments SET status='cancelled' WHERE id=? AND user_id=? AND status IN ('pending','confirmed') AND appt_date>=CURDATE()",
                [(int) ($_POST["id"] ?? 0), $u["id"]],
            )->rowCount();
            if (!$n) {
                throw new RuntimeException("Không thể hủy lịch này.");
            }
            flash("Đã hủy lịch khám.");
            redirect("dat-lich.php");
        }

        $date = field("date", 10);
        $time = field("time", 5);
        $name = field("patient_name", 120);
        $phone = field("phone", 20);
        $specialty = field("specialty", 80);
        $reason = field("reason", 1000);

        $dt = appt_valid_date($date);
        if (!$dt || $date < $today || $date > $maxDate || $dt->format("w") === "0") {
            throw new RuntimeException("Ngày khám không hợp lệ (không đặt Chủ nhật, tối đa " . APPT_MAX_DAYS . " ngày tới).");
        }
        if (!in_array($time, appt_slots(), true) || ($date === $today && $time <= date("H:i"))) {
            throw new RuntimeException("Khung giờ không hợp lệ hoặc đã qua.");
        }
        if (!in_array($specialty, appt_specialties(), true)) {
            throw new RuntimeException("Vui lòng chọn chuyên khoa.");
        }
        if ($name === "" || !preg_match('/^0\d{9,10}$/', $phone)) {
            throw new RuntimeException("Nhập họ tên và số điện thoại hợp lệ (10-11 số, bắt đầu bằng 0).");
        }

        db()->beginTransaction();
        $active = (int) query(
            "SELECT COUNT(*) FROM appointments WHERE user_id=? AND status IN ('pending','confirmed') AND appt_date>=CURDATE()",
            [$u["id"]],
        )->fetchColumn();
        if ($active >= APPT_MAX_ACTIVE) {
            throw new RuntimeException("Bạn đang có " . APPT_MAX_ACTIVE . " lịch chưa khám. Hãy hủy hoặc chờ khám xong trước khi đặt thêm.");
        }
        $dup = query(
            "SELECT id FROM appointments WHERE user_id=? AND appt_date=? AND appt_time=? AND status IN ('pending','confirmed')",
            [$u["id"], $date, $time],
        )->fetch();
        if ($dup) {
            throw new RuntimeException("Bạn đã có lịch vào khung giờ này.");
        }
        $taken = (int) query(
            "SELECT COUNT(*) FROM appointments WHERE appt_date=? AND appt_time=? AND status<>'cancelled' FOR UPDATE",
            [$date, $time],
        )->fetchColumn();
        if ($taken >= APPT_CAPACITY) {
            throw new RuntimeException("Khung giờ này đã đầy, vui lòng chọn giờ khác.");
        }
        query(
            "INSERT INTO appointments(user_id,patient_name,phone,specialty,appt_date,appt_time,reason) VALUES(?,?,?,?,?,?,?)",
            [$u["id"], $name, $phone, $specialty, $date, $time, $reason],
        );
        db()->commit();
        flash("Đặt lịch thành công. Phòng khám sẽ xác nhận sớm.");
        redirect("dat-lich.php");
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $error = $ex->getMessage();
    }
}

// Ngày đang chọn (bước 1) và số chỗ còn lại theo từng khung giờ
$pick = (string) ($_POST["date"] ?? ($_GET["date"] ?? ""));
$pickDt = appt_valid_date($pick);
if (!$pickDt || $pick < $today || $pick > $maxDate) {
    $pick = "";
}
$left = [];
if ($pick) {
    $taken = query(
        "SELECT TIME_FORMAT(appt_time,'%H:%i') t, COUNT(*) c FROM appointments WHERE appt_date=? AND status<>'cancelled' GROUP BY appt_time",
        [$pick],
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach (appt_slots() as $s) {
        $left[$s] = APPT_CAPACITY - (int) ($taken[$s] ?? 0);
        if ($pick === $today && $s <= date("H:i")) {
            $left[$s] = 0;
        }
    }
}
$mine = query(
    "SELECT * FROM appointments WHERE user_id=? ORDER BY appt_date DESC, appt_time DESC LIMIT 20",
    [$u["id"]],
)->fetchAll();

head("Đặt lịch khám");
notice($error);
?>
<div class="row g-4">
  <div class="col-lg-6">
    <div class="auth-card p-4">
      <h2 class="h5">1. Chọn ngày khám</h2>
      <form method="get" class="mb-3">
        <input type="date" name="date" class="form-control" value="<?php echo e($pick); ?>"
               min="<?php echo e($today); ?>" max="<?php echo e($maxDate); ?>" onchange="this.form.submit()" required>
        <div class="form-text">Nhận lịch Thứ 2 - Thứ 7, đặt trước tối đa <?php echo APPT_MAX_DAYS; ?> ngày.</div>
      </form>

      <?php if ($pick): ?>
      <h2 class="h5">2. Thông tin lịch khám</h2>
      <form method="post"><?php echo csrf(); ?>
        <input type="hidden" name="date" value="<?php echo e($pick); ?>">
        <label>Khung giờ (<?php echo e(date("d/m/Y", strtotime($pick))); ?>)</label>
        <select name="time" class="form-select" required>
          <option value="">-- Chọn giờ --</option>
          <?php foreach ($left as $s => $n): ?>
            <option value="<?php echo e($s); ?>" <?php echo $n <= 0 ? "disabled" : ""; ?>
              <?php echo (($_POST["time"] ?? "") === $s) ? "selected" : ""; ?>>
              <?php echo e($s); ?> — <?php echo $n > 0 ? "còn " . $n . " chỗ" : "đã đầy"; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <label>Chuyên khoa</label>
        <select name="specialty" class="form-select" required>
          <?php foreach (appt_specialties() as $sp): ?>
            <option <?php echo (($_POST["specialty"] ?? "") === $sp) ? "selected" : ""; ?>><?php echo e($sp); ?></option>
          <?php endforeach; ?>
        </select>
        <?php
        input("patient_name", "Họ tên người khám", $_POST["patient_name"] ?? $u["full_name"], "text", true);
        input("phone", "Số điện thoại", $_POST["phone"] ?? ($u["phone"] ?? ""), "tel", true);
        ?>
        <label>Triệu chứng / lý do khám</label>
        <textarea name="reason" class="form-control" rows="3" maxlength="1000"><?php echo e($_POST["reason"] ?? ""); ?></textarea>
        <button class="btn btn-brand mt-3">Đặt lịch</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-6">
    <h2 class="h5">Lịch khám của tôi</h2>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead><tr><th>Ngày giờ</th><th>Chuyên khoa</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($mine as $a):
            $canCancel = in_array($a["status"], ["pending", "confirmed"], true) && $a["appt_date"] >= $today; ?>
          <tr>
            <td><?php echo e(date("d/m/Y", strtotime($a["appt_date"]))); ?><br><small><?php echo e(substr($a["appt_time"], 0, 5)); ?></small></td>
            <td><?php echo e($a["specialty"]); ?></td>
            <td><?php echo appt_badge($a["status"]); ?></td>
            <td>
              <?php if ($canCancel): ?>
              <form method="post" onsubmit="return confirm('Hủy lịch khám này?')"><?php echo csrf(); ?>
                <input type="hidden" name="act" value="cancel">
                <input type="hidden" name="id" value="<?php echo (int) $a["id"]; ?>">
                <button class="btn btn-sm btn-outline-danger">Hủy</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$mine): ?><tr><td colspan="4" class="text-muted text-center">Bạn chưa đặt lịch nào.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php foot(); ?>
