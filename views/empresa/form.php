<section class="page-head">
    <div>
        <h1>Empresa</h1>
        <p>Identificação fiscal, exercício e taxas usadas na simulação (IVA e IRC).</p>
    </div>
</section>

<form class="panel sheet" method="post" action="/empresa">
    <?= csrf_field() ?>
    <div class="fields">
        <div class="full">
            <label>Nome</label>
            <input name="nome" required value="<?= e($company['nome'] ?? '') ?>">
        </div>
        <div>
            <label>NIF</label>
            <input name="nif" value="<?= e($company['nif'] ?? '') ?>">
        </div>
        <div>
            <label>Cidade</label>
            <input name="cidade" value="<?= e($company['cidade'] ?? '') ?>">
        </div>
        <div class="full">
            <label>Morada</label>
            <input name="morada" value="<?= e($company['morada'] ?? '') ?>">
        </div>
        <div>
            <label>País</label>
            <input name="pais" value="<?= e($company['pais'] ?? 'Portugal') ?>">
        </div>
        <div>
            <label>Moeda</label>
            <select name="moeda">
                <?php foreach (['EUR' => 'Euro (EUR)', 'AOA' => 'Kwanza (AOA)', 'USD' => 'Dólar (USD)'] as $code => $label): ?>
                    <option value="<?= e($code) ?>" <?= ($company['moeda'] ?? 'EUR') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Exercício</label>
            <input type="number" name="exercicio" required value="<?= e((string) ($company['exercicio'] ?? 2025)) ?>">
        </div>
        <div>
            <label>Capital social</label>
            <input name="capital_social" required value="<?= e((string) ($company['capital_social'] ?? '50000')) ?>">
        </div>
        <div>
            <label>Taxa de IVA (ex.: 0.23)</label>
            <input name="iva_taxa" value="<?= e((string) ($company['iva_taxa'] ?? '0.23')) ?>">
        </div>
        <div>
            <label>Taxa de IRC (ex.: 0.21)</label>
            <input name="irc_taxa" value="<?= e((string) ($company['irc_taxa'] ?? '0.21')) ?>">
        </div>
    </div>
    <p class="actions" style="margin-top:18px"><button class="btn gold" type="submit">Gravar empresa</button></p>
</form>
