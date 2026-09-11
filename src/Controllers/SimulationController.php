<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Models\Journal;
use App\Services\SimulationEngine;
use App\Support\View;

final class SimulationController
{
    public function index(): void
    {
        $company = Company::get();
        View::render('simulacao/index', [
            'title' => 'Simulação',
            'company' => $company,
            'run' => Journal::lastRun(),
            'entries' => $company ? Journal::countYear((int) $company['exercicio']) : 0,
        ]);
    }

    public function generate(): void
    {
        verify_csrf();
        try {
            $result = (new SimulationEngine())->generate($_POST);
            flash('ok', sprintf(
                'Exercício %d gerado: %d lançamentos · RL %s (seed %s).',
                $result['exercicio'],
                $result['lancamentos'],
                money((float) $result['resultado_liquido']),
                $result['seed']
            ));
            redirect('/balancete');
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
            redirect('/simulacao');
        }
    }

    public function demo(): void
    {
        verify_csrf();
        try {
            $engine = new SimulationEngine();
            $engine->loadDemo();
            $result = $engine->generate([
                'exercicio' => 2025,
                'seed' => 2025,
                'variabilidade' => 0.12,
                'incluir_iva' => '1',
                'taxa_recebimento' => 0.82,
                'taxa_pagamento' => 0.75,
            ]);
            flash('ok', 'Empresa demonstração carregada e exercício 2025 simulado (seed 2025). Resultado líquido: ' . money((float) $result['resultado_liquido']));
            redirect('/');
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
            redirect('/simulacao');
        }
    }
}
