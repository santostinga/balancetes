<section class="page-head">
    <div>
        <h1>Simulação do exercício</h1>
        <p>O motor gera 12 meses de vendas, custos, pessoal, IVA, depreciação e IRC, com partidas dobradas.</p>
    </div>
</section>

<div class="grid grid-2">
    <form class="panel sheet" method="post" action="/simulacao/gerar" data-confirm="Os lançamentos simulados deste exercício serão substituídos. Continuar?">
        <?= csrf_field() ?>
        <div class="fields">
            <div>
                <label>Exercício</label>
                <input type="number" name="exercicio" value="<?= e((string) ($company['exercicio'] ?? 2025)) ?>">
            </div>
            <div>
                <label>Seed (reproduzível)</label>
                <input type="number" name="seed" value="2025">
            </div>
            <div>
                <label>Variabilidade (0 a 0.40)</label>
                <input name="variabilidade" value="0.12">
            </div>
            <div>
                <label>Taxa de recebimento no mês</label>
                <input name="taxa_recebimento" value="0.82">
            </div>
            <div>
                <label>Taxa de pagamento no mês</label>
                <input name="taxa_pagamento" value="0.75">
            </div>
            <div class="check">
                <input type="checkbox" name="incluir_iva" value="1" checked>
                <label style="margin:0">Incluir IVA nas vendas e custos</label>
            </div>
        </div>
        <p class="actions" style="margin-top:16px">
            <button class="btn gold" type="submit" <?= $company ? '' : 'disabled' ?>>Gerar balancete simulado</button>
        </p>
        <?php if (!$company): ?><p class="muted">Configure a empresa, áreas e produtos antes de simular.</p><?php endif; ?>
    </form>

    <div class="panel">
        <h2>Atalhos</h2>
        <p class="muted">Pode carregar um caso completo (NorteAtlântico Serviços) com quatro áreas e oito serviços, e simular 2025 de imediato.</p>
        <form method="post" action="/simulacao/demo" data-confirm="Substitui empresa, áreas, produtos e lançamentos. Continuar?">
            <?= csrf_field() ?>
            <button class="btn" type="submit">Carregar demonstração</button>
        </form>
        <?php if ($run): ?>
            <p class="small muted" style="margin-top:18px">Última simulação: exercício <?= e((string) $run['exercicio']) ?> · seed <?= e((string) $run['seed']) ?> · <?= (int) $entries ?> lançamentos</p>
        <?php endif; ?>
        <ul class="muted">
            <li>Débito = crédito em cada lançamento.</li>
            <li>Sazonalidade mais baixa em Agosto e mais alta em Outubro/Novembro.</li>
            <li>O mesmo seed gera o mesmo balancete.</li>
        </ul>
    </div>
</div>
