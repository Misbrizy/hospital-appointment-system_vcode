<?php
declare(strict_types=1);

/** Money in this system is whole francs (RWF) — all invoice arithmetic is integer, so printed totals always add up. */

/**
 * Reads the posted line items. Blank rows are ignored; a half-filled row is an error rather than silently dropped.
 * @return array{0: array<int,array{description:string,quantity:int,unit_price:int,line_total:int}>, 1: array<string>}
 */
function invoice_items_from_post(): array
{
    $descs = (array) ($_POST['item_desc'] ?? []);
    $qtys = (array) ($_POST['item_qty'] ?? []);
    $prices = (array) ($_POST['item_price'] ?? []);
    $items = []; $errors = [];
    foreach ($descs as $i => $d) {
        $d = trim((string) $d);
        $qRaw = trim((string) ($qtys[$i] ?? '')); $pRaw = trim((string) ($prices[$i] ?? ''));
        if ($d === '' && $qRaw === '' && $pRaw === '') { continue; }
        $n = count($items) + 1;
        if ($d === '') { $errors[] = "Line $n needs a description."; continue; }
        if (!ctype_digit($qRaw) || (int) $qRaw < 1 || (int) $qRaw > 100000) { $errors[] = "Line $n: quantity must be a whole number of 1 or more."; continue; }
        if (!ctype_digit($pRaw) || (int) $pRaw > 2000000000) { $errors[] = "Line $n: unit price must be a whole amount (0 or more)."; continue; }
        $items[] = ['description' => mb_substr($d, 0, 255), 'quantity' => (int) $qRaw, 'unit_price' => (int) $pRaw, 'line_total' => (int) $qRaw * (int) $pRaw];
        if (count($items) > 40) { $errors[] = 'An invoice can have at most 40 lines.'; break; }
    }
    if (!$items && !$errors) { $errors[] = 'Add at least one service or treatment line.'; }
    return [$items, $errors];
}

/** Subtotal, tax and total from the items — computed on the server, never trusted from the browser. */
function invoice_totals(array $items, float $taxRate, int $otherCharges): array
{
    $subtotal = array_sum(array_column($items, 'line_total'));
    $tax = (int) round($subtotal * $taxRate / 100);
    return ['subtotal' => $subtotal, 'tax_amount' => $tax, 'other_charges' => $otherCharges, 'total' => $subtotal + $tax + $otherCharges];
}

/**
 * The lines to show/print for an invoice. Invoices created before line items existed were a single description +
 * amount, so they are presented as one line (nothing is rewritten in the database for that).
 */
function invoice_lines(array $invoice): array
{
    $items = rows('SELECT description, quantity, unit_price, line_total FROM invoice_items WHERE invoice_id=? ORDER BY line_no, id', [$invoice['id']]);
    if ($items) {
        return $items;
    }
    $base = (int) $invoice['subtotal'] ?: (int) $invoice['amount'];
    return [['description' => $invoice['description'], 'quantity' => 1, 'unit_price' => $base, 'line_total' => $base]];
}
