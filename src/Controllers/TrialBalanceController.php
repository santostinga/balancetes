<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Services\TrialBalanceService;
use App\Support\View;

final class TrialBalanceController
{
    public function index(): void
    {
        $company = Company::get();
        $year = (int) ($_GET['ano'] ?? $company['exercicio'] ?? date('Y'));
        $from = (int) ($_GET['de'] ?? 1);
        $to = (int) ($_GET['ate'] ?? 12);
        $only = !isset($_GET['todas']);
        $trial = $company ? (new TrialBalanceService())->build($year, $from, $to, $only) : null;

        if (isset($_GET['export']) && $trial) {
            $this->csv($trial, $company);
            return;
        }

        View::render('balancete/index', [
            'title' => 'Balancete',
            'company' => $company,
            'trial' => $trial,
            'year' => $year,
            'from' => $from,
            'to' => $to,
            'only' => $only,
        ]);
    }

    private function csv(array $trial, array $company): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="balancete-' . $trial['year'] . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Empresa', $company['nome'], 'Exercício', $trial['year']], ';');
        fputcsv($out, ['Conta', 'Descrição', 'S.Ant.Débito', 'S.Ant.Crédito', 'Débito', 'Crédito', 'Saldo Débito', 'Saldo Crédito'], ';');
        foreach ($trial['rows'] as $row) {
            fputcsv($out, [
                $row['codigo'], $row['nome'],
                $row['ant_d'], $row['ant_c'], $row['mov_d'], $row['mov_c'], $row['sal_d'], $row['sal_c'],
            ], ';');
        }
        fputcsv($out, ['TOTAIS', '', $trial['totals']['ant_d'], $trial['totals']['ant_c'], $trial['totals']['mov_d'], $trial['totals']['mov_c'], $trial['totals']['sal_d'], $trial['totals']['sal_c']], ';');
        fclose($out);
        exit;
    }
}
