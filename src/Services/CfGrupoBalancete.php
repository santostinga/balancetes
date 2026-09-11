<?php

declare(strict_types=1);

namespace App\Services;

final class CfGrupoBalancete
{
    public const COMPANY = 'CF GRUPO SA';

    /** @var array<string, array{codigo:string,nome:string,nivel:int,parent:?string,d:int,c:int,children:list<string>}> */
    private array $accounts = [];

    /** @var list<string> */
    private array $order = [];

    public function build(int $year): array
    {
        if ($year !== 2024 && $year !== 2025) {
            throw new \InvalidArgumentException('Exercício inválido.');
        }

        $this->bootChart();
        $this->postAbertura2024();
        $this->postOperacoes2024();

        if ($year === 2025) {
            $profit = $this->resultado();
            $abertura = $this->saldosFolhaBalanco();
            $this->bootChart();
            foreach ($abertura as $codigo => $saldo) {
                if ($saldo > 0) {
                    $this->post($codigo, $saldo, 0);
                } else {
                    $this->post($codigo, 0, -$saldo);
                }
            }
            if ($profit > 0) {
                $this->post('591', 0, $profit);
            } elseif ($profit < 0) {
                $this->post('591', -$profit, 0);
            }
            $this->postOperacoes2025();
        }

        return $this->export($year);
    }

    public function documentHtml(bool $pdfMode = false): string
    {
        $y2024 = $this->build(2024);
        $y2025 = $this->build(2025);
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/cfgrupo-balancete.css');
        $data = [
            'company' => self::COMPANY,
            'sections' => [
                ['year' => 2024, 'rows' => $y2024['rows']],
                ['year' => 2025, 'rows' => $y2025['rows']],
            ],
            'comparativo' => [
                2025 => $this->posicaoFinanceira($y2025),
                2024 => $this->posicaoFinanceira($y2024),
            ],
        ];
        unset($pdfMode);
        ob_start();
        include dirname(__DIR__, 2) . '/views/cfgrupo/documento.php';
        return (string) ob_get_clean();
    }

    public function headerHtml(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script>
function subst() {
    var vars = {};
    document.location.search.substring(1).split('&').forEach(function (pair) {
        var parts = pair.split('=', 2);
        vars[parts[0]] = decodeURIComponent(parts[1] || '');
    });
    var page = document.getElementById('page');
    var topage = document.getElementById('topage');
    if (page) page.textContent = vars.page || '';
    if (topage) topage.textContent = vars.topage || '';
}
</script>
<style type="text/css">
body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #444444; }
table { width: 100%; border-collapse: collapse; }
td { font-size: 8px; vertical-align: top; }
.page-number { text-align: right; white-space: nowrap; }
</style>
</head>
<body onload="subst()">
<table>
<tr>
    <td>CF GRUPO SA</td>
    <td class="page-number">Pág. <span id="page"></span>/<span id="topage"></span></td>
</tr>
</table>
</body>
</html>
HTML;
    }

    public function footerHtml(): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body></body></html>';
    }

    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign . number_format(abs($cents) / 100, 2, ',', ' ');
    }

    private function bootChart(): void
    {
        $this->accounts = [];
        $this->order = [];
        foreach ($this->chart() as [$codigo, $nome, $nivel, $parent]) {
            $this->accounts[$codigo] = [
                'codigo' => $codigo,
                'nome' => $nome,
                'nivel' => $nivel,
                'parent' => $parent,
                'd' => 0,
                'c' => 0,
                'children' => [],
            ];
            $this->order[] = $codigo;
            if ($parent !== null) {
                $this->accounts[$parent]['children'][] = $codigo;
            }
        }
    }

    private function post(string|int $codigo, int $debito, int $credito): void
    {
        $codigo = (string) $codigo;
        if (!isset($this->accounts[$codigo])) {
            throw new \RuntimeException('Conta inexistente: ' . $codigo);
        }
        $current = $codigo;
        while ($current !== null) {
            $this->accounts[$current]['d'] += $debito;
            $this->accounts[$current]['c'] += $credito;
            $current = $this->accounts[$current]['parent'];
        }
    }

    /** @param list<array{0:string,1:int,2:int}> $lines */
    private function lancar(array $lines): void
    {
        $d = 0;
        $c = 0;
        foreach ($lines as [$conta, $debito, $credito]) {
            $this->post((string) $conta, $debito, $credito);
            $d += $debito;
            $c += $credito;
        }
        if ($d !== $c) {
            throw new \RuntimeException('Lançamento desequilibrado: ' . $d . ' vs ' . $c);
        }
    }

    private function money(int $meticais, int $centavos = 0): int
    {
        return $meticais * 100 + $centavos;
    }

    private function mt24(int $meticais, int $centavos = 0): int
    {
        return (int) round($this->money($meticais, $centavos) * 5.52);
    }

    private function mt25(int $meticais, int $centavos = 0): int
    {
        return (int) round($this->money($meticais, $centavos) * 6.13);
    }

    /** @param array<string,int> $pesos */
    private function repartir(int $total, array $pesos): array
    {
        $soma = array_sum($pesos);
        $out = [];
        $acc = 0;
        $keys = array_keys($pesos);
        $last = $keys[count($keys) - 1];
        foreach ($pesos as $codigo => $peso) {
            if ($codigo === $last) {
                $out[$codigo] = $total - $acc;
            } else {
                $parte = (int) round($total * $peso / $soma);
                $out[$codigo] = $parte;
                $acc += $parte;
            }
        }

        return $out;
    }

    private function lancarFolha(int $remuneracoes, int $inssResidual, int $vencimentoAPagar = 0, int $pagarAnterior = 0): void
    {
        $inssTrab = (int) round($remuneracoes * 0.03);
        $inssEnt = (int) round($remuneracoes * 0.04);
        $liquido = $remuneracoes - $inssTrab;
        $linhasFolha = [];
        foreach ($this->repartir($remuneracoes, $this->pesosFolha()) as $conta => $valor) {
            $linhasFolha[] = [$conta, $valor, 0];
        }
        $linhasFolha[] = ['6231', $inssEnt, 0];
        $linhasFolha[] = ['12112', 0, $liquido];
        $linhasFolha[] = ['4491', 0, $inssTrab + $inssEnt];
        $this->lancar($linhasFolha);

        $inssPago = $inssTrab + $inssEnt - $inssResidual;
        $this->lancar([['4491', $inssPago, 0], ['12112', 0, $inssPago]]);

        if ($pagarAnterior > 0) {
            $this->lancar([['4622', $pagarAnterior, 0], ['12111', 0, $pagarAnterior]]);
        }

        if ($vencimentoAPagar > 0) {
            $linhasFolha = [];
            foreach ($this->repartir($vencimentoAPagar, $this->pesosFolha()) as $conta => $valor) {
                $linhasFolha[] = [$conta, $valor, 0];
            }
            $linhasFolha[] = ['4622', 0, $vencimentoAPagar];
            $this->lancar($linhasFolha);
        }
    }

    private function postAbertura2024(): void
    {
        $this->lancar([
            ['1111', $this->mt24(168_500, 50), 0],
            ['12111', $this->mt24(1_420_000), 0],
            ['12112', $this->mt24(1_085_000), 0],
            ['12113', $this->mt24(640_000), 0],
            ['12114', $this->mt24(520_000), 0],
            ['12115', $this->mt24(418_000), 0],
            ['12116', $this->mt24(365_000), 0],
            ['12117', $this->mt24(312_000), 0],
            ['12118', $this->mt24(248_000), 0],
            ['12119', $this->mt24(168_500), 0],
            ['12120', $this->mt24(111_000), 0],
            ['3231', $this->mt24(742_000), 0],
            ['3241', $this->mt24(1_780_000), 0],
            ['3251', $this->mt24(268_000), 0],
            ['3252', $this->mt24(154_000), 0],
            ['51', 0, $this->mt24(6_000_000)],
            ['43121', 0, $this->mt24(2_400_000, 50)],
        ]);
    }

    private function postOperacoes2024(): void
    {
        $compras = [
            '2111' => $this->mt24(3_937_450),
            '2112' => $this->mt24(2_127_600),
            '2113' => $this->mt24(2_257_950),
            '2114' => $this->mt24(2_542_430),
        ];
        $totalCompras = array_sum($compras);
        $ivaCompras = (int) round($totalCompras * 0.16);
        $fornPesos = $this->pesosFornecedores(2024);
        $linhas = [];
        foreach ($compras as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        $linhas[] = ['4431', $ivaCompras, 0];
        foreach ($this->repartir($totalCompras + $ivaCompras, $fornPesos) as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $this->lancar($linhas);

        $adicionais = $this->mt24(394_870, 25);
        $ivaAd = (int) round($adicionais * 0.16);
        $this->lancar([
            ['2115', $adicionais, 0],
            ['4431', $ivaAd, 0],
            ['4211012', 0, $adicionais + $ivaAd],
        ]);
        $this->lancar([
            ['451', $this->mt24(80_500), 0],
            ['12112', 0, $this->mt24(80_500)],
        ]);

        $entrada = $totalCompras + $adicionais;
        $linhas = [];
        foreach ($this->repartir($entrada, $this->pesosInventario()) as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($compras as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $linhas[] = ['2115', 0, $adicionais];
        $this->lancar($linhas);

        $vendas = [
            '7111' => $this->mt24(5_599_250),
            '7112' => $this->mt24(3_999_560),
            '7113' => $this->mt24(2_058_970),
            '7114' => $this->mt24(1_528_640),
        ];
        $totalVendas = array_sum($vendas);
        $ivaVendas = (int) round($totalVendas * 0.16);
        $clientesVenda = $this->repartir($totalVendas + $ivaVendas, $this->pesosClientes(2024));
        $linhas = [];
        foreach ($clientesVenda as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($vendas as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $linhas[] = ['4432', 0, $ivaVendas];
        $this->lancar($linhas);

        $servicos = $this->mt24(486_750);
        $ivaServ = (int) round($servicos * 0.16);
        $this->lancar([
            ['4111001', $servicos + $ivaServ, 0],
            ['722', 0, $servicos],
            ['4432', 0, $ivaServ],
        ]);

        $cogs = [
            '6111' => $this->mt24(3_710_980),
            '6112' => $this->mt24(2_605_850),
            '6113' => $this->mt24(1_396_450),
            '6114' => $this->mt24(1_028_900),
        ];
        $totalCogs = array_sum($cogs);
        $linhas = [];
        foreach ($cogs as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($this->repartir($totalCogs, $this->pesosInventario()) as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $this->lancar($linhas);

        $receber = $totalVendas + $ivaVendas + $servicos + $ivaServ;
        $recebido = (int) round($receber * 0.905);
        $recPartes = $this->repartir($recebido, $this->pesosClientes(2024));
        foreach ($recPartes as $conta => $valor) {
            $this->lancar([[$this->bancoPorValor((int) $valor), $valor, 0], [$conta, 0, $valor]]);
        }

        $pagoForn = (int) round(($totalCompras + $ivaCompras + $adicionais + $ivaAd) * 0.88);
        foreach ($this->repartir($pagoForn, $fornPesos + ['4211012' => 4]) as $conta => $valor) {
            $this->lancar([[$conta, $valor, 0], ['12111', 0, $valor]]);
        }

        $this->lancarFolha($this->mt24(1_164_800), $this->mt24(4_180), $this->mt24(18_600));

        $fse = [
            '63213' => $this->mt24(698_750),
            '63235' => $this->mt24(444_000),
            '63231' => $this->mt24(124_780, 50),
            '63232' => $this->mt24(38_640),
            '63233' => $this->mt24(42_150),
            '63234' => $this->mt24(36_900),
            '63236' => $this->mt24(270_470),
            '63237' => $this->mt24(68_450),
            '63211' => $this->mt24(79_230),
            '63238' => $this->mt24(72_000),
            '63239' => $this->mt24(57_600),
            '63241' => $this->mt24(58_750),
            '63242' => $this->mt24(63_200),
            '63221' => $this->mt24(65_730),
            '63243' => $this->mt24(67_800),
        ];
        foreach ($fse as $conta => $valor) {
            $iva = (int) round($valor * 0.16);
            $this->lancar([
                [$conta, $valor, 0],
                ['4431', $iva, 0],
                ['12111', 0, $valor + $iva],
            ]);
        }

        $amort = [
            '38231' => $this->mt24(74_200),
            '38241' => $this->mt24(178_000),
            '38251' => $this->mt24(53_600),
            '38252' => $this->mt24(15_400),
        ];
        foreach ($amort as $conta => $valor) {
            $this->lancar([['6411', $valor, 0], [(string) $conta, 0, $valor]]);
        }

        $this->lancar([
            ['6821', $this->mt24(48_760), 0],
            ['6822', $this->mt24(21_350), 0],
            ['12113', 0, $this->mt24(70_110)],
        ]);
        $this->lancar([
            ['6981', $this->mt24(36_842, 75), 0],
            ['12111', 0, $this->mt24(36_842, 75)],
        ]);

        $ivaLiq = $this->accounts['4432']['c'] - $this->accounts['4432']['d'];
        $ivaDed = $this->accounts['4431']['d'] - $this->accounts['4431']['c'];
        $ivaApurar = $ivaLiq - $ivaDed;
        if ($ivaApurar > 0) {
            $this->lancar([
                ['4432', $ivaLiq, 0],
                ['4431', 0, $ivaDed],
                ['4433', 0, $ivaApurar],
            ]);
            $ivaPago = $ivaApurar - $this->mt24(18_640);
            $this->lancar([['4433', $ivaPago, 0], ['12111', 0, $ivaPago]]);
        }

        $rai = $this->resultado();
        $irpc = $rai > 0 ? (int) round($rai * 0.32) : 0;
        if ($irpc > 0) {
            $this->lancar([['6823', $irpc, 0], ['4411', 0, $irpc]]);
            $this->lancar([['4411', (int) round($irpc * 0.75), 0], ['12112', 0, (int) round($irpc * 0.75)]]);
        }

        $this->lancar([
            ['43121', $this->mt24(384_000), 0],
            ['12111', 0, $this->mt24(384_000)],
        ]);
    }

    private function postOperacoes2025(): void
    {
        $compras = [
            '2111' => $this->mt25(4_287_600),
            '2112' => $this->mt25(2_317_800),
            '2113' => $this->mt25(2_623_420),
            '2114' => $this->mt25(2_765_630),
        ];
        $totalCompras = array_sum($compras);
        $ivaCompras = (int) round($totalCompras * 0.16);
        $fornPesos = $this->pesosFornecedores(2025);
        $linhas = [];
        foreach ($compras as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        $linhas[] = ['4431', $ivaCompras, 0];
        foreach ($this->repartir($totalCompras + $ivaCompras, $fornPesos) as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $this->lancar($linhas);

        $adicionais = $this->mt25(428_960);
        $ivaAd = (int) round($adicionais * 0.16);
        $this->lancar([
            ['2115', $adicionais, 0],
            ['4431', $ivaAd, 0],
            ['4211012', 0, $adicionais + $ivaAd],
        ]);
        $this->lancar([
            ['451', 0, $this->mt25(28_500)],
            ['4211002', $this->mt25(28_500), 0],
        ]);
        $this->lancar([
            ['451', $this->mt25(41_200), 0],
            ['12112', 0, $this->mt25(41_200)],
        ]);

        $entrada = $totalCompras + $adicionais;
        $linhas = [];
        foreach ($this->repartir($entrada, $this->pesosInventario()) as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($compras as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $linhas[] = ['2115', 0, $adicionais];
        $this->lancar($linhas);

        $vendas = [
            '7111' => $this->mt25(6_093_300),
            '7112' => $this->mt25(4_308_980),
            '7113' => $this->mt25(2_285_400),
            '7114' => $this->mt25(1_642_900),
        ];
        $totalVendas = array_sum($vendas);
        $ivaVendas = (int) round($totalVendas * 0.16);
        $clientesVenda = $this->repartir($totalVendas + $ivaVendas, $this->pesosClientes(2025));
        $linhas = [];
        foreach ($clientesVenda as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($vendas as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $linhas[] = ['4432', 0, $ivaVendas];
        $this->lancar($linhas);

        $servicos = $this->mt25(562_180);
        $ivaServ = (int) round($servicos * 0.16);
        $this->lancar([
            ['4111004', $servicos + $ivaServ, 0],
            ['722', 0, $servicos],
            ['4432', 0, $ivaServ],
        ]);

        $cogs = [
            '6111' => $this->mt25(4_040_500),
            '6112' => $this->mt25(2_825_350),
            '6113' => $this->mt25(1_538_400),
            '6114' => $this->mt25(1_102_880),
        ];
        $totalCogs = array_sum($cogs);
        $linhas = [];
        foreach ($cogs as $conta => $valor) {
            $linhas[] = [$conta, $valor, 0];
        }
        foreach ($this->repartir($totalCogs, $this->pesosInventario()) as $conta => $valor) {
            $linhas[] = [$conta, 0, $valor];
        }
        $this->lancar($linhas);

        $receber = $totalVendas + $ivaVendas + $servicos + $ivaServ;
        $recebido = (int) round($receber * 0.918);
        foreach ($this->repartir($recebido, $this->pesosClientes(2025)) as $conta => $valor) {
            $this->lancar([[$this->bancoPorValor((int) $valor), $valor, 0], [$conta, 0, $valor]]);
        }

        $pagoForn = (int) round(($totalCompras + $ivaCompras + $adicionais + $ivaAd) * 0.90);
        foreach ($this->repartir($pagoForn, $fornPesos + ['4211012' => 4]) as $conta => $valor) {
            $this->lancar([[$conta, $valor, 0], ['12111', 0, $valor]]);
        }

        $this->lancarFolha($this->mt25(1_286_400), $this->mt25(5_240), $this->mt25(21_200), $this->mt24(18_600));

        $fse = [
            '63213' => $this->mt25(750_350),
            '63235' => $this->mt25(480_000),
            '63231' => $this->mt25(136_420),
            '63232' => $this->mt25(41_200),
            '63233' => $this->mt25(46_800),
            '63234' => $this->mt25(39_600),
            '63236' => $this->mt25(298_830),
            '63237' => $this->mt25(74_800),
            '63211' => $this->mt25(86_960),
            '63238' => $this->mt25(78_000),
            '63239' => $this->mt25(61_750),
            '63241' => $this->mt25(71_250),
            '63242' => $this->mt25(67_400),
            '63221' => $this->mt25(71_150),
            '63243' => $this->mt25(72_600),
        ];
        foreach ($fse as $conta => $valor) {
            $iva = (int) round($valor * 0.16);
            $this->lancar([
                [$conta, $valor, 0],
                ['4431', $iva, 0],
                ['12111', 0, $valor + $iva],
            ]);
        }

        $amort = [
            '38231' => $this->mt24(74_200),
            '38241' => $this->mt24(178_000),
            '38251' => $this->mt24(53_600),
            '38252' => $this->mt24(15_400),
        ];
        foreach ($amort as $conta => $valor) {
            $this->lancar([['6411', $valor, 0], [(string) $conta, 0, $valor]]);
        }

        $this->lancar([
            ['6821', $this->mt25(52_140), 0],
            ['6822', $this->mt25(23_890), 0],
            ['12113', 0, $this->mt25(76_030)],
        ]);
        $this->lancar([
            ['6981', $this->mt25(41_265, 40), 0],
            ['12111', 0, $this->mt25(41_265, 40)],
        ]);

        $ivaLiq = $this->accounts['4432']['c'] - $this->accounts['4432']['d'];
        $ivaDed = $this->accounts['4431']['d'] - $this->accounts['4431']['c'];
        $ivaApurar = $ivaLiq - $ivaDed;
        if ($ivaApurar > 0) {
            $this->lancar([
                ['4432', $ivaLiq, 0],
                ['4431', 0, $ivaDed],
                ['4433', 0, $ivaApurar],
            ]);
            $ivaPago = $ivaApurar - $this->mt25(21_150);
            $this->lancar([['4433', $ivaPago, 0], ['12111', 0, $ivaPago]]);
        }

        $rai = $this->resultado();
        $irpc = $rai > 0 ? (int) round($rai * 0.32) : 0;
        if ($irpc > 0) {
            $this->lancar([['6823', $irpc, 0], ['4411', 0, $irpc]]);
            $this->lancar([['4411', (int) round($irpc * 0.78), 0], ['12112', 0, (int) round($irpc * 0.78)]]);
        }

        $this->lancar([
            ['43121', $this->mt24(384_000), 0],
            ['12111', 0, $this->mt24(384_000)],
        ]);
    }

    private function resultado(): int
    {
        $rend = 0;
        $gast = 0;
        foreach ($this->accounts as $codigo => $acc) {
            if ($acc['children'] !== []) {
                continue;
            }
            $cls = (int) ((string) $codigo)[0];
            if ($cls === 7) {
                $rend += $acc['c'] - $acc['d'];
            }
            if ($cls === 6) {
                $gast += $acc['d'] - $acc['c'];
            }
        }

        return $rend - $gast;
    }

    /** @return array<string,int> */
    private function saldosFolhaBalanco(): array
    {
        $out = [];
        foreach ($this->accounts as $codigo => $acc) {
            if ($acc['children'] !== []) {
                continue;
            }
            $cls = (int) ((string) $codigo)[0];
            if ($cls >= 6) {
                continue;
            }
            $net = $acc['d'] - $acc['c'];
            if ($net !== 0) {
                $out[$codigo] = $net;
            }
        }

        return $out;
    }

    /** @return array<string,int> */
    private function pesosInventario(): array
    {
        return [
            '2211' => 22, '2212' => 16,
            '2221' => 14, '2222' => 10,
            '2231' => 13, '2232' => 9,
            '2241' => 9, '2242' => 7,
        ];
    }

    /** @return array<string,int> */
    private function pesosClientes(int $year): array
    {
        $nomes = $this->clientesNomes();
        $n = count($nomes);
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $pos = $year === 2025 ? ($n - $i + 1) : $i;
            $out[$this->codigoCliente($i)] = max(1, 14 - (int) floor(($pos - 1) / 14));
        }

        return $out;
    }

    /** @return array<string,int> */
    private function pesosFornecedores(int $year): array
    {
        $nomes = $this->fornecedoresNomes();
        $n = count($nomes);
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $pos = $year === 2025 ? ($n - $i + 1) : $i;
            $out[$this->codigoFornecedor($i)] = max(1, 16 - (int) floor(($pos - 1) / 4));
        }

        return $out;
    }

    private function codigoCliente(int $i): string
    {
        return '4111' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
    }

    private function codigoFornecedor(int $i): string
    {
        return '4211' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
    }

    /** @return array<string,int> */
    private function pesosFolha(): array
    {
        return [
            '62211' => 28,
            '62212' => 18,
            '62213' => 16,
            '62214' => 22,
            '62215' => 9,
            '62216' => 7,
        ];
    }

    private function bancoPorValor(int $valor): string
    {
        $codigos = array_keys($this->bancos());

        return (string) $codigos[$valor % count($codigos)];
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<list<array<string,mixed>>>
     */
    private function paginate(array $rows, int $perPage): array
    {
        $blocks = [];
        $i = 0;
        $n = count($rows);
        while ($i < $n) {
            $tipo = (string) ($rows[$i]['tipo'] ?? '');
            if (
                ($tipo === 'soma-liquida' || $tipo === 'total-liquida')
                && isset($rows[$i + 1])
            ) {
                $blocks[] = [$rows[$i], $rows[$i + 1]];
                $i += 2;
                continue;
            }
            $blocks[] = [$rows[$i]];
            $i++;
        }

        $pages = [];
        $current = [];
        $count = 0;
        foreach ($blocks as $block) {
            $width = count($block);
            if ($current !== [] && $count + $width > $perPage) {
                $pages[] = $current;
                $current = [];
                $count = 0;
            }
            foreach ($block as $row) {
                $current[] = $row;
            }
            $count += $width;
        }
        if ($current !== []) {
            $pages[] = $current;
        }

        return $pages;
    }

    /** @return array<string,string> */
    private function bancos(): array
    {
        return [
            '12111' => 'Millennium bim - 00010844721001',
            '12112' => 'BCI - 00021933548002',
            '12113' => 'Standard Bank - 00033190215003',
            '12114' => 'Absa Bank Mozambique - 00044012876004',
            '12115' => 'Moza Banco - 00055190334005',
            '12116' => 'Access Bank Mozambique - 00066221458006',
            '12117' => 'Nedbank Moçambique - 00077330519007',
            '12118' => 'FNB - 00088441620008',
            '12119' => 'First Capital Bank - 00099552731009',
            '12120' => 'Ecobank - 00100663842010',
        ];
    }

    /** @return list<string> */
    private function clientesNomes(): array
    {
        return [
            'Hotel Polana', 'Hotel Avenida', 'Hotel Tivoli Maputo', 'Grupo Pestana', 'Grupo VIP', 'Turvisa',
            'Lin Limpezas', 'Clean Africa', 'Limpers', 'Ecolife', 'Help Multiservice', 'Ping Serviços',
            'Mega Distribuição', 'Tropigalia', 'Unicomo', 'Pers Shop', 'BAT', 'Moçambique Terramar Trading',
            'Kambeny Comercial', 'Procongel', 'Global Health', 'F&L Águas e Sistemas', 'Paramédicos', 'Mozambique Good Trade',
            'CDM', 'Coca-Cola Sabco Moçambique', 'Mozal', 'Vulcan Moçambique', 'HCB', 'Sasol Petroleum Temane',
            'Mozambique Leaf Tobacco', 'Cimentos de Moçambique', 'CMH', 'Eni Rovuma Basin', 'Cimentos de Nacala', 'SAT',
            'Lactalis Produtos Alimentares', 'Beiranave', 'Modet', 'Incala', 'Cimbetão', 'Siesta',
            'Ocean Fresh', 'Indústria e Construções Sotomane', 'Montepuez Ruby Mining',
            'Mota-Engil África', 'CMC Africa Austral', 'SS-Construções', 'WBHO Projects Mozambique', 'Teixeira Duarte',
            'Martifer Visabeira', 'Sogitel', 'CAPA Engenharia', 'SETH', 'Sanlo Moçambique',
            'CFM', 'EMODRAGA', 'Cornelder', 'MPDC', 'LAM', 'Terminal de Carvão da Matola',
            'Aeroportos de Moçambique', 'AGL/Bolloré', 'Matola Cargo Terminal', 'EMTPM', 'MAHS', 'Rangel',
            'Maputo Car Terminal', 'PIL', 'SERMOZ', 'STEMA', 'TTI', 'Skynet',
            'PERMAR', 'TL', 'CMA CGM', 'MEX',
            'Motraco', 'Contact Moçambique', 'Televisa', 'Consultec', 'Operadora da Estrada do Zambeze', 'Domus',
            'Grindrod Mozambique', 'Gondwana', 'PLM', 'Visabeira', 'Pinto & Cruz', 'SDVE',
            'Hidroáfrica', 'Aries Sercon', 'COTUR', 'Dora Consultores', 'GMS', 'Logica Tecnologia',
            'SDI', '2iBi', 'SEG', 'Howard Johnson Associates', 'Colliers', 'Escopil Holding',
            'Unity Designer', 'Autrase', 'Tihove Trading', 'Sotel', 'Zoe Comércio Construções', 'Pine3',
            'Maputo Relocation Solutions', 'Susamati', 'Tecninfo', 'JF Travel & Serviços', 'IMOPETRO',
            'EMOSE', 'ICE Seguros', 'Global Alliance Insurance', 'SIM', 'Companhia de Seguros Índico', 'MCS',
            'Emeritus Resseguros', 'Britam', 'ARIS Seguros', 'CMS Seguros',
            'Rádio Moçambique', 'Business Connexion (BCX)', 'TVSD',
            'Pescamar', 'Efripel', 'SIP', 'Belúzi Bananas', 'SAN', 'Pescabom',
        ];
    }

    /** @return list<string> */
    private function fornecedoresNomes(): array
    {
        return [
            'Técnica Industrial', 'Caetano Equipamentos', 'Medis Farmacêutica', 'Neoquímica', 'Omnia Holding',
            'Embalagens Mpact', 'Topack Moçambique', 'Farmac', 'PETROMOC', 'TotalEnergies Moçambique',
            'Electrotec', 'Transitex', 'Transportes Lalgy', 'Puma Energy', 'Manica Freight Services',
            'Tecnel Service', 'Rimpex', 'Bearing Man', 'Afritool', 'Intermetal',
            'Tintas CIN', 'Aberdare Intelec', 'Celmoque', 'Construa', 'Fuchs',
            'GESPETRO', 'Autogás', 'Petrogal/Galp', 'Mecwide Moçambique', 'Sulbrita',
            'Transportes Carlos Mesquita', 'Belutécnica', 'CMA CGM', 'Cornelder',
        ];
    }

    private function export(int $year): array
    {
        $groups = [
            ['11', '12'],
            ['21', '22'],
            ['32', '38'],
            ['41', '42', '43', '44', '45', '46'],
            ['51', '59'],
            ['61', '62', '63', '64', '68', '69'],
            ['71', '72'],
        ];
        $rows = [];
        $groupIndex = 0;
        $groupDebit = 0;
        $groupCredit = 0;
        $groupSaldoD = 0;
        $groupSaldoC = 0;
        $grandD = 0;
        $grandC = 0;
        $grandSD = 0;
        $grandSC = 0;

        $flush = function () use (&$rows, &$groupDebit, &$groupCredit, &$groupSaldoD, &$groupSaldoC) {
            $rows[] = ['tipo' => 'soma-liquida', 'd' => $groupDebit, 'c' => $groupCredit, 'sd' => $groupSaldoD, 'sc' => $groupSaldoC];
            $rows[] = ['tipo' => 'soma-saldos', 'sd' => $groupSaldoD, 'sc' => $groupSaldoC];
            $groupDebit = $groupCredit = $groupSaldoD = $groupSaldoC = 0;
        };

        $topSeen = [];
        foreach ($this->order as $codigo) {
            $acc = $this->accounts[$codigo];
            if ($acc['d'] === 0 && $acc['c'] === 0) {
                continue;
            }
            $top = $this->topo($codigo);
            while ($groupIndex < count($groups) && !in_array($top, $groups[$groupIndex], true)) {
                if ($topSeen !== []) {
                    $flush();
                    $topSeen = [];
                }
                $groupIndex++;
            }
            $net = $acc['d'] - $acc['c'];
            $sd = $net > 0 ? $net : 0;
            $sc = $net < 0 ? -$net : 0;
            $rows[] = [
                'tipo' => 'conta',
                'codigo' => $codigo,
                'nome' => $acc['nome'],
                'nivel' => $acc['nivel'],
                'd' => $acc['d'],
                'c' => $acc['c'],
                'sd' => $sd,
                'sc' => $sc,
            ];
            if ($codigo === $top) {
                $topSeen[$top] = true;
                $groupDebit += $acc['d'];
                $groupCredit += $acc['c'];
                $groupSaldoD += $sd;
                $groupSaldoC += $sc;
                $grandD += $acc['d'];
                $grandC += $acc['c'];
                $grandSD += $sd;
                $grandSC += $sc;
            }
        }
        if ($topSeen !== []) {
            $flush();
        }

        $rows[] = ['tipo' => 'total-liquida', 'd' => $grandD, 'c' => $grandC, 'sd' => $grandSD, 'sc' => $grandSC];
        $rows[] = ['tipo' => 'total-saldos', 'sd' => $grandSD, 'sc' => $grandSC];

        $vendas = ($this->accounts['71']['c'] ?? 0) + ($this->accounts['72']['c'] ?? 0);

        $saldos = [];
        foreach ($this->accounts as $codigo => $acc) {
            $saldos[$codigo] = $acc['d'] - $acc['c'];
        }

        return [
            'company' => self::COMPANY,
            'year' => $year,
            'rows' => $rows,
            'saldos' => $saldos,
            'resultado' => $this->resultado(),
            'totais' => ['d' => $grandD, 'c' => $grandC, 'sd' => $grandSD, 'sc' => $grandSC],
            'volume' => $vendas,
            'linhas' => count(array_filter($rows, fn ($r) => $r['tipo'] === 'conta')),
            'equilibrado' => $grandD === $grandC && $grandSD === $grandSC,
        ];
    }

    /**
     * @param array{saldos:array<string,int>,resultado:int} $data
     * @return array<string,int>
     */
    public function posicaoFinanceira(array $data): array
    {
        $n = static function (string $codigo) use ($data): int {
            return (int) ($data['saldos'][$codigo] ?? 0);
        };
        $activo = static fn (int $net): int => $net > 0 ? $net : 0;
        $passivo = static fn (int $net): int => $net < 0 ? -$net : 0;

        $tangiveis = $n('32') + $n('38');
        $intangiveis = $activo($n('33') + $n('34') + $n('37'));
        $naoCorrentes = $tangiveis + $intangiveis;

        $clientes = $activo($n('41'));
        $inventarios = $activo($n('21')) + $activo($n('22'));
        $caixaBancos = $activo($n('11')) + $activo($n('12'));
        $outrosActivos = $activo($n('45'));
        $correntes = $clientes + $outrosActivos + $inventarios + $caixaBancos;
        $totalActivos = $naoCorrentes + $correntes;

        $capital = $passivo($n('51'));
        $transitados = -$n('59');
        $reservas = $passivo($n('55'));
        $resultado = (int) $data['resultado'];
        $totalCapital = $capital + $transitados + $reservas + $resultado;

        $emprestimos = $passivo($n('43'));
        $impostos = $passivo($n('44'));
        $fornecedores = $passivo($n('42'));
        $outrasPagar = $passivo($n('46'));
        $passivosNaoCorrentes = $emprestimos;
        $passivosCorrentes = $impostos + $fornecedores + $outrasPagar;
        $totalPassivos = $passivosNaoCorrentes + $passivosCorrentes;
        $totalPassivosCapital = $totalPassivos + $totalCapital;

        return [
            'tangiveis' => $tangiveis,
            'intangiveis' => $intangiveis,
            'nao_correntes' => $naoCorrentes,
            'clientes' => $clientes,
            'outros_activos' => $outrosActivos,
            'inventarios' => $inventarios,
            'caixa_bancos' => $caixaBancos,
            'correntes' => $correntes,
            'total_activos' => $totalActivos,
            'transitados' => $transitados,
            'capital' => $capital,
            'reservas' => $reservas,
            'resultado' => $resultado,
            'total_capital' => $totalCapital,
            'emprestimos' => $emprestimos,
            'impostos' => $impostos,
            'fornecedores' => $fornecedores,
            'outras_pagar' => $outrasPagar,
            'passivos_nao_correntes' => $passivosNaoCorrentes,
            'passivos_correntes' => $passivosCorrentes,
            'total_passivos' => $totalPassivos,
            'total_passivos_capital' => $totalPassivosCapital,
            'fecha' => $totalActivos === $totalPassivosCapital,
        ];
    }

    private function topo(string $codigo): string
    {
        $current = $codigo;
        while ($this->accounts[$current]['parent'] !== null) {
            $current = $this->accounts[$current]['parent'];
        }

        return $current;
    }

    /** @return list<array{0:string,1:string,2:int,3:?string}> */
    private function chart(): array
    {
        $c = [];
        $add = function (string|int $codigo, string $nome, int $nivel, string|int|null $parent) use (&$c) {
            $c[] = [(string) $codigo, $nome, $nivel, $parent === null ? null : (string) $parent];
        };

        $add('11', 'Caixa', 1, null);
        $add('111', 'Caixa - sede', 2, '11');
        $add('1111', 'Caixa - moeda nacional', 3, '111');
        $add('12', 'Bancos', 1, null);
        $add('121', 'Depósitos à ordem', 2, '12');
        $add('1211', 'Depósitos à ordem - moeda nacional', 3, '121');
        foreach ($this->bancos() as $codigo => $nome) {
            $add($codigo, $nome, 4, '1211');
        }

        $add('21', 'Compras', 1, null);
        $add('211', 'Mercadorias', 2, '21');
        $add('2111', 'Utensílios e artigos de cozinha', 3, '211');
        $add('2112', 'Equipamentos e pequenos electrodomésticos', 3, '211');
        $add('2113', 'Material de higiene e limpeza', 3, '211');
        $add('2114', 'Artigos domésticos diversos', 3, '211');
        $add('2115', 'Adicionais à compra', 3, '211');

        $add('22', 'Mercadorias', 1, null);
        $add('221', 'Utensílios e artigos de cozinha', 2, '22');
        $add('2211', 'Utensílios e artigos de cozinha - Maputo', 3, '221');
        $add('2212', 'Utensílios e artigos de cozinha - Matola', 3, '221');
        $add('222', 'Equipamentos e pequenos electrodomésticos', 2, '22');
        $add('2221', 'Equipamentos e pequenos electrodomésticos - Maputo', 3, '222');
        $add('2222', 'Equipamentos e pequenos electrodomésticos - Matola', 3, '222');
        $add('223', 'Material de higiene e limpeza', 2, '22');
        $add('2231', 'Material de higiene e limpeza - Maputo', 3, '223');
        $add('2232', 'Material de higiene e limpeza - Matola', 3, '223');
        $add('224', 'Artigos domésticos diversos', 2, '22');
        $add('2241', 'Artigos domésticos diversos - Maputo', 3, '224');
        $add('2242', 'Artigos domésticos diversos - Matola', 3, '224');

        $add('32', 'Activos tangíveis', 1, null);
        $add('323', 'Equipamento básico', 2, '32');
        $add('3231', 'Prateleiras e equipamento de armazém', 3, '323');
        $add('324', 'Equipamento de transporte', 2, '32');
        $add('3241', 'Carrinha de distribuição', 3, '324');
        $add('325', 'Equipamento administrativo', 2, '32');
        $add('3251', 'Computadores e impressoras', 3, '325');
        $add('3252', 'Mobiliário de escritório', 3, '325');
        $add('38', 'Amortizações acumuladas', 1, null);
        $add('382', 'Activos tangíveis', 2, '38');
        $add('3823', 'Equipamento básico', 3, '382');
        $add('38231', 'Prateleiras e equipamento de armazém', 4, '3823');
        $add('3824', 'Equipamento de transporte', 3, '382');
        $add('38241', 'Carrinha de distribuição', 4, '3824');
        $add('3825', 'Equipamento administrativo', 3, '382');
        $add('38251', 'Computadores e impressoras', 4, '3825');
        $add('38252', 'Mobiliário de escritório', 4, '3825');

        $add('41', 'Clientes', 1, null);
        $add('411', 'Clientes c/c', 2, '41');
        $add('4111', 'Clientes c/c - moeda nacional', 3, '411');
        $i = 1;
        foreach ($this->clientesNomes() as $nome) {
            $add($this->codigoCliente($i), $nome, 4, '4111');
            $i++;
        }

        $add('42', 'Fornecedores', 1, null);
        $add('421', 'Fornecedores c/c', 2, '42');
        $add('4211', 'Fornecedores c/c - moeda nacional', 3, '421');
        $i = 1;
        foreach ($this->fornecedoresNomes() as $nome) {
            $add($this->codigoFornecedor($i), $nome, 4, '4211');
            $i++;
        }

        $add('43', 'Empréstimos obtidos', 1, null);
        $add('431', 'Empréstimos bancários', 2, '43');
        $add('4312', 'De médio e longo prazo', 3, '431');
        $add('43121', 'Millennium bim - financiamento de equipamento', 4, '4312');

        $add('44', 'Estado', 1, null);
        $add('441', 'Imposto sobre o rendimento', 2, '44');
        $add('4411', 'IRPC a pagar', 3, '441');
        $add('443', 'IVA', 2, '44');
        $add('4431', 'IVA dedutível', 3, '443');
        $add('4432', 'IVA liquidado', 3, '443');
        $add('4433', 'IVA a pagar', 3, '443');
        $add('449', 'Contribuições para o INSS', 2, '44');
        $add('4491', 'INSS a pagar', 3, '449');

        $add('45', 'Outros devedores', 1, null);
        $add('451', 'Adiantamentos a fornecedores', 2, '45');
        $add('46', 'Outros credores', 1, null);
        $add('462', 'Pessoal', 2, '46');
        $add('4622', 'Remunerações a pagar aos trabalhadores', 3, '462');

        $add('51', 'Capital', 1, null);
        $add('59', 'Resultados transitados', 1, null);
        $add('591', 'Resultados anteriores', 2, '59');

        $add('61', 'Custo dos inventários', 1, null);
        $add('611', 'Custo das mercadorias vendidas', 2, '61');
        $add('6111', 'Utensílios e artigos de cozinha', 3, '611');
        $add('6112', 'Equipamentos e pequenos electrodomésticos', 3, '611');
        $add('6113', 'Material de higiene e limpeza', 3, '611');
        $add('6114', 'Artigos domésticos diversos', 3, '611');

        $add('62', 'Gastos com o pessoal', 1, null);
        $add('622', 'Remunerações dos trabalhadores', 2, '62');
        $add('6221', 'Ordenados e salários', 3, '622');
        $add('62211', 'Ordenados e salários - vendas', 4, '6221');
        $add('62212', 'Ordenados e salários - armazém', 4, '6221');
        $add('62213', 'Ordenados e salários - distribuição', 4, '6221');
        $add('62214', 'Ordenados e salários - administração', 4, '6221');
        $add('62215', 'Ordenados e salários - limpeza e higiene', 4, '6221');
        $add('62216', 'Ordenados e salários - manutenção', 4, '6221');
        $add('623', 'Encargos sobre remunerações', 2, '62');
        $add('6231', 'Contribuição da entidade para o INSS', 3, '623');

        $add('63', 'Fornecimentos e serviços de terceiros', 1, null);
        $add('632', 'Fornecimentos e serviços', 2, '63');
        $add('63211', 'Conservação e reparação - Electrotec', 3, '632');
        $add('63213', 'Combustíveis - PETROMOC', 3, '632');
        $add('63221', 'Material de escritório - Mercury Comercial', 3, '632');
        $add('63231', 'Electricidade - EDM', 3, '632');
        $add('63232', 'Água - Águas da Região de Maputo', 3, '632');
        $add('63233', 'Comunicações - Teledata de Moçambique', 3, '632');
        $add('63234', 'Internet - Tv Cabo', 3, '632');
        $add('63235', 'Rendas e alugueres - IMOPAR', 3, '632');
        $add('63236', 'Transporte de mercadorias - Transportes Lalgy', 3, '632');
        $add('63237', 'Manutenção de viaturas - Centrocar', 3, '632');
        $add('63238', 'Segurança e vigilância - G4S', 3, '632');
        $add('63239', 'Limpeza de instalações - Folha Verde', 3, '632');
        $add('63241', 'Publicidade e propaganda - Sociedade do Notícias', 3, '632');
        $add('63242', 'Seguros - Hollard Moçambique', 3, '632');
        $add('63243', 'Honorários de contabilidade - KPMG', 3, '632');

        $add('64', 'Amortizações do exercício', 1, null);
        $add('641', 'Activos tangíveis', 2, '64');
        $add('6411', 'Equipamento básico, transporte e administrativo', 3, '641');

        $add('68', 'Outros gastos e perdas operacionais', 1, null);
        $add('682', 'Impostos e taxas', 2, '68');
        $add('6821', 'Imposto autárquico e taxas', 3, '682');
        $add('6822', 'Licenças e emolumentos', 3, '682');
        $add('6823', 'IRPC do exercício', 3, '682');

        $add('69', 'Gastos e perdas financeiros', 1, null);
        $add('698', 'Outros gastos e perdas financeiros', 2, '69');
        $add('6981', 'Serviços bancários', 3, '698');

        $add('71', 'Vendas', 1, null);
        $add('711', 'Vendas de mercadorias', 2, '71');
        $add('7111', 'Utensílios e artigos de cozinha', 3, '711');
        $add('7112', 'Equipamentos e pequenos electrodomésticos', 3, '711');
        $add('7113', 'Material de higiene e limpeza', 3, '711');
        $add('7114', 'Artigos domésticos diversos', 3, '711');

        $add('72', 'Prestação de serviços', 1, null);
        $add('722', 'Serviços acessórios às vendas', 2, '72');

        return $c;
    }
}
