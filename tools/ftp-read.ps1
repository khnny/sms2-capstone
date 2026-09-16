param([string]$RemotePath = '/.override')

$FtpHost = if ($env:SMS2_FTP_HOST) { $env:SMS2_FTP_HOST } else { 'ftpupload.net' }
$FtpUser = $env:SMS2_FTP_USER
$FtpPass = $env:SMS2_FTP_PASS

if (-not $FtpUser -or -not $FtpPass) {
    Write-Error 'Set SMS2_FTP_USER and SMS2_FTP_PASS before running.'
    exit 1
}

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
[Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }

$local = Join-Path $env:TEMP 'ftp-download.tmp'
$uri = "ftp://${FtpHost}${RemotePath}"
$request = [System.Net.FtpWebRequest]::Create($uri)
$request.Method = [System.Net.WebRequestMethods+Ftp]::DownloadFile
$request.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
$request.EnableSsl = $true
$request.UsePassive = $true
$response = $request.GetResponse()
$stream = $response.GetResponseStream()
$file = [System.IO.File]::Create($local)
$stream.CopyTo($file)
$file.Close()
$stream.Close()
$response.Close()
Get-Content $local
