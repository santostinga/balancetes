<section class="page-head">
    <div>
        <h1>Produtos e margens</h1>
        <p>Preço mínimo/máximo, custo unitário e volume mensal. A margem é calculada sobre o preço médio.</p>
    </div>
</section>

<div class="panel">
    <div class="table-wrap">
        <table class="ledger">
            <thead>
            <tr>
                <th>Código</th><th>Produto / serviço</th><th>Área</th>
                <th class="num">Preço</th><th class="num">Custo</th><th class="num">Margem</th><th class="num">Volume/mês</th><th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= e($p['codigo']) ?></td>
                    <td><?= e($p['nome']) ?><?php if (!(int) $p['activo']): ?> <span class="pill">inactivo</span><?php endif; ?></td>
                    <td><?= e($p['area_codigo']) ?></td>
                    <td class="num"><?= money_cell((float) $p['preco_min']) ?> – <?= money_cell((float) $p['preco_max']) ?></td>
                    <td class="num"><?= money_cell((float) $p['custo_unitario']) ?></td>
                    <td class="num"><?= e((string) $p['margem_percent']) ?>%</td>
                    <td class="num"><?= (int) $p['volume_mensal_min'] ?>–<?= (int) $p['volume_mensal_max'] ?></td>
                    <td>
                        <a href="/produtos?id=<?= (int) $p['id'] ?>">Editar</a>
                        <form method="post" action="/produtos/<?= (int) $p['id'] ?>/delete" data-confirm="Eliminar este produto?" style="display:inline">
                            <?= csrf_field() ?>
                            <button class="btn ghost" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr><td colspan="8" class="muted">Ainda não há produtos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<form class="panel sheet" method="post" action="/produtos">
    <?= csrf_field() ?>
    <h2><?= $edit ? 'Editar produto' : 'Novo produto / serviço' ?></h2>
    <input type="hidden" name="id" value="<?= e((string) ($edit['id'] ?? '')) ?>">
    <div class="fields three">
        <div>
            <label>Área</label>
            <select name="area_id" required>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= (int) $area['id'] ?>" <?= (int) ($edit['area_id'] ?? 0) === (int) $area['id'] ? 'selected' : '' ?>>
                        <?= e($area['codigo'] . ' · ' . $area['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Código</label>
            <input name="codigo" required value="<?= e($edit['codigo'] ?? '') ?>">
        </div>
        <div>
            <label>Tipo</label>
            <select name="tipo">
                <option value="servico" <?= ($edit['tipo'] ?? 'servico') === 'servico' ? 'selected' : '' ?>>Serviço</option>
                <option value="produto" <?= ($edit['tipo'] ?? '') === 'produto' ? 'selected' : '' ?>>Produto</option>
            </select>
        </div>
        <div class="full">
            <label>Nome</label>
            <input name="nome" required value="<?= e($edit['nome'] ?? '') ?>">
        </div>
        <div>
            <label>Preço mínimo</label>
            <input name="preco_min" required value="<?= e((string) ($edit['preco_min'] ?? '')) ?>">
        </div>
        <div>
            <label>Preço máximo</label>
            <input name="preco_max" required value="<?= e((string) ($edit['preco_max'] ?? '')) ?>">
        </div>
        <div>
            <label>Custo unitário</label>
            <input name="custo_unitario" required value="<?= e((string) ($edit['custo_unitario'] ?? '')) ?>">
        </div>
        <div>
            <label>Volume mensal mín.</label>
            <input type="number" name="volume_mensal_min" required value="<?= e((string) ($edit['volume_mensal_min'] ?? '1')) ?>">
        </div>
        <div>
            <label>Volume mensal máx.</label>
            <input type="number" name="volume_mensal_max" required value="<?= e((string) ($edit['volume_mensal_max'] ?? '5')) ?>">
        </div>
        <div>
            <label>Margem % (opcional — calculada se vazio)</label>
            <input name="margem_percent" value="<?= e((string) ($edit['margem_percent'] ?? '')) ?>">
        </div>
    </div>
    <label class="check">
        <input type="checkbox" name="activo" value="1" <?= !$edit || (int) $edit['activo'] ? 'checked' : '' ?>> Activo na simulação
    </label>
    <p class="actions" style="margin-top:16px">
        <button class="btn gold" type="submit"><?= $edit ? 'Actualizar' : 'Criar produto' ?></button>
        <?php if ($edit): ?><a class="btn ghost" href="/produtos">Cancelar</a><?php endif; ?>
    </p>
</form>
