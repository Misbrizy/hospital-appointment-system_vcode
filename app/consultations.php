<?php
declare(strict_types=1);

/**
 * Consultation records. They live in the existing `medical_records` table (no parallel table): the clinical
 * content is ONE encrypted JSON document in notes_enc, while who/when/which-department/numbering are plain
 * columns so lists and access checks never need to decrypt anything.
 *
 * Records are append-only: a saved consultation is never edited or deleted from the application. A doctor who
 * needs to correct or add something writes an ADDENDUM (a new, dated record linked via addendum_of), so the
 * original wording always remains available — standard medical-documentation practice.
 */

/** Sections of a consultation note, in the order they are written and printed. [key => [label, rows, hint]] */
const CONSULTATION_SECTIONS = [
    'complaint'            => ['Presenting symptoms & complaints', 3, 'Why the patient came, in their own words; since when; how severe'],
    'history'              => ['History of present illness', 3, 'Relevant medical history, allergies, current medicines (optional)'],
    'examination'          => ['Examination findings', 4, 'What you found on examination'],
    'diagnosis'            => ['Diagnosis', 2, 'Working or confirmed diagnosis'],
    'treatment'            => ['Treatment & procedures', 3, 'Treatment given or planned, advice, investigations ordered'],
    'medication'           => ['Prescribed medication', 4, 'Medicine, dose, frequency, duration'],
    'notes'                => ['Additional clinical notes', 3, ''],
    'followup_instructions' => ['Follow-up instructions & recommendations', 3, 'When to return, warning signs, lifestyle advice'],
];

/** Optional vital signs captured with the consultation. [key => [label, unit, max-length]] */
const CONSULTATION_VITALS = [
    'bp' => ['Blood pressure', 'mmHg', 9], 'pulse' => ['Pulse', 'bpm', 3], 'temp' => ['Temperature', '°C', 5],
    'resp' => ['Respiratory rate', '/min', 3], 'spo2' => ['Oxygen saturation', '%', 3], 'weight' => ['Weight', 'kg', 6],
];

function next_consultation_no(int $hospitalId): string
{
    return 'CN-' . str_pad((string) next_seq($hospitalId, 'consultation_seq'), 6, '0', STR_PAD_LEFT);
}

/** Packs the posted fields into the encrypted payload stored in notes_enc. */
function consultation_encode(array $sections, array $vitals): string
{
    return (string) encrypt_field(json_encode(['_fmt' => 'consultation/1', 'sections' => $sections, 'vitals' => $vitals], JSON_UNESCAPED_UNICODE));
}

/**
 * Reads a record back. Older records (written before structured consultations) hold one block of plain text; they
 * are returned under 'legacy' so they stay readable and nothing is lost.
 *
 * @return array{sections:array<string,string>, vitals:array<string,string>, legacy:?string, addendum:?string}
 */
function consultation_decode(?string $enc): array
{
    $out = ['sections' => [], 'vitals' => [], 'legacy' => null, 'addendum' => null];
    $plain = decrypt_field($enc);
    if ($plain === null) {
        $out['legacy'] = '(this note could not be decrypted)';
        return $out;
    }
    $j = json_decode($plain, true);
    if (is_array($j) && ($j['_fmt'] ?? '') === 'consultation/1') {
        $out['sections'] = array_map('strval', $j['sections'] ?? []);
        $out['vitals'] = array_map('strval', $j['vitals'] ?? []);
        $out['addendum'] = isset($j['addendum']) ? (string) $j['addendum'] : null;
        return $out;
    }
    if (is_array($j) && ($j['_fmt'] ?? '') === 'addendum/1') {
        $out['addendum'] = (string) ($j['text'] ?? '');
        return $out;
    }
    $out['legacy'] = $plain;
    return $out;
}

/** One-line preview for lists (diagnosis, else complaint, else the start of a legacy note). */
function consultation_preview(?string $enc, int $len = 90): string
{
    $d = consultation_decode($enc);
    $t = $d['sections']['diagnosis'] ?? '';
    $t = $t !== '' ? $t : ($d['sections']['complaint'] ?? ($d['legacy'] ?? ($d['addendum'] ?? '')));
    return mb_strimwidth(preg_replace('/\s+/', ' ', (string) $t), 0, $len, '…');
}

/** Reads and trims the posted consultation form. @return array{0:array,1:array,2:array} [sections, vitals, errors] */
function consultation_from_post(): array
{
    $sections = []; $vitals = []; $errors = [];
    foreach (array_keys(CONSULTATION_SECTIONS) as $k) {
        $sections[$k] = mb_substr(trim((string) ($_POST[$k] ?? '')), 0, 5000);
    }
    foreach (CONSULTATION_VITALS as $k => [$label, $unit, $max]) {
        $v = trim((string) ($_POST['v_' . $k] ?? ''));
        if ($v === '') { continue; }
        $pattern = $k === 'bp' ? '/^\d{2,3}\/\d{2,3}$/' : '/^\d{1,3}(\.\d{1,2})?$/';
        if (!preg_match($pattern, $v) || strlen($v) > $max) {
            $errors[] = "$label looks wrong ($v). " . ($k === 'bp' ? 'Use e.g. 120/80.' : 'Use a number.');
        } else {
            $vitals[$k] = $v;
        }
    }
    if ($sections['complaint'] === '' && $sections['diagnosis'] === '') {
        $errors[] = 'Record at least the presenting complaint or the diagnosis.';
    }
    return [$sections, $vitals, $errors];
}
