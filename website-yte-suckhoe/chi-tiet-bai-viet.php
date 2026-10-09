<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$id = (int) ($_GET["id"] ?? 0);
$a = query(
    "SELECT a.*,c.name category_name,u.full_name FROM articles a JOIN categories c ON c.id=a.category_id JOIN users u ON u.id=a.author_id WHERE a.id=? AND a.status='published'",
    [$id],
)->fetch();
if (!$a) {
    http_response_code(404);
    head("Không tìm thấy bài viết");
    foot();
    exit();
}
query("UPDATE articles SET view_count=view_count+1 WHERE id=?", [$id]);
head($a["title"]);
?><p><?php echo e($a["category_name"]); ?> · <?php echo e(
     $a["full_name"],
 ); ?> · <?php echo e(
     date("d/m/Y", strtotime($a["created_at"])),
 ); ?> · <?php echo (int) $a["view_count"] + 1; ?> lượt xem</p><?php if (
     $a["thumbnail_url"]
 ): ?><img class="img-fluid mb-4" style="max-height:400px" src="<?php echo e(
    $a["thumbnail_url"],
); ?>" alt=""><?php endif; ?><p class="lead"><?php echo e(
    $a["summary"],
); ?></p><div class="article-content mb-5"><?php echo safe_content(
    $a["content"],
); ?></div><h2 class="h4">Bài viết liên quan</h2><?php
cards(
    query(
        "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE a.category_id=? AND a.id<>? AND a.status='published' ORDER BY a.created_at DESC LIMIT 3",
        [$a["category_id"], $id],
    )->fetchAll(),
);
foot();
 ?>
