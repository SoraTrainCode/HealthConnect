<?php
require __DIR__ . "/../includes/bootstrap.php";
require __DIR__ . "/../includes/news_sync.php";
header("Content-Type: application/json; charset=utf-8");
try {
    $cache=news_sync();$items=$cache["items"]??[];
    echo json_encode(["items"=>$items,"fetched_at"=>$cache["fetched_at"]??null,"count"=>count($items),"stale"=>$cache["stale"]??false],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $error) {
    error_log("HealthConnect news API: ".$error->getMessage());$cache=news_cache_read();
    http_response_code($cache?200:503);
    echo json_encode(["items"=>$cache["items"]??[],"fetched_at"=>$cache["fetched_at"]??null,"count"=>count($cache["items"]??[]),"stale"=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
