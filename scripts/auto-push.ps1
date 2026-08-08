<#
Auto-push watcher for Windows PowerShell
Usage: run from the plugin repo root with PowerShell (ExecutionPolicy may need bypass)
  powershell -ExecutionPolicy Bypass -File .\scripts\auto-push.ps1

Behavior:
- Watches the repository folder for file changes (adds/edits/deletes/renames)
- Debounces changes for 4 seconds, then runs: git add -A, git commit, git push
- Requires git credentials already configured (credential manager, HTTPS with stored PAT, or SSH agent)

Cautions:
- This will auto-commit any changed file. Keep sensible .gitignore and don't edit secrets here.
- Use only on development machines.
#>

param(
    [string]$RepoPath = (Get-Location).Path,
    [string]$Branch = 'main',
    [int]$DebounceSeconds = 4
)

Set-Location -Path $RepoPath
Write-Host "Starting auto-push watcher in: $RepoPath (branch: $Branch)" -ForegroundColor Cyan

# Quick sanity check
if (-not (Test-Path "$RepoPath\.git")) {
    Write-Error "No .git repo found at $RepoPath. Initialize or run this from the repo root."; exit 1
}

$fsw = New-Object System.IO.FileSystemWatcher $RepoPath, '*.*'
$fsw.IncludeSubdirectories = $true
$fsw.EnableRaisingEvents = $true

$changedFiles = New-Object System.Collections.Generic.HashSet[string]
$syncLock = New-Object Object
$lastEvent = Get-Date

$action = {
    param($sender, $eventArgs)
    $full = $eventArgs.FullPath
    # ignore changes inside .git
    if ($full -like "*\\.git*") { return }
    lock ($syncLock) {
        [void]$changedFiles.Add($full)
        $script:lastEvent = Get-Date
    }
}

Register-ObjectEvent $fsw Changed -SourceIdentifier FileChanged -Action $action | Out-Null
Register-ObjectEvent $fsw Created -SourceIdentifier FileCreated -Action $action | Out-Null
Register-ObjectEvent $fsw Deleted -SourceIdentifier FileDeleted -Action $action | Out-Null
Register-ObjectEvent $fsw Renamed -SourceIdentifier FileRenamed -Action $action | Out-Null

while ($true) {
    Start-Sleep -Seconds 1
    if ((Get-Date) - $script:lastEvent -gt (New-TimeSpan -Seconds $DebounceSeconds)) {
        $toCommit = @()
        lock ($syncLock) {
            if ($changedFiles.Count -eq 0) { continue }
            $toCommit = $changedFiles.ToArray()
            $changedFiles.Clear()
        }

        Write-Host "Detected changes: $($toCommit.Count) file(s). Preparing commit..." -ForegroundColor Yellow
        # Show files
        $toCommit | ForEach-Object { Write-Host " - $_" }

        # Git add/commit/push
        try {
            & git add -A
            $msg = "Auto-update: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss") - " + ($toCommit | ForEach-Object { Split-Path $_ -NoQualifier } | Select-Object -First 10 -Join ", ")
            & git commit -m $msg
            Write-Host "Committed: $msg" -ForegroundColor Green
        } catch {
            Write-Host "Nothing to commit or error committing: $_" -ForegroundColor DarkYellow
        }

        try {
            & git push origin $Branch
            Write-Host "Pushed to origin/$Branch" -ForegroundColor Green
        } catch {
            Write-Host "Push failed: $_" -ForegroundColor Red
        }
    }
}
