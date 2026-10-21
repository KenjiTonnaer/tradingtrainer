$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
$artisanPath = Join-Path $projectPath 'artisan'
$phpPath = Join-Path $env:USERPROFILE '.config\herd\bin\php84\php.exe'
if (-not (Test-Path -LiteralPath $phpPath)) {
    $phpPath = (Get-Command php -ErrorAction Stop).Source
}
$running = @(Get-CimInstance Win32_Process -Filter "name = 'php.exe'" | Where-Object {
    $_.CommandLine -and $_.CommandLine.Contains('schedule:work') -and $_.CommandLine.Contains($artisanPath)
})
if ($running.Count -gt 0) {
    Write-Output "TradingTrainer scheduler draait al (PID $($running[0].ProcessId))."
    exit 0
}
$process = Start-Process -FilePath $phpPath -ArgumentList @('"' + $artisanPath + '"', 'schedule:work', '--no-interaction') -WorkingDirectory $projectPath -WindowStyle Hidden -RedirectStandardOutput (Join-Path $projectPath 'storage\logs\trading-scheduler.log') -RedirectStandardError (Join-Path $projectPath 'storage\logs\trading-scheduler-error.log') -PassThru
Write-Output "TradingTrainer scheduler gestart (PID $($process.Id))."
