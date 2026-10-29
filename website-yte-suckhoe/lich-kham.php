<?php
// Giữ URL cũ hoạt động; trang quản trị chính nằm trong admin/.
require __DIR__ . "/includes/bootstrap.php";
require_admin();
redirect("admin/lich-kham.php");
