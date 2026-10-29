<?php
/**
 * Giao diện dùng chung cho khu vực admin.
 * Dùng: gọi admin_bar('bai-viet.php') ngay sau head(...).
 */

function admin_bar(string $active): void
{
    $items = [
        "dashboard.php" => "Tổng quan",
        "lich-kham.php" => "Lịch khám",
        "bac-si.php" => "Bác sĩ",
        "bai-viet.php" => "Bài viết",
        "danh-muc.php" => "Danh mục",
        "nguoi-dung.php" => "Người dùng",
    ];
    echo '<style>
.adm-bar{display:flex;flex-wrap:wrap;align-items:center;gap:.25rem;padding:.5rem .75rem;margin:0 0 1.25rem;background:#1f2933;border-radius:.6rem}
.adm-bar .adm-tag{color:#9aa5b1;font-size:.8rem;margin-right:.75rem;padding-right:.75rem;border-right:1px solid #3e4c59}
.adm-bar a{color:#cbd2d9;text-decoration:none;padding:.4rem .85rem;border-radius:.4rem;font-size:.95rem}
.adm-bar a:hover{background:#323f4b;color:#fff}
.adm-bar a.on{background:#fff;color:#1f2933;font-weight:600}
.adm-bar .adm-out{margin-left:auto}
.adm-stat{background:#fff;border:1px solid #e4e7eb;border-radius:.6rem;padding:1rem 1.1rem;height:100%}
.adm-stat .n{font-size:1.9rem;font-weight:700;line-height:1.1}
.adm-stat .l{color:#52606d;font-size:.9rem}
.adm-stat .s{color:#7b8794;font-size:.8rem;margin-top:.25rem}
.adm-box{background:#fff;border:1px solid #e4e7eb;border-radius:.6rem;padding:1rem 1.1rem;margin-bottom:1.25rem}
.adm-box h2{font-size:1.05rem;margin:0 0 .75rem}
.adm-badge{display:inline-block;padding:.15rem .55rem;border-radius:2rem;font-size:.78rem;font-weight:600}
.adm-badge.ok{background:#e3f9e5;color:#1b6b2f}
.adm-badge.draft{background:#fff3c4;color:#8a6100}
.adm-badge.lock{background:#ffe3e3;color:#a61b1b}
.adm-badge.admin{background:#e0e8ff;color:#2a3f9e}
.adm-bar-row{display:flex;align-items:center;gap:.6rem;margin-bottom:.45rem;font-size:.9rem}
.adm-bar-row .lbl{width:38%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.adm-bar-row .trk{flex:1;background:#eef0f2;border-radius:1rem;height:.7rem;overflow:hidden}
.adm-bar-row .fill{height:100%;background:#2f855a;border-radius:1rem}
.adm-bar-row .val{width:2.5rem;text-align:right;color:#52606d}
.adm-filter{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
.adm-filter .form-control,.adm-filter .form-select{width:auto;min-width:150px}
.adm-bulk{display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin:.75rem 0}
.adm-pager{display:flex;gap:.35rem;flex-wrap:wrap;margin-top:1rem}
.adm-pager a,.adm-pager span{padding:.3rem .7rem;border:1px solid #cbd2d9;border-radius:.4rem;text-decoration:none;font-size:.9rem}
.adm-pager span{background:#1f2933;color:#fff;border-color:#1f2933}
.adm-empty{text-align:center;color:#7b8794;padding:2rem 0}
</style>';
    echo '<nav class="adm-bar" aria-label="Điều hướng quản trị"><span class="adm-tag">Quản trị</span>';
    foreach ($items as $file => $label) {
        $cls = $file === $active ? ' class="on" aria-current="page"' : "";
        echo '<a href="' . $file . '"' . $cls . ">" . htmlspecialchars($label) . "</a>";
    }
    echo '<a class="adm-out" href="../index.php" target="_blank" rel="noopener">Xem trang chủ</a></nav>';
}

/** Phân trang đơn giản, giữ nguyên các tham số lọc đang có. */
function admin_pager(int $page, int $pages): void
{
    if ($pages < 2) {
        return;
    }
    echo '<div class="adm-pager">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === $page) {
            echo "<span>$i</span>";
        } else {
            $qs = http_build_query(array_merge($_GET, ["page" => $i]));
            echo '<a href="?' . htmlspecialchars($qs) . '">' . $i . "</a>";
        }
    }
    echo "</div>";
}
