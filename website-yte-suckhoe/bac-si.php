<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/layout.php';require __DIR__.'/includes/appointments.php';
$specialty=mb_substr(trim((string)($_GET['specialty']??'')),0,100,'UTF-8');
$q=mb_substr(trim((string)($_GET['q']??'')),0,100,'UTF-8');
$doctorId=max(0,(int)($_GET['id']??0));
$days=['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'];
$featuredExperts=[
 ['specialty'=>'Tim mạch','name'=>'GS.TS.NGND Nguyễn Lân Việt','note'=>'Nguyên Viện trưởng Viện Tim mạch Việt Nam, Bệnh viện Bạch Mai.','url'=>'https://bachmai.gov.vn/bai-viet/gs-ts-nguyen-lan-viet-chuyen-gia-tim-mach-hang-dau-viet-nam?id=16a2fe1e-cbb4-e4d5-1e8c-6faeef811866','source'=>'Bệnh viện Bạch Mai'],
 ['specialty'=>'Đột quỵ / thần kinh','name'=>'GS.TS Mai Duy Tôn','note'=>'Giám đốc Trung tâm Đột quỵ, Bệnh viện Bạch Mai theo hồ sơ bệnh viện.','url'=>'https://bachmai.gov.vn/doi-ngu-bac-si/6622e0a5-48ab-b217-4a2a-5a19144c1e10','source'=>'Bệnh viện Bạch Mai'],
 ['specialty'=>'Nhi khoa','name'=>'GS.TS Nguyễn Thanh Liêm','note'=>'Nguyên Giám đốc Bệnh viện Nhi Trung ương; chuyên gia ngoại nhi.','url'=>'https://benhviennhitrunguong.gov.vn/gs-ts-nguyen-thanh-liem-nguoi-tien-phong.html','source'=>'Bệnh viện Nhi Trung ương'],
 ['specialty'=>'Sản phụ khoa','name'=>'GS.TS Nguyễn Viết Tiến','note'=>'Từng là Giám đốc Bệnh viện Phụ sản Trung ương và Trưởng Bộ môn Phụ sản, Đại học Y Hà Nội.','url'=>'https://benhvienphusantrunguong.org.vn/news/tin-tuc-su-kien/cac-su-kien-noi-bat/anh-hung-lao-dong-thoi-ky-doi-moi-nguyen-viet-tien.html','source'=>'Bệnh viện Phụ sản Trung ương'],
 ['specialty'=>'Nội tiết','name'=>'PGS.TS.BS Tạ Văn Bình','note'=>'Nguyên Giám đốc Bệnh viện Nội tiết Trung ương giai đoạn 1999–2009.','url'=>'https://benhviennoitiet.vn/gioi-thieu/lich-su-phat-trien/','source'=>'Bệnh viện Nội tiết Trung ương'],
 ['specialty'=>'Da liễu','name'=>'GS.TS Trần Hậu Khang','note'=>'Nguyên Giám đốc Bệnh viện Da liễu Trung ương, chuyên gia da liễu.','url'=>'https://soyte.hatinh.gov.vn/tin-tuc-su-kien/ra-mat-cuon-sach-dau-tien-ve-benh-da-hiem-gap-o-viet-nam.html','source'=>'Sở Y tế Hà Tĩnh'],
 ['specialty'=>'Ung bướu','name'=>'GS.TS Nguyễn Bá Đức','note'=>'Nguyên Giám đốc Bệnh viện K, chuyên gia ung bướu.','url'=>'https://benhvienk.vn/gioi-thieu-khoa-noi-1-nd91154.html','source'=>'Bệnh viện K'],
 ['specialty'=>'Nhãn khoa','name'=>'GS.TS Tôn Thị Kim Thanh','note'=>'Được Bệnh viện Mắt Trung ương giới thiệu là Chủ tịch Hội Nhãn khoa Việt Nam tại hội thảo năm 2022.','url'=>'https://www.benhvienmat.vn/hoi-thao-nhung-tien-bo-trong-chan-doan-va-dieu-tri-benh-ly-nhan-khoa-n6973.html','source'=>'Bệnh viện Mắt Trung ương'],
];
$loadSchedule=static function(int $id) use ($days): array {
    $schedule=[];
    foreach(query('SELECT weekday,TIME_FORMAT(slot,\'%H:%i\') slot FROM doctor_schedules WHERE doctor_id=? ORDER BY weekday,slot',[$id])->fetchAll() as $slot) {
        $day=(int)$slot['weekday'];if(isset($days[$day]))$schedule[$day][]=$slot['slot'];
    }
    return $schedule;
};
$loadDaysOff=static fn(int $id)=>query('SELECT off_date FROM doctor_days_off WHERE doctor_id=? AND off_date>=CURRENT_DATE ORDER BY off_date LIMIT 5',[$id])->fetchAll(PDO::FETCH_COLUMN);
if(array_key_exists('id',$_GET)) {
    $doctor=query('SELECT * FROM doctors WHERE id=? AND active=1',[$doctorId])->fetch();
    head($doctor?'Bác sĩ '.(string)$doctor['name']:'Không tìm thấy bác sĩ');
    if(!$doctor): ?><div class="empty-state"><h2 class="h5">Không tìm thấy hồ sơ bác sĩ</h2><p>Bác sĩ này không tồn tại hoặc hiện không nhận đặt lịch.</p><a class="btn btn-brand" href="bac-si.php">Quay lại danh sách bác sĩ</a></div><?php else:
        $schedule=$loadSchedule($doctorId);$daysOff=$loadDaysOff($doctorId); ?>
        <p><a href="bac-si.php">← Quay lại danh sách bác sĩ</a></p>
        <article class="doctor-card"><div class="doctor-avatar" aria-hidden="true"><?php echo e(mb_substr((string)$doctor['name'],0,1,'UTF-8')); ?></div><span class="eyebrow"><?php echo e($doctor['specialty']); ?></span><h2><?php echo e($doctor['name']); ?></h2><p><?php echo nl2br(e((string)$doctor['bio'])); ?></p><p><strong>Phòng khám:</strong> <?php echo e((string)$doctor['room']); ?></p>
        <h3 class="h5">Lịch làm việc hàng tuần</h3><?php if($schedule): ?><ul><?php foreach($schedule as $day=>$slots): ?><li><?php echo e($days[$day]); ?>: <?php echo e(implode(', ',$slots)); ?></li><?php endforeach; ?></ul><?php else: ?><p>Chưa có lịch khám hàng tuần được cập nhật.</p><?php endif; ?>
        <h3 class="h5">Ngày nghỉ sắp tới</h3><?php if($daysOff): ?><ul><?php foreach($daysOff as $off): ?><li><?php echo e(date('d/m/Y',strtotime((string)$off))); ?></li><?php endforeach; ?></ul><?php else: ?><p>Chưa có ngày nghỉ sắp tới.</p><?php endif; ?>
        <a class="btn btn-brand mt-2" href="dat-lich.php?doctor=<?php echo (int)$doctor['id']; ?>">Đặt lịch</a></article>
    <?php endif; foot();return;
}
$where='active=1';$params=[];
if($specialty!==''){$where.=' AND specialty=?';$params[]=$specialty;}
if($q!==''){$where.=' AND name LIKE ?';$params[]='%'.$q.'%';}
$rows=query('SELECT * FROM doctors WHERE '.$where.' ORDER BY name',$params)->fetchAll();
$doctorCount=(int)query('SELECT COUNT(*) FROM doctors')->fetchColumn();
head('Tìm bác sĩ phù hợp');
?>
<p class="page-intro">Xem chuyên khoa, lịch làm việc và gửi yêu cầu đặt lịch. Hồ sơ bác sĩ mẫu dùng để minh họa đồ án.</p>
<details class="hc-panel mb-4"><summary class="fw-semibold">Tham khảo 8 bác sĩ tiêu biểu theo chuyên ngành</summary><p class="small text-muted mt-3">Các bác sĩ dưới đây là nhân vật có thật, chỉ để tham khảo về chuyên ngành; họ không liên kết và không nhận đặt lịch qua HealthConnect.</p><div class="row g-3"><?php foreach($featuredExperts as $expert): ?><div class="col-md-6 col-xl-3"><article class="appointment-card h-100 mb-0"><span class="eyebrow"><?php echo e($expert['specialty']); ?></span><strong><?php echo e($expert['name']); ?></strong><p class="small mb-2"><?php echo e($expert['note']); ?></p><a class="small" href="<?php echo e($expert['url']); ?>" target="_blank" rel="noopener noreferrer">Nguồn: <?php echo e($expert['source']); ?> ↗</a></article></div><?php endforeach; ?></div></details>
<form class="row g-2 mb-4" method="get"><div class="col-12 col-md-5"><label class="form-label" for="q">Tìm theo tên bác sĩ</label><input class="form-control" id="q" name="q" maxlength="100" value="<?php echo e($q); ?>" placeholder="Nhập tên bác sĩ"></div><div class="col-12 col-md-5"><label class="form-label" for="specialty">Chuyên khoa</label><select id="specialty" name="specialty" class="form-select"><option value="">Tất cả chuyên khoa</option><?php foreach(appt_specialties() as $sp): ?><option value="<?php echo e($sp); ?>" <?php echo $specialty===$sp?'selected':''; ?>><?php echo e($sp); ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-2 d-flex align-items-end mb-3"><button class="btn btn-brand w-100">Tìm bác sĩ</button></div></form>
<div class="row g-4"><?php foreach($rows as $d): $id=(int)$d['id'];$schedule=$loadSchedule($id);$daysOff=$loadDaysOff($id); ?><div class="col-md-6 col-xl-4"><article class="doctor-card"><div class="doctor-avatar" aria-hidden="true"><?php echo e(mb_substr((string)$d['name'],0,1,'UTF-8')); ?></div><span class="eyebrow"><?php echo e($d['specialty']); ?></span><h2 class="h4 mt-2"><a href="bac-si.php?id=<?php echo $id; ?>"><?php echo e($d['name']); ?></a></h2><p><?php echo nl2br(e((string)$d['bio'])); ?></p><p class="small text-muted"><strong>Phòng khám:</strong> <?php echo e((string)$d['room']); ?></p><strong class="small">Lịch khám:</strong><?php if($schedule): ?><ul class="small"><?php foreach($schedule as $day=>$slots): ?><li><?php echo e($days[$day].': '.implode(', ',$slots)); ?></li><?php endforeach; ?></ul><?php else: ?><p class="small">Chưa cập nhật lịch khám.</p><?php endif; ?><?php if($daysOff): ?><p class="small"><strong>Ngày nghỉ sắp tới:</strong> <?php echo e(implode(', ',array_map(fn($date)=>date('d/m/Y',strtotime((string)$date)),$daysOff))); ?></p><?php endif; ?><div class="d-flex flex-wrap gap-2 mt-auto"><a class="btn btn-brand" href="dat-lich.php?doctor=<?php echo $id; ?>">Đặt lịch</a><a class="btn btn-outline-brand" href="bac-si.php?id=<?php echo $id; ?>">Chi tiết</a></div></article></div><?php endforeach; ?></div>
<?php if(!$rows): ?><div class="empty-state"><?php if($doctorCount===0): ?>Hiện chưa có bác sĩ nào trong hệ thống.<?php else: ?>Không tìm thấy bác sĩ phù hợp với tên hoặc chuyên khoa đã chọn.<?php endif; ?></div><?php endif; foot(); ?>
