param(
    [ValidateSet('Setup', 'Switch', 'Rollback')][string]$Action = 'Setup'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$currentCompose = Join-Path $root 'docker-compose.yml'
if ((Test-Path -LiteralPath $currentCompose) -and (Get-Content -Raw -LiteralPath $currentCompose) -match '(?m)^name:\s*labmania-realdata-') {
    throw 'Snapshot data asli lokal sedang dipakai. Setup/Switch/Rollback UAT ditolak; gunakan Docker Compose dan runbook README.realdata-8080.md agar data tidak terganti.'
}
$statePath = Join-Path $root 'laravel/local/.state.json'
$template = Join-Path $root 'laravel/local/compose.yaml'
$utf8 = New-Object System.Text.UTF8Encoding($false)
$nativeDocker = Get-Command docker -ErrorAction SilentlyContinue

function Invoke-Docker {
    param([string[]]$DockerArgs)
    if ($nativeDocker) { & $nativeDocker.Source @DockerArgs }
    else {
        $fontValue = $env:UAT_FONT_DIR
        $localEnv = Join-Path $root '.env'
        if (-not $fontValue -and (Test-Path -LiteralPath $localEnv)) {
            $fontLine = Get-Content -LiteralPath $localEnv | Where-Object { $_ -match '^UAT_FONT_DIR=' } | Select-Object -Last 1
            if ($fontLine) { $fontValue = $fontLine.Substring('UAT_FONT_DIR='.Length).Trim().Trim([char]34, [char]39) }
        }
        if ($fontValue -match '^[A-Za-z]:[/\\]') { $fontValue = Docker-Path $fontValue }
        if ($fontValue) { & wsl.exe -d Ubuntu-24.04 --exec env "UAT_FONT_DIR=$fontValue" docker @DockerArgs }
        else { & wsl.exe -d Ubuntu-24.04 --exec docker @DockerArgs }
    }
    if ($LASTEXITCODE -ne 0) { throw "Docker gagal (exit $LASTEXITCODE): $($DockerArgs[0])" }
}

function Docker-Path([string]$Path) {
    $full = [System.IO.Path]::GetFullPath($Path)
    if ($nativeDocker) { return $full.Replace('\', '/') }
    return '/mnt/' + $full.Substring(0, 1).ToLowerInvariant() + $full.Substring(2).Replace('\', '/')
}

function Write-New([string]$Path, [string]$Content) {
    $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::CreateNew)
    try {
        $bytes = $utf8.GetBytes($Content)
        $stream.Write($bytes, 0, $bytes.Length)
    } finally { $stream.Dispose() }
}

function Random-Base64([int]$Length) {
    $bytes = New-Object byte[] $Length
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $rng.GetBytes($bytes) } finally { $rng.Dispose() }
    return [Convert]::ToBase64String($bytes)
}

function Compose-Args([string]$File) {
    return @('compose', '--project-directory', (Docker-Path $root), '--env-file', (Docker-Path (Join-Path $root '.env')), '-p', 'labmania', '-f', (Docker-Path $File))
}

function Restore-Ci3 {
    if (-not (Test-Path -LiteralPath $statePath)) { throw 'State rollback tidak ditemukan; tidak ada layanan diubah.' }
    $saved = Get-Content -Raw -LiteralPath $statePath | ConvertFrom-Json
    $backupRoot = [IO.Path]::GetFullPath([string]$saved.backup)
    $allowed = [IO.Path]::GetFullPath((Join-Path $root '.artifacts/docker_laravel_local')) + [IO.Path]::DirectorySeparatorChar
    if (-not $backupRoot.StartsWith($allowed, [StringComparison]::OrdinalIgnoreCase)) { throw 'Lokasi backup di luar artifact fitur.' }
    $ci3 = Join-Path $backupRoot 'compose-ci3.yaml'
    $overlay = Join-Path $backupRoot 'rollback.override.yaml'
    if ((Get-FileHash $ci3 -Algorithm SHA256).Hash -ne $saved.composeHash) { throw 'Hash cadangan Compose CI3 berubah.' }
    $rollbackArgs = Compose-Args $ci3
    $merged = Invoke-Docker ($rollbackArgs + @('-f', (Docker-Path $overlay), 'config'))
    Invoke-Docker @('stop', 'labmania_web')
    Start-Sleep -Seconds 3
    [IO.File]::WriteAllText((Join-Path $root 'docker-compose.yml'), ($merged -join "`n"), $utf8)
    Invoke-Docker ((Compose-Args (Join-Path $root 'docker-compose.yml')) + @('up', '-d', '--no-deps', '--no-build', 'web'))
    Invoke-Docker @('exec', 'labmania_web', 'apache2ctl', 'configtest')
    $saved.active = 'ci3'
    [IO.File]::WriteAllText($statePath, ($saved | ConvertTo-Json), $utf8)
    Write-Host 'CI3 kembali di http://localhost:8080. Volume UAT tetap disimpan.'
}

Push-Location $root
try {
    Invoke-Docker @('info', '--format', '{{.OSType}}')
    Invoke-Docker @('compose', 'version')
    if ($Action -eq 'Rollback') { Restore-Ci3; return }

    if (-not (Test-Path -LiteralPath $statePath)) {
        if ($Action -eq 'Switch') { throw 'Jalankan Setup sebelum Switch.' }
        $labels = (Invoke-Docker @('inspect', '--format', '{{json .Config.Labels}}', 'labmania_web')) | ConvertFrom-Json
        if ($labels.'com.docker.compose.project' -ne 'labmania' -or $labels.'com.docker.compose.service' -ne 'web') { throw 'Container web bukan project labmania/web.' }
        $backup = Join-Path $root ('.artifacts/docker_laravel_local/backup_' + (Get-Date -Format 'dd-MM-yyyy_HH-mm-ss-fff'))
        New-Item -ItemType Directory -Path $backup | Out-Null
        Copy-Item -LiteralPath (Join-Path $root 'docker-compose.yml') -Destination (Join-Path $backup 'compose-ci3.yaml')
        Invoke-Docker @('cp', 'labmania_web:/etc/apache2/sites-available/000-default.conf', (Docker-Path (Join-Path $backup 'apache-ci3.conf')))
        $image = (Invoke-Docker @('inspect', '--format', '{{.Image}}', 'labmania_web')).Trim()
        $tag = 'labmania-ci3-rollback:' + (Get-Date -Format 'yyyyMMddHHmmss')
        Invoke-Docker @('image', 'tag', $image, $tag)
        $apache = Docker-Path (Join-Path $backup 'apache-ci3.conf')
        Write-New (Join-Path $backup 'rollback.override.yaml') ("services:`n  web:`n    image: $tag`n    volumes:`n      - type: bind`n        source: '$apache'`n        target: /etc/apache2/sites-available/000-default.conf`n        read_only: true`n")
        $state = [ordered]@{ backup = $backup; composeHash = (Get-FileHash (Join-Path $backup 'compose-ci3.yaml') -Algorithm SHA256).Hash; active = 'ci3' }
        Write-New $statePath ($state | ConvertTo-Json)
    }

    $envPath = Join-Path $root '.env'
    $envText = if (Test-Path -LiteralPath $envPath) { [IO.File]::ReadAllText($envPath) } else { '' }
    if ($envText -match '(?m)^COMPOSE_PROJECT_NAME=(?!labmania\s*$).+$') { throw 'COMPOSE_PROJECT_NAME existing berbeda; tidak diubah.' }
    $defaults = [ordered]@{
        COMPOSE_PROJECT_NAME = 'labmania'
        UAT_APP_KEY = 'base64:' + (Random-Base64 32)
        UAT_DB_PASSWORD = (Random-Base64 32)
        UAT_DB_ROOT_PASSWORD = (Random-Base64 32)
        UAT_FONT_DIR = (Docker-Path (Join-Path $env:WINDIR 'Fonts'))
    }
    foreach ($entry in $defaults.GetEnumerator()) {
        if ($envText -notmatch "(?m)^$($entry.Key)=.+$") { $envText += "`n$($entry.Key)=$($entry.Value)`n" }
    }
    if ($envText -match '(?m)^COMPOSE_(FILE|PROFILES)=') { throw 'COMPOSE_FILE/COMPOSE_PROFILES existing harus ditinjau sebelum setup.' }
    foreach ($font in @('times.ttf', 'timesbd.ttf')) {
        if (-not (Test-Path (Join-Path (Join-Path $env:WINDIR 'Fonts') $font))) { throw "Font $font belum tersedia." }
    }
    [IO.File]::WriteAllText($envPath, $envText, $utf8)
    $compose = Compose-Args $template
    Invoke-Docker ($compose + @('config', '--quiet'))
    if ($Action -eq 'Setup') {
        Invoke-Docker ($compose + @('build', 'web'))
        Invoke-Docker ($compose + @('up', '-d', '--wait', '--wait-timeout', '180', 'uat-db'))
        Invoke-Docker ($compose + @('run', '--rm', '--no-deps', '-T', 'web', 'chown', '-R', 'www-data:www-data', 'storage'))
        Invoke-Docker ($compose + @('run', '--rm', '--no-deps', '-T', '--user', 'www-data', 'web', 'php', 'artisan', 'labmania:uat'))
    }
    Invoke-Docker ($compose + @('run', '--rm', '--no-deps', '-T', '--user', 'www-data', 'web', 'php', 'artisan', 'labmania:uat', '--check'))
    try {
        Invoke-Docker @('stop', 'labmania_web')
        Start-Sleep -Seconds 3
        Copy-Item -LiteralPath $template -Destination (Join-Path $root 'docker-compose.yml')
        Invoke-Docker ((Compose-Args (Join-Path $root 'docker-compose.yml')) + @('up', '-d', '--no-deps', '--wait', '--wait-timeout', '120', 'web'))
    } catch {
        Write-Warning 'Pergantian web gagal; mencoba mengembalikan CI3.'
        Restore-Ci3
        throw
    }
    $state = Get-Content -Raw $statePath | ConvertFrom-Json
    $state.active = 'laravel'
    [IO.File]::WriteAllText($statePath, ($state | ConvertTo-Json), $utf8)
    Write-Host 'Laravel UAT: http://localhost:8080. Restart berikutnya: docker compose up -d.'
    Write-Host 'Password awal tampil sekali saat bootstrap pertama. Login UAT memakai username/password tanpa token atau TOTP.'
} finally { Pop-Location }
