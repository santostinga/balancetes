<section class="page-head">
    <div>
        <h1>Painel do exercício</h1>
        <p>Configure áreas e produtos, simule o diário e leia o balancete como no PRIMAVERA.</p>
    </div>
    <div class="actions">
        <?php if (!$company): ?>
            <a class="btn gold" href="/empresa">Criar empresa</a>
        <?php else: ?>
            <a class="btn" href="/simulacao">Simular exercício</a>
            <a class="btn gold" href="/relatorio-anual">Relatório anual</a>
        <?php endif; ?>
    </div>
</section>

<?php if (!$company): ?>
    <div class="panel">
        <h2>Como começar</h2>
        <ol>
            <li>Registe a empresa (NIF, exercício, taxas de IVA e IRC).</li>
            <li>Crie as áreas de serviço e os custos mensais de estrutura e pessoal.</li>
            <li>Indique os produtos/serviços, intervalos de preço, custo e volume.</li>
            <li>Gere a simulação — o motor cria lançamentos a débito e crédito equilibrados.</li>
            <li>Consulte o balancete, a razão e o relatório anual.</li>
        </ol>
        <form method="post" action="/simulacao/demo" data-confirm="Isto substitui dados existentes pela empresa demonstração NorteAtlântico. Continuar?">
            <?= csrf_field() ?>
            <button class="btn gold" type="submit">Carregar empresa demonstração + simular 2025</button>
        </form>
    </div>
<?php else: ?>
    <div class="grid grid-4">
        <div class="card kpi"><span>Áreas</span><strong><?= count($areas) ?></strong></div>
        <div class="card kpi"><span>Produtos</span><strong><?= count($products) ?></strong></div>
        <div class="card kpi"><span>Lançamentos <?= e((string) $year) ?></span><strong><?= (int) $entries ?></strong></div>
        <div class="card kpi"><span>Resultado líquido</span>
            <strong><?= $report ? money((float) $report['dr']['resultado_liquido'], $company['moeda']) : '—' ?></strong>
            <em><?= $report ? 'Margem ' . $report['kpis']['margem_liquida'] . '%' : 'Ainda sem simulação' ?></em>
        </div>
    </div>

    <?php if ($report): ?>
        <div class="grid grid-2" style="margin-top:16px">
            <div class="panel">
                <h2>Rendimentos vs gastos</h2>
                <canvas data-chart='<?= e(json_encode([
                    "type" => "bar",
                    "data" => [
                        "labels" => array_map(fn ($m) => substr(month_name((int) $m["mes"]), 0, 3), $report["mensal"]),
                        "datasets" => [
                            ["label" => "Rendimentos", "data" => array_column($report["mensal"], "rendimentos"), "backgroundColor" => "#1f6b52"],
                            ["label" => "Gastos", "data" => array_column($report["mensal"], "gastos"), "backgroundColor" => "#8f2d3c"],
                        ],
                    ],
                    "options" => ["responsive" => true, "plugins" => ["legend" => ["position" => "bottom"]]],
                ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
            </div>
            <div class="panel">
                <h2>Contribuição por área</h2>
                <table class="ledger">
                    <thead><tr><th>Área</th><th class="num">Rendimentos</th><th class="num">Margem</th></tr></thead>
                    <tbody>
                    <?php foreach ($report['areas'] as $area): ?>
                        <tr>
                            <td><?= e($area['codigo'] . ' · ' . $area['nome']) ?></td>
                            <td class="num"><?= money_cell((float) $area['rendimentos']) ?></td>
                            <td class="num"><?= e((string) $area['margem']) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="panel">
            <p>A empresa está definida, mas o exercício ainda não foi simulado.</p>
            <a class="btn gold" href="/simulacao">Abrir simulação</a>
        </div>
    <?php endif; ?>
<?php endif; ?>
