<#
Wrapper PowerShell para executar os testes automatizados PHP
Uso: abra PowerShell e execute:
    php .\scripts\automated_tests.php
Ou use este wrapper que busca php no PATH:
    .\scripts\run_tests.ps1
#>

$php = 'php'
if (-not (Get-Command $php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP não encontrado no PATH. Abra XAMPP Shell ou adicione PHP ao PATH."; exit 1
}

& $php (Join-Path $PSScriptRoot 'automated_tests.php')
