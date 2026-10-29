<?php
define('ADMIN_PAGE',true);
require dirname(__DIR__).'/includes/bootstrap.php';
require dirname(__DIR__).'/includes/layout.php';
require dirname(__DIR__).'/includes/admin_ui.php';
require dirname(__DIR__).'/includes/appointments.php';
require dirname(__DIR__).'/includes/demo.php';
require_admin(); $error=null;
$id=max(0,(int)($_POST['id']??$_GET['edit']??0));
if(post()) {
 check_csrf();
 try {
  appt_begin();
  if(($_POST['act']??'')==='seed') { seed_demo_doctors(); } elseif(($_POST['act']??'')==='dayoff') {
   $date=field('off_date',10);
   if(!appt_valid_date($date)||$date<date('Y-m-d')||!query('SELECT id FROM doctors WHERE id=?',[$id])->fetchColumn()) throw new RuntimeException('Bác sĩ hoặc ngày nghỉ không hợp lệ.');
   if(query("SELECT id FROM appointments WHERE doctor_id=? AND appt_date=? AND status IN ('pending','confirmed') LIMIT 1",[$id,$date])->fetchColumn()) throw new RuntimeException('Ngày này còn lịch hẹn. Hãy xử lý các lịch hẹn trước khi đặt ngày nghỉ.');
   query('INSERT IGNORE INTO doctor_days_off(doctor_id,off_date) VALUES(?,?)',[$id,$date]);
  } elseif(($_POST['act']??'')==='remove_dayoff') {
   query('DELETE FROM doctor_days_off WHERE doctor_id=? AND off_date=?',[$id,field('off_date',10)]);
  } else {
   $name=field('name',120);$specialty=field('specialty',80);$bio=field('bio',2000);$room=field('room',80);$active=isset($_POST['active'])?1:0;
   $days=array_values(array_unique(array_map('intval',(array)($_POST['days']??[]))));
   $slots=array_values(array_unique((array)($_POST['slots']??[])));
   if(!$name||!in_array($specialty,appt_specialties(),true)||!$days||!$slots||array_diff($days,[1,2,3,4,5,6])||array_diff($slots,appt_slots())) throw new RuntimeException('Nhập tên, chuyên khoa và ít nhất một ngày/giờ làm việc hợp lệ.');
   if($id) {
    if(!query('SELECT id FROM doctors WHERE id=?',[$id])->fetchColumn()) throw new RuntimeException('Không tìm thấy bác sĩ.');
    $booked=query("SELECT appt_date,appt_time FROM appointments WHERE doctor_id=? AND status IN ('pending','confirmed') AND TIMESTAMP(appt_date,appt_time)>NOW()",[$id])->fetchAll();
    foreach($booked as $a) if(!in_array((int)date('w',strtotime($a['appt_date'])),$days,true)||!in_array(substr($a['appt_time'],0,5),$slots,true)) throw new RuntimeException('Lịch làm việc mới bỏ khung giờ đã có người đặt. Hãy xử lý lịch hẹn đó trước.');
    query('UPDATE doctors SET name=?,specialty=?,bio=?,room=?,active=? WHERE id=?',[$name,$specialty,$bio,$room,$active,$id]);
   } else {
    query('INSERT INTO doctors(name,specialty,bio,room,active) VALUES(?,?,?,?,?)',[$name,$specialty,$bio,$room,$active]);$id=(int)db()->lastInsertId();
   }
   query('DELETE FROM doctor_schedules WHERE doctor_id=?',[$id]);
   foreach($days as $day) foreach($slots as $slot) query('INSERT INTO doctor_schedules(doctor_id,weekday,slot) VALUES(?,?,?)',[$id,$day,$slot]);
  }
  db()->commit();flash('Đã lưu thông tin bác sĩ / lịch làm việc.');redirect('bac-si.php?edit='.$id);
 } catch(Throwable $ex) {
  if(db()->inTransaction())db()->rollBack();
  if($ex instanceof PDOException) {error_log((string)$ex);$error='Chưa thể lưu thông tin. Kiểm tra database và thử lại.';} else $error=$ex->getMessage();
 }
}
$d=$id?query('SELECT * FROM doctors WHERE id=?',[$id])->fetch():null;
$schedule=$id?query('SELECT weekday,TIME_FORMAT(slot,"%H:%i") slot FROM doctor_schedules WHERE doctor_id=?',[$id])->fetchAll():[];
$days=array_map('intval',array_column($schedule,'weekday'));$slots=array_column($schedule,'slot');
if(!$id){$days=[1,2,3,4,5,6];$slots=['08:00','08:30','09:00','09:30','10:00','10:30','14:00','14:30','15:00'];}
$off=$id?query('SELECT off_date FROM doctor_days_off WHERE doctor_id=? AND off_date>=CURDATE() ORDER BY off_date',[$id])->fetchAll(PDO::FETCH_COLUMN):[];
head('Bác sĩ & lịch làm việc');admin_bar('bac-si.php');notice($error);
?>
<div class="row g-4"><section class="col-lg-5"><div class="hc-panel"><h2 class="h5">Danh sách bác sĩ</h2><a class="btn btn-brand mb-3" href="bac-si.php">Thêm bác sĩ</a><?php if(!(int)query('SELECT COUNT(*) FROM doctors')->fetchColumn()): ?><form method="post" class="mb-3"><?php echo csrf(); ?><input type="hidden" name="act" value="seed"><button class="btn btn-outline-brand">Tạo 3 hồ sơ minh họa</button></form><?php endif; ?>
<?php foreach(query('SELECT * FROM doctors ORDER BY active DESC,name') as $row): ?><article class="appointment-card"><strong><?php echo e($row['name']); ?></strong><p><?php echo e($row['specialty']); ?> · <?php echo $row['active']?'Đang nhận lịch':'Ngừng nhận lịch mới'; ?></p><a href="?edit=<?php echo (int)$row['id']; ?>">Chỉnh sửa & lịch làm việc →</a></article><?php endforeach; ?></div></section>
<section class="col-lg-7"><div class="hc-panel"><h2 class="h5"><?php echo $id?'Chỉnh sửa bác sĩ':'Thêm bác sĩ'; ?></h2><form method="post"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo $id; ?>">
<?php input('name','Tên hiển thị',$_POST['name']??$d['name']??'','text',true);input('room','Phòng khám / địa điểm',$_POST['room']??$d['room']??''); ?>
<label for="specialty" class="form-label">Chuyên khoa</label><select name="specialty" id="specialty" class="form-select"><?php foreach(appt_specialties() as $sp): ?><option <?php echo ($_POST['specialty']??$d['specialty']??'')===$sp?'selected':''; ?>><?php echo e($sp); ?></option><?php endforeach; ?></select>
<label for="bio" class="form-label">Giới thiệu</label><textarea id="bio" name="bio" class="form-control" maxlength="2000" rows="3"><?php echo e($_POST['bio']??$d['bio']??''); ?></textarea>
<fieldset class="mb-3"><legend class="h6">Ngày làm việc hằng tuần</legend><div class="choice-grid"><?php for($i=1;$i<=6;$i++): ?><label><input type="checkbox" name="days[]" value="<?php echo $i; ?>" <?php echo in_array($i,$days,true)?'checked':''; ?>> Thứ <?php echo $i+1; ?></label><?php endfor; ?></div></fieldset>
<fieldset class="mb-3"><legend class="h6">Giờ nhận lịch (một người / khung giờ)</legend><div class="choice-grid"><?php foreach(appt_slots() as $slot): ?><label><input type="checkbox" name="slots[]" value="<?php echo e($slot); ?>" <?php echo in_array($slot,$slots,true)?'checked':''; ?>> <?php echo e($slot); ?></label><?php endforeach; ?></div></fieldset>
<label class="d-block mb-3"><input type="checkbox" name="active" <?php echo !$d||$d['active']?'checked':''; ?>> Nhận lịch mới</label><p class="small text-muted">Ngừng nhận lịch mới vẫn giữ các lịch hẹn đã đặt. Đổi chuyên khoa không sửa lịch sử của bệnh nhân.</p><button class="btn btn-brand">Lưu bác sĩ & lịch</button></form></div>
<?php if($d): ?><div class="hc-panel mt-4"><h2 class="h5">Ngày nghỉ riêng</h2><form method="post"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="act" value="dayoff"><?php input('off_date','Ngày nghỉ','','date',true); ?><button class="btn btn-outline-brand">Thêm ngày nghỉ</button></form><div class="mt-3"><?php foreach($off as $date): ?><form method="post" class="d-flex justify-content-between align-items-center mb-2"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo $id; ?>"><input type="hidden" name="act" value="remove_dayoff"><input type="hidden" name="off_date" value="<?php echo e($date); ?>"><span><?php echo e($date); ?></span><button class="btn btn-sm btn-outline-danger">Bỏ ngày nghỉ</button></form><?php endforeach; ?></div></div><?php endif; ?></section></div><?php foot(); ?>
