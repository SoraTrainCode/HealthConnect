/* admin.js, layout khung quản trị dùng chung cho các trang trong /admin */

function renderAdminShell(active) {
  const nav = [
    { key: "dashboard", href: "dashboard.html", label: "Tổng quan" },
    { key: "articles", href: "bai-viet.html", label: "Quản lý bài viết" },
    { key: "categories", href: "danh-muc.html", label: "Quản lý danh mục" },
    { key: "users", href: "nguoi-dung.html", label: "Quản lý người dùng" },
  ];
  const navHtml = nav
    .map((n) => `<a class="${active === n.key ? "active" : ""}" href="${n.href}">${n.label}</a>`)
    .join("");

  document.getElementById("adminSidebar").innerHTML = `
    <div class="brand-mark">${logoSVG()}Quản trị</div>
    ${navHtml}
    <hr style="border-color:rgba(255,255,255,.15)">
    <a href="../index.html">← Về trang người dùng</a>
    <a href="#" onclick="logoutUser(event)">Đăng xuất</a>
  `;
}
