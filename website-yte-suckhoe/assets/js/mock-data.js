/* mock-data.js, dữ liệu giả lập, thay bằng gọi API PHP khi có back-end thật */

const CATEGORIES = [
  { id: 1, name: "Dinh dưỡng", slug: "dinh-duong", color: "#2F6B4F" },
  { id: 2, name: "Vận động", slug: "van-dong", color: "#C98A2B" },
  { id: 3, name: "Bệnh thường gặp", slug: "benh-thuong-gap", color: "#B3432B" },
  { id: 4, name: "Tâm lý", slug: "tam-ly", color: "#6C5CE0" },
  { id: 5, name: "Mẹo vặt sức khỏe", slug: "meo-vat", color: "#1F7A8C" },
];

function catColor(catId) {
  const c = CATEGORIES.find((x) => x.id === catId);
  return c ? c.color : "#2F6B4F";
}
function catName(catId) {
  const c = CATEGORIES.find((x) => x.id === catId);
  return c ? c.name : "Khác";
}

const ARTICLES = [
  {
    id: 1,
    categoryId: 1,
    title: "5 nhóm thực phẩm nên có trong bữa ăn hằng ngày",
    summary:
      "Một bữa ăn cân bằng cần đủ tinh bột, đạm, chất béo, rau củ và trái cây. Cùng tìm hiểu tỉ lệ hợp lý cho từng nhóm.",
    content: `<p>Một chế độ ăn cân bằng là nền tảng của sức khỏe lâu dài. Theo khuyến nghị dinh dưỡng phổ biến, mỗi bữa ăn nên có đủ 5 nhóm chất: tinh bột, chất đạm, chất béo, vitamin, khoáng chất và chất xơ.</p>
    <p><strong>1. Tinh bột (carbohydrate):</strong> gạo, khoai, ngũ cốc nguyên hạt cung cấp năng lượng chính cho cơ thể. Nên ưu tiên ngũ cốc nguyên cám thay vì tinh bột đã qua tinh chế.</p>
    <p><strong>2. Chất đạm (protein):</strong> thịt, cá, trứng, đậu đỗ giúp xây dựng và phục hồi mô cơ. Người trưởng thành nên đa dạng nguồn đạm động vật và thực vật.</p>
    <p><strong>3. Chất béo:</strong> ưu tiên chất béo không bão hòa từ dầu ô liu, cá béo, các loại hạt, hạn chế chất béo bão hòa và chất béo chuyển hóa.</p>
    <p><strong>4. Rau củ và trái cây:</strong> cung cấp vitamin, khoáng chất và chất xơ, nên chiếm ít nhất một nửa khẩu phần ăn.</p>
    <p><strong>5. Nước:</strong> uống đủ 1.5 đến 2 lít nước mỗi ngày, tùy theo cường độ vận động và điều kiện thời tiết.</p>
    <p>Việc duy trì thói quen ăn uống cân bằng, kết hợp vận động đều đặn, là cách đơn giản nhưng hiệu quả để phòng ngừa nhiều bệnh mạn tính.</p>`,
    thumb: "🥗",
    date: "2026-09-10",
    views: 482,
  },
  {
    id: 2,
    categoryId: 2,
    title: "Đi bộ 30 phút mỗi ngày: lợi ích ít ai ngờ tới",
    summary:
      "Chỉ cần đi bộ nhanh 30 phút mỗi ngày cũng có thể cải thiện tim mạch, giấc ngủ và tâm trạng rõ rệt.",
    content: `<p>Đi bộ là hình thức vận động đơn giản, dễ tiếp cận nhất nhưng lại mang lại nhiều lợi ích sức khỏe đáng kể nếu duy trì đều đặn.</p>
    <p><strong>Tốt cho tim mạch:</strong> đi bộ nhanh giúp tăng nhịp tim ở mức vừa phải, cải thiện tuần hoàn máu và giảm nguy cơ mắc bệnh tim mạch.</p>
    <p><strong>Cải thiện giấc ngủ:</strong> vận động vào ban ngày, đặc biệt là buổi sáng hoặc chiều, giúp điều hòa nhịp sinh học và ngủ sâu hơn vào ban đêm.</p>
    <p><strong>Giảm căng thẳng:</strong> đi bộ ngoài trời kích thích cơ thể sản sinh endorphin, giúp cải thiện tâm trạng và giảm lo âu.</p>
    <p>Bạn có thể bắt đầu với 10 đến 15 phút mỗi ngày rồi tăng dần lên 30 phút, chia làm nhiều đợt ngắn nếu không sắp xếp được thời gian liên tục.</p>`,
    thumb: "🚶",
    date: "2026-09-08",
    views: 356,
  },
  {
    id: 3,
    categoryId: 3,
    title: "Cảm cúm thông thường: khi nào cần đi khám?",
    summary:
      "Phân biệt cảm cúm thông thường với các dấu hiệu cảnh báo cần đến cơ sở y tế ngay.",
    content: `<p>Cảm cúm thông thường thường tự khỏi sau 5 đến 7 ngày với các biện pháp chăm sóc tại nhà như nghỉ ngơi, uống nhiều nước, hạ sốt khi cần.</p>
    <p><strong>Dấu hiệu cần đi khám ngay:</strong></p>
    <ul>
      <li>Sốt cao trên 39°C kéo dài quá 3 ngày không đáp ứng thuốc hạ sốt</li>
      <li>Khó thở, đau tức ngực</li>
      <li>Triệu chứng nặng hơn thay vì cải thiện sau 7 đến 10 ngày</li>
      <li>Người có bệnh nền (tim mạch, hô hấp, tiểu đường) xuất hiện triệu chứng bất thường</li>
    </ul>
    <p>Đây chỉ là thông tin tham khảo chung. Mỗi cơ địa khác nhau, nên tham khảo ý kiến bác sĩ nếu có bất kỳ lo ngại nào về sức khỏe.</p>`,
    thumb: "🤒",
    date: "2026-09-05",
    views: 611,
  },
  {
    id: 4,
    categoryId: 4,
    title: "Quản lý căng thẳng trong mùa thi cử",
    summary: "Một vài kỹ thuật đơn giản giúp học sinh, sinh viên giảm áp lực trong giai đoạn thi cử căng thẳng.",
    content: `<p>Căng thẳng trong mùa thi là điều phổ biến, nhưng nếu không quản lý tốt có thể ảnh hưởng đến sức khỏe tinh thần và thể chất.</p>
    <p><strong>Một số kỹ thuật hữu ích:</strong> hít thở sâu theo nhịp 4, 7, 8, chia nhỏ thời gian ôn tập (kỹ thuật Pomodoro), ngủ đủ giấc thay vì thức khuya học dồn, và dành thời gian vận động nhẹ mỗi ngày.</p>
    <p>Nếu cảm thấy áp lực kéo dài ảnh hưởng nghiêm trọng đến cuộc sống hằng ngày, đừng ngần ngại tìm đến chuyên gia tâm lý học đường hoặc người thân để được hỗ trợ.</p>`,
    thumb: "🧘",
    date: "2026-09-02",
    views: 274,
  },
  {
    id: 5,
    categoryId: 5,
    title: "Mẹo giữ dáng ngồi đúng khi làm việc với máy tính",
    summary: "Ngồi sai tư thế trong thời gian dài là nguyên nhân phổ biến gây đau lưng, mỏi cổ vai gáy.",
    content: `<p>Dân văn phòng và học sinh, sinh viên thường dành nhiều giờ mỗi ngày trước máy tính, dễ dẫn đến các vấn đề về cơ xương khớp nếu tư thế ngồi không đúng.</p>
    <p><strong>Một số lưu ý:</strong> giữ màn hình ngang tầm mắt, lưng thẳng tựa vào ghế, hai bàn chân đặt phẳng trên sàn, cứ mỗi 45 đến 60 phút nên đứng dậy vận động 3 đến 5 phút.</p>`,
    thumb: "💻",
    date: "2026-08-29",
    views: 198,
  },
  {
    id: 6,
    categoryId: 1,
    title: "Uống đủ nước: bao nhiêu là đủ?",
    summary: "Nhu cầu nước mỗi người khác nhau tùy cân nặng, mức độ vận động và thời tiết.",
    content: `<p>Một công thức tham khảo đơn giản là 30 đến 35ml nước cho mỗi kg cân nặng mỗi ngày, có thể điều chỉnh tăng thêm khi vận động nhiều hoặc thời tiết nóng.</p>
    <p>Dấu hiệu cơ thể thiếu nước gồm: khát nước, nước tiểu sẫm màu, mệt mỏi, khô da. Nên uống nước đều đặn trong ngày thay vì uống dồn một lúc.</p>`,
    thumb: "💧",
    date: "2026-08-25",
    views: 342,
  },
];

function getArticleById(id) {
  return ARTICLES.find((a) => a.id === Number(id));
}
function getRelatedArticles(article, limit = 3) {
  return ARTICLES.filter((a) => a.categoryId === article.categoryId && a.id !== article.id).slice(0, limit);
}
function formatDate(iso) {
  const d = new Date(iso);
  return d.toLocaleDateString("vi-VN", { day: "2-digit", month: "2-digit", year: "numeric" });
}
