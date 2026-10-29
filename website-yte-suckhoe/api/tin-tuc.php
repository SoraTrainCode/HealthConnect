<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/news_sync.php";
header("Content-Type: application/json; charset=utf-8");
try {
    $cache=news_sync();$items=$cache["items"]??[];$stale=(bool)($cache["stale"]??false);
    if (!$items && $stale) {http_response_code(503);$payload=["thanh_cong"=>false,"thong_bao"=>"Chưa có tin tức. Vui lòng thử lại sau.","danh_sach"=>[]];}
    else {$payload=["thanh_cong"=>true,"thong_bao"=>$stale?"Đang hiển thị tin đã lưu do chưa kết nối được nguồn tin.":"Đã cập nhật tin tức sức khỏe mới nhất.","tong_so"=>count($items),"thoi_gian_cap_nhat"=>isset($cache["fetched_at"])?date(DATE_ATOM,(int)$cache["fetched_at"]):null,"du_lieu_cu"=>$stale,"danh_sach"=>array_map(static fn($item)=>["id"=>$item["id"]??"","tieu_de"=>$item["title"]??"","tom_tat"=>$item["summary"]??"","anh_minh_hoa"=>$item["thumbnail_url"]??"","danh_muc"=>$item["category_name"]??$item["category"]??"","ngay_dang"=>$item["published_at"]??$item["created_at"]??"","ten_nguon"=>$item["source_name"]??"","lien_ket_nguon"=>$item["source_url"]??""],$items)];}
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $error) {
    error_log("HealthConnect news API: ".$error->getMessage());$cache=news_cache_read();$items=$cache["items"]??[];
    http_response_code($items?200:503);
    $payload=$items?["thanh_cong"=>true,"thong_bao"=>"Đang hiển thị tin đã lưu do chưa kết nối được nguồn tin.","tong_so"=>count($items),"thoi_gian_cap_nhat"=>isset($cache["fetched_at"])?date(DATE_ATOM,(int)$cache["fetched_at"]):null,"du_lieu_cu"=>true,"danh_sach"=>array_map(static fn($item)=>["id"=>$item["id"]??"","tieu_de"=>$item["title"]??"","tom_tat"=>$item["summary"]??"","anh_minh_hoa"=>$item["thumbnail_url"]??"","danh_muc"=>$item["category_name"]??$item["category"]??"","ngay_dang"=>$item["published_at"]??$item["created_at"]??"","ten_nguon"=>$item["source_name"]??"","lien_ket_nguon"=>$item["source_url"]??""],$items)]:["thanh_cong"=>false,"thong_bao"=>"Chưa có tin tức. Vui lòng thử lại sau.","danh_sach"=>[]];
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
