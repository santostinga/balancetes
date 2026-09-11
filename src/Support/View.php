<?php

declare(strict_types=1);

namespace App\Support;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'layout'): void
    {
        $views = dirname(__DIR__, 2) . '/views';
        extract($data, EXTR_SKIP);
        ob_start();
        $file = $views . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vista em falta: ' . $template);
        }
        include $file;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        include $views . '/' . $layout . '.php';
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
