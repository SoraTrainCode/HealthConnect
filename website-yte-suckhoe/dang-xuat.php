<?php require __DIR__ . "/includes/bootstrap.php";
if (!post()) {
    http_response_code(405);
    exit();
}
check_csrf();
$_SESSION = [];
session_regenerate_id(true);
redirect("index.php");
