@echo off
setlocal
color 0B
title Subiendo Portal a InfinityFree

echo ==========================================================
echo        DESPLIEGUE AUTOMATICO A INFINITY FREE
echo ==========================================================
echo.
echo Iniciando proceso de despliegue recursivo...
echo Por favor, espera mientras se sincronizan los archivos.
echo ==========================================================

set host=ftpupload.net
set user=if0_41416105
set pass=ZbfpzMES6ia

powershell -ExecutionPolicy Bypass -File "Deploy-InfinityFree.ps1" -FtpHost "%host%" -FtpUser "%user%" -FtpPass "%pass%" -SourceFolder "%cd%"

pause
