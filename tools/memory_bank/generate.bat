@echo off
REM Regenerate Obsidian memory bank (Windows XAMPP)
cd /d "%~dp0..\.."
c:\xampp\php\php.exe tools\memory_bank\generate_all.php
exit /b %ERRORLEVEL%
