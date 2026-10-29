<?php
if (PHP_SAPI !== "cli") { http_response_code(404); exit; }
// Uses a NEW random test database; never reads app config or touches the app DB.
require __DIR__ . '/appointments.php';
$host = getenv('HC_TEST_DB_HOST') ?: '127.0.0.1';
$port = getenv('HC_TEST_DB_PORT') ?: '3306';
$login = getenv('HC_TEST_DB_USER') ?: 'root';
$password = getenv('HC_TEST_DB_PASS') ?: '';
$name = 'healthconnect_test_' . bin2hex(random_bytes(6));
$pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $login, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
function db(): PDO { global $pdo; return $pdo; }
function query(string $sql, array $params = []): PDOStatement { $s=db()->prepare($sql); $s->execute($params); return $s; }
function rejected(callable $work, string $label): void {
    $failed = false;
    try { $work(); } catch (RuntimeException $e) { if ($e instanceof PDOException) throw $e; $failed = true; }
    verify($failed, $label);
}
function book(int $uid, string $date, string $time): void {
    try {
        appt_begin();
        appt_check_capacity($uid, $date, $time);
        query("INSERT INTO appointments(user_id,patient_name,phone,specialty,appt_date,appt_time) VALUES(?,'Test','0900000000','Nội khoa',?,?)", [$uid,$date,$time]);
        db()->commit();
    } catch (Throwable $e) { if (db()->inTransaction()) db()->rollBack(); throw $e; }
}
try {
    $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");
    $pdo->exec("SET time_zone = '+07:00'");
    $root = dirname(__DIR__) . '/website-yte-suckhoe/database/';
    $schema = str_replace("\r\n", "\n", file_get_contents($root . 'schema.sql'));
    $schema = preg_replace('/^(CREATE DATABASE.*|USE healthconnect_moi;)$/m', '', $schema);
    $pdo->exec($schema);
    verify(count(query('SHOW TABLES')->fetchAll()) === 16, 'Fresh install has all sixteen tables');
    $migration = str_replace('USE healthconnect_moi;', '', file_get_contents($root . 'appointments.sql'));
    $pdo->exec($migration); $pdo->exec($migration);
    verify((int) query('SELECT COUNT(*) FROM appointment_mutex')->fetchColumn() === 1, 'Migration safely repeatable');
    for ($i=1;$i<=6;$i++) query("INSERT INTO users(full_name,email,password_hash) VALUES(?,?,'test-only')", ['Test '.$i,'test'.$i.'@example.test']);
    $day = date('Y-m-d', strtotime('+2 days'));
    book(1,$day,'09:00');
    rejected(fn()=>book(1,$day,'09:00'), 'Duplicate appointment rejected');
    book(2,$day,'09:00'); book(3,$day,'09:00');
    rejected(fn()=>book(4,$day,'09:00'), 'Fourth booking rejected');
    appt_admin_update(1,'cancel'); book(4,$day,'09:00');
    rejected(fn()=>appt_admin_update(1,'reopen'), 'Reopen cannot exceed capacity');
    appt_admin_update(2,'cancel'); appt_admin_update(1,'reopen');
    verify(query('SELECT status FROM appointments WHERE id=1')->fetchColumn()==='pending','Reopen after capacity available');
    book(1,$day,'10:00'); book(1,$day,'11:00');
    rejected(fn()=>book(1,$day,'14:00'), 'Per-user active limit enforced');
    appt_admin_update(1,'confirm');
    rejected(fn()=>appt_admin_update(1,'done'), 'Server rejects early completion');
    rejected(fn()=>appt_admin_update(999999,'confirm'), 'Missing appointment rejected');
    verify(!db()->inTransaction(), 'Rejected updates release transaction');
    // Separate PHP processes compete for a single remaining slot.
    query('DELETE FROM appointments');
    book(1,$day,'15:00'); book(2,$day,'15:00');
    $children=[];
    for ($uid=3;$uid<=6;$uid++) {
        $cmd=[PHP_BINARY];
        if (php_ini_loaded_file()) { $cmd[]='-c'; $cmd[]=php_ini_loaded_file(); }
        array_push($cmd,__DIR__.'/booking-worker.php',$name,(string)$uid,$day);
        $pipes=[];
        $proc=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if (!is_resource($proc)) throw new RuntimeException('Could not start concurrency worker');
        fclose($pipes[0]); $children[]=[$proc,$pipes];
    }
    $success=0;
    foreach ($children as [$proc,$pipes]) {
        $output=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $code=proc_close($proc);
        if ($code===0) $success++;
        elseif ($code!==2) throw new RuntimeException('Worker error: '.$output.$err);
    }
    verify($success===1,'Exactly one concurrent booking takes last slot');
    verify((int)query('SELECT COUNT(*) FROM appointments')->fetchColumn()===3,'Concurrent booking never overfills');
    $upgrade=file_get_contents($root.'upgrade_final.sql');
    $pdo->exec($upgrade);$pdo->exec($upgrade);
    verify((int)query('SELECT COUNT(*) FROM appointments')->fetchColumn()===3,'Upgrade preserves existing appointments');
    // Emulate pre-upgrade schema: appointments existed before doctor_id.
    query('ALTER TABLE appointments DROP INDEX idx_doctor_slot, DROP COLUMN doctor_id');
    $pdo->exec($upgrade);
    verify((int)query('SELECT COUNT(*) FROM appointments WHERE doctor_id IS NULL')->fetchColumn()===3,'Upgrade adds nullable doctor column and retains legacy rows');
    query('DELETE FROM appointments');
    require dirname(__DIR__).'/website-yte-suckhoe/includes/demo.php';
    seed_demo_doctors();
    verify((int)query('SELECT COUNT(*) FROM doctors')->fetchColumn()===3,'Demo doctors seeded');
    $doctorDay=date('Y-m-d',strtotime('+2 days'));
    while(date('w',strtotime($doctorDay))==='0') $doctorDay=date('Y-m-d',strtotime($doctorDay.' +1 day'));
    $u=['id'=>1];
    $id=appt_book($u,1,$doctorDay,'09:00','Test','0900000000','Demo');
    rejected(fn()=>appt_book(['id'=>2],1,$doctorDay,'09:00','Test','0900000000','Demo'),'Doctor slot capacity one');
    rejected(fn()=>appt_book($u,2,$doctorDay,'09:00','Test','0900000000','Demo'),'User cannot book same time across doctors');
    $id2=appt_book(['id'=>2],2,$doctorDay,'09:00','Test','0900000000','Demo');
    verify($id2>$id,'Different doctors accept same time');
    rejected(fn()=>appt_book(['id'=>3],1,$doctorDay,'11:00','Test','0900000000','Demo'),'Reject outside schedule');
    query('INSERT INTO doctor_days_off(doctor_id,off_date) VALUES(3,?)',[$doctorDay]);
    rejected(fn()=>appt_book(['id'=>3],3,$doctorDay,'09:00','Test','0900000000','Demo'),'Reject doctor day off');
    rejected(fn()=>appt_cancel($id,2),'Owner isolation for cancellation');
    appt_cancel($id,1);
    verify((int)query('SELECT COUNT(*) FROM appointment_events WHERE appointment_id=?',[$id])->fetchColumn()===2,'Booking and cancellation audited');
    query('UPDATE doctors SET active=0 WHERE id=1');
    rejected(fn()=>appt_admin_update($id,'reopen'),'Reopen inactive doctor rejected');
    echo "$count total rule/database/concurrency checks passed\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec("DROP DATABASE IF EXISTS `$name`");
}
