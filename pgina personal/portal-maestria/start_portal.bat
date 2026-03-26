@echo off
echo =========================================================
echo INICIANDO SERVIDOR LOCAL PHP PARA EL PORTAL PUCP
echo =========================================================
echo.
echo Presiona CTRL + C en esta ventana para detener el servidor.
echo.
start http://localhost:8000
..\releases\v1.0.0\pagina_maestria\php\php.exe -S localhost:8000
pause
