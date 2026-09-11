<section class="page-head">
    <div>
        <h1>Balancete de verificação</h1>
        <p>Saldo anterior, movimento do período e saldo actual — formato inspirado no PRIMAVERA.</p>
    </div>
    <?php if ($trial): ?>
        <div class="actions">
            <a class="btn ghost" href="?ano=<?= (int) $year ?>&amp;de=<?= (int) $from ?>&amp;ate=<?= (int) $to ?>&amp;export=1">Exportar CSV</a>
            <button class="btn" type="button" onclick="window.print()">Imprimir</button>
        </div>
    <?php endif; ?>
</section>

<form class="panel filters" method="get">
    <div class="fields three">
        <div>
            <label>Exercício</label>
            <input type="number" name="ano" value="<?= e((string) $year) ?>">
        </div>
        <div>
            <label>De</label>
            <select name="de">
                <?php foreach (range(1, 12) as $m): ?>
                    <option value="<?= $m ?>" <?= $from === $m ? 'selected' : '' ?>><?= e(month_name($m)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Até</label>
            <select name="ate">
                <?php foreach (range(1, 12) as $m): ?>
                    <option value="<?= $m ?>" <?= $to === $m ? 'selected' : '' ?>><?= e(month_name($m)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <label class="check"><input type="checkbox" name="todas" value="1" <?= !$only ? 'checked' : '' ?>> Mostrar contas sem movimento</label>
    <p class="actions" style="margin-top:12px"><button class="btn gold" type="submit">Actualizar balancete</button></p>
</form>

<?php if (!$trial || !$trial['rows']): ?>
    <div class="panel muted">Sem movimentos. Configure a empresa e gere a simulação.</div>
<?php else: ?>
    <?php if (!$trial['balanced']): ?>
        <div class="flash erro">Atenção: totais a débito e crédito não coincidem.</div>
    <?php endif; ?>
    <div class="panel">
        <p class="muted"><?= e($company['nome'] ?? '') ?> · <?= e(month_name($from)) ?> a <?= e(month_name($to)) ?> de <?= (int) $year ?></p>
        <div class="table-wrap">
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
                            <td><a href="/razao?conta=<?= e(urlencode($row['codigo'])) ?>&amp;ano=<?= (int) $year ?>"><?= e($row['codigo']) ?></a></td>
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
        </div>
    </div>
<?php endif; ?>
