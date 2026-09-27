/* auth.js, mô phỏng đăng ký/đăng nhập bằng localStorage.
   Khi nối back-end: thay bằng fetch tới /api/auth.php (kiểm tra email/mật khẩu, tạo session). */

function initLoginForm() {
  const form = document.getElementById("loginForm");
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = document.getElementById("loginEmail").value.trim();
    const password = document.getElementById("loginPassword").value;
    const errEl = document.getElementById("loginError");

    if (!email || password.length < 6) {
      errEl.textContent = "Email hoặc mật khẩu không hợp lệ (mật khẩu tối thiểu 6 ký tự).";
      errEl.style.display = "block";
      return;
    }
    // Mock: chấp nhận mọi email hợp lệ để demo giao diện
    localStorage.setItem("sths_logged_in", "1");
    localStorage.setItem("sths_user_name", email.split("@")[0]);
    window.location.href = "index.html";
  });
}

function initRegisterForm() {
  const form = document.getElementById("registerForm");
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const fullName = document.getElementById("regFullName").value.trim();
    const email = document.getElementById("regEmail").value.trim();
    const password = document.getElementById("regPassword").value;
    const confirm = document.getElementById("regConfirm").value;
    const errEl = document.getElementById("registerError");

    if (!fullName || !email) {
      errEl.textContent = "Vui lòng nhập đầy đủ họ tên và email.";
      errEl.style.display = "block";
      return;
    }
    if (password.length < 8) {
      errEl.textContent = "Mật khẩu cần tối thiểu 8 ký tự.";
      errEl.style.display = "block";
      return;
    }
    if (password !== confirm) {
      errEl.textContent = "Mật khẩu nhập lại không khớp.";
      errEl.style.display = "block";
      return;
    }
    localStorage.setItem("sths_logged_in", "1");
    localStorage.setItem("sths_user_name", fullName.split(" ").pop());
    window.location.href = "index.html";
  });
}
