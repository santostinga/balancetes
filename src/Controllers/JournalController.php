<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Journal;
use App\Models\ServiceArea;
use App\Support\View;

final class JournalController
{
    public function index(): void
    {
        $company = Company::get();
        $year = (int) ($_GET['ano'] ?? $company['exercicio'] ?? date('Y'));
        $month = isset($_GET['mes']) && $_GET['mes'] !== '' ? (int) $_GET['mes'] : null;
        $areaId = isset($_GET['area']) && $_GET['area'] !== '' ? (int) $_GET['area'] : null;
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $perPage = 40;
        $total = $company ? Journal::countFiltered($year, $month, $areaId) : 0;
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $entries = $company ? Journal::entries($year, $month, $areaId, $perPage, ($page - 1) * $perPage) : [];
        $withLines = [];
        foreach ($entries as $entry) {
            $entry['lines'] = Journal::linesFor((int) $entry['id']);
            $withLines[] = $entry;
        }

        View::render('diario/index', [
            'title' => 'Diário',
            'company' => $company,
            'year' => $year,
            'month' => $month,
            'areaId' => $areaId,
            'areas' => ServiceArea::all(),
            'entries' => $withLines,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }
}
