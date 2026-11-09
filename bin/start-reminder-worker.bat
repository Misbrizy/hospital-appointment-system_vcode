@echo off
rem  Alternative to Task Scheduler: double-click this and leave the window open. It delivers due reminders
rem  every 30 seconds for as long as it stays open (and the PC is on). Close the window to stop.
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
  echo PHP not found at %PHP_EXE% - edit PHP_EXE in this file.
  pause
  exit /b 1
)
"%PHP_EXE%" "%~dp0..\cron\worker.php"
pause
