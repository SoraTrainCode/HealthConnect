<?php
declare(strict_types=1);
date_default_timezone_set("Asia/Ho_Chi_Minh");
ini_set("display_errors", "0");
ini_set("session.use_strict_mode", "1");
session_set_cookie_params([
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax",
]);
session_start();
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store");
$config = require dirname(__DIR__) . "/config.example.php";
if (is_file(dirname(__DIR__) . "/config.local.php")) {
    $config = array_replace(
        $config,
        require dirname(__DIR__) . "/config.local.php",
    );
}
function cfg(string $key): string
{
    global $config;
    return (string) ($config[$key] ?? "");
}
function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            "mysql:host=" .
                cfg("db_host") .
                ";port=" .
                cfg("db_port") .
                ";dbname=" .
                cfg("db_name") .
                ";charset=utf8mb4",
            cfg("db_user"),
            cfg("db_pass"),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
    $pdo->exec("SET time_zone = '+07:00'");
    return $pdo;
}
function query(string $sql, array $params = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($params);
    return $s;
}
function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8",
    );
}
function url(string $path = ""): string
{
    return (defined("ADMIN_PAGE") ? "../" : "") . $path;
}
function redirect(string $path): void
{
    header("Location: " . $path);
    exit();
}
function token(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}
function csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(token()) . '">';
}
function check_csrf(): void
{
    if (
        !hash_equals(
            token(),
            (string) ($_POST["csrf"] ?? ($_SERVER["HTTP_X_CSRF_TOKEN"] ?? "")),
        )
    ) {
        http_response_code(403);
        exit("Phiên gửi biểu mẫu không hợp lệ. Hãy tải lại trang.");
    }
}
function user(): ?array
{
    if (empty($_SESSION["uid"])) {
        return null;
    }
    $u = query("SELECT * FROM users WHERE id=?", [$_SESSION["uid"]])->fetch();
    if (
        !$u ||
        $u["status"] !== "active" ||
        !hash_equals(
            (string) ($_SESSION["auth_hash"] ?? ""),
            hash("sha256", $u["password_hash"]),
        )
    ) {
        unset($_SESSION["uid"]);
        return null;
    }
    return $u;
}
function require_user(): array
{
    $u = user();
    if (!$u) {
        redirect(url("dang-nhap.php"));
    }
    return $u;
}
function require_admin(): array
{
    $u = require_user();
    if ($u["role"] !== "admin") {
        http_response_code(403);
        exit("Bạn không có quyền quản trị.");
    }
    return $u;
}
function flash(string $message): void
{
    $_SESSION["flash"] = $message;
}
function post(): bool
{
    return $_SERVER["REQUEST_METHOD"] === "POST";
}
function field(string $name, int $max = 1000): string
{
    $v = trim((string) ($_POST[$name] ?? ""));
    if (mb_strlen($v) > $max) {
        throw new RuntimeException("Nội dung quá dài: " . $name);
    }
    return $v;
}
function password_valid(string $p): void
{
    if (strlen($p) < 8 || strlen($p) > 72) {
        throw new RuntimeException("Mật khẩu cần từ 8 đến 72 byte.");
    }
}
function bmi_class(float $b): string
{
    return $b < 18.5
        ? "Thiếu cân"
        : ($b < 23
            ? "Bình thường"
            : ($b < 27.5
                ? "Thừa cân"
                : "Béo phì"));
}
function notice(?string $error): void
{
    if ($error) {
        echo '<div class="alert alert-danger">' . e($error) . "</div>";
    }
}
set_exception_handler(function (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    echo "<h2>Chưa thể xử lý yêu cầu</h2><p>Kiểm tra cấu hình database, import SQL và nhật ký PHP. Xem HUONG_DAN.md để cài đặt.</p>";
});

function safe_content(string $html): string
{
    if (strpos($html, "<") === false) {
        return nl2br(e($html));
    }
    $dom = new DOMDocument("1.0", "UTF-8");
    $previous = libxml_use_internal_errors(true);
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div>' . $html . "</div>",
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $walk = function ($node) use (&$walk): string {
        if ($node instanceof DOMText) {
            return e($node->nodeValue);
        }
        if (!($node instanceof DOMElement)) {
            return "";
        }
        $tag = strtolower($node->tagName);
        if (
            in_array(
                $tag,
                [
                    "script",
                    "style",
                    "iframe",
                    "object",
                    "svg",
                    "math",
                    "template",
                ],
                true,
            )
        ) {
            return "";
        }
        $children = "";
        foreach ($node->childNodes as $child) {
            $children .= $walk($child);
        }
        if (
            !in_array(
                $tag,
                [
                    "p",
                    "br",
                    "strong",
                    "b",
                    "em",
                    "i",
                    "u",
                    "ul",
                    "ol",
                    "li",
                    "h2",
                    "h3",
                    "blockquote",
                    "a",
                ],
                true,
            )
        ) {
            return $children;
        }
        if ($tag === "br") {
            return "<br>";
        }
        $attrs = "";
        if ($tag === "a") {
            $href = $node->getAttribute("href");
            if (
                filter_var($href, FILTER_VALIDATE_URL) &&
                in_array(
                    parse_url($href, PHP_URL_SCHEME),
                    ["http", "https"],
                    true,
                )
            ) {
                $attrs = ' href="' . e($href) . '" rel="noopener noreferrer"';
            }
        }
        return "<" . $tag . $attrs . ">" . $children . "</" . $tag . ">";
    };
    $result = "";
    foreach ($dom->childNodes as $node) {
        $result .= $walk($node);
    }
    return $result;
}
