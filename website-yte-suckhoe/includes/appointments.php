<?php
/** Cấu hình và hàm dùng chung cho chức năng đặt lịch khám. */
const APPT_CAPACITY = 3;      // số lượt tối đa mỗi khung giờ
const APPT_MAX_DAYS = 30;     // chỉ đặt trước tối đa 30 ngày
const APPT_MAX_ACTIVE = 3;    // mỗi người tối đa 3 lịch chưa khám

function appt_slots(): array
{
    return ["08:00","08:30","09:00","09:30","10:00","10:30","11:00","11:30",
            "13:30","14:00","14:30","15:00","15:30","16:00","16:30"];
}
function appt_specialties(): array
{
    return ["Khám tổng quát","Nội khoa","Nhi khoa","Tim mạch","Dinh dưỡng","Da liễu","Tai mũi họng","Răng hàm mặt"];
}
function appt_statuses(): array
{
    return [
        "pending"   => ["Chờ xác nhận", "text-bg-warning"],
        "confirmed" => ["Đã xác nhận",  "text-bg-primary"],
        "done"      => ["Đã khám",      "text-bg-success"],
        "cancelled" => ["Đã hủy",       "text-bg-secondary"],
        "no_show"   => ["Không đến",    "text-bg-danger"],
    ];
}
function appt_badge(string $status): string
{
    [$label, $cls] = appt_statuses()[$status] ?? [$status, "text-bg-light"];
    return '<span class="badge ' . $cls . '">' . e($label) . "</span>";
}
function appt_valid_date(string $d): ?DateTime
{
    $dt = DateTime::createFromFormat("!Y-m-d", $d);
    return ($dt && $dt->format("Y-m-d") === $d) ? $dt : null;
}

/** Một mutex InnoDB dùng chung: mọi đường ghi lịch đều phải lấy khóa này.
 * Quy mô đồ án nhỏ: ưu tiên tính đúng; khi mở rộng dùng khóa theo user/slot.
 * Khóa được giải phóng tự động khi commit, rollback hoặc mất kết nối.
 */
function appt_begin(): void
{
    db()->beginTransaction();
    if (!query("SELECT id FROM appointment_mutex WHERE id=1 FOR UPDATE")->fetchColumn()) {
        throw new RuntimeException("Cần chạy database/appointments.sql trước khi đặt lịch.");
    }
}
function appt_future(string $date, string $time): bool
{
    return $date . " " . substr($time, 0, 5) > date("Y-m-d H:i");
}
function appt_check_capacity(int $userId, string $date, string $time, ?int $doctorId = null): void
{
    if (query('SELECT status FROM users WHERE id=?',[$userId])->fetchColumn() !== 'active') {
        throw new RuntimeException('Tài khoản không còn hoạt động. Vui lòng đăng nhập lại.');
    }
    if ($doctorId !== null) {
        $doctor = query("SELECT * FROM doctors WHERE id=? AND active=1", [$doctorId])->fetch();
        if (!$doctor || !in_array(substr($time, 0, 5), doctor_slots($doctorId, $date), true)) {
            throw new RuntimeException("Bác sĩ không nhận lịch vào ngày/giờ đã chọn.");
        }
    }
    $active = (int) query("SELECT COUNT(*) FROM appointments WHERE user_id=? AND status IN ('pending','confirmed') AND TIMESTAMP(appt_date,appt_time)>NOW()", [$userId])->fetchColumn();
    if ($active >= APPT_MAX_ACTIVE) {
        throw new RuntimeException("Bạn đã có đủ " . APPT_MAX_ACTIVE . " lịch sắp tới.");
    }
    if (query("SELECT id FROM appointments WHERE user_id=? AND appt_date=? AND appt_time=? AND status IN ('pending','confirmed')", [$userId, $date, $time])->fetch()) {
        throw new RuntimeException("Bạn đã có lịch vào khung giờ này.");
    }
    $taken = (int) query("SELECT COUNT(*) FROM appointments WHERE appt_date=? AND appt_time=? AND doctor_id <=> ? AND status<>'cancelled'", [$date, $time, $doctorId])->fetchColumn();
    if ($taken >= ($doctorId === null ? APPT_CAPACITY : 1)) {
        throw new RuntimeException("Khung giờ này đã đầy, vui lòng chọn giờ khác.");
    }
}
function appt_next_status(string $current, string $action, bool $future): ?string
{
    if ($current === "pending") {
        return match ($action) {
            "confirm" => $future ? "confirmed" : null,
            "cancel" => "cancelled",
            default => null,
        };
    }
    if ($current === "confirmed") {
        return match ($action) {
            "done" => !$future ? "done" : null,
            "noshow" => !$future ? "no_show" : null,
            "cancel" => "cancelled",
            default => null,
        };
    }
    return $current === "cancelled" && $action === "reopen" && $future ? "pending" : null;
}
function appt_admin_update(int $id, string $action, ?int $actorId = null): void
{
    try {
        appt_begin();
        $a = query("SELECT * FROM appointments WHERE id=? FOR UPDATE", [$id])->fetch();
        if (!$a) {
            throw new RuntimeException("Không tìm thấy lịch khám.");
        }
        $next = appt_next_status($a["status"], $action, appt_future($a["appt_date"], $a["appt_time"]));
        if ($next === null) {
            throw new RuntimeException("Thao tác không phù hợp với trạng thái hoặc giờ khám. Hãy tải lại trang.");
        }
        if ($next === "pending") {
            $owner = query("SELECT status FROM users WHERE id=?", [$a["user_id"]])->fetchColumn();
            if ($owner !== "active") {
                throw new RuntimeException("Tài khoản người đặt không còn hoạt động.");
            }
            appt_check_capacity((int) $a["user_id"], $a["appt_date"], $a["appt_time"], isset($a["doctor_id"]) ? (int) $a["doctor_id"] : null);
        }
        query("UPDATE appointments SET status=? WHERE id=?", [$next, $id]);
        appt_event($id, $next, $actorId);
        db()->commit();
    } catch (Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        throw $ex;
    }
}

function doctor_slots(int $id, string $date): array
{
    $dt = appt_valid_date($date);
    if (!$dt || query("SELECT 1 FROM doctor_days_off WHERE doctor_id=? AND off_date=?", [$id,$date])->fetchColumn()) return [];
    return query("SELECT TIME_FORMAT(slot,'%H:%i') FROM doctor_schedules WHERE doctor_id=? AND weekday=? ORDER BY slot", [$id,(int)$dt->format('w')])->fetchAll(PDO::FETCH_COLUMN);
}
function appt_event(int $id, string $status, ?int $actor): void
{
    query("INSERT INTO appointment_events(appointment_id,actor_id,status) VALUES(?,?,?)", [$id,$actor,$status]);
}
function appt_book(array $u, int $doctorId, string $date, string $time, string $name, string $phone, string $reason): int
{
    $dt=appt_valid_date($date);
    if (!$dt || $date<date('Y-m-d') || $date>date('Y-m-d',strtotime('+'.APPT_MAX_DAYS.' days')) || !appt_future($date,$time)) throw new RuntimeException('Chọn lịch trong 30 ngày tới và chưa qua giờ.');
    if (trim($name)==='' || mb_strlen($name)>120 || !preg_match('/^0[0-9]{9,10}$/',$phone) || mb_strlen($reason)>1000) throw new RuntimeException('Kiểm tra họ tên, điện thoại (10–11 chữ số) và lý do khám.');
    try {
        appt_begin();
        if (!appt_future($date,$time)) throw new RuntimeException('Khung giờ vừa qua, hãy chọn giờ khác.');
        appt_check_capacity((int)$u['id'],$date,$time,$doctorId);
        $doctor=query('SELECT specialty FROM doctors WHERE id=?',[$doctorId])->fetch();
        query('INSERT INTO appointments(user_id,doctor_id,patient_name,phone,specialty,appt_date,appt_time,reason) VALUES(?,?,?,?,?,?,?,?)',[$u['id'],$doctorId,$name,$phone,$doctor['specialty'],$date,$time,$reason]);
        $id=(int)db()->lastInsertId();
        appt_event($id,'pending',(int)$u['id']);
        db()->commit(); return $id;
    } catch (Throwable $ex) { if(db()->inTransaction()) db()->rollBack(); throw $ex; }
}
function appt_cancel(int $id, int $userId): void
{
    try {
        appt_begin();
        $n=query("UPDATE appointments SET status='cancelled' WHERE id=? AND user_id=? AND status IN ('pending','confirmed') AND TIMESTAMP(appt_date,appt_time)>NOW()",[$id,$userId])->rowCount();
        if (!$n) throw new RuntimeException('Lịch không tồn tại, đã qua giờ hoặc không thể hủy.');
        appt_event($id,'cancelled',$userId); db()->commit();
    } catch(Throwable $ex) {if(db()->inTransaction()) db()->rollBack();throw $ex;}
}
