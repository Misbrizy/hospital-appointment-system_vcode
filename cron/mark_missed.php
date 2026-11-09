<?php
/**
 * Marks appointments as missed a configurable number of hours after their scheduled time
 * if nobody checked the patient in. Run hourly:
 *   5 * * * * php /path/to/gahunda/cron/mark_missed.php >> /path/to/gahunda/storage/logs/cron.log 2>&1
 */
require dirname(__DIR__) . '/app/bootstrap.php';

$hours = (int) cfg('missed_after_hours', 3);
$cutoff = date('Y-m-d H:i:s', time() - $hours * 3600);
$stmt = q("UPDATE appointments SET status='missed', updated_at=? WHERE status IN ('scheduled','confirmed') AND TIMESTAMP(appt_date, appt_time) < ?", [now(), $cutoff]);
echo date('Y-m-d H:i:s') . ' marked ' . $stmt->rowCount() . " appointments missed\n";
