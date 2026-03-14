<#
.SYNOPSIS
    Git Auto-Commit & Sync - Simpel & Effektiv
    
.DESCRIPTION
    Erstellt automatisch Commits wenn Änderungen vorhanden sind.
    Synchronisiert mit dem Remote-Repository (pull & push).
    Läuft bis du Ctrl+C drückst oder das Fenster schließt.
    
.USAGE
    .\git-auto-commit.ps1                    # Standard: alle 1 Minute, mit Sync
    .\git-auto-commit.ps1 -Minutes 5         # Alle 5 Minuten
    .\git-auto-commit.ps1 -Minutes 0.5       # Alle 30 Sekunden
    .\git-auto-commit.ps1 -NoSync            # Ohne Push/Pull (nur lokal)
#>

param(
    [double]$Minutes = 1,
    [switch]$NoSync
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

# Remote-Check
$hasRemote = $false
$remoteName = ""
$branchName = ""

if (-not $NoSync) {
    $remotes = git remote 2>$null
    if ($remotes) {
        $hasRemote = $true
        $remoteName = ($remotes | Select-Object -First 1).Trim()
        
        # Aktuellen Branch ermitteln
        $branchName = (git branch --show-current 2>$null).Trim()
        if (-not $branchName) {
            $branchName = "main"
        }
    }
    else {
        Write-Host ""
        Write-Host "  [WARNUNG] Kein Remote konfiguriert!" -ForegroundColor Yellow
        Write-Host "  Sync deaktiviert. Nur lokale Commits." -ForegroundColor Yellow
        Write-Host "  Remote hinzufuegen: git remote add origin <URL>" -ForegroundColor DarkGray
        Write-Host ""
        $NoSync = $true
    }
}

# Sync-Modus bestimmen
if ($NoSync) {
    $syncLabel = "AUS (nur lokal)"
    $syncColor = "Yellow"
}
else {
    $syncLabel = "AN  ($remoteName/$branchName)"
    $syncColor = "Green"
}

Clear-Host
Write-Host ""
Write-Host "  +========================================+" -ForegroundColor Cyan
Write-Host "  |   Git Auto-Commit & Sync laeuft...    |" -ForegroundColor Cyan
Write-Host "  +========================================+" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Intervall: $Minutes Minute(n)" -ForegroundColor White
Write-Host "  Projekt:   $ProjectRoot" -ForegroundColor Gray
Write-Host "  Sync:      " -NoNewline -ForegroundColor White
Write-Host "$syncLabel" -ForegroundColor $syncColor
Write-Host ""
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host "  Ctrl+C oder Fenster schliessen zum Beenden" -ForegroundColor Yellow
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host ""

$commits = 0
$pushes = 0
$pulls = 0

while ($true) {
    $now = Get-Date -Format "HH:mm:ss"
    
    # === PULL: Aenderungen vom Remote holen ===
    if ($hasRemote -and -not $NoSync) {
        $pullResult = git pull $remoteName $branchName 2>&1
        $pullText = $pullResult -join " "
        
        if ($LASTEXITCODE -ne 0) {
            # Pull-Fehler (z.B. Merge-Konflikt)
            Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
            Write-Host "[KONFLIKT]" -NoNewline -ForegroundColor Red
            Write-Host " Pull fehlgeschlagen - bitte manuell loesen!" -ForegroundColor Yellow
            Write-Host "           $pullText" -ForegroundColor DarkGray
        }
        elseif ($pullText -notmatch "Already up to date" -and $pullText -notmatch "Bereits aktuell") {
            $pulls++
            Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
            Write-Host "[PULL]" -NoNewline -ForegroundColor Magenta
            Write-Host " Aenderungen vom Remote geholt (#$pulls)" -ForegroundColor White
        }
    }
    
    # === COMMIT: Lokale Aenderungen committen ===
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
            Write-Host "[COMMIT]" -NoNewline -ForegroundColor Green
            Write-Host " #$commits ($fileCount Dateien)" -ForegroundColor White
            
            # === PUSH: Zum Remote hochladen ===
            if ($hasRemote -and -not $NoSync) {
                $pushResult = git push $remoteName $branchName 2>&1
                
                if ($LASTEXITCODE -eq 0) {
                    $pushes++
                    Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
                    Write-Host "[PUSH]" -NoNewline -ForegroundColor Blue
                    Write-Host " Hochgeladen (#$pushes)" -ForegroundColor White
                }
                else {
                    Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
                    Write-Host "[FEHLER]" -NoNewline -ForegroundColor Red
                    Write-Host " Push fehlgeschlagen!" -ForegroundColor Yellow
                    $pushError = $pushResult -join " "
                    Write-Host "           $pushError" -ForegroundColor DarkGray
                }
            }
        }
    }
    else {
        Write-Host "  [$now] " -NoNewline -ForegroundColor DarkGray
        Write-Host "- Keine Aenderungen" -ForegroundColor DarkGray
    }
    
    # Warten
    Start-Sleep -Seconds ([int]($Minutes * 60))
}
