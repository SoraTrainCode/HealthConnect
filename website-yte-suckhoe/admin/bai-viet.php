<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require dirname(__DIR__) . "/includes/admin_ui.php";
$admin = require_admin();
$error = null;
if (post()) {
    check_csrf();
    try {
        $id = (int) ($_POST["id"] ?? 0);
        $act = (string) ($_POST["act"] ?? "");
        if ($act !== "") {
            // Thao tác nhanh trên từng dòng: "toggle:ID" hoặc "delete:ID"; hàng loạt: "bulk"
            if ($act === "bulk") {
                $ids = array_values(array_filter(array_map("intval", (array) ($_POST["ids"] ?? []))));
                $bulk = (string) ($_POST["bulk_action"] ?? "");
                if (!$ids || !in_array($bulk, ["publish", "draft", "delete"], true)) {
                    throw new RuntimeException("Chọn ít nhất một bài và một thao tác.");
                }
            } else {
                [$one, $oneId] = array_pad(explode(":", $act, 2), 2, "0");
                $ids = [(int) $oneId];
                $bulk = $one === "toggle" ? "toggle" : ($one === "delete" ? "delete" : "");
                if (!$ids[0] || $bulk === "") {
                    throw new RuntimeException("Thao tác không hợp lệ.");
                }
            }
            $in = implode(",", array_fill(0, count($ids), "?"));
            if ($bulk === "delete") {
                query("DELETE FROM articles WHERE id IN ($in)", $ids);
            } elseif ($bulk === "toggle") {
                query("UPDATE articles SET status=IF(status='published','draft','published') WHERE id IN ($in)", $ids);
            } else {
                $to = $bulk === "publish" ? "published" : "draft";
                query("UPDATE articles SET status=? WHERE id IN ($in)", array_merge([$to], $ids));
            }
            flash("Đã cập nhật " . count($ids) . " bài viết.");
            redirect("bai-viet.php" . (!empty($_POST["back"]) ? "?" . $_POST["back"] : ""));
        } elseif (($_POST["action"] ?? "") === "delete") {
            query("DELETE FROM articles WHERE id=?", [$id]);
        } else {
            $title = field("title", 255);
            $slug = field("slug", 190);
            $summary = field("summary", 5000);
            $content = safe_content(field("content", 100000));
            $category = (int) ($_POST["category_id"] ?? 0);
            $status = field("status", 20);
            $thumb = field("thumbnail_url", 500);
            if (
                !$title ||
                !$content ||
                !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) ||
                !in_array($status, ["draft", "published"], true)
            ) {
                throw new RuntimeException(
                    "Điền tiêu đề, nội dung, slug hợp lệ và trạng thái.",
                );
            }
            if (
                !query("SELECT id FROM categories WHERE id=?", [
                    $category,
                ])->fetch()
            ) {
                throw new RuntimeException("Danh mục không tồn tại.");
            }
            $localImage = (bool) preg_match('~^uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$~', $thumb)
                && is_file(dirname(__DIR__) . '/' . $thumb);
            if (
                $thumb !== "" && !$localImage &&
                (!filter_var($thumb, FILTER_VALIDATE_URL) ||
                    !in_array(
                        parse_url($thumb, PHP_URL_SCHEME),
                        ["http", "https"],
                        true,
                    ))
            ) {
                throw new RuntimeException("URL ảnh cần dùng http/https.");
            }
            if (
                isset($_FILES["thumbnail"]) &&
                $_FILES["thumbnail"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {
                $f = $_FILES["thumbnail"];
                if (
                    $f["error"] !== UPLOAD_ERR_OK ||
                    $f["size"] > 2 * 1024 * 1024
                ) {
                    throw new RuntimeException("Ảnh tải lên tối đa 2 MB.");
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);
                $ext =
                    [
                        "image/jpeg" => "jpg",
                        "image/png" => "png",
                        "image/webp" => "webp",
                    ][$mime] ?? null;
                if (!$ext || !getimagesize($f["tmp_name"])) {
                    throw new RuntimeException("Chỉ nhận ảnh JPEG, PNG, WebP.");
                }
                $dir = dirname(__DIR__) . "/uploads";
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $name = bin2hex(random_bytes(16)) . "." . $ext;
                if (!move_uploaded_file($f["tmp_name"], $dir . "/" . $name)) {
                    throw new RuntimeException(
                        "Không lưu được ảnh. Kiểm tra quyền thư mục uploads.",
                    );
                }
                $thumb = "uploads/" . $name;
            } elseif ($thumb === "") {
                $thumb = $id
                    ? (query("SELECT thumbnail_url FROM articles WHERE id=?", [
                        $id,
                    ])->fetchColumn() ?:
                    null)
                    : null;
            }
            if ($id) {
                query(
                    "UPDATE articles SET category_id=?,title=?,slug=?,summary=?,content=?,thumbnail_url=?,status=? WHERE id=?",
                    [
                        $category,
                        $title,
                        $slug,
                        $summary,
                        $content,
                        $thumb,
                        $status,
                        $id,
                    ],
                );
            } else {
                query(
                    "INSERT INTO articles(category_id,author_id,title,slug,summary,content,thumbnail_url,status) VALUES(?,?,?,?,?,?,?,?)",
                    [
                        $category,
                        $admin["id"],
                        $title,
                        $slug,
                        $summary,
                        $content,
                        $thumb,
                        $status,
                    ],
                );
            }
        }
        flash("Đã cập nhật bài viết.");
        redirect("bai-viet.php");
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Không lưu được. Kiểm tra slug trùng hoặc danh mục."
                : $ex->getMessage();
    }
}
$edit =
    query("SELECT * FROM articles WHERE id=?", [
        (int) ($_GET["edit"] ?? 0),
    ])->fetch() ?:
    [];
// --- Bộ lọc, tìm kiếm, phân trang ---
$q = mb_substr(trim((string) ($_GET["q"] ?? "")), 0, 120);
$fs = in_array($_GET["status"] ?? "", ["draft", "published"], true) ? $_GET["status"] : "";
$fc = (int) ($_GET["cat"] ?? 0);
$where = ["1=1"];
$params = [];
if ($q !== "") {
    $where[] = "(a.title LIKE ? OR a.slug LIKE ?)";
    $params[] = "%" . $q . "%";
    $params[] = "%" . $q . "%";
}
if ($fs !== "") {
    $where[] = "a.status=?";
    $params[] = $fs;
}
if ($fc) {
    $where[] = "a.category_id=?";
    $params[] = $fc;
}
$whereSql = implode(" AND ", $where);
$perPage = 15;
$totalRows = (int) query("SELECT COUNT(*) FROM articles a WHERE $whereSql", $params)->fetchColumn();
$pages = max(1, (int) ceil($totalRows / $perPage));
$page = min($pages, max(1, (int) ($_GET["page"] ?? 1)));
$offset = ($page - 1) * $perPage;
$rows = query(
    "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE $whereSql ORDER BY a.id DESC LIMIT $perPage OFFSET $offset",
    $params,
)->fetchAll();
$allCats = query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$back = http_build_query(array_filter(["q" => $q, "status" => $fs, "cat" => $fc ?: "", "page" => $page > 1 ? $page : ""]));

head("Quản lý bài viết");
admin_bar("bai-viet.php");
notice($error);
?>
<form method="get" class="adm-filter">
  <input name="q" value="<?php echo e($q); ?>" class="form-control" placeholder="Tìm tiêu đề hoặc slug">
  <select name="status" class="form-select">
    <option value="">Mọi trạng thái</option>
    <option value="published" <?php echo $fs === "published" ? "selected" : ""; ?>>Đã đăng</option>
    <option value="draft" <?php echo $fs === "draft" ? "selected" : ""; ?>>Bản nháp</option>
  </select>
  <select name="cat" class="form-select">
    <option value="0">Mọi danh mục</option>
    <?php foreach ($allCats as $c): ?>
      <option value="<?php echo (int) $c["id"]; ?>" <?php echo $fc === (int) $c["id"] ? "selected" : ""; ?>><?php echo e($c["name"]); ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-brand">Lọc</button>
  <a class="btn btn-outline-secondary" href="bai-viet.php">Xóa lọc</a>
  <a class="btn btn-outline-brand ms-auto" href="#editor">Viết bài mới</a>
</form>

<form method="post" id="bulkForm">
<?php echo csrf(); ?>
<input type="hidden" name="back" value="<?php echo e($back); ?>">
<div class="table-responsive"><table class="table align-middle">
<thead><tr>
  <th style="width:32px"><input type="checkbox" id="checkAll" aria-label="Chọn tất cả"></th>
  <th>Tiêu đề</th><th>Danh mục</th><th>Trạng thái</th><th class="text-end">Lượt xem</th><th>Thao tác</th>
</tr></thead><tbody>
<?php foreach ($rows as $a): ?>
<tr>
  <td><input type="checkbox" name="ids[]" value="<?php echo (int) $a["id"]; ?>" class="rowCheck" aria-label="Chọn bài"></td>
  <td><?php echo e($a["title"]); ?><div class="small text-muted"><?php echo e($a["slug"]); ?></div></td>
  <td><?php echo e($a["category_name"]); ?></td>
  <td><?php echo $a["status"] === "published"
      ? '<span class="adm-badge ok">Đã đăng</span>'
      : '<span class="adm-badge draft">Bản nháp</span>'; ?></td>
  <td class="text-end"><?php echo number_format((int) ($a["view_count"] ?? 0)); ?></td>
  <td class="text-nowrap">
    <a href="?edit=<?php echo (int) $a["id"]; ?>#editor">Sửa</a>
    <button name="act" value="toggle:<?php echo (int) $a["id"]; ?>" class="btn btn-sm btn-outline-brand">
      <?php echo $a["status"] === "published" ? "Gỡ xuống" : "Đăng"; ?></button>
    <button name="act" value="delete:<?php echo (int) $a["id"]; ?>" class="btn btn-sm btn-outline-danger"
      onclick="return confirm('Xóa bài viết này?')">Xóa</button>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="adm-empty">Không có bài viết nào khớp bộ lọc.</td></tr><?php endif; ?>
</tbody></table></div>

<div class="adm-bulk">
  <span class="text-muted small"><?php echo $totalRows; ?> bài</span>
  <select name="bulk_action" class="form-select" style="width:auto">
    <option value="">Thao tác với bài đã chọn</option>
    <option value="publish">Đăng</option>
    <option value="draft">Chuyển về bản nháp</option>
    <option value="delete">Xóa</option>
  </select>
  <button name="act" value="bulk" class="btn btn-sm btn-brand"
    onclick="if(this.form.bulk_action.value==='delete')return confirm('Xóa các bài đã chọn?')">Áp dụng</button>
</div>
</form>
<?php admin_pager($page, $pages); ?>
<script>
document.getElementById('checkAll').addEventListener('change',function(){
  document.querySelectorAll('.rowCheck').forEach(c=>c.checked=this.checked);
});
</script>
<form method="post" enctype="multipart/form-data" id="editor" class="bmi-panel mt-4"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo e(
    $edit["id"] ?? 0,
); ?>"><h2 class="h5"><?php echo $edit ? "Sửa" : "Thêm"; ?> bài viết</h2><?php
 input("title", "Tiêu đề", $edit["title"] ?? "", "text", true);
 input(
     "slug",
     "Slug (không dấu, cách nhau bằng -)",
     $edit["slug"] ?? "",
     "text",
     true,
 );
 ?><label>Danh mục</label><select name="category_id" class="form-select" required><?php foreach (
    query("SELECT * FROM categories ORDER BY name")
    as $c
): ?><option value="<?php echo $c["id"]; ?>" <?php echo ($edit["category_id"] ??
    0) ==
$c["id"]
    ? "selected"
    : ""; ?>><?php echo e(
    $c["name"],
); ?></option><?php endforeach; ?></select><label>Trạng thái</label><select class="form-select" name="status"><option value="draft">Bản nháp</option><option value="published" <?php echo ($edit[
    "status"
] ??
    "") ===
"published"
    ? "selected"
    : ""; ?>>Đã đăng</option></select><label>Tóm tắt</label><textarea class="form-control" name="summary" rows="3"><?php echo e(
    $edit["summary"] ?? "",
); ?></textarea><label>Nội dung bài viết</label><div class="d-flex flex-wrap gap-2 mb-2" id="formatTools"><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="bold">Đậm</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="italic">Nghiêng</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="insertUnorderedList">Danh sách</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="formatBlock" data-value="h2">Tiêu đề</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="formatBlock" data-value="p">Đoạn văn</button></div><div id="richContent" class="form-control" contenteditable="true" role="textbox" aria-label="Nội dung bài viết" style="min-height:260px"><?php echo safe_content(
    $edit["content"] ?? "",
); ?></div><textarea name="content" id="rawContent" class="form-control" rows="12" required><?php echo e(
    $edit["content"] ?? "",
); ?></textarea><script>const rich=document.getElementById('richContent'),raw=document.getElementById('rawContent');raw.hidden=true;raw.required=false;document.querySelectorAll('#formatTools button').forEach(b=>{b.onmousedown=e=>e.preventDefault();b.onclick=()=>{rich.focus();document.execCommand(b.dataset.cmd,false,b.dataset.value||null);};});document.getElementById('editor').addEventListener('submit',e=>{raw.value=rich.innerHTML;if(!rich.textContent.trim()){e.preventDefault();alert('Nhập nội dung bài viết.');}});rich.addEventListener('paste',e=>{e.preventDefault();document.execCommand('insertText',false,e.clipboardData.getData('text/plain'));});</script><?php input(
    "thumbnail_url",
    "URL ảnh mới (để trống để giữ ảnh cũ)",
    "",
    "url",
); ?><?php if(!empty($edit['thumbnail_url'])): ?><div class="mb-3"><p class="small text-muted">Ảnh hiện tại</p><img src="<?php echo e(str_starts_with($edit['thumbnail_url'],'uploads/')?url($edit['thumbnail_url']):$edit['thumbnail_url']); ?>" alt="Ảnh bài viết hiện tại" style="max-width:160px;max-height:120px;object-fit:cover;border-radius:8px"></div><?php endif; ?><label>Hoặc tải ảnh (JPEG/PNG/WebP, tối đa 2 MB)</label><input class="form-control" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"><button class="btn btn-brand">Lưu bài viết</button> <a href="bai-viet.php">Tạo mới</a></form><?php foot(); ?>
