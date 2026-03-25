@echo off
echo =========================================================
echo INICIANDO SERVIDOR LOCAL PHP PARA EL PORTAL PUCP
echo =========================================================
echo.
echo Presiona CTRL + C en esta ventana para detener el servidor.
echo.
start http://localhost:8000
d:\antigravity\pagina_maestria\php\php.exe -S localhost:8000
pause
