<?php define("ADMIN_PAGE", true);
require dirname(__DIR__) . "/includes/bootstrap.php";
require dirname(__DIR__) . "/includes/layout.php";
require dirname(__DIR__) . "/includes/admin_ui.php";
require_admin();

function count_of(string $sql, array $params = []): int
{
    return (int) query($sql, $params)->fetchColumn();
}

$stats = [
    [
        "Bài viết",
        count_of("SELECT COUNT(*) FROM articles"),
        count_of("SELECT COUNT(*) FROM articles WHERE status='published'") . " đã đăng, " .
        count_of("SELECT COUNT(*) FROM articles WHERE status='draft'") . " bản nháp",
    ],
    ["Danh mục", count_of("SELECT COUNT(*) FROM categories"), ""],
    [
        "Tài khoản",
        count_of("SELECT COUNT(*) FROM users"),
        count_of("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") . " mới trong 7 ngày, " .
        count_of("SELECT COUNT(*) FROM users WHERE status='locked'") . " bị khóa",
    ],
    ["Lần đo BMI", count_of("SELECT COUNT(*) FROM bmi_logs"), ""],
    ["Phiên chat", count_of("SELECT COUNT(*) FROM chat_sessions"), ""],
    [
        "Lịch khám hôm nay",
        count_of("SELECT COUNT(*) FROM appointments WHERE appt_date=CURDATE() AND status<>'cancelled'"),
        count_of("SELECT COUNT(*) FROM appointments WHERE appt_date=CURDATE() AND status='pending'") . " chờ xác nhận",
    ],
];

$totalViews = count_of("SELECT COALESCE(SUM(view_count),0) FROM articles");

$byCategory = query(
    "SELECT c.name, COUNT(a.id) total FROM categories c LEFT JOIN articles a ON a.category_id=c.id GROUP BY c.id ORDER BY total DESC, c.name",
)->fetchAll();
$maxCat = max(1, (int) max(array_merge([0], array_map("intval", array_column($byCategory, "total")))));

$topViewed = query(
    "SELECT a.id,a.title,a.view_count,a.status,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id ORDER BY a.view_count DESC LIMIT 6",
)->fetchAll();

$recentUsers = query(
    "SELECT full_name,email,role,status,created_at FROM users ORDER BY id DESC LIMIT 6",
)->fetchAll();

$drafts = query(
    "SELECT id,title FROM articles WHERE status='draft' ORDER BY id DESC LIMIT 5",
)->fetchAll();

head("Tổng quan quản trị");
admin_bar("dashboard.php");
?>
<div class="row g-3 mb-4">
<?php foreach ($stats as [$label, $num, $sub]): ?>
  <div class="col-6 col-md">
    <div class="adm-stat">
      <div class="n"><?php echo number_format($num); ?></div>
      <div class="l"><?php echo e($label); ?></div>
      <?php if ($sub): ?><div class="s"><?php echo e($sub); ?></div><?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="adm-box">
      <h2>Bài viết được xem nhiều <small class="text-muted">(tổng <?php echo number_format($totalViews); ?> lượt xem)</small></h2>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Tiêu đề</th><th>Danh mục</th><th class="text-end">Lượt xem</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($topViewed as $a): ?>
            <tr>
              <td><?php echo e($a["title"]); ?>
                <?php if ($a["status"] !== "published"): ?><span class="adm-badge draft">Nháp</span><?php endif; ?></td>
              <td><?php echo e($a["category_name"]); ?></td>
              <td class="text-end"><?php echo number_format((int) $a["view_count"]); ?></td>
              <td><a href="bai-viet.php?edit=<?php echo (int) $a["id"]; ?>#editor">Sửa</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$topViewed): ?><tr><td colspan="4" class="adm-empty">Chưa có bài viết.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="adm-box">
      <h2>Tài khoản mới nhất</h2>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Họ tên</th><th>Email</th><th>Trạng thái</th><th>Ngày tạo</th></tr></thead>
          <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td><?php echo e($u["full_name"]); ?>
                <?php if ($u["role"] === "admin"): ?><span class="adm-badge admin">Admin</span><?php endif; ?></td>
              <td><?php echo e($u["email"]); ?></td>
              <td><?php echo $u["status"] === "active"
                  ? '<span class="adm-badge ok">Hoạt động</span>'
                  : '<span class="adm-badge lock">Đã khóa</span>'; ?></td>
              <td><?php echo e(date("d/m/Y", strtotime($u["created_at"]))); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="nguoi-dung.php">Xem tất cả người dùng</a>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="adm-box">
      <h2>Số bài theo danh mục</h2>
      <?php foreach ($byCategory as $c): ?>
        <div class="adm-bar-row">
          <span class="lbl" title="<?php echo e($c["name"]); ?>"><?php echo e($c["name"]); ?></span>
          <span class="trk"><span class="fill d-block" style="width:<?php echo round(($c["total"] / $maxCat) * 100); ?>%"></span></span>
          <span class="val"><?php echo (int) $c["total"]; ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$byCategory): ?><div class="adm-empty">Chưa có danh mục.</div><?php endif; ?>
    </div>

    <div class="adm-box">
      <h2>Bản nháp cần hoàn thiện</h2>
      <?php if ($drafts): ?>
        <ul class="list-unstyled mb-2">
          <?php foreach ($drafts as $d): ?>
            <li class="mb-1"><a href="bai-viet.php?edit=<?php echo (int) $d["id"]; ?>#editor"><?php echo e($d["title"]); ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="adm-empty">Không còn bản nháp nào.</div>
      <?php endif; ?>
      <a class="btn btn-sm btn-brand" href="bai-viet.php#editor">Viết bài mới</a>
    </div>
  </div>
</div>
<?php foot(); ?>
