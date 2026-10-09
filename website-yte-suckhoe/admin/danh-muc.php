<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require_admin();
$error = null;
if (post()) {
    check_csrf();
    try {
        $id = (int) ($_POST["id"] ?? 0);
        if (($_POST["action"] ?? "") === "delete") {
            if (
                query("SELECT COUNT(*) FROM articles WHERE category_id=?", [
                    $id,
                ])->fetchColumn()
            ) {
                throw new RuntimeException(
                    "Không thể xóa danh mục đang có bài viết.",
                );
            }
            query("DELETE FROM categories WHERE id=?", [$id]);
        } else {
            $name = field("name", 120);
            $slug = field("slug", 140);
            $description = field("description", 5000);
            if (!$name || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
                throw new RuntimeException(
                    "Nhập tên và slug không dấu, chỉ dùng chữ thường, số, dấu gạch ngang.",
                );
            }
            if ($id) {
                query(
                    "UPDATE categories SET name=?,slug=?,description=? WHERE id=?",
                    [$name, $slug, $description, $id],
                );
            } else {
                query(
                    "INSERT INTO categories(name,slug,description) VALUES(?,?,?)",
                    [$name, $slug, $description],
                );
            }
        }
        flash("Đã cập nhật danh mục.");
        redirect("danh-muc.php");
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Tên hoặc slug bị trùng, hoặc danh mục đang được sử dụng."
                : $ex->getMessage();
    }
}
$edit =
    query("SELECT * FROM categories WHERE id=?", [
        (int) ($_GET["edit"] ?? 0),
    ])->fetch() ?:
    [];
head("Quản lý danh mục");
notice($error);
?><div class="row"><div class="col-md-7"><table class="table"><thead><tr><th>Tên</th><th>Số bài</th><th>Thao tác</th></tr></thead><tbody><?php foreach (
    query(
        "SELECT c.*,COUNT(a.id) total FROM categories c LEFT JOIN articles a ON a.category_id=c.id GROUP BY c.id ORDER BY c.id",
    )
    as $c
): ?><tr><td><?php echo e($c["name"]); ?></td><td><?php echo $c[
    "total"
]; ?></td><td><a href="?edit=<?php echo $c[
    "id"
]; ?>">Sửa</a><form method="post" class="d-inline" onsubmit="return confirm('Xóa danh mục này?')"><?php echo csrf(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $c[
    "id"
]; ?>"><button class="btn btn-sm btn-outline-danger" <?php echo $c["total"]
    ? "disabled"
    : ""; ?>>Xóa</button></form></td></tr><?php endforeach; ?></tbody></table></div><div class="col-md-5"><form method="post" class="bmi-panel"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo e(
    $edit["id"] ?? 0,
); ?>"><h2 class="h5"><?php echo $edit ? "Sửa" : "Thêm"; ?> danh mục</h2><?php
 input("name", "Tên danh mục", $edit["name"] ?? "", "text", true);
 input("slug", "Slug (ví dụ: dinh-duong)", $edit["slug"] ?? "", "text", true);
 ?><label>Mô tả</label><textarea name="description" class="form-control"><?php echo e(
    $edit["description"] ?? "",
); ?></textarea><button class="btn btn-brand">Lưu</button> <a href="danh-muc.php">Tạo mới</a></form></div></div><?php foot(); ?>
