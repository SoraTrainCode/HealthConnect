<?php
// Development server: php -S localhost:8000 tools/router.php
$path = rawurldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "/");
if (
    preg_match(
        '~(?:^|/)(?:\.|includes(?:/|$)|database(?:/|$)|docs(?:/|$)|tools(?:/|$)|config\.|HUONG_DAN\.md)~i',
        $path,
    ) ||
    preg_match('~^/uploads/.*\.(php\d*|phtml|phar)$~i', $path)
) {
    http_response_code(403);
    exit("Forbidden");
}
return false;
