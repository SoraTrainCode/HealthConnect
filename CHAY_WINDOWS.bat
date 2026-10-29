@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"
set "HC_PHP=C:\xampp\php\php.exe"
if not exist "%HC_PHP%" (
  echo Khong tim thay PHP tai C:\xampp\php\php.exe. Sua HC_PHP trong file nay theo may ban.
  pause
  exit /b 1
)
"%HC_PHP%" website-yte-suckhoe\tools\doctor.php
if errorlevel 1 (
  echo Hay hoan thanh cai dat theo README.md, sau do chay lai.
  pause
  exit /b 1
)
cd website-yte-suckhoe
echo Mo http://localhost:8000 . Nhan Ctrl+C de dung server.
"%HC_PHP%" -S localhost:8000 -t . tools/router.php
pause
