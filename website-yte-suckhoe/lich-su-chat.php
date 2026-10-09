<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$u = require_user();
if (post()) {
    check_csrf();
    query("DELETE FROM chat_sessions WHERE id=? AND user_id=?", [
        (int) ($_POST["id"] ?? 0),
        $u["id"],
    ]);
    flash("Đã xóa hội thoại.");
    redirect("lich-su-chat.php");
}
$sid = (int) ($_GET["id"] ?? 0);
$sessions = query(
    "SELECT s.*,(SELECT content FROM chat_messages WHERE session_id=s.id ORDER BY id LIMIT 1) title FROM chat_sessions s WHERE user_id=? ORDER BY id DESC",
    [$u["id"]],
)->fetchAll();
$selected = $sid
    ? query("SELECT id FROM chat_sessions WHERE id=? AND user_id=?", [
        $sid,
        $u["id"],
    ])->fetch()
    : null;
if ($sid && !$selected) {
    http_response_code(404);
    head("Không tìm thấy hội thoại");
    foot();
    exit();
}
head("Lịch sử hội thoại");
?><div class="row"><aside class="col-md-4"><?php
if (!$sessions) {
    echo "<p>Chưa có hội thoại.</p>";
}
foreach (
    $sessions
    as $s
): ?><div class="border rounded p-3 mb-2"><a href="?id=<?php echo $s[
    "id"
]; ?>"><?php echo e(
    mb_strimwidth($s["title"] ?? "Hội thoại", 0, 80, "…"),
); ?></a><small class="d-block"><?php echo e(
    $s["created_at"],
); ?></small><form method="post" onsubmit="return confirm('Xóa hội thoại này?')"><?php echo csrf(); ?><input type="hidden" name="id" value="<?php echo $s[
    "id"
]; ?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form></div><?php endforeach;
?></aside><section class="col-md-8"><?php if ($selected):
    foreach (
        query("SELECT * FROM chat_messages WHERE session_id=? ORDER BY id", [
            $sid,
        ])
        as $m
    ): ?><div class="border rounded p-3 mb-2"><strong><?php echo $m["role"] ===
"user"
    ? "Bạn"
    : "Trợ lý"; ?></strong><p style="white-space:pre-wrap"><?php echo e(
    $m["content"],
); ?></p></div><?php endforeach; ?><button class="btn btn-brand" id="continueChat">Tiếp tục hội thoại</button><script>window.HC_SESSION=<?php echo $sid; ?>;</script><?php
else:
     ?><p>Chọn hội thoại để đọc lại hoặc mở khung chat để bắt đầu.</p><?php
endif; ?></section></div><?php foot(); ?>
