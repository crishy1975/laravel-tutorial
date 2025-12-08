@echo off
REM Startet das PowerShell Deploy-Script
REM Doppelklick auf diese Datei zum Deployen!

PowerShell -ExecutionPolicy Bypass -File "%~dp0deploy.ps1"
pause
