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
$pdf = new App\Services\PdfService();
$dir = $root . '/storage/tmp';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$html = $gen->documentHtml(true);
$bytes = $pdf->render($html, [
    'orientation' => 'Portrait',
    'margin_top' => '12mm',
    'margin_right' => '10mm',
    'margin_bottom' => '10mm',
    'margin_left' => '10mm',
    'header_spacing' => '2',
    'footer_spacing' => '0',
    'header_html' => $gen->headerHtml(),
    'footer_html' => '',
]);
$file = $dir . '/balancete-cf-grupo-2024-2025.pdf';
file_put_contents($file, $bytes);
$pages = 0;
if (preg_match('/\/N\s+(\d+)/', $bytes, $n)) {
    $pages = (int) $n[1];
} elseif (preg_match('/\/Type\s*\/Pages\b.*?\/Count\s+(\d+)/s', $bytes, $n)) {
    $pages = (int) $n[1];
} else {
    preg_match_all('/\/Type\s*\/Page[^s]/', $bytes, $m);
    $pages = count($m[0]);
}
echo 'bytes=' . strlen($bytes) . ' pages=' . $pages . ' file=' . $file . PHP_EOL;
