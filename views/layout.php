<?php
$company = $company ?? \App\Models\Company::get();
$ok = flash('ok');
$erro = flash('erro');
$path = request_path();
$nav = [
    ['/', 'Painel', 'grid'],
    ['/empresa', 'Empresa', 'building'],
    ['/areas', 'Áreas de serviço', 'layers'],
    ['/produtos', 'Produtos e margens', 'box'],
    ['/plano-contas', 'Plano de contas', 'book'],
    ['/simulacao', 'Simulação', 'play'],
    ['/diario', 'Diário', 'list'],
    ['/balancete', 'Balancete', 'table'],
    ['/razao', 'Razão', 'rows'],
    ['/relatorio-anual', 'Relatório anual', 'file'],
    ['/cf-grupo', 'CF GRUPO SA', 'pdf'],
];
function nav_active(string $path, string $href): bool {
    if ($href === '/') {
        return $path === '/';
    }
    return $path === $href || str_starts_with($path, $href . '/');
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Balancete') . ' · Simulador SNC') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
</head>
<body>
<div class="shell">
    <aside class="rail">
        <div class="brand">
            <span class="brand-mark">SNC</span>
            <div>
                <strong>Balancete</strong>
                <small>Simulador anual</small>
            </div>
        </div>
        <nav>
            <?php foreach ($nav as [$href, $label]): ?>
                <a href="<?= e(url($href)) ?>" class="<?= nav_active($path, $href) ? 'is-on' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="rail-foot">
            <?php if ($company): ?>
                <div class="entity"><?= e($company['nome']) ?></div>
                <div class="meta">Exercício <?= e((string) $company['exercicio']) ?> · <?= e($company['moeda']) ?></div>
            <?php else: ?>
                <div class="meta">Configure a empresa para começar</div>
            <?php endif; ?>
        </div>
    </aside>
    <main class="stage">
        <?php if ($ok): ?><div class="flash ok"><?= e($ok) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="flash erro"><?= e($erro) ?></div><?php endif; ?>
        <?= $content ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('/assets/app.js')) ?>"></script>
</body>
</html>
