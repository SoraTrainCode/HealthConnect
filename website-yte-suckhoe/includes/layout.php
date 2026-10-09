<?php
function head(string $title): void
{
    $u = user();
    $p = url();
    ?><!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo e($title); ?> • Sổ Tay Sức Khỏe</title><link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet"><link href="<?php echo $p; ?>assets/css/style.css" rel="stylesheet"><meta name="csrf-token" content="<?php echo e(token()); ?>"><style>.article-content{white-space:pre-wrap}.table-responsive{overflow-x:auto}.form-control,.form-select{margin-bottom:12px}.site-header nav{gap:12px;flex-wrap:wrap}main{min-height:65vh}.chat-bubble{white-space:pre-wrap}img.avatar{width:80px;height:80px;object-fit:cover;border-radius:50%}</style></head><body><header class="site-header"><nav class="container d-flex align-items-center py-3"><a class="brand-mark me-auto" href="<?php echo $p; ?>index.php">✚ Sổ Tay Sức Khỏe</a><a href="<?php echo $p; ?>tin-tuc.php">Tin tức y tế</a><a href="<?php echo $p; ?>bmi.php">Công cụ BMI</a><?php if ($u): ?><a href="<?php echo $p; ?>tai-khoan.php"><?php echo e($u["full_name"]); ?></a><?php if ($u["role"] === "admin"): ?><a href="<?php echo $p; ?>admin/dashboard.php">Quản trị</a><?php endif; ?><form action="<?php echo $p; ?>dang-xuat.php" method="post" class="m-0"><?php echo csrf(); ?><button class="btn btn-outline-brand">Đăng xuất</button></form><?php else: ?><a href="<?php echo $p; ?>dang-nhap.php">Đăng nhập</a><a class="btn btn-brand" href="<?php echo $p; ?>dang-ky.php">Đăng ký</a><?php endif; ?></nav></header><main class="container py-4"><?php if (defined("ADMIN_PAGE")): ?><nav class="d-flex gap-3 flex-wrap mb-4"><a href="dashboard.php">Tổng quan</a><a href="bai-viet.php">Bài viết</a><a href="danh-muc.php">Danh mục</a><a href="nguoi-dung.php">Người dùng</a></nav><?php endif; ?><h1 class="h3 mb-4"><?php echo e($title); ?></h1><?php if (isset($_SESSION["flash"])): ?><div class="alert alert-success"><?php echo e($_SESSION["flash"]); ?></div><?php unset($_SESSION["flash"]);endif;
}
function foot(): void
{
    ?></main><footer class="site-footer mt-5 p-4"><div class="container">Sổ Tay Sức Khỏe — HealthConnect<p>Nội dung chỉ mang tính tham khảo, không thay thế chẩn đoán hoặc điều trị của bác sĩ.</p></div></footer><script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script><script>window.HC_BASE=<?php echo json_encode(
    url(),
); ?>;</script><script src="<?php echo url(
    "assets/js/chatbot-php.js",
); ?>"></script></body></html><?php
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
    ): ?><div class="col-md-4"><article class="article-card p-4 h-100"><?php if (
    !empty($a["thumbnail_url"])
): ?><img src="<?php echo e(
    str_starts_with($a["thumbnail_url"], "uploads/") ? url($a["thumbnail_url"]) : $a["thumbnail_url"],
); ?>" alt="" class="img-fluid mb-3" loading="lazy"><?php endif; ?><small><?php echo e(
    $a["category_name"] ?? "",
); ?> · <?php echo e(
     date("d/m/Y", strtotime($a["created_at"])),
 ); ?></small><h2 class="h5 mt-2"><a href="<?php echo url(
    "chi-tiet-bai-viet.php?id=" . (int) $a["id"],
); ?>"><?php echo e($a["title"]); ?></a></h2><p><?php echo e(
    $a["summary"],
); ?></p></article></div><?php endforeach;
    echo "</div>";
    if (!$articles) {
        echo "<p>Chưa có bài viết phù hợp.</p>";
    }
}
