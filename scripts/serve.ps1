$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
Write-Host "Simulador de balancete em http://127.0.0.1:8080"
& $php -S 127.0.0.1:8080 -t public public\router.php
