<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/layout.php';require __DIR__.'/includes/appointments.php';
$u=require_user();$id=max(0,(int)($_GET['id']??0));
$a=query('SELECT a.*,d.name doctor_name,d.room FROM appointments a LEFT JOIN doctors d ON d.id=a.doctor_id WHERE a.id=? AND (a.user_id=? OR ?=1)',[$id,$u['id'],$u['role']==='admin'?1:0])->fetch();
if(!$a){http_response_code(404);head('Không tìm thấy lịch');echo '<p>Lịch không tồn tại hoặc bạn không có quyền xem.</p>';foot();exit;}
if(isset($_GET['calendar'])) {
 header('Content-Type: text/calendar; charset=utf-8');header('Content-Disposition: attachment; filename="HealthConnect-'.$id.'.ics"');
 $start=new DateTimeImmutable($a['appt_date'].' '.$a['appt_time'],new DateTimeZone('Asia/Ho_Chi_Minh'));
 $escape=fn($s)=>str_replace(["\\","\r","\n",';',','],["\\\\",'',"\\n","\\;","\\,"],$s);
 echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//HealthConnect//Appointment//VI\r\nBEGIN:VEVENT\r\nUID:hc-".$id.'-'.substr(hash('sha256',cfg('app_url')),0,16)."@healthconnect\r\nDTSTAMP:".gmdate('Ymd\THis\Z')."\r\nDTSTART:".$start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z')."\r\nDTEND:".$start->modify('+30 minutes')->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z')."\r\nSUMMARY:HealthConnect appointment\r\nDESCRIPTION:".$escape('Ma lich HC-'.$id.'. Kiem tra trang thai tren website truoc khi den.')."\r\nSTATUS:".($a['status']==='cancelled'?'CANCELLED':($a['status']==='confirmed'?'CONFIRMED':'TENTATIVE'))."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";exit;
}
head('Chi tiết lịch hẹn');
?>
<div class="hc-panel receipt"><div class="section-heading"><div><span class="eyebrow">HEALTHCONNECT</span><h2 class="h4">Phiếu hẹn HC-<?php echo $id; ?></h2></div><?php echo appt_badge($a['status']); ?></div>
<dl class="detail-grid"><dt>Người khám</dt><dd><?php echo e($a['patient_name']); ?></dd><dt>Ngày & giờ</dt><dd><?php echo e(date('d/m/Y',strtotime($a['appt_date'])).' · '.substr($a['appt_time'],0,5)); ?></dd><dt>Bác sĩ</dt><dd><?php echo e($a['doctor_name']??'Tiếp nhận chung (lịch cũ)'); ?></dd><dt>Chuyên khoa</dt><dd><?php echo e($a['specialty']); ?></dd><dt>Địa điểm</dt><dd><?php echo e($a['room']??'Liên hệ phòng khám'); ?></dd><dt>Điện thoại</dt><dd><?php echo e($a['phone']); ?></dd><dt>Lý do khám</dt><dd><?php echo nl2br(e($a['reason']?:'Không cung cấp')); ?></dd></dl>
<p class="small text-muted">Lịch mô phỏng phục vụ đồ án. Lịch lưu vào điện thoại là bản chụp tại thời điểm tải; kiểm tra trạng thái mới nhất trên website.</p>
<div class="d-flex flex-wrap gap-2 no-print"><button class="btn btn-brand" onclick="window.print()">In phiếu hẹn</button><a class="btn btn-outline-brand" href="?id=<?php echo $id; ?>&calendar=1">Thêm vào lịch điện thoại</a><a class="btn btn-outline-brand" href="dat-lich.php?filter=all">Lịch của tôi</a></div>
<h3 class="h6 mt-4">Lịch sử trạng thái</h3><ol class="timeline"><?php $events=query('SELECT * FROM appointment_events WHERE appointment_id=? ORDER BY id',[$id])->fetchAll();foreach($events as $event): ?><li><span><?php echo e($event['created_at']); ?></span> <?php echo appt_badge($event['status']); ?></li><?php endforeach; ?><?php if(!$events): ?><li>Lịch cũ chưa có nhật ký thay đổi.</li><?php endif; ?></ol></div><?php foot(); ?>
