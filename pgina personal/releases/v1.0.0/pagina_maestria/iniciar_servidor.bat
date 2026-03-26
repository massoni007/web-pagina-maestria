@echo off
title Servidor Local - Maestria (PHP)
echo Iniciando servidor en tu computadora...
echo Por favor, no cierres esta ventana negra mientras estes probando tu pagina.
echo.
echo Ve a tu explorador y abre: http://127.0.0.1:8000
echo.
cd /d "%~dp0htdocs"
..\php\php.exe -S 127.0.0.1:8000
pause
