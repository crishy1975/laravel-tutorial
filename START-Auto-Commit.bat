@echo off
title Git Auto-Commit - UschiWeb
chcp 65001 >nul 2>&1

:: ============================================
:: Versuche das Projektverzeichnis zu finden
:: Prioritaet: 1) Eigener Ordner  2) Nachfragen
:: ============================================

:: 1) Pruefen ob die .bat im Projektordner liegt (neben .git)
set "PROJECT_DIR=%~dp0"
if exist "%PROJECT_DIR%.git" (
    echo [OK] Projekt gefunden: %PROJECT_DIR%
    goto :START
)

:: 2) Pruefen ob git-auto-commit.ps1 im gleichen Ordner liegt
if exist "%PROJECT_DIR%git-auto-commit.ps1" (
    echo [OK] Script gefunden in: %PROJECT_DIR%
    goto :START
)

:: 3) Bekannte Pfade pruefen
for %%P in (
    "C:\Users\Christian Resch\Documents\entwicklung\laravel-tutorial"
    "C:\Users\Christian\Documents\entwicklung\laravel-tutorial"
    "C:\Users\Christian Resch\Documents\entwicklung\php\uschiWeb2\uschiWeb2"
    "C:\Users\Christian\Documents\entwicklung\php\uschiWeb2\uschiWeb2"
) do (
    if exist "%%~P\.git" (
        set "PROJECT_DIR=%%~P\"
        echo [OK] Projekt gefunden: %%~P
        goto :START
    )
)

:: 4) Nichts gefunden - User fragen
echo.
echo  ============================================
echo   Projektordner nicht gefunden!
echo  ============================================
echo.
echo  Bitte den Pfad zum Git-Projekt eingeben:
echo  (z.B. C:\Users\Christian\Documents\entwicklung\laravel-tutorial)
echo.
set /p "PROJECT_DIR=  Pfad: "

:: Backslash am Ende sicherstellen
if not "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR%\"

:: Pruefen ob gueltig
if not exist "%PROJECT_DIR%.git" (
    echo.
    echo  [FEHLER] Kein Git-Repository in: %PROJECT_DIR%
    echo.
    pause
    exit /b 1
)

:START
:: In Projektordner wechseln
cd /d "%PROJECT_DIR%"

:: Pruefen ob PowerShell-Script vorhanden
if not exist "%PROJECT_DIR%git-auto-commit.ps1" (
    echo.
    echo  [FEHLER] git-auto-commit.ps1 nicht gefunden in:
    echo  %PROJECT_DIR%
    echo.
    echo  Bitte die Datei dorthin kopieren.
    pause
    exit /b 1
)

:: Script starten
echo.
echo  Starte Auto-Commit in: %PROJECT_DIR%
echo.
powershell -ExecutionPolicy Bypass -File "%PROJECT_DIR%git-auto-commit.ps1" -Minutes 1
pause
