/* bmi.js, tính BMI, phân loại theo WHO, lưu lịch sử (mock bằng localStorage).
   Khi nối back-end: thay saveBmiLog()/loadBmiLogs() bằng fetch tới /api/bmi_logs.php */

function classifyBmi(bmi) {
  if (bmi < 18.5) return { label: "Thiếu cân", color: "#1F7A8C" };
  if (bmi < 23) return { label: "Bình thường", color: "#2F6B4F" };
  if (bmi < 27.5) return { label: "Thừa cân", color: "#C98A2B" };
  return { label: "Béo phì", color: "#B3432B" };
}

function calcBmi(heightCm, weightKg) {
  const h = heightCm / 100;
  return weightKg / (h * h);
}

function loadBmiLogs() {
  const raw = localStorage.getItem("sths_bmi_logs");
  return raw ? JSON.parse(raw) : [];
}

function saveBmiLog(entry) {
  const logs = loadBmiLogs();
  logs.unshift(entry);
  localStorage.setItem("sths_bmi_logs", JSON.stringify(logs.slice(0, 30)));
  return logs;
}

function initBmiPage() {
  const form = document.getElementById("bmiForm");
  const resultBox = document.getElementById("bmiResultBox");
  const gaugeMarker = document.getElementById("gaugeMarker");
  const loggedIn = localStorage.getItem("sths_logged_in") === "1";

  document.getElementById("bmiGuestNote").style.display = loggedIn ? "none" : "block";
  document.getElementById("bmiHistorySection").style.display = loggedIn ? "block" : "none";

  if (loggedIn) renderBmiHistory();

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    const height = parseFloat(document.getElementById("heightInput").value);
    const weight = parseFloat(document.getElementById("weightInput").value);
    if (!height || !weight || height < 80 || height > 250 || weight < 20 || weight > 300) {
      alert("Vui lòng nhập chiều cao (từ 80 đến 250cm) và cân nặng (từ 20 đến 300kg) hợp lệ.");
      return;
    }
    const bmi = calcBmi(height, weight);
    const cls = classifyBmi(bmi);

    resultBox.hidden = false;
    document.getElementById("bmiValue").textContent = bmi.toFixed(1);
    document.getElementById("bmiLabel").textContent = cls.label;
    document.getElementById("bmiLabel").style.color = cls.color;

    // Thang hiển thị gauge từ 15 đến 32
    const pct = Math.min(Math.max((bmi - 15) / (32 - 15), 0), 1) * 100;
    gaugeMarker.style.left = pct + "%";

    if (loggedIn) {
      saveBmiLog({
        date: new Date().toISOString().slice(0, 10),
        height,
        weight,
        bmi: Number(bmi.toFixed(1)),
        classification: cls.label,
      });
      renderBmiHistory();
    }
  });
}

function renderBmiHistory() {
  const logs = loadBmiLogs();
  const tbody = document.getElementById("bmiHistoryBody");
  const emptyMsg = document.getElementById("bmiHistoryEmpty");
  tbody.innerHTML = "";

  if (logs.length === 0) {
    emptyMsg.style.display = "block";
    return;
  }
  emptyMsg.style.display = "none";

  logs.forEach((l) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `<td>${formatDate(l.date)}</td><td>${l.height} cm</td><td>${l.weight} kg</td>
      <td><strong>${l.bmi}</strong></td><td>${l.classification}</td>`;
    tbody.appendChild(tr);
  });

  drawTrendChart(logs.slice(0, 10).reverse());
}

function drawTrendChart(logs) {
  const canvas = document.getElementById("bmiTrendChart");
  if (!canvas || logs.length < 2) return;
  const ctx = canvas.getContext("2d");
  const w = canvas.width,
    h = canvas.height,
    pad = 30;
  ctx.clearRect(0, 0, w, h);

  const values = logs.map((l) => l.bmi);
  const min = Math.min(...values) - 1,
    max = Math.max(...values) + 1;
  const stepX = (w - pad * 2) / (values.length - 1);

  ctx.strokeStyle = "#DCD7C4";
  ctx.beginPath();
  ctx.moveTo(pad, h - pad);
  ctx.lineTo(w - pad, h - pad);
  ctx.stroke();

  ctx.strokeStyle = "#2F6B4F";
  ctx.lineWidth = 2;
  ctx.beginPath();
  values.forEach((v, i) => {
    const x = pad + i * stepX;
    const y = h - pad - ((v - min) / (max - min)) * (h - pad * 2);
    i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
  });
  ctx.stroke();

  ctx.fillStyle = "#2F6B4F";
  values.forEach((v, i) => {
    const x = pad + i * stepX;
    const y = h - pad - ((v - min) / (max - min)) * (h - pad * 2);
    ctx.beginPath();
    ctx.arc(x, y, 3.5, 0, Math.PI * 2);
    ctx.fill();
  });
}
