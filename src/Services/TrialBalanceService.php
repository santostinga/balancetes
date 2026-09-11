<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Support\Database;

final class TrialBalanceService
{
    public function build(int $year, int $fromMonth = 1, int $toMonth = 12, bool $onlyMovement = true): array
    {
        $fromMonth = max(1, min(12, $fromMonth));
        $toMonth = max($fromMonth, min(12, $toMonth));
        $accounts = Account::map();

        $movements = Database::run(
            'SELECT l.account_codigo,
                    ROUND(SUM(CASE WHEN e.mes < :from THEN l.debito ELSE 0 END), 2) AS ant_d,
                    ROUND(SUM(CASE WHEN e.mes < :from THEN l.credito ELSE 0 END), 2) AS ant_c,
                    ROUND(SUM(CASE WHEN e.mes BETWEEN :from AND :to THEN l.debito ELSE 0 END), 2) AS mov_d,
                    ROUND(SUM(CASE WHEN e.mes BETWEEN :from AND :to THEN l.credito ELSE 0 END), 2) AS mov_c
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.entry_id
             WHERE e.ano = :year
             GROUP BY l.account_codigo
             ORDER BY l.account_codigo',
            [':from' => $fromMonth, ':to' => $toMonth, ':year' => $year]
        )->fetchAll();

        $rows = [];
        $totals = [
            'ant_d' => 0.0, 'ant_c' => 0.0, 'mov_d' => 0.0, 'mov_c' => 0.0, 'sal_d' => 0.0, 'sal_c' => 0.0,
        ];

        foreach ($movements as $m) {
            $codigo = $m['account_codigo'];
            $account = $accounts[$codigo] ?? null;
            $antD = (float) $m['ant_d'];
            $antC = (float) $m['ant_c'];
            $movD = (float) $m['mov_d'];
            $movC = (float) $m['mov_c'];
            $net = round(($antD + $movD) - ($antC + $movC), 2);
            $salD = $net > 0 ? $net : 0.0;
            $salC = $net < 0 ? abs($net) : 0.0;

            if ($onlyMovement && abs($antD) < 0.005 && abs($antC) < 0.005 && abs($movD) < 0.005 && abs($movC) < 0.005) {
                continue;
            }

            $rows[] = [
                'codigo' => $codigo,
                'nome' => $account['nome'] ?? $codigo,
                'classe' => (int) ($account['classe'] ?? (int) $codigo[0]),
                'tipo' => $account['tipo'] ?? '',
                'natureza' => $account['natureza'] ?? '',
                'ant_d' => $antD,
                'ant_c' => $antC,
                'mov_d' => $movD,
                'mov_c' => $movC,
                'sal_d' => $salD,
                'sal_c' => $salC,
            ];

            $totals['ant_d'] += $antD;
            $totals['ant_c'] += $antC;
            $totals['mov_d'] += $movD;
            $totals['mov_c'] += $movC;
            $totals['sal_d'] += $salD;
            $totals['sal_c'] += $salC;
        }

        foreach ($totals as $k => $v) {
            $totals[$k] = round($v, 2);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['classe']][] = $row;
        }
        ksort($grouped);

        return [
            'year' => $year,
            'from' => $fromMonth,
            'to' => $toMonth,
            'rows' => $rows,
            'grouped' => $grouped,
            'totals' => $totals,
            'balanced' => abs($totals['sal_d'] - $totals['sal_c']) < 0.05
                && abs($totals['mov_d'] - $totals['mov_c']) < 0.05,
        ];
    }

    public function monthlySeries(int $year): array
    {
        $rows = Database::run(
            'SELECT e.mes,
                    ROUND(SUM(CASE WHEN a.tipo = \'rendimento\' THEN l.credito - l.debito ELSE 0 END), 2) AS rendimentos,
                    ROUND(SUM(CASE WHEN a.tipo IN (\'gasto\', \'resultado\') THEN l.debito - l.credito ELSE 0 END), 2) AS gastos
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.entry_id
             JOIN accounts a ON a.codigo = l.account_codigo
             WHERE e.ano = ?
             GROUP BY e.mes
             ORDER BY e.mes',
            [$year]
        )->fetchAll();

        $series = [];
        foreach (range(1, 12) as $month) {
            $series[$month] = ['mes' => $month, 'rendimentos' => 0.0, 'gastos' => 0.0];
        }
        foreach ($rows as $row) {
            $series[(int) $row['mes']] = [
                'mes' => (int) $row['mes'],
                'rendimentos' => (float) $row['rendimentos'],
                'gastos' => (float) $row['gastos'],
            ];
        }

        return array_values($series);
    }

    public function byArea(int $year): array
    {
        return Database::run(
            'SELECT a.id, a.codigo, a.nome,
                    ROUND(SUM(CASE WHEN acc.tipo = \'rendimento\' THEN l.credito - l.debito ELSE 0 END), 2) AS rendimentos,
                    ROUND(SUM(CASE WHEN acc.tipo IN (\'gasto\', \'resultado\') AND acc.codigo NOT IN (\'812\') THEN l.debito - l.credito ELSE 0 END), 2) AS gastos
             FROM service_areas a
             LEFT JOIN journal_entries e ON e.area_id = a.id AND e.ano = :year
             LEFT JOIN journal_lines l ON l.entry_id = e.id
             LEFT JOIN accounts acc ON acc.codigo = l.account_codigo
             GROUP BY a.id
             ORDER BY a.codigo',
            [':year' => $year]
        )->fetchAll();
    }

    public function byProduct(int $year): array
    {
        return Database::run(
            'SELECT p.id, p.codigo, p.nome, p.margem_percent, sa.nome AS area_nome,
                    ROUND(SUM(CASE WHEN acc.tipo = \'rendimento\' THEN l.credito - l.debito ELSE 0 END), 2) AS rendimentos,
                    ROUND(SUM(CASE WHEN acc.codigo = \'624\' THEN l.debito - l.credito ELSE 0 END), 2) AS custos_directos
             FROM products p
             JOIN service_areas sa ON sa.id = p.area_id
             LEFT JOIN journal_entries e ON e.product_id = p.id AND e.ano = :year
             LEFT JOIN journal_lines l ON l.entry_id = e.id
             LEFT JOIN accounts acc ON acc.codigo = l.account_codigo
             GROUP BY p.id
             ORDER BY rendimentos DESC',
            [':year' => $year]
        )->fetchAll();
    }
}
