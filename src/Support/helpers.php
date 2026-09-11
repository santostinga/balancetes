<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(?float $value, string $currency = 'EUR'): string
{
    $amount = number_format((float) $value, 2, ',', '.');

    return match ($currency) {
        'AOA' => $amount . ' Kz',
        'USD' => '$ ' . $amount,
        default => $amount . ' €',
    };
}

function money_cell(?float $value): string
{
    if ($value === null || abs((float) $value) < 0.005) {
        return '';
    }

    return number_format((float) $value, 2, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['_csrf'] ?? '', (string) $token)) {
        http_response_code(419);
        exit('Sessão expirada. Recarregue a página.');
    }
}

function app_base(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(dirname($script), '/');
    if ($dir === '' || $dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }

    return $dir;
}

function url(string $path = '/'): string
{
    $query = '';
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }
    $path = '/' . ltrim($path, '/');
    $base = app_base();
    if ($path === '/') {
        return ($base === '' ? '/' : $base . '/') . $query;
    }

    return $base . $path . $query;
}

function redirect(string $path): never
{
    if (!preg_match('#^https?://#i', $path)) {
        $path = url($path);
    }
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function month_name(int $month): string
{
    $names = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    return $names[$month] ?? (string) $month;
}

function account_class_name(int $class): string
{
    return match ($class) {
        1 => 'Meios líquidos',
        2 => 'Contas a receber e a pagar',
        3 => 'Inventários e activos biológicos',
        4 => 'Investimentos',
        5 => 'Capital, reservas e resultados transitados',
        6 => 'Gastos',
        7 => 'Rendimentos',
        8 => 'Resultados',
        default => 'Classe ' . $class,
    };
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/.');

    if ($base !== '' && $base !== '/' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }

    return '/' . trim($uri, '/');
}
