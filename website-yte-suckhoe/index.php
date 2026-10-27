<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
require __DIR__ . "/includes/appointments.php";
$localArticles = query(
    "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.status='published' ORDER BY a.created_at DESC,a.id DESC LIMIT 6",
)->fetchAll();
require __DIR__ . "/includes/news_sync.php";
$articles = array_merge($localArticles, array_slice(news_listing_items(), 0, 6));
usort($articles, static fn($a, $b) => strcmp($b["created_at"], $a["created_at"]));
$articles = array_slice($articles, 0, 6);
head("Chăm sóc sức khỏe từ những điều nhỏ nhất");
?><section class="hero p-4 mb-4"><p class="lead">Tra cứu kiến thức sức khỏe, theo dõi BMI và trò chuyện với trợ lý sức khỏe.</p><a class="btn btn-brand" href="bmi.php">Đo BMI ngay</a> <a class="btn btn-brand" href="dat-lich.php">Đặt lịch khám</a> <a class="btn btn-outline-brand" href="tin-tuc.php">Khám phá bài viết</a></section><?php
$me = user();
$upcoming = [];
if ($me) {
    try {
        $upcoming = query(
            "SELECT * FROM appointments WHERE user_id=? AND appt_date>=CURDATE() AND status IN ('pending','confirmed') ORDER BY appt_date, appt_time LIMIT 3",
            [$me["id"]],
        )->fetchAll();
    } catch (Throwable $ex) {
        error_log((string) $ex); // chưa tạo bảng appointments thì bỏ qua, không làm hỏng trang chủ
    }
}
if ($upcoming): ?><section class="mb-4"><h2 class="h5 mb-3">Lịch khám sắp tới của bạn</h2><div class="list-group"><?php foreach ($upcoming as $a): ?><a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="dat-lich.php"><span><strong><?php echo e(date("d/m/Y", strtotime($a["appt_date"]))); ?> lúc <?php echo e(substr($a["appt_time"], 0, 5)); ?></strong> · <?php echo e($a["specialty"]); ?></span><?php echo appt_badge($a["status"]); ?></a><?php endforeach; ?></div></section><?php endif; ?><h2 class="h4 mb-3">Bài viết mới nhất</h2><?php
cards($articles);
foot();
 ?>
