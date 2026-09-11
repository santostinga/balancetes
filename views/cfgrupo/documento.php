<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= htmlspecialchars($data['company'] . ' - Balancete 2024 e 2025', ENT_QUOTES, 'UTF-8') ?></title>
    <style type="text/css"><?= $css ?></style>
</head>
<body>
<?php foreach ($data['sections'] as $index => $section): ?>
<div class="section-page<?= $index > 0 ? ' break-before' : '' ?>">
    <table class="balance">
        <colgroup>
            <col class="account">
            <col class="description">
            <col class="money">
            <col class="money">
            <col class="money">
            <col class="money">
        </colgroup>
        <thead>
            <tr>
                <td colspan="6" class="repeat-head">
                    <div class="screen-only"><?= htmlspecialchars($data['company'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="title-line">
                        <table class="title-table">
                            <tr>
                                <td class="report-title">Balancete Geral (Acumulado até Dezembro) - <?= (int) $section['year'] ?></td>
                                <td class="currency">Valores em MT</td>
                            </tr>
                        </table>
                    </div>
                    <table class="info-table">
                        <tr>
                            <td><div class="info-box">Lançamento: &lt;TODOS&gt;</div></td>
                            <td class="info-gap">&nbsp;</td>
                            <td><div class="info-box">Data Contab.: 31-12-<?= (int) $section['year'] ?></div></td>
                            <td>&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <th class="account">Conta</th>
                <th>Descrição</th>
                <th class="num">Mov. Débito</th>
                <th class="num">Mov. Crédito</th>
                <th class="num">Saldo Débito</th>
                <th class="num">Saldo Crédito</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($section['rows'] as $row): ?>
            <?php if ($row['tipo'] === 'conta'): ?>
                <tr class="level-<?= (int) $row['nivel'] ?>">
                    <td class="account"><?= htmlspecialchars($row['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="description"><?= htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['d']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['c']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sd']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sc']) ?></td>
                </tr>
            <?php elseif ($row['tipo'] === 'soma-liquida'): ?>
                <tr class="total">
                    <td colspan="2" class="total-label">Soma Líquida</td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['d']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['c']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sd']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sc']) ?></td>
                </tr>
            <?php elseif ($row['tipo'] === 'soma-saldos'): ?>
                <tr class="total-saldos">
                    <td colspan="4" class="total-label">Soma Saldos</td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sd']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sc']) ?></td>
                </tr>
            <?php elseif ($row['tipo'] === 'total-liquida'): ?>
                <tr class="grand-total">
                    <td colspan="2" class="total-label">Soma Líquida</td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['d']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['c']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sd']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sc']) ?></td>
                </tr>
            <?php elseif ($row['tipo'] === 'total-saldos'): ?>
                <tr class="grand-total-saldos">
                    <td colspan="4" class="total-label">Soma Saldos</td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sd']) ?></td>
                    <td class="num"><?= \App\Services\CfGrupoBalancete::format((int) $row['sc']) ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php
$a = $data['comparativo'][2025];
$b = $data['comparativo'][2024];
$fmt = static fn (int $cents): string => \App\Services\CfGrupoBalancete::format($cents);
?>
<div class="comparativo break-before">
    <div class="report">
    <table>
        <colgroup>
            <col class="col-rubrica">
            <col class="col-nota">
            <col class="col-valor">
            <col class="col-valor">
        </colgroup>
        <thead>
            <tr>
                <th>Rubricas</th>
                <th class="center">Notas</th>
                <th class="num">31/12/2025</th>
                <th class="num">31/12/2024</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section">
                <td class="rubrica">Activos Não Correntes</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr>
                <td class="rubrica level-1">Activos tangíveis</td>
                <td class="center">6</td>
                <td class="num"><?= $fmt((int) $a['tangiveis']) ?></td>
                <td class="num"><?= $fmt((int) $b['tangiveis']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Activos Intangíveis</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['intangiveis']) ?></td>
                <td class="num"><?= $fmt((int) $b['intangiveis']) ?></td>
            </tr>
            <tr class="subtotal">
                <td></td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['nao_correntes']) ?></td>
                <td class="num"><?= $fmt((int) $b['nao_correntes']) ?></td>
            </tr>
            <tr class="section">
                <td class="rubrica">Activos Correntes</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr>
                <td class="rubrica level-1">Clientes</td>
                <td class="center">7</td>
                <td class="num"><?= $fmt((int) $a['clientes']) ?></td>
                <td class="num"><?= $fmt((int) $b['clientes']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Outros Activos Correntes</td>
                <td class="center">8</td>
                <td class="num"><?= $fmt((int) $a['outros_activos']) ?></td>
                <td class="num"><?= $fmt((int) $b['outros_activos']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Inventários</td>
                <td class="center">9</td>
                <td class="num"><?= $fmt((int) $a['inventarios']) ?></td>
                <td class="num"><?= $fmt((int) $b['inventarios']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Caixa e Bancos</td>
                <td class="center">10</td>
                <td class="num"><?= $fmt((int) $a['caixa_bancos']) ?></td>
                <td class="num"><?= $fmt((int) $b['caixa_bancos']) ?></td>
            </tr>
            <tr class="subtotal">
                <td></td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['correntes']) ?></td>
                <td class="num"><?= $fmt((int) $b['correntes']) ?></td>
            </tr>
            <tr class="total">
                <td class="rubrica">TOTAL DOS ACTIVOS</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['total_activos']) ?></td>
                <td class="num"><?= $fmt((int) $b['total_activos']) ?></td>
            </tr>
            <tr class="section">
                <td class="rubrica">CAPITAL PRÓPRIO E PASSIVOS</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr class="subsection">
                <td class="rubrica">Capital Próprio</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr>
                <td class="rubrica level-1">Resultados Transitados</td>
                <td class="center">11</td>
                <td class="num"><?= $fmt((int) $a['transitados']) ?></td>
                <td class="num"><?= $fmt((int) $b['transitados']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Capital Social</td>
                <td class="center">11</td>
                <td class="num"><?= $fmt((int) $a['capital']) ?></td>
                <td class="num"><?= $fmt((int) $b['capital']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Reservas Legais</td>
                <td class="center">11</td>
                <td class="num"><?= $fmt((int) $a['reservas']) ?></td>
                <td class="num"><?= $fmt((int) $b['reservas']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Resultado Líquido do Período</td>
                <td class="center">11</td>
                <td class="num"><?= $fmt((int) $a['resultado']) ?></td>
                <td class="num"><?= $fmt((int) $b['resultado']) ?></td>
            </tr>
            <tr class="subtotal">
                <td class="rubrica">Total do Capital Próprio</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['total_capital']) ?></td>
                <td class="num"><?= $fmt((int) $b['total_capital']) ?></td>
            </tr>
            <tr class="subsection">
                <td class="rubrica">Passivos Não Correntes</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr>
                <td class="rubrica level-1">Empréstimos obtidos</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['emprestimos']) ?></td>
                <td class="num"><?= $fmt((int) $b['emprestimos']) ?></td>
            </tr>
            <tr class="subsection">
                <td class="rubrica">Passivos Correntes</td>
                <td></td>
                <td class="num">0,00</td>
                <td class="num">0,00</td>
            </tr>
            <tr>
                <td class="rubrica level-1">Impostos a Pagar</td>
                <td class="center">12</td>
                <td class="num"><?= $fmt((int) $a['impostos']) ?></td>
                <td class="num"><?= $fmt((int) $b['impostos']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Fornecedores</td>
                <td class="center">13</td>
                <td class="num"><?= $fmt((int) $a['fornecedores']) ?></td>
                <td class="num"><?= $fmt((int) $b['fornecedores']) ?></td>
            </tr>
            <tr>
                <td class="rubrica level-1">Outras Contas a Pagar</td>
                <td class="center">14</td>
                <td class="num"><?= $fmt((int) $a['outras_pagar']) ?></td>
                <td class="num"><?= $fmt((int) $b['outras_pagar']) ?></td>
            </tr>
            <tr class="subtotal">
                <td class="rubrica">Total dos Passivos</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['total_passivos']) ?></td>
                <td class="num"><?= $fmt((int) $b['total_passivos']) ?></td>
            </tr>
            <tr class="grand-total">
                <td class="rubrica">TOTAL DOS PASSIVOS E CAPITAL PRÓPRIO</td>
                <td></td>
                <td class="num"><?= $fmt((int) $a['total_passivos_capital']) ?></td>
                <td class="num"><?= $fmt((int) $b['total_passivos_capital']) ?></td>
            </tr>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>
