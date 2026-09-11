<div class="doc-head">
    <div class="mark">SNC</div>
    <h1>Razão da conta <?= e($account['codigo']) ?></h1>
    <div class="meta">
        <?= e($company['nome']) ?> · <?= e($account['nome']) ?>
        · Natureza <?= e($account['natureza']) ?> · Exercício <?= (int) $year ?>
    </div>
</div>

<table class="ledger">
    <thead>
    <tr>
        <th>Data</th>
        <th>Nº</th>
        <th>Descrição</th>
        <th class="num">Débito</th>
        <th class="num">Crédito</th>
        <th class="num">Saldo</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($lines as $line): ?>
        <tr>
            <td><?= e($line['data']) ?></td>
            <td><?= e($line['numero']) ?></td>
            <td><?= e($line['descricao']) ?><?= $line['linha_descricao'] ? ' — ' . e($line['linha_descricao']) : '' ?></td>
            <td class="num debit"><?= money_cell((float) $line['debito']) ?></td>
            <td class="num credit"><?= money_cell((float) $line['credito']) ?></td>
            <td class="num"><?= money_cell((float) $line['saldo']) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lines): ?>
        <tr><td colspan="6" class="muted">Sem movimentos nesta conta.</td></tr>
    <?php endif; ?>
    </tbody>
    <tfoot>
    <tr>
        <td colspan="5">Saldo final (débito − crédito)</td>
        <td class="num"><?= money_cell((float) $saldo) ?></td>
    </tr>
    </tfoot>
</table>
