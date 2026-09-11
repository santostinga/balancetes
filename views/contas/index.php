<section class="page-head">
    <div>
        <h1>Plano de contas SNC</h1>
        <p>Plano resumido ao estilo PRIMAVERA, classes 1 a 8, usado nos lançamentos da simulação.</p>
    </div>
</section>

<div class="panel">
    <table class="ledger">
        <thead><tr><th>Conta</th><th>Descrição</th><th>Classe</th><th>Natureza</th><th>Tipo</th></tr></thead>
        <tbody>
        <?php $last = null; foreach ($accounts as $account): ?>
            <?php if ($last !== (int) $account['classe']): $last = (int) $account['classe']; ?>
                <tr class="class-row"><td colspan="5"><?= e((string) $last . ' · ' . account_class_name($last)) ?></td></tr>
            <?php endif; ?>
            <tr>
                <td><a href="<?= e(url('/razao')) ?>?conta=<?= e(urlencode($account['codigo'])) ?>"><?= e($account['codigo']) ?></a></td>
                <td><?= e($account['nome']) ?></td>
                <td><?= e((string) $account['classe']) ?></td>
                <td><?= e($account['natureza']) ?></td>
                <td><?= e($account['tipo']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
