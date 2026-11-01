param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('prometheus-data', 'grafana-data')]
    [string]$Volume,

    [Parameter(Mandatory = $true)]
    [string]$Archive
)

$ErrorActionPreference = 'Stop'

function Invoke-Docker {
    param(
        [Parameter(Mandatory = $true)]
        [string[]]$Arguments
    )

    $output = & docker @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "La commande Docker a échoué : docker $($Arguments -join ' ')"
    }

    return $output
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw 'Docker CLI est introuvable. Installez Docker Desktop et ajoutez docker au PATH.'
}

try {
    Invoke-Docker -Arguments @('info', '--format', '{{.ServerVersion}}') | Out-Null
}
catch {
    throw 'Docker ne semble pas démarré. Lancez Docker Desktop puis réessayez.'
}

if (-not (Test-Path -LiteralPath $Archive -PathType Leaf)) {
    throw "Archive introuvable : $Archive"
}
$archivePath = (Resolve-Path -LiteralPath $Archive).Path

try {
    Invoke-Docker -Arguments @('volume', 'inspect', $Volume) | Out-Null
}
catch {
    throw "Le volume Docker '$Volume' n'existe pas. Démarrez d'abord la pile de monitoring."
}

Invoke-Docker -Arguments @(
    'run',
    '--rm',
    '--volume', "${archivePath}:/backup/archive.tar.gz:ro",
    'alpine:3.20',
    'sh',
    '-c',
    'tar -tzf /backup/archive.tar.gz >/dev/null'
) | Out-Null

$service = if ($Volume -eq 'prometheus-data') { 'prometheus' } else { 'grafana' }
$owner = if ($Volume -eq 'prometheus-data') { '65534:65534' } else { '472:0' }
$restoreCommand = 'find /data -mindepth 1 -maxdepth 1 -exec rm -rf {} \; && tar -xzf /backup/archive.tar.gz -C /data && chown -R {0} /data' -f $owner
$confirmation = Read-Host "La restauration va écraser toutes les données du volume '$Volume'. Tapez RESTAURER pour continuer"
if ($confirmation -cne 'RESTAURER') {
    throw 'Restauration annulée : confirmation explicite incorrecte.'
}

$composeFile = Join-Path $PSScriptRoot 'docker-compose.monitoring.yml'
$serviceStopped = $false
try {
    Invoke-Docker -Arguments @('compose', '-f', $composeFile, 'stop', $service) | Out-Null
    $serviceStopped = $true

    Invoke-Docker -Arguments @(
        'run',
        '--rm',
        '--volume', "${Volume}:/data",
        '--volume', "${archivePath}:/backup/archive.tar.gz:ro",
        'alpine:3.20',
        'sh',
        '-c',
        $restoreCommand
    ) | Out-Null

    Write-Host "Données restaurées dans '$Volume' depuis '$archivePath'."
}
finally {
    if ($serviceStopped) {
        Invoke-Docker -Arguments @('compose', '-f', $composeFile, 'start', $service) | Out-Null
        Write-Host "Service '$service' relancé."
    }
}
