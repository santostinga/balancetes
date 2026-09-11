<section class="page-head">
    <div>
        <h1>Razão</h1>
        <p>Extracto da conta no exercício, com saldo corrente.</p>
    </div>
    <div class="actions">
        <a class="btn gold" href="<?= e(url('/razao/pdf')) ?>?conta=<?= e(urlencode($codigo)) ?>&amp;ano=<?= (int) $year ?>">Descarregar PDF</a>
    </div>
</section>

<form class="panel filters" method="get">
    <div class="fields three">
        <div>
            <label>Conta</label>
            <select name="conta">
                <?php foreach ($accounts as $item): ?>
                    <option value="<?= e($item['codigo']) ?>" <?= $codigo === $item['codigo'] ? 'selected' : '' ?>>
                        <?= e($item['codigo'] . ' · ' . $item['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Exercício</label>
            <input type="number" name="ano" value="<?= e((string) $year) ?>">
        </div>
    </div>
    <p class="actions" style="margin-top:12px"><button class="btn" type="submit">Abrir razão</button></p>
</form>

<?php if (!$account): ?>
    <div class="panel muted">Conta inexistente.</div>
<?php else: ?>
    <div class="panel">
        <h2><?= e($account['codigo'] . ' — ' . $account['nome']) ?></h2>
        <p class="muted">Natureza <?= e($account['natureza']) ?> · <?= e($account['tipo']) ?></p>
        <table class="ledger">
            <thead>
            <tr><th>Data</th><th>Nº</th><th>Descrição</th><th class="num">Débito</th><th class="num">Crédito</th><th class="num">Saldo</th></tr>
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
    </div>
<?php endif; ?>
