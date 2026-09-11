<?php

declare(strict_types=1);

namespace App\Services;

final class PdfService
{
    public function binary(): string
    {
        $candidates = array_filter([
            getenv('WKHTMLTOPDF') ?: null,
            dirname(__DIR__, 2) . '/bin/wkhtmltopdf.exe',
            'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            'C:\\Program Files (x86)\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            '/usr/local/bin/wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
        ]);

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'command -v';
        $found = trim((string) shell_exec($which . ' wkhtmltopdf 2>NUL'));
        if ($found !== '' && is_file(strtok($found, "\n"))) {
            return strtok($found, "\n");
        }

        throw new \RuntimeException(
            'wkhtmltopdf não foi encontrado. Instale-o ou defina a variável WKHTMLTOPDF com o caminho do executável.'
        );
    }

    /**
     * @param array{orientation?:string,title?:string,subtitle?:string} $options
     */
    public function render(string $html, array $options = []): string
    {
        $tmp = dirname(__DIR__, 2) . '/storage/tmp';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0777, true);
        }

        $id = bin2hex(random_bytes(8));
        $htmlFile = $tmp . '/doc-' . $id . '.html';
        $pdfFile = $tmp . '/doc-' . $id . '.pdf';
        $headerFile = $tmp . '/hdr-' . $id . '.html';
        $footerFile = $tmp . '/ftr-' . $id . '.html';

        $marginTop = (string) ($options['margin_top'] ?? '18mm');
        $marginBottom = (string) ($options['margin_bottom'] ?? '14mm');
        $marginLeft = (string) ($options['margin_left'] ?? '10mm');
        $marginRight = (string) ($options['margin_right'] ?? '10mm');

        file_put_contents($htmlFile, $html);

        $headerHtml = array_key_exists('header_html', $options)
            ? (string) $options['header_html']
            : $this->headerHtml(
                (string) ($options['title'] ?? 'Documento'),
                (string) ($options['subtitle'] ?? '')
            );
        $footerHtml = array_key_exists('footer_html', $options)
            ? (string) $options['footer_html']
            : $this->footerHtml();

        $cmd = [
            $this->binary(),
            '--quiet',
            '--encoding', 'UTF-8',
            '--page-size', 'A4',
            '--orientation', ($options['orientation'] ?? 'Portrait') === 'Landscape' ? 'Landscape' : 'Portrait',
            '--dpi', '96',
            '--print-media-type',
            '--enable-local-file-access',
            '--disable-smart-shrinking',
            '--no-outline',
            '--margin-top', $marginTop,
            '--margin-bottom', $marginBottom,
            '--margin-left', $marginLeft,
            '--margin-right', $marginRight,
        ];
        if ($headerHtml !== '') {
            file_put_contents($headerFile, $headerHtml);
            $cmd[] = '--header-html';
            $cmd[] = $headerFile;
            $cmd[] = '--header-spacing';
            $cmd[] = (string) ($options['header_spacing'] ?? '4');
        }
        if ($footerHtml !== '') {
            file_put_contents($footerFile, $footerHtml);
            $cmd[] = '--footer-html';
            $cmd[] = $footerFile;
            $cmd[] = '--footer-spacing';
            $cmd[] = (string) ($options['footer_spacing'] ?? '3');
        }
        $cmd[] = $htmlFile;
        $cmd[] = $pdfFile;

        try {
            $this->run($cmd);
            if (!is_file($pdfFile) || filesize($pdfFile) < 100) {
                throw new \RuntimeException('O wkhtmltopdf não produziu um PDF válido.');
            }
            $pdf = (string) file_get_contents($pdfFile);
        } finally {
            foreach ([$htmlFile, $pdfFile, $headerFile, $footerFile] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }

        return $pdf;
    }

    public function download(string $html, string $filename, array $options = []): never
    {
        set_time_limit(180);
        $pdf = $this->render($html, $options);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    /** @param list<string> $cmd */
    private function run(array $cmd): void
    {
        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $spec, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($proc)) {
            throw new \RuntimeException('Não foi possível iniciar o wkhtmltopdf.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        if ($code !== 0) {
            $hint = trim($stderr . ' ' . $stdout) ?: 'código ' . $code;
            throw new \RuntimeException('Falha no wkhtmltopdf: ' . $hint);
        }
    }

    private function headerHtml(string $title, string $subtitle): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subtitle = htmlspecialchars($subtitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { margin: 0; padding: 0 2mm; font-family: "Segoe UI", Calibri, Arial, sans-serif; color: #12263a; }
.bar { border-bottom: 1.5pt solid #a6844a; padding: 0 0 3px; overflow: hidden; }
.l { float: left; font-size: 9px; }
.r { float: right; font-size: 9px; text-align: right; }
.l strong, .r strong { display: block; font-size: 11px; }
</style>
</head>
<body>
<div class="bar">
    <div class="l"><strong>{$title}</strong>Simulador de balancete SNC</div>
    <div class="r"><strong>{$subtitle}</strong>Gerado em {$this->now()}</div>
</div>
</body>
</html>
HTML;
    }

    private function footerHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script>
function subst() {
    var vars = {};
    document.location.search.substring(1).split('&').forEach(function (pair) {
        var parts = pair.split('=', 2);
        vars[parts[0]] = decodeURIComponent(parts[1] || '');
    });
    var page = document.getElementById('page');
    var topage = document.getElementById('topage');
    if (page) page.textContent = vars.page || '';
    if (topage) topage.textContent = vars.topage || '';
}
</script>
<style>
body { margin: 0; padding: 2mm 2mm 0; font-family: "Segoe UI", Calibri, Arial, sans-serif; font-size: 8px; color: #6b6258; }
.bar { border-top: 0.6pt solid #d9d1c3; padding-top: 3px; overflow: hidden; }
.l { float: left; }
.r { float: right; }
</style>
</head>
<body onload="subst()">
<div class="bar">
    <div class="l">Documento de simulação — não substitui certificação legal de contas</div>
    <div class="r">Página <span id="page"></span> / <span id="topage"></span></div>
</div>
</body>
</html>
HTML;
    }

    private function now(): string
    {
        return htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
    }
}
