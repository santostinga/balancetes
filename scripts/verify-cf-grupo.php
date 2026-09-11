<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/src/Support/helpers.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $path = $root . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$gen = new App\Services\CfGrupoBalancete();
foreach ([2024, 2025] as $year) {
    $data = $gen->build($year);
    $t = $data['totais'];
    $p = $gen->posicaoFinanceira($data);
    echo sprintf(
        "%d linhas=%d volume=%s D=%s C=%s SD=%s SC=%s eq=%s\n",
        $year,
        $data['linhas'],
        App\Services\CfGrupoBalancete::format((int) $data['volume']),
        App\Services\CfGrupoBalancete::format((int) $t['d']),
        App\Services\CfGrupoBalancete::format((int) $t['c']),
        App\Services\CfGrupoBalancete::format((int) $t['sd']),
        App\Services\CfGrupoBalancete::format((int) $t['sc']),
        $data['equilibrado'] ? 'sim' : 'NAO'
    );
    echo sprintf(
        "  BS activos=%s pass+CP=%s fecha=%s RL=%s\n",
        App\Services\CfGrupoBalancete::format((int) $p['total_activos']),
        App\Services\CfGrupoBalancete::format((int) $p['total_passivos_capital']),
        $p['fecha'] ? 'sim' : 'NAO',
        App\Services\CfGrupoBalancete::format((int) $p['resultado'])
    );
    if (!$p['fecha']) {
        foreach (['tangiveis', 'clientes', 'outros_activos', 'inventarios', 'caixa_bancos', 'capital', 'transitados', 'resultado', 'emprestimos', 'impostos', 'fornecedores', 'outras_pagar'] as $k) {
            echo '    ' . $k . '=' . App\Services\CfGrupoBalancete::format((int) $p[$k]) . PHP_EOL;
        }
        echo '    diff=' . App\Services\CfGrupoBalancete::format((int) $p['total_activos'] - (int) $p['total_passivos_capital']) . PHP_EOL;
    }
}
