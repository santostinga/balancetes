<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Support\Database;

final class SimulationEngine
{
    private \PDO $pdo;
    private int $seq = 0;

    public function generate(array $params): array
    {
        $company = Company::get();
        if (!$company) {
            throw new \RuntimeException('Configure primeiro a empresa.');
        }

        $areas = array_values(array_filter(ServiceArea::all(), fn ($a) => (int) $a['activa'] === 1));
        $products = array_values(array_filter(Product::all(), fn ($p) => (int) $p['activo'] === 1));
        if ($areas === [] || $products === []) {
            throw new \RuntimeException('Defina pelo menos uma área activa e um produto activo.');
        }

        $year = (int) ($params['exercicio'] ?? $company['exercicio']);
        $seed = (int) ($params['seed'] ?? random_int(1000, 999999));
        $variability = max(0, min(0.4, (float) str_replace(',', '.', (string) ($params['variabilidade'] ?? 0.12))));
        $includeVat = !empty($params['incluir_iva']);
        $collection = max(0.4, min(1, (float) str_replace(',', '.', (string) ($params['taxa_recebimento'] ?? 0.82))));
        $payment = max(0.4, min(1, (float) str_replace(',', '.', (string) ($params['taxa_pagamento'] ?? 0.75))));
        $vatRate = $includeVat ? (float) $company['iva_taxa'] : 0.0;
        $ircRate = (float) $company['irc_taxa'];
        $capital = (float) $company['capital_social'];

        $this->pdo = Database::pdo();
        $this->seq = 0;
        mt_srand($seed);

        $this->pdo->beginTransaction();
        try {
            $this->clearYear($year);
            $totals = [
                'rendimentos' => 0.0,
                'gastos' => 0.0,
                'iva_liquidado' => 0.0,
                'iva_dedutivel' => 0.0,
                'lancamentos' => 0,
            ];

            $equipment = round($capital * 0.28, 2);
            $bank = round($capital - $equipment, 2);
            $this->post($year, 1, sprintf('%d-01-02', $year), 'Abertura do exercício — capital social', 'abertura', null, null, [
                ['12', $bank, 0, 'Depósito do capital'],
                ['43', $equipment, 0, 'Equipamento inicial'],
                ['51', 0, $capital, 'Capital realizado'],
            ]);
            $totals['lancamentos']++;

            foreach (range(1, 12) as $month) {
                $date = sprintf('%d-%02d-%02d', $year, $month, min(28, 6 + ($month % 5)));

                foreach ($products as $product) {
                    $season = json_decode((string) $product['sazonalidade'], true) ?: Product::defaultSeasonality();
                    $factor = (float) ($season[$month - 1] ?? 1);
                    $volume = $this->randInt((int) $product['volume_mensal_min'], (int) $product['volume_mensal_max']);
                    $volume = max(0, (int) round($volume * $factor * $this->jitter($variability)));
                    if ($volume === 0) {
                        continue;
                    }

                    $price = $this->randFloat((float) $product['preco_min'], (float) $product['preco_max']);
                    $price *= $this->jitter($variability * 0.5);
                    $revenue = round($volume * $price, 2);
                    $unitCost = (float) $product['custo_unitario'];
                    $cogs = round($volume * $unitCost * $this->jitter($variability * 0.35), 2);
                    $vatOut = round($revenue * $vatRate, 2);
                    $receivable = round($revenue + $vatOut, 2);

                    $desc = sprintf('Prestação %s · %s · %s', $product['codigo'], $product['nome'], month_name($month));
                    $saleLines = [
                        ['21', $receivable, 0, 'Clientes'],
                        ['72', 0, $revenue, 'Rendimento'],
                    ];
                    if ($vatOut > 0) {
                        $saleLines[] = ['2433', 0, $vatOut, 'IVA liquidado'];
                    }
                    $this->post($year, $month, $date, $desc, 'simulacao', (int) $product['area_id'], (int) $product['id'], $saleLines, $product['codigo']);
                    $totals['rendimentos'] += $revenue;
                    $totals['iva_liquidado'] += $vatOut;
                    $totals['lancamentos']++;

                    $collected = round($receivable * $collection, 2);
                    if ($collected > 0) {
                        $this->post($year, $month, $this->shiftDate($date, 9), 'Recebimento clientes · ' . $product['codigo'], 'simulacao', (int) $product['area_id'], (int) $product['id'], [
                            ['12', $collected, 0, 'Transferência bancária'],
                            ['21', 0, $collected, 'Liquidação de clientes'],
                        ], 'RB-' . $product['codigo']);
                        $totals['lancamentos']++;
                    }

                    $vatIn = round($cogs * $vatRate, 2);
                    $payable = round($cogs + $vatIn, 2);
                    $costLines = [
                        ['624', $cogs, 0, 'Custo directo do serviço'],
                        ['22', 0, $payable, 'Fornecedores'],
                    ];
                    if ($vatIn > 0) {
                        array_splice($costLines, 1, 0, [['2432', $vatIn, 0, 'IVA dedutível']]);
                    }
                    $this->post($year, $month, $this->shiftDate($date, 2), 'Custos directos · ' . $product['codigo'], 'simulacao', (int) $product['area_id'], (int) $product['id'], $costLines, 'FT-' . $product['codigo']);
                    $totals['gastos'] += $cogs;
                    $totals['iva_dedutivel'] += $vatIn;
                    $totals['lancamentos']++;

                    $paid = round($payable * $payment, 2);
                    if ($paid > 0) {
                        $this->post($year, $month, $this->shiftDate($date, 14), 'Pagamento fornecedores · ' . $product['codigo'], 'simulacao', (int) $product['area_id'], (int) $product['id'], [
                            ['22', $paid, 0, 'Liquidação de fornecedores'],
                            ['12', 0, $paid, 'Transferência bancária'],
                        ]);
                        $totals['lancamentos']++;
                    }
                }

                foreach ($areas as $area) {
                    $payroll = round((float) $area['custo_pessoal_mensal'] * $this->jitter($variability * 0.25), 2);
                    if ($payroll > 0) {
                        $ss = round($payroll * 0.2375, 2);
                        $this->post($year, $month, sprintf('%d-%02d-25', $year, $month), 'Processamento de salários · ' . $area['codigo'], 'simulacao', (int) $area['id'], null, [
                            ['632', $payroll, 0, 'Remunerações brutas'],
                            ['635', $ss, 0, 'Encargos da entidade (TSU)'],
                            ['12', 0, $payroll, 'Pagamento líquido simplificado'],
                            ['238', 0, $ss, 'Segurança Social a pagar'],
                        ], 'RH-' . $area['codigo']);
                        $totals['gastos'] += $payroll + $ss;
                        $totals['lancamentos']++;

                        $this->post($year, $month, sprintf('%d-%02d-28', $year, $month), 'Pagamento encargos sociais · ' . $area['codigo'], 'simulacao', (int) $area['id'], null, [
                            ['238', $ss, 0, 'Liquidação SS'],
                            ['12', 0, $ss, 'Transferência bancária'],
                        ]);
                        $totals['lancamentos']++;
                    }

                    $fixed = round((float) $area['custo_fixo_mensal'] * $this->jitter($variability * 0.2), 2);
                    if ($fixed > 0) {
                        $splitRent = round($fixed * 0.55, 2);
                        $splitEnergy = round($fixed * 0.18, 2);
                        $splitComms = round($fixed * 0.12, 2);
                        $splitOther = round($fixed - $splitRent - $splitEnergy - $splitComms, 2);
                        $vatOh = round($fixed * $vatRate, 2);
                        $ohLines = [
                            ['622', $splitRent, 0, 'Renda imputada'],
                            ['621', $splitEnergy, 0, 'Electricidade'],
                            ['623', $splitComms, 0, 'Comunicações'],
                            ['68', $splitOther, 0, 'Outros gastos da área'],
                            ['12', 0, round($fixed + $vatOh, 2), 'Pagamento overhead'],
                        ];
                        if ($vatOh > 0) {
                            array_splice($ohLines, 4, 0, [['2432', $vatOh, 0, 'IVA dedutível overhead']]);
                        }
                        $this->post($year, $month, sprintf('%d-%02d-08', $year, $month), 'Gastos de estrutura · ' . $area['codigo'], 'simulacao', (int) $area['id'], null, $ohLines);
                        $totals['gastos'] += $fixed;
                        $totals['iva_dedutivel'] += $vatOh;
                        $totals['lancamentos']++;
                    }
                }

                $travel = round(420 * $this->jitter($variability + 0.15) * (in_array($month, [7, 8], true) ? 0.4 : 1), 2);
                $ads = round(680 * $this->jitter($variability) * (in_array($month, [10, 11], true) ? 1.6 : 0.9), 2);
                $this->post($year, $month, sprintf('%d-%02d-18', $year, $month), 'Deslocações e comunicação comercial', 'simulacao', null, null, [
                    ['626', $travel, 0, 'Deslocações'],
                    ['627', $ads, 0, 'Publicidade'],
                    ['12', 0, round($travel + $ads, 2), 'Pagamento'],
                ]);
                $totals['gastos'] += $travel + $ads;
                $totals['lancamentos']++;

                if ($includeVat) {
                    $monthVat = $this->monthVat($year, $month);
                    $net = round($monthVat['liquidado'] - $monthVat['dedutivel'], 2);
                    if (abs($net) >= 0.01) {
                        if ($net > 0) {
                            $this->post($year, $month, sprintf('%d-%02d-27', $year, $month), 'Apuramento de IVA · ' . month_name($month), 'simulacao', null, null, [
                                ['2433', $monthVat['liquidado'], 0, 'Estorno liquidado'],
                                ['2432', 0, $monthVat['dedutivel'], 'Estorno dedutível'],
                                ['2431', 0, $net, 'IVA a entregar'],
                            ]);
                            $pay = round($net * 0.92, 2);
                            if ($pay > 0) {
                                $this->post($year, $month, sprintf('%d-%02d-28', $year, $month), 'Pagamento IVA · ' . month_name($month), 'simulacao', null, null, [
                                    ['2431', $pay, 0, 'Pagamento AT'],
                                    ['12', 0, $pay, 'Transferência bancária'],
                                ]);
                                $totals['lancamentos']++;
                            }
                        } else {
                            $this->post($year, $month, sprintf('%d-%02d-27', $year, $month), 'Apuramento de IVA · ' . month_name($month), 'simulacao', null, null, [
                                ['2433', $monthVat['liquidado'], 0, 'Estorno liquidado'],
                                ['2431', abs($net), 0, 'IVA a recuperar'],
                                ['2432', 0, $monthVat['dedutivel'], 'Estorno dedutível'],
                            ]);
                        }
                        $totals['lancamentos']++;
                    }
                }
            }

            $depreciation = round($equipment / 5, 2);
            $this->post($year, 12, sprintf('%d-12-31', $year), 'Depreciação anual do equipamento básico', 'apuramento', null, null, [
                ['64', $depreciation, 0, 'Quota de depreciação (5 anos)'],
                ['438', 0, $depreciation, 'Depreciações acumuladas'],
            ]);
            $totals['gastos'] += $depreciation;
            $totals['lancamentos']++;

            $profitBeforeTax = round($totals['rendimentos'] - $totals['gastos'], 2);
            $tax = $profitBeforeTax > 0 ? round($profitBeforeTax * $ircRate, 2) : 0.0;
            if ($tax > 0) {
                $this->post($year, 12, sprintf('%d-12-31', $year), 'Estimativa de IRC do exercício', 'apuramento', null, null, [
                    ['812', $tax, 0, 'Imposto sobre o rendimento'],
                    ['2411', 0, $tax, 'IRC a pagar'],
                ]);
                $totals['gastos'] += $tax;
                $totals['lancamentos']++;
            }

            $net = round($totals['rendimentos'] - $totals['gastos'], 2);
            $resumo = json_encode([
                'rendimentos' => round($totals['rendimentos'], 2),
                'gastos' => round($totals['gastos'], 2),
                'resultado_liquido' => $net,
                'iva_liquidado' => round($totals['iva_liquidado'], 2),
                'iva_dedutivel' => round($totals['iva_dedutivel'], 2),
                'lancamentos' => $totals['lancamentos'],
            ], JSON_UNESCAPED_UNICODE);

            Database::run(
                'INSERT INTO simulation_runs (exercicio, seed, variabilidade, incluir_iva, taxa_recebimento, taxa_pagamento, created_at, resumo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$year, $seed, $variability, $includeVat ? 1 : 0, $collection, $payment, date('c'), $resumo]
            );

            $this->assertBalanced();
            $this->pdo->commit();

            return json_decode((string) $resumo, true) + ['seed' => $seed, 'exercicio' => $year];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function loadDemo(): void
    {
        $this->pdo = Database::pdo();
        $this->pdo->beginTransaction();
        try {
            Database::run('DELETE FROM journal_lines');
            Database::run('DELETE FROM journal_entries');
            Database::run('DELETE FROM simulation_runs');
            Database::run('DELETE FROM products');
            Database::run('DELETE FROM service_areas');
            Database::run('DELETE FROM company');

            Company::save([
                'nome' => 'NorteAtlântico Serviços, Lda',
                'nif' => '516842901',
                'morada' => 'Rua das Flores, 120, 2º',
                'cidade' => 'Porto',
                'pais' => 'Portugal',
                'moeda' => 'EUR',
                'exercicio' => 2025,
                'capital_social' => 75000,
                'iva_taxa' => 0.23,
                'irc_taxa' => 0.21,
            ]);

            $areas = [
                ['CONS', 'Consultoria de Gestão', 'Diagnóstico, auditoria de processos e estudos estratégicos.', 900, 6200],
                ['DEV', 'Desenvolvimento de Software', 'Projectos web, aplicações à medida e integrações.', 1400, 10800],
                ['FORM', 'Formação Profissional', 'Acções de formação interna e cursos certificados.', 700, 3600],
                ['SUP', 'Suporte e Manutenção', 'Contratos SLA e intervenção avulsa.', 500, 4200],
            ];
            $areaIds = [];
            foreach ($areas as $area) {
                $areaIds[$area[0]] = ServiceArea::create([
                    'codigo' => $area[0],
                    'nome' => $area[1],
                    'descricao' => $area[2],
                    'custo_fixo_mensal' => $area[3],
                    'custo_pessoal_mensal' => $area[4],
                    'activa' => 1,
                ]);
            }

            $products = [
                ['CONS', 'CONS-AUD', 'Auditoria de processos', 1800, 2500, 900, 2, 5],
                ['CONS', 'CONS-EST', 'Estudo estratégico', 3500, 6000, 1800, 1, 3],
                ['DEV', 'DEV-WEB', 'Projecto web', 4000, 9000, 2500, 1, 4],
                ['DEV', 'DEV-APP', 'Aplicação à medida', 8000, 18000, 5500, 0, 2],
                ['FORM', 'FORM-INT', 'Acção de formação interna', 800, 1400, 350, 3, 8],
                ['FORM', 'FORM-CERT', 'Curso certificado (por formando)', 250, 400, 90, 10, 25],
                ['SUP', 'SUP-SLA', 'Contrato SLA mensal', 450, 900, 180, 8, 15],
                ['SUP', 'SUP-INC', 'Incidente avulso', 90, 180, 40, 12, 40],
            ];
            foreach ($products as $p) {
                Product::create([
                    'area_id' => $areaIds[$p[0]],
                    'codigo' => $p[1],
                    'nome' => $p[2],
                    'tipo' => 'servico',
                    'preco_min' => $p[3],
                    'preco_max' => $p[4],
                    'custo_unitario' => $p[5],
                    'volume_mensal_min' => $p[6],
                    'volume_mensal_max' => $p[7],
                    'sazonalidade' => json_encode(Product::defaultSeasonality()),
                    'activo' => 1,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function clearYear(int $year): void
    {
        Database::run(
            'DELETE FROM journal_lines WHERE entry_id IN (SELECT id FROM journal_entries WHERE ano = ?)',
            [$year]
        );
        Database::run('DELETE FROM journal_entries WHERE ano = ?', [$year]);
        Database::run('DELETE FROM simulation_runs WHERE exercicio = ?', [$year]);
    }

    private function post(
        int $year,
        int $month,
        string $date,
        string $description,
        string $origin,
        ?int $areaId,
        ?int $productId,
        array $lines,
        ?string $document = null
    ): void {
        $debit = 0.0;
        $credit = 0.0;
        $clean = [];
        foreach ($lines as $line) {
            $d = round((float) $line[1], 2);
            $c = round((float) $line[2], 2);
            if ($d < 0.005 && $c < 0.005) {
                continue;
            }
            $debit += $d;
            $credit += $c;
            $clean[] = $line;
        }
        $debit = round($debit, 2);
        $credit = round($credit, 2);
        if (abs($debit - $credit) >= 0.02) {
            $diff = round($debit - $credit, 2);
            $last = count($clean) - 1;
            if ($diff > 0) {
                $clean[$last][2] = round((float) $clean[$last][2] + $diff, 2);
                $credit = round($credit + $diff, 2);
            } else {
                $clean[$last][1] = round((float) $clean[$last][1] + abs($diff), 2);
                $debit = round($debit + abs($diff), 2);
            }
        }
        if ($clean === [] || abs($debit - $credit) >= 0.02) {
            throw new \RuntimeException('Lançamento desequilibrado: ' . $description);
        }

        $this->seq++;
        $numero = sprintf('L%s%02d%04d', $year, $month, $this->seq);
        Database::run(
            'INSERT INTO journal_entries (data, numero, descricao, origem, mes, ano, area_id, product_id, documento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$date, $numero, $description, $origin, $month, $year, $areaId, $productId, $document]
        );
        $id = (int) $this->pdo->lastInsertId();
        $stmt = $this->pdo->prepare(
            'INSERT INTO journal_lines (entry_id, account_codigo, debito, credito, descricao) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($clean as $line) {
            $stmt->execute([$id, $line[0], round((float) $line[1], 2), round((float) $line[2], 2), $line[3] ?? null]);
        }
    }

    private function monthVat(int $year, int $month): array
    {
        $row = Database::run(
            'SELECT
                ROUND(SUM(CASE WHEN l.account_codigo = \'2433\' THEN l.credito - l.debito ELSE 0 END), 2) AS liquidado,
                ROUND(SUM(CASE WHEN l.account_codigo = \'2432\' THEN l.debito - l.credito ELSE 0 END), 2) AS dedutivel
             FROM journal_lines l
             JOIN journal_entries e ON e.id = l.entry_id
             WHERE e.ano = ? AND e.mes = ? AND e.origem != \'apuramento\'',
            [$year, $month]
        )->fetch();

        return [
            'liquidado' => (float) ($row['liquidado'] ?? 0),
            'dedutivel' => (float) ($row['dedutivel'] ?? 0),
        ];
    }

    private function assertBalanced(): void
    {
        $row = Database::run('SELECT ROUND(SUM(debito), 2) d, ROUND(SUM(credito), 2) c FROM journal_lines')->fetch();
        if (abs((float) $row['d'] - (float) $row['c']) >= 0.05) {
            throw new \RuntimeException('O diário não equilibra (D ' . $row['d'] . ' / C ' . $row['c'] . ').');
        }
    }

    private function jitter(float $variability): float
    {
        if ($variability <= 0) {
            return 1.0;
        }
        $delta = (mt_rand(0, 10000) / 10000) * $variability * 2 - $variability;

        return max(0.55, 1 + $delta);
    }

    private function randInt(int $min, int $max): int
    {
        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        return mt_rand($min, $max);
    }

    private function randFloat(float $min, float $max): float
    {
        if ($max < $min) {
            [$min, $max] = [$max, $min];
        }

        return $min + (mt_rand(0, 10000) / 10000) * ($max - $min);
    }

    private function shiftDate(string $date, int $days): string
    {
        return date('Y-m-d', strtotime($date . ' +' . $days . ' days'));
    }
}
