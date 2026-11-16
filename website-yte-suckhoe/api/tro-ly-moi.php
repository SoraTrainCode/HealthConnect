<?php require dirname(__DIR__) . "/includes/bootstrap.php";
if (!post()) {
    http_response_code(405);
    exit();
}
check_csrf();
unset($_SESSION["guest_chat"]);
header("Content-Type: application/json");
echo '{"ok":true}';
