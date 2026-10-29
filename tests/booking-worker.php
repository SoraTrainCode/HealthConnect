<?php
if (PHP_SAPI !== 'cli' || !preg_match('/^healthconnect_test_[a-f0-9]{12}$/', $argv[1] ?? '')) exit(1);
require dirname(__DIR__) . '/website-yte-suckhoe/includes/appointments.php';
$host=getenv('HC_TEST_DB_HOST') ?: '127.0.0.1'; $port=getenv('HC_TEST_DB_PORT') ?: '3306';
$pdo=new PDO("mysql:host=$host;port=$port;dbname={$argv[1]};charset=utf8mb4",getenv('HC_TEST_DB_USER') ?: 'root',getenv('HC_TEST_DB_PASS') ?: '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$pdo->exec("SET time_zone='+07:00'");
function db(): PDO {global $pdo;return $pdo;}
function query(string $sql,array $params=[]): PDOStatement {$s=db()->prepare($sql);$s->execute($params);return $s;}
try {
    appt_begin();
    appt_check_capacity((int)$argv[2],$argv[3],'15:00');
    query("INSERT INTO appointments(user_id,patient_name,phone,specialty,appt_date,appt_time) VALUES(?,'Concurrency test','0900000000','Nội khoa',?,'15:00')",[$argv[2],$argv[3]]);
    db()->commit();
} catch (PDOException $e) {
    if(db()->inTransaction()) db()->rollBack();
    fwrite(STDERR,$e->getMessage()); exit(1);
} catch (RuntimeException $e) {
    if(db()->inTransaction()) db()->rollBack(); exit(2);
}
