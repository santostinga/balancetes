<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Journal
{
    public static function entries(
        int $year,
        ?int $month = null,
        ?int $areaId = null,
        int $limit = 40,
        int $offset = 0
    ): array {
        [$sql, $params] = self::filterSql($year, $month, $areaId);
        $sql .= ' ORDER BY e.data, e.numero LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

        return Database::run($sql, $params)->fetchAll();
    }

    public static function countFiltered(int $year, ?int $month = null, ?int $areaId = null): int
    {
        [$sql, $params] = self::filterSql($year, $month, $areaId, true);

        return (int) Database::run($sql, $params)->fetchColumn();
    }

    private static function filterSql(int $year, ?int $month, ?int $areaId, bool $count = false): array
    {
        $select = $count
            ? 'SELECT COUNT(*) FROM journal_entries e WHERE e.ano = :ano'
            : 'SELECT e.*, a.nome AS area_nome, p.nome AS produto_nome
               FROM journal_entries e
               LEFT JOIN service_areas a ON a.id = e.area_id
               LEFT JOIN products p ON p.id = e.product_id
               WHERE e.ano = :ano';
        $params = [':ano' => $year];
        if ($month) {
            $select .= ' AND e.mes = :mes';
            $params[':mes'] = $month;
        }
        if ($areaId) {
            $select .= ' AND e.area_id = :area';
            $params[':area'] = $areaId;
        }

        return [$select, $params];
    }

    public static function linesFor(int $entryId): array
    {
        return Database::run(
            'SELECT l.*, c.nome AS conta_nome
             FROM journal_lines l
             JOIN accounts c ON c.codigo = l.account_codigo
             WHERE l.entry_id = ?
             ORDER BY l.id',
            [$entryId]
        )->fetchAll();
    }

    public static function ledger(string $codigo, int $year): array
    {
        return Database::run(
            'SELECT e.data, e.numero, e.descricao, l.debito, l.credito, l.descricao AS linha_descricao
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.entry_id
             WHERE l.account_codigo = :codigo AND e.ano = :ano
             ORDER BY e.data, e.numero, l.id',
            [':codigo' => $codigo, ':ano' => $year]
        )->fetchAll();
    }

    public static function countYear(int $year): int
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM journal_entries WHERE ano = ?',
            [$year]
        )->fetchColumn();
    }

    public static function lastRun(): ?array
    {
        return Database::run('SELECT * FROM simulation_runs ORDER BY id DESC LIMIT 1')->fetch() ?: null;
    }
}
