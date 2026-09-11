# Abre o gerador no Apache do Laragon.
$local = "http://127.0.0.1/balancetes/public/cf-grupo"
Start-Process $local
Write-Host "Aberto: $local"
Write-Host "No menu do Laragon: www -> balancetes"
Write-Host "Pretty URL (depois de Reload no Laragon): http://balancetes.test/cf-grupo"
