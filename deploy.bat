@echo off
title Travelpont Uticelok Plugin Deploy
color 0A
chcp 65001 >nul

echo.
echo ========================================
echo   TRAVELPONT UTICELOK PLUGIN DEPLOY
echo ========================================
echo.

cd /d "%~dp0"

REM ── Safe directory beallitas (Windows tulajdonos-utkozes megoldasa) ─────────
git config --global --add safe.directory "%CD:\=/%" >nul 2>&1

set /p COMMIT_MSG="Commit uzenet (Enter = 'Update'): "
if "%COMMIT_MSG%"=="" set COMMIT_MSG=Update

echo.
echo ========================================
echo   Git add...
echo ========================================
git add .

echo.
echo ========================================
echo   Git commit: %COMMIT_MSG%
echo ========================================
git commit -m "%COMMIT_MSG%"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [INFO] Nincs uj valtozas, nincs mit commitolni.
    echo.
    pause
    exit /b 0
)

echo.
echo ========================================
echo   Git push...
echo ========================================
git push

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ========================================
    echo   [HIBA] Push sikertelen!
    echo   Ellenorizd a fenti hibauzeneteket.
    echo ========================================
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo   DEPLOY SIKERES!
echo ========================================
echo.
echo [+] GitHub repo:
echo     https://github.com/Travelpont/travelpont-uticelok
echo.
echo [+] Kovetkezo lepes (ha nem all be automatikusan):
echo     cPanel - Git Version Control - Manage
echo     - Update from Remote
echo     - Deploy HEAD Commit
echo.
echo ========================================
echo.
pause
