$ErrorActionPreference = "Continue"

Write-Host "1/5 Docker Desktop..." -ForegroundColor Cyan
docker info *> $null
if ($LASTEXITCODE -ne 0) { Write-Host "Docker Desktop n'est pas demarre. Lance-le puis relance ce script." -ForegroundColor Red; exit 1 }

Write-Host "2/5 vm.max_map_count pour SonarQube (WSL2)..." -ForegroundColor Cyan
wsl -d docker-desktop sysctl -w vm.max_map_count=262144

Write-Host "3/5 Reseau app-network..." -ForegroundColor Cyan
docker network inspect app-network *> $null
if ($LASTEXITCODE -ne 0) { docker network create app-network }

Write-Host "4/5 Jenkins + MySQL + SonarQube..." -ForegroundColor Cyan
docker compose -f devops/docker-compose.yml up -d --build

Write-Host "5/5 Prometheus + Grafana + cAdvisor..." -ForegroundColor Cyan
if (-not (Test-Path monitoring/.env)) { Copy-Item monitoring/.env.example monitoring/.env }
docker compose -f monitoring/docker-compose.monitoring.yml --env-file monitoring/.env up -d

Write-Host ""
Write-Host "Jenkins    -> http://localhost:8082"
Write-Host "SonarQube  -> http://localhost:9000   (admin / admin au 1er demarrage)"
Write-Host "Grafana    -> http://localhost:3000   (admin / mot de passe dans monitoring/.env)"
Write-Host "Prometheus -> http://localhost:9090"
Write-Host "cAdvisor   -> http://localhost:8081"
Write-Host ""
Write-Host "Mot de passe initial Jenkins :"
Start-Sleep -Seconds 20
docker exec jenkins-laravel cat /var/jenkins_home/secrets/initialAdminPassword