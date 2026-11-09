<?php
$u = require_login();
require_can('finance.view');
$hid = current_hospital_id();

if (is_post()) {
    csrf_verify();
    require_can('finance.write');
    $category = post('category'); $amount = (int) post('amount'); $date = post('expense_date');
    $errors = [];
    if ($category === '') { $errors[] = 'Choose a category.'; }
    if ($amount <= 0) { $errors[] = 'Enter an amount greater than zero.'; }
    if (!valid_date($date)) { $errors[] = 'Enter a valid date.'; }
    if (!$errors) {
        $id = insert('expenses', ['hospital_id' => $hid, 'category' => $category, 'description' => post('description') ?: null, 'amount' => $amount, 'expense_date' => $date, 'recorded_by' => $u['id'], 'created_at' => now()]);
        audit('expense.create', 'expense', $id);
        flash('success', 'Expense recorded.');
        redirect('index.php?page=expenses');
    }
    flash('error', implode(' ', $errors));
    redirect('index.php?page=expenses');
}

$from = get('from', date('Y-m-01'));
$to = get('to', date('Y-m-d'));
if (!valid_date($from)) { $from = date('Y-m-01'); }
if (!valid_date($to)) { $to = date('Y-m-d'); }
$total = (int) val("SELECT COUNT(*) FROM expenses WHERE hospital_id=? AND expense_date BETWEEN ? AND ?", [$hid, $from, $to]);
$p = paginate($total, 30);
$expenses = rows("SELECT e.*, u.name AS recorded_by_name FROM expenses e LEFT JOIN users u ON u.id=e.recorded_by WHERE e.hospital_id=? AND e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date DESC LIMIT {$p['per']} OFFSET {$p['offset']}", [$hid, $from, $to]);
$sum = (int) val("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE hospital_id=? AND expense_date BETWEEN ? AND ?", [$hid, $from, $to]);
render_page('Expenses', 'expenses', compact('expenses', 'p', 'from', 'to', 'sum'));
