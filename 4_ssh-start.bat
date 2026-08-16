@echo off
REM Startet eine SSH-Session zu Hostinger
REM Doppelklick zum Verbinden!

PowerShell -ExecutionPolicy Bypass -File "%~dp0ssh.ps1"
