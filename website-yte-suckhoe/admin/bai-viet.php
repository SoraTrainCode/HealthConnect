<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
$admin = require_admin();
$error = null;
if (post()) {
    check_csrf();
    try {
        $id = (int) ($_POST["id"] ?? 0);
        if (($_POST["action"] ?? "") === "delete") {
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
            if (
                $thumb !== "" &&
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
head("Quản lý bài viết");
notice($error);
?><div class="table-responsive"><table class="table"><thead><tr><th>Tiêu đề</th><th>Danh mục</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach (
    query(
        "SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id ORDER BY a.id DESC",
    )
    as $a
): ?><tr><td><?php echo e($a["title"]); ?></td><td><?php echo e(
    $a["category_name"],
); ?></td><td><?php echo $a["status"] === "published"
    ? "Đã đăng"
    : "Bản nháp"; ?></td><td><a href="?edit=<?php echo $a[
    "id"
]; ?>#editor">Sửa</a><form method="post" class="d-inline" onsubmit="return confirm('Xóa bài viết này?')"><?php echo csrf(); ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo $a[
    "id"
]; ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form></td></tr><?php endforeach; ?></tbody></table></div><form method="post" enctype="multipart/form-data" id="editor" class="bmi-panel mt-4"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo e(
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
); ?></textarea><label>Nội dung bài viết</label><div class="d-flex gap-2 mb-2" id="formatTools"><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="bold">Đậm</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="italic">Nghiêng</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="insertUnorderedList">Danh sách</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="formatBlock" data-value="h2">Tiêu đề</button><button type="button" class="btn btn-sm btn-outline-brand" data-cmd="formatBlock" data-value="p">Đoạn văn</button></div><div id="richContent" class="form-control" contenteditable="true" role="textbox" aria-label="Nội dung bài viết" style="min-height:260px"><?php echo safe_content(
    $edit["content"] ?? "",
); ?></div><textarea name="content" id="rawContent" class="form-control" rows="12" required><?php echo e(
    $edit["content"] ?? "",
); ?></textarea><script>const rich=document.getElementById('richContent'),raw=document.getElementById('rawContent');raw.hidden=true;raw.required=false;document.querySelectorAll('#formatTools button').forEach(b=>{b.onmousedown=e=>e.preventDefault();b.onclick=()=>{rich.focus();document.execCommand(b.dataset.cmd,false,b.dataset.value||null);};});document.getElementById('editor').addEventListener('submit',e=>{raw.value=rich.innerHTML;if(!rich.textContent.trim()){e.preventDefault();alert('Nhập nội dung bài viết.');}});rich.addEventListener('paste',e=>{e.preventDefault();document.execCommand('insertText',false,e.clipboardData.getData('text/plain'));});</script><?php input(
    "thumbnail_url",
    "URL ảnh mới (để trống để giữ ảnh cũ)",
    "",
    "url",
); ?><label>Hoặc tải ảnh (JPEG/PNG/WebP, tối đa 2 MB)</label><input class="form-control" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp"><button class="btn btn-brand">Lưu bài viết</button> <a href="bai-viet.php">Tạo mới</a></form><?php foot(); ?>
