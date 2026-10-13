<?php
/** Cấu hình và hàm dùng chung cho chức năng đặt lịch khám. */
const APPT_CAPACITY = 3;      // số lượt tối đa mỗi khung giờ
const APPT_MAX_DAYS = 30;     // chỉ đặt trước tối đa 30 ngày
const APPT_MAX_ACTIVE = 3;    // mỗi người tối đa 3 lịch chưa khám

function appt_slots(): array
{
    return ["08:00","08:30","09:00","09:30","10:00","10:30","11:00","11:30",
            "13:30","14:00","14:30","15:00","15:30","16:00","16:30"];
}
function appt_specialties(): array
{
    return ["Khám tổng quát","Nội khoa","Nhi khoa","Tim mạch","Dinh dưỡng","Da liễu","Tai mũi họng","Răng hàm mặt"];
}
function appt_statuses(): array
{
    return [
        "pending"   => ["Chờ xác nhận", "text-bg-warning"],
        "confirmed" => ["Đã xác nhận",  "text-bg-primary"],
        "done"      => ["Đã khám",      "text-bg-success"],
        "cancelled" => ["Đã hủy",       "text-bg-secondary"],
        "no_show"   => ["Không đến",    "text-bg-danger"],
    ];
}
function appt_badge(string $status): string
{
    [$label, $cls] = appt_statuses()[$status] ?? [$status, "text-bg-light"];
    return '<span class="badge ' . $cls . '">' . e($label) . "</span>";
}
function appt_valid_date(string $d): ?DateTime
{
    $dt = DateTime::createFromFormat("Y-m-d", $d);
    return ($dt && $dt->format("Y-m-d") === $d) ? $dt : null;
}
