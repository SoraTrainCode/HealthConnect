<?php
// Chỉ CLI; không in mật khẩu, khóa cài đặt hoặc thông tin tài khoản.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$failures = 0;
function report(bool $ok, string $label): void {
    global $failures;
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) $failures++;
}
report(version_compare(PHP_VERSION, '8.1', '>='), 'PHP >= 8.1');
foreach (['pdo_mysql', 'mbstring', 'curl', 'dom', 'fileinfo', 'SimpleXML', 'openssl'] as $ext) {
    report(extension_loaded($ext), 'Extension ' . $ext);
}
$root = dirname(__DIR__);
report(is_file($root . '/config.local.php'), 'config.local.php exists');
$config = require $root . '/config.example.php';
if (is_file($root . '/config.local.php')) $config = array_replace($config, require $root . '/config.local.php');
report((bool) filter_var($config['app_url'], FILTER_VALIDATE_URL), 'app_url is a valid URL (verify actual host/port yourself)');
try {
    $db = new PDO('mysql:host='.$config['db_host'].';port='.$config['db_port'].';dbname='.$config['db_name'].';charset=utf8mb4', $config['db_user'], $config['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    report(true, 'Database connection');
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['users','categories','articles','bmi_logs','chat_sessions','chat_messages','login_attempts','password_resets','appointments','appointment_mutex','doctors','doctor_schedules','doctor_days_off','appointment_events','wellness_logs','health_goals'] as $table) report(in_array($table, $tables, true), 'Table ' . $table);
    if (in_array('appointment_mutex', $tables, true)) report((bool) $db->query('SELECT id FROM appointment_mutex WHERE id=1')->fetchColumn(), 'Appointment transaction lock');
    if (in_array('appointments', $tables, true)) {
        $columns=$db->query('SHOW COLUMNS FROM appointments')->fetchAll(PDO::FETCH_COLUMN);
        report(in_array('doctor_id',$columns,true), 'Doctor assignment column (upgrade_final.sql)');
    }
    if (in_array('users', $tables, true)) {
        $hasAdmin = (bool) $db->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
        report($hasAdmin || strlen($config['setup_key']) >= 16, 'Admin exists or private setup key configured');
    }
} catch (Throwable $ex) {
    report(false, 'Database check failed; verify service, credentials and SQL imports locally');
}
report(is_dir($root . '/uploads') && is_writable($root . '/uploads'), 'uploads directory writable');
echo "Ollama generation and real email delivery need separate end-to-end checks.\n";
exit($failures ? 1 : 0);
