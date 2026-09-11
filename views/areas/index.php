<section class="page-head">
    <div>
        <h1>Áreas de serviço</h1>
        <p>Centros de custo da empresa: estrutura mensal e massa salarial imputada a cada área.</p>
    </div>
</section>

<div class="grid grid-2">
    <div class="panel">
        <table class="ledger">
            <thead>
            <tr><th>Código</th><th>Área</th><th class="num">Custo fixo/mês</th><th class="num">Pessoal/mês</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($areas as $area): ?>
                <tr>
                    <td><?= e($area['codigo']) ?></td>
                    <td><?= e($area['nome']) ?><?php if (!(int) $area['activa']): ?> <span class="pill">inactiva</span><?php endif; ?></td>
                    <td class="num"><?= money_cell((float) $area['custo_fixo_mensal']) ?></td>
                    <td class="num"><?= money_cell((float) $area['custo_pessoal_mensal']) ?></td>
                    <td>
                        <a href="/areas?id=<?= (int) $area['id'] ?>">Editar</a>
                        <form method="post" action="/areas/<?= (int) $area['id'] ?>/delete" data-confirm="Eliminar esta área?" style="display:inline">
                            <?= csrf_field() ?>
                            <button class="btn ghost" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$areas): ?>
                <tr><td colspan="5" class="muted">Ainda não há áreas. Crie a primeira à direita.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form class="panel sheet" method="post" action="/areas">
        <?= csrf_field() ?>
        <h2><?= $edit ? 'Editar área' : 'Nova área' ?></h2>
        <input type="hidden" name="id" value="<?= e((string) ($edit['id'] ?? '')) ?>">
        <div class="fields">
            <div>
                <label>Código</label>
                <input name="codigo" required value="<?= e($edit['codigo'] ?? '') ?>" placeholder="CONS">
            </div>
            <div>
                <label>Nome</label>
                <input name="nome" required value="<?= e($edit['nome'] ?? '') ?>">
            </div>
            <div class="full">
                <label>Descrição</label>
                <textarea name="descricao"><?= e($edit['descricao'] ?? '') ?></textarea>
            </div>
            <div>
                <label>Custo fixo mensal</label>
                <input name="custo_fixo_mensal" value="<?= e((string) ($edit['custo_fixo_mensal'] ?? '0')) ?>">
            </div>
            <div>
                <label>Custo de pessoal mensal</label>
                <input name="custo_pessoal_mensal" value="<?= e((string) ($edit['custo_pessoal_mensal'] ?? '0')) ?>">
            </div>
        </div>
        <label class="check"><input type="checkbox" name="activa" value="1" <?= !$edit || (int) $edit['activa'] ? 'checked' : '' ?>> Área activa na simulação</label>
        <p class="actions" style="margin-top:16px">
            <button class="btn gold" type="submit"><?= $edit ? 'Actualizar' : 'Criar área' ?></button>
            <?php if ($edit): ?><a class="btn ghost" href="/areas">Cancelar</a><?php endif; ?>
        </p>
    </form>
</div>
