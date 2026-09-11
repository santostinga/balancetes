<section class="page-head">
    <div>
        <h1>Diário</h1>
        <p>Lançamentos do exercício, com documento, área e linhas a débito/crédito.</p>
    </div>
</section>

<form class="panel filters" method="get" action="/diario">
    <div class="fields three">
        <div>
            <label>Ano</label>
            <input type="number" name="ano" value="<?= e((string) $year) ?>">
        </div>
        <div>
            <label>Mês</label>
            <select name="mes">
                <option value="">Todos</option>
                <?php foreach (range(1, 12) as $m): ?>
                    <option value="<?= $m ?>" <?= $month === $m ? 'selected' : '' ?>><?= e(month_name($m)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Área</label>
            <select name="area">
                <option value="">Todas</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= (int) $area['id'] ?>" <?= $areaId === (int) $area['id'] ? 'selected' : '' ?>><?= e($area['codigo'] . ' · ' . $area['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <p class="actions" style="margin-top:12px"><button class="btn" type="submit">Filtrar</button></p>
</form>

<p class="muted"><?= (int) $total ?> lançamentos · página <?= (int) $page ?> de <?= (int) $pages ?></p>
<?php if ($pages > 1): ?>
    <p class="actions">
        <?php if ($page > 1): ?>
            <a class="btn ghost" href="?ano=<?= (int) $year ?>&amp;mes=<?= e((string) ($month ?? '')) ?>&amp;area=<?= e((string) ($areaId ?? '')) ?>&amp;pagina=<?= $page - 1 ?>">Anterior</a>
        <?php endif; ?>
        <?php if ($page < $pages): ?>
            <a class="btn ghost" href="?ano=<?= (int) $year ?>&amp;mes=<?= e((string) ($month ?? '')) ?>&amp;area=<?= e((string) ($areaId ?? '')) ?>&amp;pagina=<?= $page + 1 ?>">Seguinte</a>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if (!$entries): ?>
    <div class="panel muted">Não há lançamentos neste filtro. Corra a simulação primeiro.</div>
<?php endif; ?>

<?php foreach ($entries as $entry): ?>
    <article class="panel entry">
        <header>
            <div>
                <strong><?= e($entry['numero']) ?></strong>
                <span class="muted"> · <?= e($entry['data']) ?> · <?= e($entry['origem']) ?></span>
                <div><?= e($entry['descricao']) ?></div>
                <div class="small muted">
                    <?= e($entry['area_nome'] ?? 'Empresa') ?>
                    <?= $entry['produto_nome'] ? ' · ' . e($entry['produto_nome']) : '' ?>
                    <?= $entry['documento'] ? ' · Doc. ' . e($entry['documento']) : '' ?>
                </div>
            </div>
        </header>
        <table class="ledger">
            <thead><tr><th>Conta</th><th>Descrição</th><th class="num">Débito</th><th class="num">Crédito</th></tr></thead>
            <tbody>
            <?php foreach ($entry['lines'] as $line): ?>
                <tr>
                    <td><a href="/razao?conta=<?= e(urlencode($line['account_codigo'])) ?>&amp;ano=<?= e((string) $year) ?>"><?= e($line['account_codigo']) ?></a></td>
                    <td><?= e($line['conta_nome']) ?><?= $line['descricao'] ? ' — ' . e($line['descricao']) : '' ?></td>
                    <td class="num debit"><?= money_cell((float) $line['debito']) ?></td>
                    <td class="num credit"><?= money_cell((float) $line['credito']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
<?php endforeach; ?>
