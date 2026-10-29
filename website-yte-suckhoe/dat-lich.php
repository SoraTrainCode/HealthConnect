<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/layout.php';
require __DIR__.'/includes/appointments.php';
$u=require_user(); $error=null;
$doctorId=max(0,(int)($_POST['doctor_id']??$_GET['doctor']??0));
$date=(string)($_POST['date']??$_GET['date']??date('Y-m-d',strtotime('+1 day')));
if (!appt_valid_date($date) || $date<date('Y-m-d') || $date>date('Y-m-d',strtotime('+30 days'))) $date=date('Y-m-d',strtotime('+1 day'));
if(post()) {
 check_csrf();
 try {
  if(($_POST['act']??'')==='cancel') {appt_cancel((int)($_POST['id']??0),(int)$u['id']);flash('Đã hủy lịch khám.');redirect('dat-lich.php');}
  $id=appt_book($u,$doctorId,field('date',10),field('time',5),field('patient_name',120),field('phone',20),field('reason',1000));
  flash('Đã gửi yêu cầu đặt lịch. Vui lòng chờ phòng khám xác nhận.');redirect('phieu-hen.php?id='.$id);
 } catch(PDOException $ex) {error_log((string)$ex);$error='Chưa thể lưu lịch. Hãy thử lại hoặc kiểm tra nâng cấp database.';}
 catch(RuntimeException $ex) {$error=$ex->getMessage();}
}
$doctors=query('SELECT * FROM doctors WHERE active=1 ORDER BY specialty,name')->fetchAll();
$selected=null;foreach($doctors as $d) if((int)$d['id']===$doctorId) $selected=$d;
$slots=$selected?doctor_slots($doctorId,$date):[];
$taken=$selected?query("SELECT TIME_FORMAT(appt_time,'%H:%i'),COUNT(*) FROM appointments WHERE doctor_id=? AND appt_date=? AND status<>'cancelled' GROUP BY appt_time",[$doctorId,$date])->fetchAll(PDO::FETCH_KEY_PAIR):[];
$filter=in_array($_GET['filter']??'',['upcoming','all'],true)?$_GET['filter']:'upcoming';
$page=max(1,(int)($_GET['page']??1));
$where="a.user_id=?".($filter==='upcoming'?" AND a.status IN ('pending','confirmed') AND TIMESTAMP(a.appt_date,a.appt_time)>NOW()":"");
$total=(int)query('SELECT COUNT(*) FROM appointments a WHERE '.$where,[$u['id']])->fetchColumn();$pages=max(1,(int)ceil($total/10));$page=min($page,$pages);
$mine=query('SELECT a.*,d.name doctor_name FROM appointments a LEFT JOIN doctors d ON d.id=a.doctor_id WHERE '.$where.' ORDER BY a.appt_date '.($filter==='upcoming'?'ASC':'DESC').',a.appt_time LIMIT 10 OFFSET '.(($page-1)*10),[$u['id']])->fetchAll();
head('Đặt lịch khám');notice($error);
?>
<p class="page-intro">Chọn bác sĩ và khung giờ phù hợp. Lịch chỉ được chốt sau khi phòng khám xác nhận. Dữ liệu phòng khám trong đồ án là mô phỏng.</p>
<div class="row g-4"><section class="col-lg-5"><div class="hc-panel">
<span class="eyebrow">BƯỚC 1</span><h2 class="h5">Bác sĩ & ngày khám</h2>
<?php if(!$doctors): ?><p>Chưa có bác sĩ nhận lịch. Vui lòng quay lại sau.</p><?php else: ?>
<form method="get"><label for="doctor" class="form-label">Bác sĩ</label><select name="doctor" id="doctor" class="form-select" required><option value="">Chọn bác sĩ</option><?php foreach($doctors as $d): ?><option value="<?php echo (int)$d['id']; ?>" <?php echo $doctorId===$d['id'] || $doctorId===(int)$d['id']?'selected':''; ?>><?php echo e($d['name'].' · '.$d['specialty']); ?></option><?php endforeach; ?></select>
<label for="date" class="form-label">Ngày khám</label><input id="date" class="form-control" type="date" name="date" value="<?php echo e($date); ?>" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d',strtotime('+30 days')); ?>" required><button class="btn btn-outline-brand">Xem giờ trống</button></form>
<?php endif; ?>
<?php if($selected): ?><hr><span class="eyebrow">BƯỚC 2</span><h2 class="h5">Thông tin người khám</h2><p><?php echo e($selected['name'].' · '.$selected['room']); ?></p>
<?php if(!$slots): ?><p class="alert alert-info">Bác sĩ không nhận lịch ngày này. Hãy chọn ngày khác.</p><?php else: ?>
<form method="post"><?php echo csrf(); ?><input type="hidden" name="doctor_id" value="<?php echo $doctorId; ?>"><input type="hidden" name="date" value="<?php echo e($date); ?>">
<label for="time" class="form-label">Khung giờ</label><select name="time" id="time" class="form-select" required><option value="">Chọn giờ trống</option><?php foreach($slots as $slot): $available=empty($taken[$slot])&&appt_future($date,$slot); ?><option value="<?php echo e($slot); ?>" <?php echo !$available?'disabled':''; ?> <?php echo ($_POST['time']??'')===$slot?'selected':''; ?>><?php echo e($slot).' — '.($available?'Còn chỗ':'Không còn chỗ'); ?></option><?php endforeach; ?></select>
<?php input('patient_name','Họ tên người khám',$_POST['patient_name']??$u['full_name'],'text',true);input('phone','Số điện thoại',$_POST['phone']??$u['phone'],'tel',true); ?>
<label for="reason" class="form-label">Lý do khám (không bắt buộc)</label><textarea class="form-control" id="reason" name="reason" maxlength="1000" rows="3"><?php echo e($_POST['reason']??''); ?></textarea><button class="btn btn-brand w-100">Gửi yêu cầu đặt lịch</button></form>
<?php endif; endif; ?></div></section>
<section class="col-lg-7"><div class="section-heading"><h2 class="h4">Lịch khám của tôi</h2><a href="bac-si.php">Xem bác sĩ →</a></div><div class="d-flex gap-2 mb-3"><a class="btn btn-sm <?php echo $filter==='upcoming'?'btn-brand':'btn-outline-brand'; ?>" href="?filter=upcoming">Sắp tới</a><a class="btn btn-sm <?php echo $filter==='all'?'btn-brand':'btn-outline-brand'; ?>" href="?filter=all">Tất cả lịch</a></div>
<?php if(!$mine): ?><div class="empty-state"><h3 class="h5">Chưa có lịch trong mục này</h3><p>Chọn bác sĩ và ngày khám để tạo lịch hẹn đầu tiên.</p></div><?php endif; ?>
<?php foreach($mine as $a): ?><article class="appointment-card"><div class="d-flex justify-content-between flex-wrap gap-2"><strong><?php echo e(date('d/m/Y',strtotime($a['appt_date'])).' · '.substr($a['appt_time'],0,5)); ?></strong><?php echo appt_badge($a['status']); ?></div><h3 class="h6 mt-3"><?php echo e($a['doctor_name']??'Tiếp nhận chung (lịch cũ)'); ?></h3><p><?php echo e($a['specialty'].' · '.$a['patient_name']); ?></p><div class="d-flex gap-2"><a class="btn btn-sm btn-outline-brand" href="phieu-hen.php?id=<?php echo (int)$a['id']; ?>">Chi tiết lịch</a><?php if(in_array($a['status'],['pending','confirmed'],true)&&appt_future($a['appt_date'],$a['appt_time'])): ?><form method="post" onsubmit="return confirm('Hủy lịch khám này?')"><?php echo csrf(); ?><input type="hidden" name="act" value="cancel"><input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>"><button class="btn btn-sm btn-outline-danger">Hủy lịch</button></form><?php endif; ?></div></article><?php endforeach; ?>
<nav class="d-flex gap-2 mt-3" aria-label="Trang lịch khám"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a class="btn btn-sm <?php echo $i===$page?'btn-brand':'btn-outline-brand'; ?>" href="?<?php echo e(http_build_query(['filter'=>$filter,'page'=>$i])); ?>"><?php echo $i; ?></a><?php endfor; ?></nav></section></div><?php foot(); ?>
