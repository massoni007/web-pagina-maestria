@echo off
color 0B
echo.
echo ====================================================
echo   HERRAMIENTA MAESTRA DE SUBIDA - INFINITYFREE
echo ====================================================
echo.
echo 1. Empaquetando versiones mas recientes en 'web'...
call preparar_produccion.bat

echo.
echo 2. Estableciendo conexion FTP segura para subir los archivos...
powershell -ExecutionPolicy Bypass -File "upload_ftp.ps1"

echo.
pause
