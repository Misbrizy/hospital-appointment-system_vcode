<div class="section-head"><h2>Message delivery <span class="muted" style="font-weight:400">(SMS · Email · WhatsApp)</span></h2>
  <?php if (can('sms.manage')): ?><form method="post" style="margin:0"><?= csrf_field() ?><input type="hidden" name="action" value="run_now"><button class="btn small" type="submit">Deliver due messages now</button></form><?php endif; ?>
</div>
<?php if (!$scheduler['healthy']): ?>
<div class="alert error" style="margin-bottom:16px"><strong>The reminder scheduler is not running.</strong>
  <?= $scheduler['last_run'] ? 'It last ran ' . e(fmt_dt(date('Y-m-d H:i:s', $scheduler['last_run']))) . '.' : 'It has never run.' ?>
  Reminders stay <em>Pending</em> until it does. On XAMPP/Windows run <code>bin\install-windows-task.bat</code> once (as administrator), or keep <code>cron\worker.php</code> open.
  Until then, reminders are also delivered whenever someone uses the system.</div>
<?php else: ?>
<?php if (!empty($overdue)): ?>
<div class="alert error" style="margin-bottom:16px"><strong><?= (int) $overdue ?> message(s) are overdue.</strong>
  They were due more than 5 minutes ago but are still pending. Press <em>Deliver due messages now</em>; if they stay pending, run
  <code>php bin\check_reminders.php</code> on the server to see why.</div>
<?php endif; ?>
<p class="muted" style="margin-top:0">Scheduler active — last run <?= e(fmt_dt(date('Y-m-d H:i:s', $scheduler['last_run']))) ?> (<?= e((string) $scheduler['source']) ?>).</p>
<?php endif; ?>
<div class="grid stat-grid" style="margin-bottom:20px">
  <div class="stat"><span class="n"><?= (int) $summary['total'] ?></span><span class="l">Total messages</span></div>
  <div class="stat"><span class="n"><?= (int) $summary['sent'] + (int) $summary['delivered'] ?></span><span class="l">Sent</span></div>
  <div class="stat danger"><span class="n"><?= (int) $summary['failed'] ?></span><span class="l">Failed</span></div>
  <div class="stat amber"><span class="n"><?= (int) $summary['pending'] ?></span><span class="l">Pending</span></div>
</div>
<form method="get" style="margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap">
  <input type="hidden" name="page" value="sms_logs">
  <select name="status" onchange="this.form.submit()" style="max-width:200px">
    <option value="">All statuses</option>
    <?php foreach (['pending','sending','sent','delivered','failed','cancelled'] as $s): ?><option value="<?= $s ?>" <?= selected($status, $s) ?>><?= STATUS_LABELS[$s] ?></option><?php endforeach; ?>
  </select>
  <select name="channel" onchange="this.form.submit()" style="max-width:200px">
    <option value="">All channels</option>
    <?php foreach (['sms' => 'SMS', 'email' => 'Email', 'whatsapp' => 'WhatsApp'] as $k => $lbl): ?><option value="<?= $k ?>" <?= selected($channel, $k) ?>><?= $lbl ?></option><?php endforeach; ?>
  </select>
</form>
<div class="table-wrap"><table class="list">
  <thead><tr><th>Scheduled</th><th>Channel</th><th>To</th><th>Message</th><th>Status</th><th>Attempts</th></tr></thead>
  <tbody>
  <?php if (!$logs): ?><tr><td colspan="6" class="empty">No messages logged yet.</td></tr><?php endif; ?>
  <?php foreach ($logs as $l): ?>
    <tr>
      <td><?= e(fmt_dt($l['scheduled_at'])) ?></td><td><?= e(ucfirst($l['channel'] ?? 'sms')) ?></td><td class="mono"><?= e($l['to_phone']) ?></td>
      <td><?= e(mb_strimwidth($l['body'], 0, 60, '…')) ?></td>
      <td><?= badge($l['status']) ?>
        <?php if ($l['status'] === 'pending' && $l['next_attempt_at']): ?><div class="muted" style="font-size:.76rem">retry at <?= e(fmt_dt($l['next_attempt_at'])) ?></div><?php endif; ?>
        <?= $l['error'] ? '<div class="muted" style="font-size:.76rem">' . e($l['error']) . '</div>' : '' ?>
        <?php if ($l['status'] === 'failed' && can('sms.manage')): ?><form method="post" style="margin:4px 0 0"><?= csrf_field() ?><input type="hidden" name="action" value="retry"><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><button class="btn small ghost" type="submit">Retry</button></form><?php endif; ?>
      </td>
      <td>
        <?php $att = $attempts[$l['id']] ?? []; if ($att): ?>
          <details><summary><?= count($att) ?> · <?= e(fmt_dt($l['last_attempt_at'])) ?></summary>
            <?php foreach ($att as $a): ?><div style="font-size:.76rem;white-space:nowrap">#<?= (int) $a['attempt_no'] ?> <?= e(fmt_dt($a['attempted_at'])) ?> — <strong><?= e($a['outcome']) ?></strong><?= $a['provider'] ? ' (' . e($a['provider']) . ')' : '' ?><?= $a['error'] ? ': ' . e($a['error']) : '' ?></div><?php endforeach; ?>
          </details>
        <?php else: ?><span class="muted">—</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?= pager($p) ?>
