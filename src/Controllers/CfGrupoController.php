<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CfGrupoBalancete;
use App\Services\PdfService;
use App\Support\View;

final class CfGrupoController
{
    public function index(): void
    {
        View::render('cfgrupo/index', ['title' => 'CF GRUPO SA'], '');
    }

    public function ver(): void
    {
        $gen = new CfGrupoBalancete();
        echo $gen->documentHtml(false);
        exit;
    }

    public function pdf(): void
    {
        $gen = new CfGrupoBalancete();
        $html = $gen->documentHtml(true);
        try {
            (new PdfService())->download($html, 'balancete-cf-grupo-2024-2025.pdf', [
                'orientation' => 'Portrait',
                'margin_top' => '12mm',
                'margin_right' => '10mm',
                'margin_bottom' => '10mm',
                'margin_left' => '10mm',
                'header_spacing' => '2',
                'footer_spacing' => '0',
                'header_html' => $gen->headerHtml(),
                'footer_html' => '',
            ]);
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
            redirect('/cf-grupo');
        }
    }
}
