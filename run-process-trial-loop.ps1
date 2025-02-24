$ErrorActionPreference = "Stop"
$maxErrors = 3
$errorCount = 0
$startTime = Get-Date
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$logFile = Join-Path $scriptPath "process-trial-scheduler.log"

function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $logFile -Value $logMessage
}

Write-Log "Starting ProcessTrialReminders scheduler"
Write-Log "Log file: $logFile"

while ($true) {
    try {
        $currentTime = Get-Date
        Write-Log "Running ProcessTrialReminders at: $currentTime"
        
        # Run the batch file
        $process = Start-Process -FilePath "$scriptPath\run-process-trial.bat" -Wait -PassThru -NoNewWindow
        
        if ($process.ExitCode -eq 0) {
            Write-Log "ProcessTrialReminders completed successfully"
            $errorCount = 0  # Reset error count on success
        } else {
            throw "Process exited with code: $($process.ExitCode)"
        }
        
        # Log memory usage every hour
        if ((Get-Date) -gt $startTime.AddHours(1)) {
            $processInfo = Get-Process -Id $PID
            Write-Log "Memory usage: $([math]::Round($processInfo.WorkingSet64 / 1MB, 2)) MB"
            $startTime = Get-Date
        }
    }
    catch {
        $errorCount++
        Write-Log "Error occurred: $_"
        
        if ($errorCount -ge $maxErrors) {
            Write-Log "Too many errors ($errorCount). Restarting script..."
            Start-Process powershell -ArgumentList "-File `"$PSCommandPath`""
            exit
        }
    }
    
    # Wait for 5 minutes before next run
    Write-Log "Waiting 5 minutes before next run..."
    Start-Sleep -Seconds 300
}
