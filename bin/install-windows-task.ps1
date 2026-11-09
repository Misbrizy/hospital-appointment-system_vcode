<#
 Registers the Windows Task Scheduler job that delivers reminders (Email / SMS / WhatsApp) every minute.
 Run via bin\install-windows-task.bat (right-click > Run as administrator).

 Why this script exists instead of a plain "schtasks /Create": tasks made that way get Windows' DEFAULT
 conditions - "start only if the computer is on AC power" and "stop if it switches to battery" - so on a laptop
 the reminders silently stop whenever the charger is unplugged. This task:
   * runs on battery,
   * runs as SYSTEM (works when nobody is logged in, no console window pops up every minute),
   * catches up after the PC was asleep/off (StartWhenAvailable),
   * never starts a second copy while one is still running,
   * appends each run's result to storage\logs\cron.log.
 The PC still has to be ON (and MySQL running) at the time a reminder is due - Windows cannot run tasks while it is off.
#>
param([string]$PhpExe = 'C:\xampp\php\php.exe')

$ErrorActionPreference = 'Stop'
$root   = Split-Path $PSScriptRoot -Parent
$script = Join-Path $root 'cron\send_sms.php'
$logDir = Join-Path $root 'storage\logs'
$log    = Join-Path $logDir 'cron.log'
$name   = 'HAPRS Reminder Scheduler'

if (-not (Test-Path $PhpExe))  { throw "PHP not found at $PhpExe - run:  powershell -File bin\install-windows-task.ps1 -PhpExe 'D:\xampp\php\php.exe'" }
if (-not (Test-Path $script))  { throw "Cannot find $script" }
New-Item -ItemType Directory -Force -Path $logDir | Out-Null

$cmdArgs = '/c ""{0}" "{1}" >> "{2}" 2>&1"' -f $PhpExe, $script, $log
$action    = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument $cmdArgs -WorkingDirectory $root
$trigger   = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1) -RepetitionDuration (New-TimeSpan -Days 3650)
$settings  = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable `
              -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 5)
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

Register-ScheduledTask -TaskName $name -Action $action -Trigger $trigger -Settings $settings -Principal $principal -Force | Out-Null
Start-ScheduledTask -TaskName $name

Write-Host ''
Write-Host "Done. Task '$name' will run every minute (also on battery, also when nobody is logged in)."
Write-Host "Check it:   $PhpExe bin\check_reminders.php"
Write-Host "Live log:   $log"
Write-Host "Remove it:  schtasks /Delete /TN `"$name`" /F"
