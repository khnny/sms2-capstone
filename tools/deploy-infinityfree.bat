@echo off
setlocal
echo SMS2 InfinityFree Deploy
echo Set SMS2_FTP_USER / SMS2_FTP_PASS / SMS2_DEPLOY_TOKEN in your environment.
echo.
if "%SMS2_FTP_PASS%"=="" set /p SMS2_FTP_PASS=Enter hosting account password: 
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-infinityfree-zip.ps1" -Password "%SMS2_FTP_PASS%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-infinityfree.ps1" -FtpPass "%SMS2_FTP_PASS%"
echo.
echo Open: https://YOUR_SITE/setup/deploy-db.php?token=YOUR_SMS2_DEPLOY_TOKEN
pause
