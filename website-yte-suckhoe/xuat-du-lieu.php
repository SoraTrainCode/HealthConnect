<?php
require __DIR__.'/includes/bootstrap.php';$u=require_user();
$type=($_GET['type']??'')==='diary'?'diary':'bmi';
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="HealthConnect-'.$type.'-'.date('Y-m-d').'.csv"');
$out=fopen('php://output','w');fwrite($out,"\xEF\xBB\xBF");
if($type==='bmi') {$headers=['Ngày đo','Chiều cao cm','Cân nặng kg','BMI','Phân loại','Ghi chú'];$sql='SELECT measured_at,height_cm,weight_kg,bmi_value,classification,note FROM bmi_logs WHERE user_id=? ORDER BY measured_at DESC';}
else {$headers=['Ngày','Ngủ giờ','Nước ml','Vận động phút','Ghi chú'];$sql='SELECT log_date,sleep_hours,water_ml,exercise_minutes,note FROM wellness_logs WHERE user_id=? ORDER BY log_date DESC';}
fputcsv($out,$headers,',','"','');
$rows=query($sql,[$u['id']]);while($row=$rows->fetch(PDO::FETCH_NUM)) {
 $row=array_map(function($value){$s=(string)$value;return preg_match('/^[\s]*[=+@-]|^[\t\r\n]/u',$s)?"'".$s:$s;},$row);
 fputcsv($out,$row,',','"','');
}fclose($out);
