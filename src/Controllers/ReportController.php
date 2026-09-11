<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Journal;
use App\Services\AnnualReportService;
use App\Services\TrialBalanceService;
use App\Support\View;

final class ReportController
{
    public function index(): void
    {
        $company = Company::get();
        $year = (int) ($_GET['ano'] ?? $company['exercicio'] ?? date('Y'));
        $report = null;
        $trial = null;
        if ($company && Journal::countYear($year) > 0) {
            $tb = new TrialBalanceService();
            $trial = $tb->build($year, 1, 12, true);
            $report = (new AnnualReportService())->build(
                $trial,
                $tb->byArea($year),
                $tb->byProduct($year),
                $tb->monthlySeries($year)
            );
        }

        View::render('relatorio/index', [
            'title' => 'Relatório anual',
            'company' => $company,
            'year' => $year,
            'report' => $report,
            'trial' => $trial,
        ]);
    }
}
