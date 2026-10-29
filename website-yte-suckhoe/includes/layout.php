<?php
function head(string $title): void
{
 $u=user();$p=url();$current=basename($_SERVER['SCRIPT_NAME']??'');
 $links=['index.php'=>'Trang chủ','suc-khoe.php'=>'Sức khỏe','bac-si.php'=>'Bác sĩ','dat-lich.php'=>'Lịch khám','tin-tuc.php'=>'Tin tức'];
 ?><!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#205d49"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="HealthConnect"><meta name="csrf-token" content="<?php echo e(token()); ?>"><title><?php echo e($title); ?> · HealthConnect</title><link rel="manifest" href="<?php echo $p; ?>manifest.webmanifest"><link rel="icon" type="image/png" href="<?php echo $p; ?>assets/icons/icon-192.png"><link rel="apple-touch-icon" href="<?php echo $p; ?>assets/icons/icon-192.png"><link rel="stylesheet" href="<?php echo $p; ?>assets/vendor/bootstrap.min.css"><link rel="stylesheet" href="<?php echo $p; ?>assets/css/style.css"><link rel="stylesheet" href="<?php echo $p; ?>assets/css/app.css"></head><body>
 <a class="skip-link" href="#main-content">Đến nội dung chính</a><header class="hc-header"><div class="container hc-topbar"><a class="hc-brand" href="<?php echo $p; ?>index.php"><span class="brand-icon" aria-hidden="true">✚</span><span>HealthConnect<small>SỔ TAY SỨC KHỎE</small></span></a><button class="menu-toggle btn btn-outline-brand" id="menu-toggle" aria-controls="main-nav" aria-expanded="false">Menu</button><nav id="main-nav" class="hc-nav" aria-label="Điều hướng chính"><?php foreach($links as $file=>$label): ?><a href="<?php echo $p.$file; ?>" <?php echo $current===$file&&!defined('ADMIN_PAGE')?'aria-current="page"':''; ?>><?php echo e($label); ?></a><?php endforeach; ?><?php if($u): ?><a href="<?php echo $p; ?>tai-khoan.php">Tài khoản</a><?php if($u['role']==='admin'): ?><a href="<?php echo $p; ?>admin/dashboard.php">Quản trị</a><?php endif; ?><form method="post" action="<?php echo $p; ?>dang-xuat.php"><?php echo csrf(); ?><button class="btn btn-sm btn-outline-brand">Đăng xuất</button></form><?php else: ?><a class="btn btn-brand" href="<?php echo $p; ?>dang-nhap.php">Đăng nhập</a><?php endif; ?></nav></div></header>
 <div id="connection-state" class="connection-state" role="status" hidden>Bạn đang ngoại tuyến. Kết nối mạng để cập nhật dữ liệu.</div><main id="main-content" class="container py-4"><div class="page-heading"><span class="eyebrow"><?php echo defined('ADMIN_PAGE')?'KHÔNG GIAN QUẢN TRỊ':'CHĂM SÓC BẢN THÂN, MỖI NGÀY'; ?></span><h1><?php echo e($title); ?></h1></div><?php if(isset($_SESSION['flash'])): ?><div class="alert alert-success" role="status"><?php echo e($_SESSION['flash']); ?></div><?php unset($_SESSION['flash']);endif;
}
function foot(): void
{
 $p=url();$current=basename($_SERVER['SCRIPT_NAME']??'');
 ?></main><footer class="hc-footer"><div class="container footer-grid"><div><strong>HealthConnect</strong><p>Sổ tay sức khỏe & lịch hẹn của bạn.</p><small>Thông tin tham khảo, không thay thế chẩn đoán hoặc điều trị của bác sĩ.</small></div><div><a href="<?php echo $p; ?>huong-dan-app.php">Cài ứng dụng</a><a href="<?php echo $p; ?>quyen-rieng-tu.php">Dữ liệu & quyền riêng tư</a><a href="<?php echo $p; ?>lich-su-chat.php">Lịch sử trò chuyện</a><button type="button" id="install-app" class="btn btn-outline-brand" hidden>Cài HealthConnect</button></div></div></footer>
 <nav class="mobile-tabs" aria-label="Điều hướng điện thoại"><?php foreach(['index.php'=>['⌂','Trang chủ'],'suc-khoe.php'=>['♡','Sức khỏe'],'dat-lich.php'=>['▦','Lịch khám'],'tai-khoan.php'=>['○','Tài khoản']] as $file=>[$icon,$label]): ?><a href="<?php echo $p.$file; ?>" <?php echo $current===$file?'aria-current="page"':''; ?>><span aria-hidden="true"><?php echo $icon; ?></span><?php echo $label; ?></a><?php endforeach; ?></nav>
 <script src="<?php echo $p; ?>assets/vendor/bootstrap.bundle.min.js"></script><script>window.HC_BASE=<?php echo json_encode($p); ?>;</script><script src="<?php echo $p; ?>assets/js/app.js" defer></script><script src="<?php echo $p; ?>assets/js/chatbot-php.js" defer></script></body></html><?php
}
function input(
    string $name,
    string $label,
    $value = "",
    string $type = "text",
    bool $required = false,
): void {
    ?><label class="form-label" for="<?php echo e($name); ?>"><?php echo e(
    $label,
); ?></label><input class="form-control" id="<?php echo e(
    $name,
); ?>" name="<?php echo e($name); ?>" type="<?php echo e(
    $type,
); ?>" value="<?php echo e($value); ?>" <?php echo $required
    ? "required"
    : ""; ?>><?php
}
function cards(array $articles): void
{
    echo '<div class="row g-4">';
    foreach (
        $articles
        as $a
    ): $isExternal = !empty($a["source_url"]); ?><div class="col-md-4"><article class="article-card p-4 h-100"><?php if (
    !empty($a["thumbnail_url"])
): ?><img src="<?php echo e(
    str_starts_with($a["thumbnail_url"], "uploads/") ? url($a["thumbnail_url"]) : $a["thumbnail_url"],
); ?>" alt="" class="img-fluid mb-3" loading="lazy"><?php endif; ?><small><?php echo e(
    $a["category_name"] ?? "",
); ?> · <?php echo e(
     date("d/m/Y", strtotime($a["created_at"])),
 ); ?><?php if ($isExternal): ?> · <?php echo e($a["source_name"] ?? "Nguồn tin"); ?><?php endif; ?></small><h2 class="h5 mt-2"><a <?php echo $isExternal ? 'target="_blank" rel="noopener noreferrer"' : ""; ?> href="<?php echo $isExternal ? e($a["source_url"]) : url(
    "chi-tiet-bai-viet.php?id=" . (int) $a["id"],
); ?>"><?php echo e($a["title"]); ?></a></h2><p><?php echo e(
    $a["summary"],
); ?></p></article></div><?php endforeach;
    echo "</div>";
    if (!$articles) {
        echo "<p>Chưa có bài viết phù hợp.</p>";
    }
}
