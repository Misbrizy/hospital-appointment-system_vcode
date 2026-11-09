<?php
/**
 * Finds appointments whose department disagrees with their assigned doctor's department and (optionally) fixes them.
 *   php bin/repair_departments.php           # report only — changes nothing
 *   php bin/repair_departments.php --apply   # set appointments.department_id to the doctor's department
 * Only the department_id of mismatching rows is updated; nothing is deleted. Take a backup first.
 */
if (PHP_SAPI !== 'cli') { exit("CLI only\n"); }
require dirname(__DIR__) . '/app/bootstrap.php';
$apply = in_array('--apply', $argv, true);
$bad = rows("SELECT a.id, a.appt_no, a.appt_date, ad.name AS appt_dept, dd.name AS doctor_dept, u.name AS doctor, u.department_id AS new_dept
    FROM appointments a JOIN users u ON u.id=a.doctor_id AND u.department_id IS NOT NULL
    LEFT JOIN departments ad ON ad.id=a.department_id LEFT JOIN departments dd ON dd.id=u.department_id
    WHERE a.department_id IS NULL OR a.department_id <> u.department_id ORDER BY a.id");
foreach ($bad as $b) { printf("%s %s  doctor %s (%s)  appointment shows: %s\n", $b['appt_no'], $b['appt_date'], $b['doctor'], $b['doctor_dept'], $b['appt_dept'] ?? 'none'); }
echo count($bad) . " mismatching appointment(s).\n";
if ($apply && $bad) {
    $n = q("UPDATE appointments a JOIN users u ON u.id=a.doctor_id AND u.department_id IS NOT NULL SET a.department_id=u.department_id WHERE a.department_id IS NULL OR a.department_id <> u.department_id")->rowCount();
    echo "Fixed $n.\n";
} elseif ($bad) { echo "Run again with --apply to fix them.\n"; }
