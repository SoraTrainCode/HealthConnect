<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require_admin();
head("Tổng quan quản trị");
?><div class="row g-3"><?php foreach (
    [
        "articles" => "Bài viết",
        "categories" => "Danh mục",
        "users" => "Tài khoản",
        "bmi_logs" => "Lần đo BMI",
        "chat_sessions" => "Phiên chat",
    ]
    as $table => $label
): ?><div class="col-md"><div class="stat-card"><div class="num"><?php echo query(
    "SELECT COUNT(*) FROM " . $table,
)->fetchColumn(); ?></div><div><?php echo e(
    $label,
); ?></div></div></div><?php endforeach; ?></div><h2 class="h5 mt-4">Bài viết được xem nhiều</h2><?php
cards(
    query(
        "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id ORDER BY view_count DESC LIMIT 6",
    )->fetchAll(),
);
foot();
 ?>
