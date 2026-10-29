<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/layout.php';head('Chọn cổng đăng nhập');
?><div class="hc-panel auth-choice"><h2 class="h4">Chào mừng trở lại</h2><p>Đăng nhập để tiếp tục theo dõi sức khỏe và lịch hẹn.</p><a class="btn btn-brand w-100 mb-3" href="dang-nhap.php?role=user">Người dùng</a><a class="btn btn-outline-brand w-100" href="dang-nhap.php?role=admin">Quản trị viên</a><p class="mt-3">Chưa có tài khoản? <a href="dang-ky.php">Đăng ký</a></p></div><?php foot(); ?>
