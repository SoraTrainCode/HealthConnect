/* components.js, render header/footer dùng chung cho mọi trang (mock front-end).
   Khi nối back-end PHP có thể thay bằng include('header.php') phía server. */

const SITE = {
  name: "Sổ Tay Sức Khỏe",
  basePath: window.__BASE__ || "", // "" ở trang gốc, "../" trong /admin
};

function logoSVG() {
  return `<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
    <circle cx="15" cy="15" r="14" stroke="#2F6B4F" stroke-width="2"/>
    <path d="M8 16h3l2-5 3 9 2-6h4" stroke="#C98A2B" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  </svg>`;
}

function renderHeader(active = "") {
  const p = SITE.basePath;
  const loggedIn = localStorage.getItem("sths_logged_in") === "1";
  const userName = localStorage.getItem("sths_user_name") || "Tài khoản";

  const navItems = [
    { key: "home", href: p + "index.html", label: "Trang chủ" },
    { key: "news", href: p + "tin-tuc.html", label: "Tin tức y tế" },
    { key: "bmi", href: p + "bmi.html", label: "Công cụ BMI" },
  ];

  const navHtml = navItems
    .map(
      (i) =>
        `<li class="nav-item"><a class="nav-link nav-link-custom ${
          active === i.key ? "active" : ""
        }" href="${i.href}">${i.label}</a></li>`
    )
    .join("");

  const authHtml = loggedIn
    ? `<div class="dropdown">
         <button class="btn btn-outline-brand dropdown-toggle" data-bs-toggle="dropdown">${userName}</button>
         <ul class="dropdown-menu dropdown-menu-end">
           <li><a class="dropdown-item" href="${p}tai-khoan.html">Hồ sơ &amp; lịch sử BMI</a></li>
           <li><hr class="dropdown-divider"></li>
           <li><a class="dropdown-item" href="#" onclick="logoutUser(event)">Đăng xuất</a></li>
         </ul>
       </div>`
    : `<a href="${p}dang-nhap.html" class="btn btn-outline-brand me-2">Đăng nhập</a>
       <a href="${p}dang-ky.html" class="btn btn-brand">Đăng ký</a>`;

  const header = document.createElement("header");
  header.className = "site-header";
  header.innerHTML = `
    <nav class="navbar navbar-expand-lg py-3">
      <div class="container">
        <a class="brand-mark" href="${p}index.html">${logoSVG()}${SITE.name}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav mx-auto">${navHtml}</ul>
          <div class="d-flex">${authHtml}</div>
        </div>
      </div>
    </nav>`;
  document.body.prepend(header);
}

function renderFooter() {
  const p = SITE.basePath;
  const footer = document.createElement("footer");
  footer.className = "site-footer";
  footer.innerHTML = `
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="brand-mark mb-2">${logoSVG()}${SITE.name}</div>
          <p class="small">Nơi tra cứu kiến thức chăm sóc sức khỏe cơ bản và tự theo dõi thể trạng cá nhân.</p>
        </div>
        <div class="col-md-4">
          <h5 class="h6">Khám phá</h5>
          <p class="mb-1"><a href="${p}tin-tuc.html">Tin tức y tế</a></p>
          <p class="mb-1"><a href="${p}bmi.html">Công cụ tính BMI</a></p>
          <p class="mb-1"><a href="${p}dang-ky.html">Tạo tài khoản</a></p>
        </div>
        <div class="col-md-4">
          <h5 class="h6">Liên hệ</h5>
          <p class="mb-1">Đồ án môn học, Khoa Công nghệ thông tin</p>
          <p class="mb-1">Email: hotro@sotaysuckhoe.vn (demo)</p>
        </div>
      </div>
      <div class="disclaimer">
        Nội dung trên website chỉ mang tính chất tham khảo, không thay thế cho chẩn đoán, tư vấn hoặc điều trị y khoa chuyên nghiệp.
        Vui lòng đến cơ sở y tế uy tín để được thăm khám khi cần thiết.
      </div>
    </div>`;
  document.body.appendChild(footer);
}

function logoutUser(e) {
  e.preventDefault();
  localStorage.removeItem("sths_logged_in");
  localStorage.removeItem("sths_user_name");
  window.location.href = SITE.basePath + "index.html";
}

function requireLoginOrRedirect() {
  if (localStorage.getItem("sths_logged_in") !== "1") {
    window.location.href = SITE.basePath + "dang-nhap.html";
  }
}
