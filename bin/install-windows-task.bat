@echo off
rem ---------------------------------------------------------------------------------------------
rem  Registers the Windows Task Scheduler job that delivers due reminders (Email / SMS / WhatsApp)
rem  every minute - even on battery and even when nobody is logged in to the website.
rem
rem  1. If XAMPP is not in C:\xampp, edit PHP_EXE below.
rem  2. Right-click this file > "Run as administrator"   (once).
rem  To remove later:  schtasks /Delete /TN "HAPRS Reminder Scheduler" /F
rem ---------------------------------------------------------------------------------------------

set "PHP_EXE=C:\xampp\php\php.exe"

if not exist "%PHP_EXE%" (
  echo PHP not found at %PHP_EXE% - edit PHP_EXE in this file and run it again.
  pause
  exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-windows-task.ps1" -PhpExe "%PHP_EXE%"
if errorlevel 1 (
  echo.
  echo Could not create the task. Did you right-click ^> Run as administrator?
  pause
  exit /b 1
)
echo.
echo Test it now with:  "%PHP_EXE%" "%~dp0check_reminders.php"
pause
