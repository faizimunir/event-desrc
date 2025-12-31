@echo off
REM Script untuk install Laravel Queue Worker sebagai Windows Service menggunakan NSSM
REM Pastikan NSSM sudah di-download dan di-extract

echo ========================================
echo Laravel Queue Worker Service Installer
echo ========================================
echo.

REM Update path sesuai lokasi project dan NSSM
set PROJECT_PATH=C:\xampp\htdocs\event-desrc
set PHP_PATH=C:\xampp\php\php.exe
set NSSM_PATH=C:\nssm\win64\nssm.exe
set SERVICE_NAME=LaravelQueueWorker

echo Project Path: %PROJECT_PATH%
echo PHP Path: %PHP_PATH%
echo NSSM Path: %NSSM_PATH%
echo Service Name: %SERVICE_NAME%
echo.

REM Check if NSSM exists
if not exist "%NSSM_PATH%" (
    echo ERROR: NSSM not found at %NSSM_PATH%
    echo Please download NSSM from https://nssm.cc/download
    echo Extract to C:\nssm\win64\
    pause
    exit /b 1
)

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please update PHP_PATH in this script
    pause
    exit /b 1
)

REM Check if project exists
if not exist "%PROJECT_PATH%\artisan" (
    echo ERROR: Laravel project not found at %PROJECT_PATH%
    echo Please update PROJECT_PATH in this script
    pause
    exit /b 1
)

echo Installing service...
"%NSSM_PATH%" install %SERVICE_NAME% "%PHP_PATH%"

echo Setting service parameters...
"%NSSM_PATH%" set %SERVICE_NAME% AppDirectory "%PROJECT_PATH%"
"%NSSM_PATH%" set %SERVICE_NAME% AppParameters "artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90"
"%NSSM_PATH%" set %SERVICE_NAME% DisplayName "Laravel Queue Worker"
"%NSSM_PATH%" set %SERVICE_NAME% Description "Laravel Queue Worker for processing Moota webhooks and other background jobs"
"%NSSM_PATH%" set %SERVICE_NAME% Start SERVICE_AUTO_START
"%NSSM_PATH%" set %SERVICE_NAME% AppStdout "%PROJECT_PATH%\storage\logs\worker.log"
"%NSSM_PATH%" set %SERVICE_NAME% AppStderr "%PROJECT_PATH%\storage\logs\worker-error.log"

echo.
echo Service installed successfully!
echo.
echo To start the service, run:
echo   "%NSSM_PATH%" start %SERVICE_NAME%
echo.
echo To check status, run:
echo   "%NSSM_PATH%" status %SERVICE_NAME%
echo.
echo To stop the service, run:
echo   "%NSSM_PATH%" stop %SERVICE_NAME%
echo.
echo To remove the service, run:
echo   "%NSSM_PATH%" remove %SERVICE_NAME% confirm
echo.

pause

