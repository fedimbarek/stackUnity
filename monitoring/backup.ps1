param(
    [string]$Destination = (Join-Path $PSScriptRoot 'backups')
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

if (-not (Test-Path -LiteralPath $Destination -PathType Container)) {
    New-Item -ItemType Directory -Path $Destination -Force | Out-Null
}
$destinationPath = (Resolve-Path -LiteralPath $Destination).Path

$timestamp = Get-Date -Format 'yyyyMMdd-HHmm'
$volumes = @('prometheus-data', 'grafana-data')
foreach ($volume in $volumes) {
    try {
        Invoke-Docker -Arguments @('volume', 'inspect', $volume) | Out-Null
    }
    catch {
        throw "Le volume Docker '$volume' n'existe pas. Démarrez d'abord la pile de monitoring."
    }

    $archiveName = '{0}-{1}.tar.gz' -f $volume.Replace('-data', ''), $timestamp
    $archivePath = Join-Path $destinationPath $archiveName
    $dockerCommand = "tar -czf '/backup/$archiveName' -C /data ."

    Invoke-Docker -Arguments @(
        'run',
        '--rm',
        '--volume', "${volume}:/data:ro",
        '--volume', "${destinationPath}:/backup",
        'alpine:3.20',
        'sh',
        '-c',
        $dockerCommand
    ) | Out-Null

    if (-not (Test-Path -LiteralPath $archivePath -PathType Leaf)) {
        throw "La sauvegarde de '$volume' n'a pas créé l'archive attendue : $archivePath"
    }
    Write-Host "Sauvegarde créée : $archivePath"

    $oldArchives = @(
        Get-ChildItem -LiteralPath $destinationPath -Filter "$($volume.Replace('-data', ''))-*.tar.gz" -File |
            Sort-Object LastWriteTime -Descending |
            Select-Object -Skip 7
    )
    foreach ($oldArchive in $oldArchives) {
        Remove-Item -LiteralPath $oldArchive.FullName -Force
        Write-Host "Ancienne sauvegarde supprimée : $($oldArchive.FullName)"
    }
}

Write-Host 'Sauvegarde des volumes Prometheus et Grafana terminée.'
