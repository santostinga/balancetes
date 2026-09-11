<?php

declare(strict_types=1);

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base = rtrim(dirname($script), '/');
if ($base === '' || $base === '.' || $base === '\\') {
    $base = '';
}

header('Location: ' . $base . '/public/', true, 302);
exit;
