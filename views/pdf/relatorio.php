<?php $dr = $report['dr']; $ba = $report['balanco']; $k = $report['kpis']; ?>
<div class="doc-head">
    <div class="mark">SNC</div>
    <h1>Relatório anual</h1>
    <div class="meta">
        <?= e($company['nome']) ?>
        · NIF <?= e($company['nif'] ?: '—') ?>
        · <?= e($company['morada'] ? $company['morada'] . ', ' : '') ?><?= e($company['cidade'] ?: '') ?>
        · Exercício <?= (int) $year ?>
        · <?= e($company['moeda']) ?>
    </div>
</div>

<table class="kpis">
    <tr>
        <td><span>Volume de negócios</span><strong><?= money((float) $dr['prestacoes'], $company['moeda']) ?></strong></td>
        <td><span>EBITDA</span><strong><?= money((float) $dr['ebitda'], $company['moeda']) ?></strong><em><?= e((string) $k['ebitda_margin']) ?>%</em></td>
        <td><span>Resultado líquido</span><strong><?= money((float) $dr['resultado_liquido'], $company['moeda']) ?></strong><em>margem <?= e((string) $k['margem_liquida']) ?>%</em></td>
        <td><span>Autonomia financeira</span><strong><?= e((string) $k['autonomia']) ?>%</strong><em>liquidez <?= e((string) $k['liquidez']) ?></em></td>
    </tr>
</table>

<table class="split">
    <tr>
        <td>
            <h2>Demonstração dos resultados</h2>
            <table class="ledger">
                <tbody>
                <tr><td>Prestações de serviços</td><td class="num credit"><?= money_cell((float) $dr['prestacoes']) ?></td></tr>
                <tr><td>Trabalhos especializados</td><td class="num debit"><?= money_cell((float) $dr['trabalhos']) ?></td></tr>
                <tr><td>Fornecimentos e serviços externos</td><td class="num debit"><?= money_cell((float) $dr['fse']) ?></td></tr>
                <tr><td>Gastos com o pessoal</td><td class="num debit"><?= money_cell((float) $dr['pessoal']) ?></td></tr>
                <tr><td>Depreciações</td><td class="num debit"><?= money_cell((float) $dr['depreciacoes']) ?></td></tr>
                <tr><td>Outros gastos</td><td class="num debit"><?= money_cell((float) $dr['outros_gastos']) ?></td></tr>
                <tr><td><strong>Resultado antes de impostos</strong></td><td class="num"><strong><?= money_cell((float) $dr['rai']) ?></strong></td></tr>
                <tr><td>Imposto sobre o rendimento</td><td class="num debit"><?= money_cell((float) $dr['irc']) ?></td></tr>
                <tr><td><strong>Resultado líquido do período</strong></td><td class="num"><strong><?= money_cell((float) $dr['resultado_liquido']) ?></strong></td></tr>
                </tbody>
            </table>
        </td>
        <td>
            <h2>Balanço</h2>
            <table class="ledger">
                <thead><tr><th>Activo</th><th class="num"></th></tr></thead>
                <tbody>
                <tr><td>Caixa e depósitos</td><td class="num"><?= money_cell((float) $ba['caixa_bancos']) ?></td></tr>
                <tr><td>Clientes</td><td class="num"><?= money_cell((float) $ba['clientes']) ?></td></tr>
                <tr><td>Equipamento líquido</td><td class="num"><?= money_cell((float) $ba['activo_nao_corrente']) ?></td></tr>
                <tr><td><strong>Total do activo</strong></td><td class="num"><strong><?= money_cell((float) $ba['activo']) ?></strong></td></tr>
                </tbody>
            </table>
            <table class="ledger">
                <thead><tr><th>Capital próprio e passivo</th><th class="num"></th></tr></thead>
                <tbody>
                <tr><td>Capital realizado</td><td class="num"><?= money_cell((float) $ba['capital']) ?></td></tr>
                <tr><td>Resultado líquido</td><td class="num"><?= money_cell((float) $ba['resultado_liquido']) ?></td></tr>
                <tr><td>Fornecedores</td><td class="num"><?= money_cell((float) $ba['fornecedores']) ?></td></tr>
                <tr><td>Estado e outros entes públicos</td><td class="num"><?= money_cell((float) $ba['estado']) ?></td></tr>
                <tr><td><strong>Total capital + passivo</strong></td><td class="num"><strong><?= money_cell((float) $ba['passivo_capital']) ?></strong></td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<h2>Evolução mensal</h2>
<table class="ledger">
    <thead>
    <tr>
        <th>Mês</th>
        <th class="num">Rendimentos</th>
        <th class="num">Gastos</th>
        <th class="num">Resultado</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($report['mensal'] as $m): ?>
        <tr>
            <td><?= e(month_name((int) $m['mes'])) ?></td>
            <td class="num credit"><?= money_cell((float) $m['rendimentos']) ?></td>
            <td class="num debit"><?= money_cell((float) $m['gastos']) ?></td>
            <td class="num"><?= money_cell((float) $m['rendimentos'] - (float) $m['gastos']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Análise por área de serviço</h2>
<table class="ledger">
    <thead>
    <tr>
        <th>Área</th>
        <th class="num">Rendimentos</th>
        <th class="num">Gastos imputados</th>
        <th class="num">Contribuição</th>
        <th class="num">Margem</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($report['areas'] as $area): ?>
        <tr>
            <td><?= e($area['codigo'] . ' · ' . $area['nome']) ?></td>
            <td class="num credit"><?= money_cell((float) $area['rendimentos']) ?></td>
            <td class="num debit"><?= money_cell((float) $area['gastos']) ?></td>
            <td class="num"><?= money_cell((float) $area['contribuicao']) ?></td>
            <td class="num"><?= e((string) $area['margem']) ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Análise por produto</h2>
<table class="ledger">
    <thead>
    <tr>
        <th>Produto</th>
        <th>Área</th>
        <th class="num">Rendimentos</th>
        <th class="num">Custos directos</th>
        <th class="num">Contribuição</th>
        <th class="num">Margem real</th>
        <th class="num">Margem alvo</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($report['produtos'] as $p): ?>
        <tr>
            <td><?= e($p['codigo'] . ' · ' . $p['nome']) ?></td>
            <td><?= e($p['area_nome']) ?></td>
            <td class="num"><?= money_cell((float) $p['rendimentos']) ?></td>
            <td class="num"><?= money_cell((float) $p['custos_directos']) ?></td>
            <td class="num"><?= money_cell((float) $p['contribuicao']) ?></td>
            <td class="num"><?= e((string) $p['margem_real']) ?>%</td>
            <td class="num"><?= e((string) $p['margem_percent']) ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="note">Indicadores calculados a partir do balancete simulado. Margem bruta <?= e((string) $k['margem_bruta']) ?>%.</p>
