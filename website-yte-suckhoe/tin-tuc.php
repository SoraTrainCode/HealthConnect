<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$q = mb_substr(trim((string) ($_GET["q"] ?? "")), 0, 200);
$cat = max(0, (int) ($_GET["category"] ?? 0));
$page = max(1, (int) ($_GET["page"] ?? 1));
$where = "a.status='published'";
$params = [];
if ($q !== "") {
    $where .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $params[] = "%" . $q . "%";
    $params[] = "%" . $q . "%";
}
if ($cat) {
    $where .= " AND a.category_id=?";
    $params[] = $cat;
}
$count = (int) query(
    "SELECT COUNT(*) FROM articles a WHERE " . $where,
    $params,
)->fetchColumn();
$pages = max(1, (int) ceil($count / 9));
$page = min($page, $pages);
$offset = ($page - 1) * 9;
$articles = query(
    "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE " .
        $where .
        " ORDER BY a.created_at DESC,a.id DESC LIMIT 9 OFFSET " .
        $offset,
    $params,
)->fetchAll();
head("Tin tức y tế");
?><form class="row mb-4" method="get"><div class="col-md-6"><input class="form-control" name="q" value="<?php echo e(
    $q,
); ?>" placeholder="Tìm theo tiêu đề hoặc nội dung"></div><div class="col-md-4"><select name="category" class="form-select"><option value="0">Tất cả danh mục</option><?php foreach (
    query("SELECT * FROM categories ORDER BY name")
    as $c
): ?><option value="<?php echo $c["id"]; ?>" <?php echo $cat == $c["id"]
    ? "selected"
    : ""; ?>><?php echo e(
    $c["name"],
); ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-brand">Tìm kiếm</button></div></form><?php cards(
    $articles,
); ?><nav class="mt-4 d-flex gap-2"><?php for (
    $i = max(1, $page - 2);
    $i <= min($pages, $page + 2);
    $i++
): ?><a class="btn <?php echo $i === $page
    ? "btn-brand"
    : "btn-outline-brand"; ?>" href="?<?php echo e(
    http_build_query(["q" => $q, "category" => $cat, "page" => $i]),
); ?>"><?php echo $i; ?></a><?php endfor; ?></nav><?php foot(); ?>
