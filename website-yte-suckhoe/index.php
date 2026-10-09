<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$articles = query(
    "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.status='published' ORDER BY a.created_at DESC,a.id DESC LIMIT 6",
)->fetchAll();
head("Chăm sóc sức khỏe từ những điều nhỏ nhất");
?><section class="hero p-4 mb-4"><p class="lead">Tra cứu kiến thức sức khỏe, theo dõi BMI và trò chuyện với trợ lý sức khỏe.</p><a class="btn btn-brand" href="bmi.php">Đo BMI ngay</a> <a class="btn btn-outline-brand" href="tin-tuc.php">Khám phá bài viết</a></section><h2 class="h4 mb-3">Bài viết mới nhất</h2><?php
cards($articles);
foot();
 ?>
