<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Models\Company;
use App\Models\Journal;
use App\Support\View;

final class LedgerController
{
    public function index(): void
    {
        $company = Company::get();
        $year = (int) ($_GET['ano'] ?? $company['exercicio'] ?? date('Y'));
        $codigo = trim((string) ($_GET['conta'] ?? '72'));
        $accounts = Account::all();
        $account = Account::map()[$codigo] ?? null;
        $lines = $company && $account ? Journal::ledger($codigo, $year) : [];

        $running = 0.0;
        $nature = $account['natureza'] ?? 'devedora';
        $prepared = [];
        foreach ($lines as $line) {
            $running += (float) $line['debito'] - (float) $line['credito'];
            $prepared[] = $line + ['saldo' => $running];
        }

        View::render('razao/index', [
            'title' => 'Razão',
            'company' => $company,
            'year' => $year,
            'codigo' => $codigo,
            'account' => $account,
            'accounts' => $accounts,
            'lines' => $prepared,
            'saldo' => $running,
            'nature' => $nature,
        ]);
    }
}
