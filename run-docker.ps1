param(
    [switch]$Fresh
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if ($Fresh) {
    docker compose down -v --remove-orphans

    docker ps -a --filter "name=payroll" --format "{{.Names}}" | ForEach-Object {
        if ($_ -and $_.Trim() -ne "") { docker rm -f $_ }
    }

    docker volume ls -q | Where-Object { $_ -like "*payroll*" } | ForEach-Object {
        if ($_ -and $_.Trim() -ne "") { docker volume rm $_ }
    }
}

docker compose up -d --build
docker compose ps

Write-Host ""
Write-Host "Aplikasi: http://localhost:8000"
Write-Host "Mailpit  : http://localhost:8025"
