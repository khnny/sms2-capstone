#Requires -Version 5.1
param(
    [switch]$Watch,
    [switch]$PullOnly,
    [int]$DebounceSeconds = 8,
    [int]$PollIntervalSeconds = 60
)

$ErrorActionPreference = 'Stop'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$LockFile = Join-Path $RepoRoot '.git-sync.lock'
$LogFile = Join-Path $RepoRoot '.git-sync.log'

$IgnorePatterns = @(
    '\.git\\',
    '\\storage\\uploads\\',
    '\\storage\\backups\\',
    '\\storage\\keys\\',
    '\\sms2_system\\',
    '\.git-sync\.'
)

function Write-SyncLog {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $Message
    Add-Content -Path $LogFile -Value $line -Encoding UTF8
}

function Test-ShouldIgnorePath {
    param([string]$Path)
    foreach ($pattern in $IgnorePatterns) {
        if ($Path -match $pattern) {
            return $true
        }
    }
    return $false
}

function Invoke-GitCommand {
    param(
        [string[]]$Arguments
    )
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & git @Arguments 2>&1
        $code = [int]$LASTEXITCODE
        foreach ($line in @($output)) {
            if ($null -ne $line -and "$line".Trim() -ne '') {
                [void](Write-SyncLog "$line")
            }
        }
    }
    finally {
        $ErrorActionPreference = $previous
    }
    return $code
}

function Invoke-GitPull {
    Set-Location $RepoRoot

    $env:Path = [System.Environment]::GetEnvironmentVariable('Path', 'Machine') + ';' +
                [System.Environment]::GetEnvironmentVariable('Path', 'User')

    git rev-parse --is-inside-work-tree 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-SyncLog 'Skipped: not a git repository.'
        return $false
    }

    $branch = (git rev-parse --abbrev-ref HEAD).Trim()
    if ($branch -eq 'HEAD') {
        Write-SyncLog 'Skipped: detached HEAD.'
        return $false
    }

    Invoke-GitCommand @('fetch', 'origin', $branch) | Out-Null
    $localHash = (git rev-parse HEAD).Trim()
    $remoteRef = "origin/$branch"
    git rev-parse --verify $remoteRef 2>$null | Out-Null
    if ($LASTEXITCODE -ne 0) {
        return $true
    }

    $remoteHash = (git rev-parse $remoteRef).Trim()
    if ($localHash -eq $remoteHash) {
        return $true
    }

    Write-SyncLog "Remote updates found on $branch"
    $mergeBase = (git merge-base HEAD $remoteRef).Trim()
    if ($mergeBase -eq $remoteHash) {
        $pullCode = Invoke-GitCommand @('pull', '--rebase', 'origin', $branch)
        return $pullCode -eq 0
    }
    if ($mergeBase -eq $localHash) {
        $pullCode = Invoke-GitCommand @('pull', '--ff-only', 'origin', $branch)
        return $pullCode -eq 0
    }

    Write-SyncLog 'Pull skipped: local and remote diverged. Resolve manually.'
    return $false
}

function Invoke-GitSync {
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'

    if (Test-Path $LockFile) {
        $lockAge = (Get-Date) - (Get-Item $LockFile).LastWriteTime
        if ($lockAge.TotalMinutes -lt 5) {
            return
        }
        Remove-Item $LockFile -Force -ErrorAction SilentlyContinue
    }

    New-Item -ItemType File -Path $LockFile -Force | Out-Null

    try {
        Write-SyncLog 'Sync started'
        if (-not (Invoke-GitPull)) {
            return
        }

        git add -A
        $status = git status --porcelain
        if ([string]::IsNullOrWhiteSpace($status)) {
            Write-SyncLog 'No local changes to commit.'
            return
        }

        $gitName = (git config --get user.name 2>$null)
        if ([string]::IsNullOrWhiteSpace($gitName)) {
            $gitName = $env:SMS2_GIT_NAME
        }
        if ([string]::IsNullOrWhiteSpace($gitName)) {
            $gitName = 'SMS2 Developer'
        }

        $gitEmail = (git config --get user.email 2>$null)
        if ([string]::IsNullOrWhiteSpace($gitEmail)) {
            $gitEmail = $env:SMS2_GIT_EMAIL
        }
        if ([string]::IsNullOrWhiteSpace($gitEmail)) {
            $gitEmail = 'sms2-dev@local'
        }

        $stamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
        $commitCode = Invoke-GitCommand @(
            '-c', "user.name=$gitName",
            '-c', "user.email=$gitEmail",
            'commit', '-m', "Auto-sync: $stamp"
        )
        if ($commitCode -ne 0) {
            Write-SyncLog 'Commit failed.'
            return
        }

        $branch = (git rev-parse --abbrev-ref HEAD).Trim()
        $pushCode = Invoke-GitCommand @('push', 'origin', $branch)
        if ($pushCode -eq 0) {
            Write-SyncLog 'Push completed.'
        }
        else {
            Write-SyncLog 'Push failed.'
        }
    }
    catch {
        Write-SyncLog ("Error: " + $_.Exception.Message)
    }
    finally {
        if (Test-Path $LockFile) {
            Remove-Item $LockFile -Force -ErrorAction SilentlyContinue
        }
        $ErrorActionPreference = $previousPreference
    }
}

if ($PullOnly) {
    if (Test-Path $LockFile) {
        $lockAge = (Get-Date) - (Get-Item $LockFile).LastWriteTime
        if ($lockAge.TotalMinutes -lt 5) {
            return
        }
        Remove-Item $LockFile -Force -ErrorAction SilentlyContinue
    }

    New-Item -ItemType File -Path $LockFile -Force | Out-Null
    try {
        Invoke-GitPull | Out-Null
    }
    finally {
        if (Test-Path $LockFile) {
            Remove-Item $LockFile -Force -ErrorAction SilentlyContinue
        }
    }
    return
}

if ($Watch) {
    Write-SyncLog 'File watcher started.'
    Write-Host "Watching $RepoRoot for changes..."
    Write-Host "Polling GitHub every $PollIntervalSeconds second(s) for remote updates."
    Write-Host "Log: $LogFile"
    Write-Host 'Press Ctrl+C to stop.'

    $script:lastChange = $null
    $script:syncQueued = $false
    $script:lastPoll = Get-Date

    $watcher = New-Object System.IO.FileSystemWatcher
    $watcher.Path = $RepoRoot
    $watcher.IncludeSubdirectories = $true
    $watcher.EnableRaisingEvents = $true
    $watcher.NotifyFilter = [IO.NotifyFilters]'FileName, LastWrite, CreationTime, Size'

    $handler = {
        $path = $Event.SourceEventArgs.FullPath
        $patterns = @('\.git\\', '\\storage\\uploads\\', '\\storage\\backups\\', '\\storage\\keys\\', '\\sms2_system\\', '\.git-sync\.')
        foreach ($pattern in $patterns) {
            if ($path -match $pattern) { return }
        }
        $script:lastChange = Get-Date
        $script:syncQueued = $true
    }

    Register-ObjectEvent -InputObject $watcher -EventName Changed -SourceIdentifier 'Sms2GitChanged' -Action $handler | Out-Null
    Register-ObjectEvent -InputObject $watcher -EventName Created -SourceIdentifier 'Sms2GitCreated' -Action $handler | Out-Null
    Register-ObjectEvent -InputObject $watcher -EventName Deleted -SourceIdentifier 'Sms2GitDeleted' -Action $handler | Out-Null
    Register-ObjectEvent -InputObject $watcher -EventName Renamed -SourceIdentifier 'Sms2GitRenamed' -Action $handler | Out-Null

    try {
        while ($true) {
            Start-Sleep -Seconds 1

            $pollElapsed = ((Get-Date) - $script:lastPoll).TotalSeconds
            if ($pollElapsed -ge $PollIntervalSeconds) {
                $script:lastPoll = Get-Date
                & $PSScriptRoot\git-auto-sync.ps1 -PullOnly
            }

            if ($script:syncQueued -and $null -ne $script:lastChange) {
                $elapsed = ((Get-Date) - $script:lastChange).TotalSeconds
                if ($elapsed -ge $DebounceSeconds) {
                    $script:syncQueued = $false
                    Invoke-GitSync
                }
            }
        }
    }
    finally {
        $watcher.EnableRaisingEvents = $false
        $watcher.Dispose()
        Get-EventSubscriber | Where-Object { $_.SourceIdentifier -like 'Sms2Git*' } | Unregister-Event
    }
}
else {
    Start-Sleep -Seconds $DebounceSeconds
    Invoke-GitSync
}
