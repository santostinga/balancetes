<div class="doc-head">
    <div class="mark">SNC</div>
    <h1>Diário</h1>
    <div class="meta">
        <?= e($company['nome']) ?>
        · Exercício <?= (int) $year ?>
        <?= $month ? ' · ' . e(month_name((int) $month)) : '' ?>
        <?= $area ? ' · Área ' . e($area['codigo'] . ' ' . $area['nome']) : '' ?>
        · <?= count($entries) ?> lançamentos
    </div>
</div>

<?php foreach ($entries as $entry): ?>
    <div class="entry">
        <div class="ttl"><?= e($entry['numero']) ?> · <?= e($entry['data']) ?></div>
        <div><?= e($entry['descricao']) ?></div>
        <div class="muted">
            <?= e($entry['area_nome'] ?? 'Empresa') ?>
            <?= $entry['produto_nome'] ? ' · ' . e($entry['produto_nome']) : '' ?>
            <?= $entry['documento'] ? ' · Doc. ' . e($entry['documento']) : '' ?>
        </div>
        <table class="ledger">
            <thead>
            <tr><th>Conta</th><th>Descrição</th><th class="num">Débito</th><th class="num">Crédito</th></tr>
            </thead>
            <tbody>
            <?php foreach ($entry['lines'] as $line): ?>
                <tr>
                    <td><?= e($line['account_codigo']) ?></td>
                    <td><?= e($line['conta_nome']) ?><?= $line['descricao'] ? ' — ' . e($line['descricao']) : '' ?></td>
                    <td class="num debit"><?= money_cell((float) $line['debito']) ?></td>
                    <td class="num credit"><?= money_cell((float) $line['credito']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
