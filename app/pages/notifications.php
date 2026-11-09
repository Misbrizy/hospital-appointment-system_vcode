<?php
$u = require_login();
// Appointment details (department, doctor, date) are read live from the appointment and doctor records,
// so a notification can never show a stale or wrong department. A doctor only sees them while the
// appointment is still assigned to them.
$items = rows("SELECT n.*, a.appt_date, a.appt_time, a.appt_no, a.status AS appt_status,
        COALESCE(ad.name, dd.name) AS dept_name, doc.name AS doctor_name
    FROM notifications n
    LEFT JOIN appointments a ON a.id = n.appointment_id AND a.hospital_id = n.hospital_id
        AND (? <> 'doctor' OR a.doctor_id = ?)
    LEFT JOIN departments ad ON ad.id = a.department_id
    LEFT JOIN users doc ON doc.id = a.doctor_id
    LEFT JOIN departments dd ON dd.id = doc.department_id
    WHERE n.user_id = ? ORDER BY n.created_at DESC LIMIT 50", [$u['role'], $u['id'], $u['id']]);
$wasUnread = array_column(array_filter($items, fn($n) => $n['read_at'] === null), 'id');
// Opening this page is itself the "read" action: everything shown here becomes read now,
// so the sidebar badge clears and these items stay read on the next visit. We still tell
// the view which ones WERE unread a moment ago so it can highlight "new since last time".
q('UPDATE notifications SET read_at=? WHERE user_id=? AND read_at IS NULL', [now(), $u['id']]);
render_page('Notifications', 'notifications', compact('items', 'wasUnread'));
