# Deploy SMS2 to InfinityFree via FTPS
param(
    [string]$FtpPass = $env:SMS2_FTP_PASS,
    [string]$ZipPath = 'C:\xampp\htdocs\sms2_deploy.zip',
    [string]$Staging = 'C:\xampp\htdocs\sms2_deploy_staging'
)

$ftpHosts = @('ftpupload.net', 'ftp.epizy.com', '185.27.134.11')
$FtpHost = $ftpHosts[0]

$ErrorActionPreference = 'Stop'

if (-not $FtpPass) {
    Write-Error 'Set SMS2_FTP_PASS or pass -FtpPass.'
}

[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
[Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }

$ftpUser = 'if0_42794375'
$remoteDir = '/htdocs'

if (-not (Test-Path $ZipPath)) {
    & "$PSScriptRoot\build-infinityfree-zip.ps1" -Password $FtpPass
}

if (Test-Path $Staging) { Remove-Item $Staging -Recurse -Force }
Expand-Archive -Path $ZipPath -DestinationPath $Staging -Force

function New-FtpRequest([string]$remotePath, [string]$method) {
    $uri = "ftp://${FtpHost}${remotePath}"
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = $method
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $FtpPass)
    $request.EnableSsl = $true
    $request.UseBinary = $true
    $request.UsePassive = $true
    $request.KeepAlive = $false
    return $request
}

function Test-FtpLogin() {
    $request = New-FtpRequest '/' ([System.Net.WebRequestMethods+Ftp]::ListDirectory)
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $listing = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    return $listing
}

function Upload-FtpFile($localPath, $remotePath) {
    $request = New-FtpRequest $remotePath ([System.Net.WebRequestMethods+Ftp]::UploadFile)
    $bytes = [System.IO.File]::ReadAllBytes($localPath)
    $request.ContentLength = $bytes.Length
    $stream = $request.GetRequestStream()
    $stream.Write($bytes, 0, $bytes.Length)
    $stream.Close()
    $response = $request.GetResponse()
    $response.Close()
}

function Ensure-FtpDir($remotePath) {
    try {
        $request = New-FtpRequest $remotePath ([System.Net.WebRequestMethods+Ftp]::MakeDirectory)
        $response = $request.GetResponse()
        $response.Close()
    } catch { }
}

function Upload-FtpTree($localDir, $remoteDirPath) {
    Ensure-FtpDir $remoteDirPath
    Get-ChildItem -Path $localDir -Force | ForEach-Object {
        $remote = ($remoteDirPath.TrimEnd('/') + '/' + $_.Name)
        if ($_.PSIsContainer) {
            Upload-FtpTree $_.FullName $remote
        } else {
            Write-Host "Uploading $remote"
            Upload-FtpFile $_.FullName $remote
        }
    }
}

$connectedHost = $null
foreach ($hostCandidate in $ftpHosts) {
    $script:FtpHost = $hostCandidate
    Write-Host "Testing FTPS login on ftp://${FtpHost} ..."
    try {
        $listing = Test-FtpLogin
        Write-Host "Connected on ${FtpHost}."
        $connectedHost = $hostCandidate
        break
    } catch {
        Write-Host "Failed on ${hostCandidate}: $($_.Exception.Message)"
    }
}
if (-not $connectedHost) {
    Write-Error 'Could not log in via FTPS on any known InfinityFree host. Open Control Panel once in the dashboard, wait 5 minutes, then retry.'
}
$FtpHost = $connectedHost

Write-Host "Uploading via FTPS to ftp://${FtpHost}${remoteDir} ..."
Upload-FtpTree $Staging $remoteDir
Write-Host 'Upload complete.'
Write-Host 'Open: https://bestlinksms2portal.free.nf/setup/deploy-db.php?token=bcp-sms2-deploy-2026'
