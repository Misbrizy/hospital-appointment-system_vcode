<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', cfg('db_host'), cfg('db_port'), cfg('db_name'));
    $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET time_zone = '" . (new DateTime('now'))->format('P') . "'");
    return $pdo;
}

/** Run a prepared statement. Always use ? placeholders; never concatenate user input into $sql. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** Insert an associative array. Table and column names come from code, never from user input. */
function insert(string $table, array $data): int
{
    $cols = implode(',', array_map(fn($c) => "`$c`", array_keys($data)));
    $marks = implode(',', array_fill(0, count($data), '?'));
    q("INSERT INTO `$table` ($cols) VALUES ($marks)", array_values($data));
    return (int) db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $whereParams = []): int
{
    $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
    return q("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams))->rowCount();
}

function transaction(callable $fn)
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $r = $fn();
        $pdo->commit();
        return $r;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/** Atomic per-hospital counter (patient numbers, appointment numbers). */
function next_seq(int $hospitalId, string $col): int
{
    if (!in_array($col, ['patient_seq', 'appt_seq', 'consultation_seq'], true)) {
        throw new InvalidArgumentException('bad sequence');
    }
    q("UPDATE hospitals SET $col = LAST_INSERT_ID($col + 1) WHERE id = ?", [$hospitalId]);
    return (int) db()->query('SELECT LAST_INSERT_ID()')->fetchColumn();
}
