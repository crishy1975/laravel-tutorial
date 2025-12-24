<#
.SYNOPSIS
    Git Auto-Commit - Simpel & Effektiv
    
.DESCRIPTION
    Erstellt automatisch Commits wenn Änderungen vorhanden sind.
    Läuft bis du Ctrl+C drückst oder das Fenster schließt.
    
.USAGE
    .\git-auto-commit.ps1                    # Standard: alle 1 Minute
    .\git-auto-commit.ps1 -Minutes 5         # Alle 5 Minuten
    .\git-auto-commit.ps1 -Minutes 0.5       # Alle 30 Sekunden
#>

param(
    [double]$Minutes = 1
)

$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectRoot

# Git-Check
if (-not (Test-Path ".git")) {
    Write-Host "FEHLER: Kein Git-Repository!" -ForegroundColor Red
    Write-Host "Fuehre erst aus: git init" -ForegroundColor Yellow
    pause
    exit 1
}

Clear-Host
Write-Host ""
Write-Host "  +========================================+" -ForegroundColor Cyan
Write-Host "  |   Git Auto-Commit laeuft...           |" -ForegroundColor Cyan
Write-Host "  +========================================+" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Intervall: $Minutes Minute(n)" -ForegroundColor White
Write-Host "  Projekt:   $ProjectRoot" -ForegroundColor Gray
Write-Host ""
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host "  Ctrl+C oder Fenster schliessen zum Beenden" -ForegroundColor Yellow
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host ""

$commits = 0

while ($true) {
    $now = Get-Date -Format "HH:mm:ss"
    
    # Aenderungen pruefen
    $changes = git status --porcelain 2>$null
    
    if ($changes) {
        $fileCount = ($changes | Measure-Object).Count
        $timestamp = Get-Date -Format "dd.MM.yyyy HH:mm"
        
        # Stage & Commit
        git add -A 2>$null
        $result = git commit -m "Auto-Backup $timestamp" 2>&1
        
        if ($LASTEXITCODE -eq 0) {
            $commits++
            Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
            Write-Host "[OK]" -NoNewline -ForegroundColor Green
            Write-Host " Commit #$commits ($fileCount Dateien)" -ForegroundColor White
        }
    }
    else {
        Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
        Write-Host "- Keine Aenderungen" -ForegroundColor DarkGray
    }
    
    # Warten
    Start-Sleep -Seconds ([int]($Minutes * 60))
}
