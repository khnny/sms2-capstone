param([string]$RemotePath = '/.override')

$FtpHost = 'ftpupload.net'
$FtpUser = 'if0_42794375'
$FtpPass = 'HVfvZIn3gF8RfyR'

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
Get-Content $local -Raw
