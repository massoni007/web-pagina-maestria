@echo off
color 0A
set "source=htdocs"
set "dest=htdocs\web"

echo ===================================================
echo     PREPARANDO ARCHIVOS PARA INFINITYFREE
echo ===================================================
echo.

if exist "%dest%" (
    echo [1/3] Limpiando carpeta web anterior...
    rmdir /s /q "%dest%"
)

echo [2/3] Creando nueva carpeta web...
mkdir "%dest%"

echo [3/3] Copiando tus archivos finales...
copy "%source%\*.html" "%dest%\" >nul
copy "%source%\*.css" "%dest%\" >nul
copy "%source%\*.js" "%dest%\" >nul
copy "%source%\*.php" "%dest%\" >nul

if exist "%source%\PHPMailer" (
    echo       - Copiando motor de correos PHPMailer...
    xcopy "%source%\PHPMailer" "%dest%\PHPMailer\" /s /e /h /y /q >nul
)

echo.
echo ===================================================
echo TODO LISTO! 
echo Tus archivos en forma limpia estan listos en la carpeta:
echo htdocs\web
echo ===================================================
echo Solo tienes que entrar a la carpeta 'web' y subir 
echo todos los archivos que hay dentro a tu host.
echo.
pause
