<?php
declare(strict_types=1);

/** The default bilingual message templates every hospital must have: [code, language, name, body]. */
function default_templates(): array
{
    return [
        ['reminder', 'rw', 'Reminder (Kinyarwanda)', "Muraho {patient_name}, turakwibutsa ko ufite gahunda yo kwisuzumisha ku wa {appointment_date} saa {appointment_time} kuri {hospital_name}. Murakoze."],
        ['reminder', 'en', 'Reminder (English)', "Hello {patient_name}, this is a reminder of your appointment on {appointment_date} at {appointment_time} at {hospital_name}. Thank you."],
        ['reminder_today', 'rw', 'Same-day reminder (Kinyarwanda)', "Muraho {patient_name}, uyu munsi ufite gahunda yo kwisuzumisha saa {appointment_time} kuri {hospital_name}. Murakaza neza."],
        ['reminder_today', 'en', 'Same-day reminder (English)', "Hello {patient_name}, today you have an appointment at {appointment_time} at {hospital_name}. See you soon."],
        ['confirmation', 'rw', 'Booking confirmation (Kinyarwanda)', "Muraho {patient_name}, gahunda yawe yo kwisuzumisha yemejwe: {appointment_date} saa {appointment_time} kuri {hospital_name}."],
        ['confirmation', 'en', 'Booking confirmation (English)', "Hello {patient_name}, your appointment is confirmed for {appointment_date} at {appointment_time} at {hospital_name}."],
        ['reschedule_approved', 'rw', 'Reschedule approved (Kinyarwanda)', "Muraho {patient_name}, gahunda yawe yahinduwe: {appointment_date} saa {appointment_time} kuri {hospital_name}."],
        ['reschedule_approved', 'en', 'Reschedule approved (English)', "Hello {patient_name}, your appointment has been moved to {appointment_date} at {appointment_time} at {hospital_name}."],
        ['cancelled', 'rw', 'Cancellation (Kinyarwanda)', "Muraho {patient_name}, gahunda yawe yo ku wa {appointment_date} yahagaritswe. Twavugane kuri {hospital_name}."],
        ['cancelled', 'en', 'Cancellation (English)', "Hello {patient_name}, your appointment on {appointment_date} has been cancelled. Please contact {hospital_name}."],
    ];
}

/** Default bilingual SMS templates every new hospital starts with; editable afterwards under SMS templates. */
function seed_default_templates(int $hid): void
{
    ensure_default_templates($hid);
}

/**
 * Adds any default template this hospital is missing (matched on code + language) and NEVER touches one that
 * already exists, so a hospital's own edits survive. Hospitals created by an older version, or whose template
 * was deleted, used to silently lose that message (e.g. the "today you have an appointment" reminder was
 * simply never queued). Called before every queueing and by bin/migrate.php.
 *
 * @return int number of templates added
 */
function ensure_default_templates(int $hid): int
{
    $have = [];
    foreach (rows('SELECT code, language FROM sms_templates WHERE hospital_id=?', [$hid]) as $t) {
        $have[$t['code'] . '|' . $t['language']] = true;
    }
    $added = 0;
    foreach (default_templates() as [$code, $lang, $name, $body]) {
        if (isset($have[$code . '|' . $lang])) { continue; }
        try {
            insert('sms_templates', ['hospital_id' => $hid, 'code' => $code, 'language' => $lang, 'name' => $name, 'body' => $body]);
            $added++;
        } catch (Throwable $e) {
            error_log("ensure_default_templates($hid, $code/$lang): " . $e->getMessage()); // e.g. a parallel request inserted it first
        }
    }
    return $added;
}

function seed_default_reminder_rules(int $hid): void
{
    $rules = [
        ['3 days before', 3, '08:00:00', 'reminder'],
        ['1 day before', 1, '08:00:00', 'reminder'],
        ['On the day', 0, '06:30:00', 'reminder_today'],
    ];
    foreach ($rules as [$label, $days, $time, $tpl]) {
        insert('reminder_rules', ['hospital_id' => $hid, 'label' => $label, 'days_before' => $days, 'send_time' => $time, 'template_code' => $tpl, 'is_active' => 1]);
    }
}

/** Gives a hospital the standard rules only if it has none at all (never adds to, or changes, rules it already has). */
function ensure_default_reminder_rules(int $hid): int
{
    if ((int) val('SELECT COUNT(*) FROM reminder_rules WHERE hospital_id=?', [$hid]) > 0) { return 0; }
    seed_default_reminder_rules($hid);
    return 3;
}
