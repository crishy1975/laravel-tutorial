@echo off
REM Startet das PowerShell Migrations-Script
REM Doppelklick auf diese Datei zum Migrieren!

PowerShell -ExecutionPolicy Bypass -File "%~dp0migrate.ps1"
pause
