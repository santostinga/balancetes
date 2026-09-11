<?php
$css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/pdf.css');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title><?= e($title ?? 'Documento') ?></title>
    <style><?= $css ?></style>
</head>
<body>
<?= $content ?>
</body>
</html>
