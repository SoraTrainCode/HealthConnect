<?php
if (PHP_SAPI !== "cli") { http_response_code(404); exit; }
// php tests/appointments.php : tests sans database.
require dirname(__DIR__) . '/website-yte-suckhoe/includes/appointments.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$count = 0;
function verify(bool $ok, string $label): void {
    global $count;
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    $count++;
}
verify(appt_valid_date('2026-02-30') === null, 'Reject overflowing calendar date');
verify(appt_valid_date('2028-02-29') !== null, 'Accept leap day');
verify(appt_valid_date('2026-1-01') === null, 'Require canonical date');
verify(!appt_future('2000-01-01', '09:00:00'), 'Past slot');
verify(appt_future(date('Y-m-d', strtotime('+1 day')), '09:00:00'), 'Future slot');
verify(appt_next_status('pending', 'confirm', true) === 'confirmed', 'Confirm upcoming');
verify(appt_next_status('pending', 'confirm', false) === null, 'Reject expired confirmation');
verify(appt_next_status('confirmed', 'done', true) === null, 'Reject completion before appointment');
verify(appt_next_status('confirmed', 'done', false) === 'done', 'Complete elapsed appointment');
verify(appt_next_status('confirmed', 'noshow', false) === 'no_show', 'No show after start');
verify(appt_next_status('cancelled', 'reopen', true) === 'pending', 'Reopen future cancellation');
verify(appt_next_status('cancelled', 'reopen', false) === null, 'Reject past reopening');
verify(appt_next_status('done', 'reopen', true) === null, 'Completed history immutable');
verify(appt_next_status('pending', 'done', false) === null, 'Cannot skip confirmation');
verify(appt_next_status('unknown', 'cancel', true) === null, 'Reject unknown status');
echo "$count appointment rule checks passed\n";
