<?php
/** Housekeeping: deletes rate-limit rows older than a day. Run daily. */
require dirname(__DIR__) . '/app/bootstrap.php';
$stmt = q('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
echo date('Y-m-d H:i:s') . ' removed ' . $stmt->rowCount() . " old rate-limit rows\n";
