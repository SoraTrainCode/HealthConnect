<?php
function seed_demo_doctors(): void
{
 if((int)query('SELECT COUNT(*) FROM doctors')->fetchColumn()) throw new RuntimeException('Đã có bác sĩ. Chỉ tạo dữ liệu mẫu khi danh sách trống.');
 foreach([
  ['BS. An (minh họa)','Khám tổng quát','Phòng A · Cơ sở mô phỏng'],
  ['BS. Bình (minh họa)','Nội khoa','Phòng B · Cơ sở mô phỏng'],
  ['BS. Chi (minh họa)','Nhi khoa','Phòng C · Cơ sở mô phỏng'],
  ['BS. Dũng (minh họa)','Tim mạch','Phòng D · Cơ sở mô phỏng'],
  ['BS. Hà (minh họa)','Dinh dưỡng','Phòng E · Cơ sở mô phỏng'],
  ['BS. Lan (minh họa)','Da liễu','Phòng F · Cơ sở mô phỏng'],
  ['BS. Minh (minh họa)','Tai mũi họng','Phòng G · Cơ sở mô phỏng'],
  ['BS. Vy (minh họa)','Răng hàm mặt','Phòng H · Cơ sở mô phỏng'],
 ] as [$name,$specialty,$room]) {
  query('INSERT INTO doctors(name,specialty,room,bio) VALUES(?,?,?,?)',[$name,$specialty,$room,'Hồ sơ giả lập phục vụ đồ án, không đại diện cho bác sĩ hoặc cơ sở y tế thật.']);$id=(int)db()->lastInsertId();
  for($day=1;$day<=6;$day++) foreach(['08:00','08:30','09:00','09:30','10:00','10:30','14:00','14:30','15:00','15:30'] as $slot) query('INSERT INTO doctor_schedules(doctor_id,weekday,slot) VALUES(?,?,?)',[$id,$day,$slot]);
 }
}
