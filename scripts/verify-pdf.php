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

App\Support\Database::pdo();
$company = App\Models\Company::get();
if (!$company) {
    fwrite(STDERR, "Corra primeiro scripts/verify-demo.php\n");
    exit(1);
}

$year = (int) $company['exercicio'];
$tb = new App\Services\TrialBalanceService();
$trial = $tb->build($year);
$report = (new App\Services\AnnualReportService())->build(
    $trial,
    $tb->byArea($year),
    $tb->byProduct($year),
    $tb->monthlySeries($year)
);

$pdf = new App\Services\PdfService();
echo 'wkhtmltopdf=' . $pdf->binary() . PHP_EOL;

$out = $root . '/storage/tmp';
if (!is_dir($out)) {
    mkdir($out, 0777, true);
}

$docs = [
    'balancete' => [
        'html' => App\Support\View::toString('pdf/balancete', [
            'title' => 'Balancete',
            'company' => $company,
            'trial' => $trial,
            'year' => $year,
            'from' => 1,
            'to' => 12,
        ]),
        'options' => ['orientation' => 'Landscape', 'title' => $company['nome'], 'subtitle' => 'Balancete ' . $year],
    ],
    'relatorio' => [
        'html' => App\Support\View::toString('pdf/relatorio', [
            'title' => 'Relatório anual',
            'company' => $company,
            'year' => $year,
            'report' => $report,
            'trial' => $trial,
        ]),
        'options' => ['orientation' => 'Portrait', 'title' => $company['nome'], 'subtitle' => 'Relatório ' . $year],
    ],
];

foreach ($docs as $name => $doc) {
    $bytes = $pdf->render($doc['html'], $doc['options']);
    $file = $out . '/' . $name . '-check.pdf';
    file_put_contents($file, $bytes);
    $ok = str_starts_with($bytes, '%PDF');
    echo sprintf("%s bytes=%d magic=%s file=%s\n", $name, strlen($bytes), $ok ? 'yes' : 'no', $file);
    if (!$ok) {
        exit(1);
    }
}
