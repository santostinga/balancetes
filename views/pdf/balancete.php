<div class="doc-head">
    <div class="mark">SNC</div>
    <h1>Balancete de verificação</h1>
    <div class="meta">
        <?= e($company['nome']) ?>
        · NIF <?= e($company['nif'] ?: '—') ?>
        · <?= e(month_name((int) $from)) ?> a <?= e(month_name((int) $to)) ?> de <?= (int) $year ?>
        · Moeda <?= e($company['moeda']) ?>
    </div>
</div>

<table class="ledger">
    <thead>
    <tr>
        <th>Conta</th>
        <th>Descrição</th>
        <th class="num">S. ant. débito</th>
        <th class="num">S. ant. crédito</th>
        <th class="num">Débito</th>
        <th class="num">Crédito</th>
        <th class="num">Saldo débito</th>
        <th class="num">Saldo crédito</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($trial['grouped'] as $classe => $rows): ?>
        <tr class="class-row"><td colspan="8"><?= e((string) $classe . ' — ' . account_class_name((int) $classe)) ?></td></tr>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['codigo']) ?></td>
                <td><?= e($row['nome']) ?></td>
                <td class="num debit"><?= money_cell((float) $row['ant_d']) ?></td>
                <td class="num credit"><?= money_cell((float) $row['ant_c']) ?></td>
                <td class="num debit"><?= money_cell((float) $row['mov_d']) ?></td>
                <td class="num credit"><?= money_cell((float) $row['mov_c']) ?></td>
                <td class="num debit"><?= money_cell((float) $row['sal_d']) ?></td>
                <td class="num credit"><?= money_cell((float) $row['sal_c']) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="2">Totais</td>
        <td class="num"><?= money_cell((float) $trial['totals']['ant_d']) ?></td>
        <td class="num"><?= money_cell((float) $trial['totals']['ant_c']) ?></td>
        <td class="num"><?= money_cell((float) $trial['totals']['mov_d']) ?></td>
        <td class="num"><?= money_cell((float) $trial['totals']['mov_c']) ?></td>
        <td class="num"><?= money_cell((float) $trial['totals']['sal_d']) ?></td>
        <td class="num"><?= money_cell((float) $trial['totals']['sal_c']) ?></td>
    </tr>
    </tfoot>
</table>
<p class="note">
    <?= $trial['balanced'] ? 'Balancete equilibrado (débito = crédito).' : 'Atenção: totais a débito e crédito não coincidem.' ?>
    Documento gerado por wkhtmltopdf a partir da simulação do exercício.
</p>
