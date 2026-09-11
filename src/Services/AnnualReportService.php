<?php

declare(strict_types=1);

namespace App\Services;

final class AnnualReportService
{
    public function build(array $trial, array $byArea, array $byProduct, array $monthly): array
    {
        $sumTipo = function (array $rows, string $tipo, string $side) {
            $total = 0.0;
            foreach ($rows as $row) {
                if ($row['tipo'] === $tipo) {
                    $total += (float) $row[$side];
                }
            }
            return round($total, 2);
        };

        $account = function (array $rows, string $codigo) {
            foreach ($rows as $row) {
                if ($row['codigo'] === $codigo) {
                    return $row;
                }
            }
            return ['sal_d' => 0.0, 'sal_c' => 0.0, 'mov_d' => 0.0, 'mov_c' => 0.0];
        };

        $rows = $trial['rows'];
        $rendimentos = $sumTipo($rows, 'rendimento', 'sal_c') - $sumTipo($rows, 'rendimento', 'sal_d');
        $gastosOp = 0.0;
        $irc = 0.0;
        foreach ($rows as $row) {
            if ($row['tipo'] === 'gasto') {
                $gastosOp += $row['sal_d'] - $row['sal_c'];
            }
            if ($row['codigo'] === '812') {
                $irc += $row['sal_d'] - $row['sal_c'];
            }
        }
        $gastosOp = round($gastosOp, 2);
        $irc = round($irc, 2);
        $ebitda = round($rendimentos - $gastosOp + ($account($rows, '64')['sal_d'] ?? 0), 2);
        $rai = round($rendimentos - $gastosOp, 2);
        $rl = round($rai - $irc, 2);

        $activoCorrente = $this->balance($rows, ['11', '12', '21', '2432']);
        $activoNaoCorrente = round(
            ($account($rows, '43')['sal_d'] ?? 0) - ($account($rows, '438')['sal_c'] ?? 0),
            2
        );
        $passivoCorrente = $this->creditBalances($rows, ['22', '231', '238', '2411', '2431', '2433']);
        $capital = $account($rows, '51')['sal_c'] ?? 0.0;
        $capitalProprio = round($capital + $rl, 2);

        $areas = [];
        foreach ($byArea as $area) {
            $rev = (float) $area['rendimentos'];
            $cost = (float) $area['gastos'];
            $areas[] = $area + [
                'contribuicao' => round($rev - $cost, 2),
                'margem' => $rev > 0 ? round((($rev - $cost) / $rev) * 100, 1) : 0.0,
            ];
        }

        $products = [];
        foreach ($byProduct as $product) {
            $rev = (float) $product['rendimentos'];
            $cogs = (float) $product['custos_directos'];
            $products[] = $product + [
                'margem_real' => $rev > 0 ? round((($rev - $cogs) / $rev) * 100, 1) : 0.0,
                'contribuicao' => round($rev - $cogs, 2),
            ];
        }

        return [
            'dr' => [
                'prestacoes' => $account($rows, '72')['sal_c'] ?? 0,
                'outros_rendimentos' => $account($rows, '78')['sal_c'] ?? 0,
                'trabalhos' => $account($rows, '624')['sal_d'] ?? 0,
                'fse' => $this->debitBalances($rows, ['621', '622', '623', '626', '627']),
                'pessoal' => $this->debitBalances($rows, ['632', '635']),
                'depreciacoes' => $account($rows, '64')['sal_d'] ?? 0,
                'outros_gastos' => $account($rows, '68')['sal_d'] ?? 0,
                'rendimentos' => $rendimentos,
                'gastos' => $gastosOp,
                'ebitda' => $ebitda,
                'rai' => $rai,
                'irc' => $irc,
                'resultado_liquido' => $rl,
            ],
            'balanco' => [
                'caixa_bancos' => $this->balance($rows, ['11', '12']),
                'clientes' => $account($rows, '21')['sal_d'] ?? 0,
                'iva_dedutivel' => $account($rows, '2432')['sal_d'] ?? 0,
                'equipamento' => $account($rows, '43')['sal_d'] ?? 0,
                'depreciacoes' => $account($rows, '438')['sal_c'] ?? 0,
                'activo_corrente' => $activoCorrente,
                'activo_nao_corrente' => $activoNaoCorrente,
                'activo' => round($activoCorrente + $activoNaoCorrente, 2),
                'fornecedores' => $account($rows, '22')['sal_c'] ?? 0,
                'estado' => $this->creditBalances($rows, ['2411', '2431', '2433', '238']),
                'passivo_corrente' => $passivoCorrente,
                'capital' => $capital,
                'resultado_liquido' => $rl,
                'capital_proprio' => $capitalProprio,
                'passivo_capital' => round($passivoCorrente + $capitalProprio, 2),
            ],
            'kpis' => [
                'margem_bruta' => $rendimentos > 0
                    ? round((($rendimentos - ($account($rows, '624')['sal_d'] ?? 0)) / $rendimentos) * 100, 1)
                    : 0,
                'margem_liquida' => $rendimentos > 0 ? round(($rl / $rendimentos) * 100, 1) : 0,
                'ebitda_margin' => $rendimentos > 0 ? round(($ebitda / $rendimentos) * 100, 1) : 0,
                'liquidez' => $passivoCorrente > 0 ? round($activoCorrente / $passivoCorrente, 2) : 0,
                'autonomia' => ($activoCorrente + $activoNaoCorrente) > 0
                    ? round(($capitalProprio / ($activoCorrente + $activoNaoCorrente)) * 100, 1)
                    : 0,
            ],
            'areas' => $areas,
            'produtos' => $products,
            'mensal' => $monthly,
        ];
    }

    private function balance(array $rows, array $codes): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (in_array($row['codigo'], $codes, true)) {
                $total += $row['sal_d'] - $row['sal_c'];
            }
        }

        return round($total, 2);
    }

    private function debitBalances(array $rows, array $codes): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (in_array($row['codigo'], $codes, true)) {
                $total += $row['sal_d'];
            }
        }

        return round($total, 2);
    }

    private function creditBalances(array $rows, array $codes): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (in_array($row['codigo'], $codes, true)) {
                $total += $row['sal_c'];
            }
        }

        return round($total, 2);
    }
}
