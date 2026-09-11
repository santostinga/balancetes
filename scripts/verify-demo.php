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

$db = $root . '/storage/database.sqlite';
if (is_file($db)) {
    unlink($db);
}

App\Support\Database::pdo();
$engine = new App\Services\SimulationEngine();
$engine->loadDemo();
$result = $engine->generate([
    'exercicio' => 2025,
    'seed' => 2025,
    'variabilidade' => 0.12,
    'incluir_iva' => '1',
    'taxa_recebimento' => 0.82,
    'taxa_pagamento' => 0.75,
]);

$tb = new App\Services\TrialBalanceService();
$trial = $tb->build(2025);
$report = (new App\Services\AnnualReportService())->build(
    $trial,
    $tb->byArea(2025),
    $tb->byProduct(2025),
    $tb->monthlySeries(2025)
);

$lines = App\Support\Database::run('SELECT ROUND(SUM(debito),2) d, ROUND(SUM(credito),2) c FROM journal_lines')->fetch();

echo "lancamentos={$result['lancamentos']}\n";
echo "rendimentos={$result['rendimentos']}\n";
echo "gastos={$result['gastos']}\n";
echo "rl={$result['resultado_liquido']}\n";
echo "diario_d={$lines['d']} diario_c={$lines['c']}\n";
echo "tb_balanced=" . ($trial['balanced'] ? 'yes' : 'no') . "\n";
echo "tb_sal_d={$trial['totals']['sal_d']} tb_sal_c={$trial['totals']['sal_c']}\n";
echo "activo={$report['balanco']['activo']} passivo_capital={$report['balanco']['passivo_capital']}\n";
echo "gap=" . round($report['balanco']['activo'] - $report['balanco']['passivo_capital'], 2) . "\n";
