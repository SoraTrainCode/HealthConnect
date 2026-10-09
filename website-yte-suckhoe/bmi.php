<?php require __DIR__ . "/includes/bootstrap.php";
require __DIR__ . "/includes/layout.php";
$u = user();
$error = null;
$result = null;
if (post()) {
    check_csrf();
    try {
        $height = (float) ($_POST["height"] ?? 0);
        $weight = (float) ($_POST["weight"] ?? 0);
        if ($height < 80 || $height > 250 || $weight < 20 || $weight > 300) {
            throw new RuntimeException(
                "Chiều cao phải từ 80–250 cm, cân nặng từ 20–300 kg.",
            );
        }
        $b = $weight / ($height / 100) ** 2;
        $class = bmi_class($b);
        $note = field("note", 1000);
        $result = ["bmi" => round($b, 1), "class" => $class];
        if ($u) {
            query(
                "INSERT INTO bmi_logs(user_id,height_cm,weight_kg,bmi_value,classification,note) VALUES(?,?,?,?,?,?)",
                [$u["id"], $height, $weight, round($b, 1), $class, $note],
            );
            flash("Đã lưu kết quả BMI: " . round($b, 1) . " — " . $class);
            redirect("bmi.php");
        }
    } catch (RuntimeException $ex) {
        $error =
            $ex instanceof PDOException
                ? "Chưa lưu được kết quả."
                : $ex->getMessage();
    }
}
head("Công cụ BMI");
notice($error);
?><p>Dùng để tham khảo thể trạng người trưởng thành; các mốc phân loại trong dự án: 18,5 / 23 / 27,5.</p><div class="row g-4"><div class="col-md-4"><form method="post" class="bmi-panel"><?php
echo csrf();
input(
    "height",
    "Chiều cao (cm)",
    $_POST["height"] ?? ($u["default_height"] ?? ""),
    "number",
    true,
);
input("weight", "Cân nặng (kg)", $_POST["weight"] ?? "", "number", true);
input("note", "Ghi chú");
?><button class="btn btn-brand">Tính <?php echo $u
    ? "và lưu"
    : ""; ?> BMI</button></form><?php
 if ($result): ?><div class="alert alert-success mt-3">BMI: <?php echo e(
    $result["bmi"],
); ?> — <?php echo e($result["class"]); ?></div><?php endif;
 if (
     !$u
 ): ?><p class="mt-3"><a href="dang-nhap.php">Đăng nhập</a> để lưu lịch sử.</p><?php endif;
 ?></div><div class="col-md-8"><?php if ($u) {
    bmi_history($u["id"]);
} ?></div></div><script>document.querySelectorAll('input[type=number]').forEach(x=>x.step='0.1');</script><?php
foot();
function bmi_history(int $uid): void
{
    $logs = query(
        "SELECT * FROM bmi_logs WHERE user_id=? ORDER BY measured_at DESC,id DESC LIMIT 100",
        [$uid],
    )->fetchAll(); ?><h2 class="h5">100 lần đo gần nhất</h2><div class="table-responsive"><table class="table"><thead><tr><th>Ngày đo</th><th>Cao (cm)</th><th>Nặng (kg)</th><th>BMI</th><th>Phân loại</th><th>Ghi chú</th></tr></thead><tbody><?php foreach ($logs as $l): ?><tr><td><?php echo e($l["measured_at"]); ?></td><td><?php echo e($l["height_cm"]); ?></td><td><?php echo e($l["weight_kg"]); ?></td><td><?php echo e($l["bmi_value"]); ?></td><td><?php echo e($l["classification"]); ?></td><td><?php echo e($l["note"]); ?></td></tr><?php endforeach; ?></tbody></table></div><?php
if (!$logs) {
    echo "<p>Chưa có lần đo nào.</p>";
}
if (
    count($logs) > 1
): ?><h3 class="h6">Xu hướng BMI (20 lần gần nhất, cũ → mới)</h3><canvas id="trend" width="700" height="260" class="w-100" aria-label="Biểu đồ BMI"></canvas><script>const ls=<?php echo json_encode(
    array_reverse(array_slice($logs, 0, 20)),
    JSON_HEX_TAG | JSON_HEX_AMP,
); ?>,c=document.getElementById('trend'),x=c.getContext('2d'),v=ls.map(l=>+l.bmi_value),lo=Math.min(...v)-1,hi=Math.max(...v)+1;x.strokeStyle='#2f6b4f';x.beginPath();v.forEach((a,i)=>{let px=45+i*610/(v.length-1),py=220-(a-lo)/(hi-lo)*180;i?x.lineTo(px,py):x.moveTo(px,py);});x.stroke();x.fillStyle='#234';v.forEach((a,i)=>{let px=45+i*610/(v.length-1),py=220-(a-lo)/(hi-lo)*180;x.fillText(a.toFixed(1),px-10,py-10);});x.fillText(ls[0].measured_at.slice(0,10),25,250);x.fillText(ls.at(-1).measured_at.slice(0,10),590,250);</script><?php endif;
}

