@echo off
REM Starts the app locally using .env.local (SQLite). The production .env is untouched.
setlocal
set "PATH=C:\xampp\php;%PATH%"
set "APP_ENV=local"
cd /d "%~dp0"
echo.
echo   URL:    http://127.0.0.1:8000
echo   Login:  admin@gmail.com  /  Greet@123
echo.
php artisan serve --host=127.0.0.1 --port=8000
endlocal
