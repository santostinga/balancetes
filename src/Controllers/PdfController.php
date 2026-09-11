<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Models\ServiceArea;
use App\Services\AnnualReportService;
use App\Services\PdfService;
use App\Services\TrialBalanceService;
use App\Support\View;

final class PdfController
{
    public function balancete(): void
    {
        $company = $this->requireCompany();
        $year = (int) ($_GET['ano'] ?? $company['exercicio']);
        $from = (int) ($_GET['de'] ?? 1);
        $to = (int) ($_GET['ate'] ?? 12);
        $only = !isset($_GET['todas']);
        $trial = (new TrialBalanceService())->build($year, $from, $to, $only);
        if (!$trial['rows']) {
            flash('erro', 'Não há movimentos para gerar o PDF do balancete.');
            redirect('/balancete');
        }

        $html = View::toString('pdf/balancete', [
            'title' => 'Balancete de verificação',
            'company' => $company,
            'trial' => $trial,
            'year' => $year,
            'from' => $from,
            'to' => $to,
        ]);

        $period = sprintf('%02d-%02d-%d', $from, $to, $year);
        $this->emit($html, 'balancete-' . $period . '.pdf', [
            'orientation' => 'Landscape',
            'title' => $company['nome'],
            'subtitle' => 'Balancete ' . $year,
        ], '/balancete');
    }

    public function relatorio(): void
    {
        $company = $this->requireCompany();
        $year = (int) ($_GET['ano'] ?? $company['exercicio']);
        if (Journal::countYear($year) === 0) {
            flash('erro', 'Não há simulação para gerar o relatório em PDF.');
            redirect('/relatorio-anual');
        }

        $tb = new TrialBalanceService();
        $trial = $tb->build($year, 1, 12, true);
        $report = (new AnnualReportService())->build(
            $trial,
            $tb->byArea($year),
            $tb->byProduct($year),
            $tb->monthlySeries($year)
        );

        $html = View::toString('pdf/relatorio', [
            'title' => 'Relatório anual',
            'company' => $company,
            'year' => $year,
            'report' => $report,
            'trial' => $trial,
        ]);

        $this->emit($html, 'relatorio-anual-' . $year . '.pdf', [
            'orientation' => 'Portrait',
            'title' => $company['nome'],
            'subtitle' => 'Relatório anual ' . $year,
        ], '/relatorio-anual');
    }

    public function razao(): void
    {
        $company = $this->requireCompany();
        $year = (int) ($_GET['ano'] ?? $company['exercicio']);
        $codigo = trim((string) ($_GET['conta'] ?? '72'));
        $account = Account::map()[$codigo] ?? null;
        if (!$account) {
            flash('erro', 'Conta inexistente.');
            redirect('/razao');
        }

        $lines = Journal::ledger($codigo, $year);
        $running = 0.0;
        $prepared = [];
        foreach ($lines as $line) {
            $running += (float) $line['debito'] - (float) $line['credito'];
            $prepared[] = $line + ['saldo' => $running];
        }

        $html = View::toString('pdf/razao', [
            'title' => 'Razão',
            'company' => $company,
            'year' => $year,
            'codigo' => $codigo,
            'account' => $account,
            'lines' => $prepared,
            'saldo' => $running,
        ]);

        $this->emit($html, 'razao-' . preg_replace('/[^0-9A-Za-z-]/', '-', $codigo) . '-' . $year . '.pdf', [
            'orientation' => 'Portrait',
            'title' => $company['nome'],
            'subtitle' => 'Razão ' . $codigo,
        ], '/razao?conta=' . urlencode($codigo) . '&ano=' . $year);
    }

    public function diario(): void
    {
        $company = $this->requireCompany();
        $year = (int) ($_GET['ano'] ?? $company['exercicio']);
        $month = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int) $_GET['mes'] : null;
        $areaId = isset($_GET['area']) && $_GET['area'] !== '' ? (int) $_GET['area'] : null;
        $entries = Journal::entries($year, $month, $areaId, 4000, 0);
        $withLines = [];
        foreach ($entries as $entry) {
            $entry['lines'] = Journal::linesFor((int) $entry['id']);
            $withLines[] = $entry;
        }
        if ($withLines === []) {
            flash('erro', 'Não há lançamentos para gerar o PDF do diário.');
            redirect('/diario');
        }

        $area = $areaId ? ServiceArea::find($areaId) : null;
        $html = View::toString('pdf/diario', [
            'title' => 'Diário',
            'company' => $company,
            'year' => $year,
            'month' => $month,
            'area' => $area,
            'entries' => $withLines,
        ]);

        $suffix = $month ? sprintf('%d-%02d', $year, $month) : (string) $year;
        $this->emit($html, 'diario-' . $suffix . '.pdf', [
            'orientation' => 'Portrait',
            'title' => $company['nome'],
            'subtitle' => 'Diário ' . $year,
        ], '/diario');
    }

    private function requireCompany(): array
    {
        $company = Company::get();
        if (!$company) {
            flash('erro', 'Configure a empresa antes de gerar PDF.');
            redirect('/empresa');
        }

        return $company;
    }

    private function emit(string $html, string $filename, array $options, string $fallback): never
    {
        try {
            $this->pdf()->download($html, $filename, $options);
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
            redirect($fallback);
        }
    }

    private function pdf(): PdfService
    {
        return new PdfService();
    }
}
