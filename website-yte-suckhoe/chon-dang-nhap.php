<?php
// Trang chọn kiểu đăng nhập. Đặt ở thư mục gốc: website-yte-suckhoe/chon-dang-nhap.php
// Sửa 2 đường dẫn dưới đây cho khớp với file đăng nhập thật của bạn.
$LOGIN_USER  = "dang-nhap.php?role=user";
$LOGIN_ADMIN = "dang-nhap.php?role=admin";
$REGISTER    = "dang-ky.php";
?><!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập - Sổ Tay Sức Khỏe</title>
<style>
:root{--bg:#f1f0e9;--ink:#1f3d2f;--brand:#2f6b4f;--brand-d:#245540;--line:#dcdacf}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
  background:var(--bg);color:var(--ink);font-family:Georgia,"Times New Roman",serif}
h1{font-size:2rem;font-weight:400;margin:0 0 1.25rem}
h1 b{font-weight:700}
.card{width:min(450px,92vw);background:#fff;border:1px solid var(--line);padding:1.5rem}
.card p{text-align:center;margin:0 0 1.25rem;color:#4b5b52}
.btn{display:block;width:100%;padding:.8rem 1rem;margin-bottom:.75rem;border-radius:.3rem;border:1px solid transparent;
  font:inherit;font-size:1.05rem;text-align:center;text-decoration:none;cursor:pointer}
.btn:focus-visible{outline:3px solid #9fd3b8;outline-offset:2px}
.btn-user{background:var(--brand);color:#fff}
.btn-user:hover{background:var(--brand-d)}
.btn-admin{background:#fff;color:var(--ink);border-color:var(--ink)}
.btn-admin:hover{background:var(--ink);color:#fff}
.note{width:min(450px,92vw);margin-top:1rem;font-style:italic;color:#4b5b52;font-size:.95rem}
.note a{color:var(--brand)}
</style>
</head>
<body>
<h1>Sổ Tay <b>Sức Khỏe</b></h1>
<div class="card">
  <p>Chọn cách đăng nhập</p>
  <a class="btn btn-user" href="<?php echo htmlspecialchars($LOGIN_USER); ?>">Đăng nhập người dùng</a>
  <a class="btn btn-admin" href="<?php echo htmlspecialchars($LOGIN_ADMIN); ?>">Đăng nhập quản trị viên</a>
</div>
<div class="note">
  Chưa có tài khoản? <a href="<?php echo htmlspecialchars($REGISTER); ?>">Đăng ký</a><br>
  Lưu ý: tài khoản người dùng không đăng nhập được ở cổng quản trị.
</div>
</body>
</html>
