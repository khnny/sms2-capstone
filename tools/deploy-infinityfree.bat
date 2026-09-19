@echo off
setlocal
echo SMS2 InfinityFree Deploy
echo Account: if0_42794375 / bestlinksms2portal.free.nf
echo.
set /p SMS2_FTP_PASS=Enter hosting account password: 
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-infinityfree-zip.ps1" -Password "%SMS2_FTP_PASS%"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy-infinityfree.ps1" -FtpPass "%SMS2_FTP_PASS%"
echo.
echo Open: https://bestlinksms2portal.free.nf/setup/deploy-db.php?token=bcp-sms2-deploy-2026
pause
