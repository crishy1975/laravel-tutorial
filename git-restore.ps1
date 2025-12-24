<#
.SYNOPSIS
    Git Restore - Zeigt Commits und stellt alte Stände wieder her
    
.USAGE
    .\git-restore.ps1              # Zeigt die letzten 20 Commits
    .\git-restore.ps1 -Count 50    # Zeigt die letzten 50 Commits
#>

param(
    [int]$Count = 20
)

$ProjectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ProjectRoot

Clear-Host
Write-Host ""
Write-Host "  +========================================+" -ForegroundColor Yellow
Write-Host "  |   Git Restore - Commits anzeigen      |" -ForegroundColor Yellow
Write-Host "  +========================================+" -ForegroundColor Yellow
Write-Host ""

# Commits holen
$commits = git log --oneline -n $Count 2>$null

if (-not $commits) {
    Write-Host "  Keine Commits gefunden." -ForegroundColor Red
    pause
    exit
}

Write-Host "  Letzte $Count Commits:" -ForegroundColor White
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host ""

$i = 1
$commitList = @()

foreach ($line in $commits) {
    $hash = $line.Substring(0, 7)
    $msg = $line.Substring(8)
    $commitList += $hash
    
    # Farbcodierung für Auto-Backups
    if ($msg -match "Auto-Backup") {
        Write-Host "  [$i] " -NoNewline -ForegroundColor DarkGray
        Write-Host "$hash" -NoNewline -ForegroundColor Cyan
        Write-Host " $msg" -ForegroundColor Gray
    }
    else {
        Write-Host "  [$i] " -NoNewline -ForegroundColor DarkGray
        Write-Host "$hash" -NoNewline -ForegroundColor Green
        Write-Host " $msg" -ForegroundColor White
    }
    $i++
}

Write-Host ""
Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  Optionen:" -ForegroundColor White
Write-Host "    [Nummer]  Zu diesem Commit wechseln" -ForegroundColor Cyan
Write-Host "    [D]       Diff zum letzten Commit anzeigen" -ForegroundColor Cyan
Write-Host "    [Q]       Beenden" -ForegroundColor Cyan
Write-Host ""

$selection = Read-Host "  Auswahl"

switch ($selection.ToUpper()) {
    'Q' { exit }
    'D' {
        Write-Host ""
        Write-Host "  Aenderungen seit letztem Commit:" -ForegroundColor Yellow
        Write-Host "  -----------------------------------------" -ForegroundColor DarkGray
        git diff --stat
        Write-Host ""
        pause
    }
    default {
        if ($selection -match '^\d+$') {
            $index = [int]$selection - 1
            if ($index -ge 0 -and $index -lt $commitList.Count) {
                $targetHash = $commitList[$index]
                
                Write-Host ""
                Write-Host "  Ausgewaehlter Commit: $targetHash" -ForegroundColor Yellow
                Write-Host ""
                
                # Commit-Details anzeigen
                Write-Host "  Geaenderte Dateien:" -ForegroundColor White
                git show --stat --oneline $targetHash
                Write-Host ""
                
                Write-Host "  Was moechtest du tun?" -ForegroundColor White
                Write-Host "    [1] Einzelne Datei aus diesem Commit wiederherstellen" -ForegroundColor Cyan
                Write-Host "    [2] Komplett zu diesem Commit zurueckkehren (VORSICHT!)" -ForegroundColor Red
                Write-Host "    [3] Nur anschauen (checkout ohne Aenderung)" -ForegroundColor Cyan
                Write-Host "    [Q] Abbrechen" -ForegroundColor Cyan
                Write-Host ""
                
                $action = Read-Host "  Auswahl"
                
                switch ($action) {
                    '1' {
                        Write-Host ""
                        Write-Host "  Dateien in diesem Commit:" -ForegroundColor White
                        $files = git show --name-only --oneline $targetHash | Select-Object -Skip 1
                        $fi = 1
                        $fileList = @()
                        foreach ($f in $files) {
                            if ($f.Trim()) {
                                Write-Host "    [$fi] $f" -ForegroundColor Gray
                                $fileList += $f.Trim()
                                $fi++
                            }
                        }
                        Write-Host ""
                        $fileNum = Read-Host "  Datei-Nummer"
                        $fileIndex = [int]$fileNum - 1
                        
                        if ($fileIndex -ge 0 -and $fileIndex -lt $fileList.Count) {
                            $targetFile = $fileList[$fileIndex]
                            Write-Host ""
                            Write-Host "  Stelle wieder her: $targetFile" -ForegroundColor Yellow
                            $confirm = Read-Host "  Bestaetigen? (j/n)"
                            if ($confirm -eq 'j') {
                                git checkout $targetHash -- $targetFile
                                Write-Host "  [OK] Datei wiederhergestellt!" -ForegroundColor Green
                            }
                        }
                    }
                    '2' {
                        Write-Host ""
                        Write-Host "  WARNUNG: Alle nicht-committeten Aenderungen gehen verloren!" -ForegroundColor Red
                        $confirm = Read-Host "  Wirklich fortfahren? (ja/nein)"
                        if ($confirm -eq 'ja') {
                            git checkout $targetHash
                            Write-Host "  [OK] Auf Commit $targetHash gewechselt!" -ForegroundColor Green
                            Write-Host "  Hinweis: Du bist jetzt im 'detached HEAD' Modus." -ForegroundColor Yellow
                            Write-Host "  Zurueck zum neuesten Stand: git checkout main" -ForegroundColor Yellow
                        }
                    }
                    '3' {
                        Write-Host ""
                        git show $targetHash
                    }
                }
                
                Write-Host ""
                pause
            }
        }
    }
}
