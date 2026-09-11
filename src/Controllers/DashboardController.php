<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Journal;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Services\AnnualReportService;
use App\Services\TrialBalanceService;
use App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        $company = Company::get();
        $areas = ServiceArea::all();
        $products = Product::all();
        $year = (int) ($company['exercicio'] ?? date('Y'));
        $entries = $company ? Journal::countYear($year) : 0;
        $run = Journal::lastRun();

        $report = null;
        $trial = null;
        if ($entries > 0) {
            $tb = new TrialBalanceService();
            $trial = $tb->build($year);
            $report = (new AnnualReportService())->build(
                $trial,
                $tb->byArea($year),
                $tb->byProduct($year),
                $tb->monthlySeries($year)
            );
        }

        View::render('dashboard', [
            'title' => 'Painel',
            'company' => $company,
            'areas' => $areas,
            'products' => $products,
            'year' => $year,
            'entries' => $entries,
            'run' => $run,
            'report' => $report,
            'trial' => $trial,
        ]);
    }
}
