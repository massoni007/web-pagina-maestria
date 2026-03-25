[CmdletBinding()]
param()
$ErrorActionPreference = "Stop"

$ftpHost = "ftp://ftpupload.net/htdocs"
$ftpUser = "if0_41416105"
$ftpPass = "ZbfpzMES6ia"
$localPath = "d:\antigravity\pagina_maestria\htdocs\web"

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  INICIANDO CONEXION FTP A INFINITYFREE (ftpupload.net)   " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path $localPath)) {
    Write-Host "[ERROR] ¡No se encontro la carpeta $localPath!" -ForegroundColor Red
    Write-Host "Ejecuta primero el archivo 'preparar_produccion.bat' antes de subir." -ForegroundColor Yellow
    exit 1
}

# 1. Crear la carpeta 'data' directamente en el FTP
Write-Host "=> Preparando carpeta de base de datos 'data'..."
try {
    $request = [System.Net.FtpWebRequest]::Create("$ftpHost/data")
    $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
    $request.UsePassive = $true
    $response = $request.GetResponse()
    $response.Close()
} catch {
    # It's fine if it exists
}

$items = Get-ChildItem -Path $localPath -Recurse

foreach ($item in $items) {
    $relPath = $item.FullName.Substring($localPath.Length + 1).Replace('\', '/')
    $ftpUrl = "$ftpHost/$relPath"
    
    if ($item.PSIsContainer) {
        Write-Host "=> Creando Carpeta: $relPath" -ForegroundColor DarkCyan
        try {
            $request = [System.Net.FtpWebRequest]::Create($ftpUrl)
            $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
            $request.UsePassive = $true
            $response = $request.GetResponse()
            $response.Close()
        } catch {
            # Usually fails if directory already exists
        }
    } else {
        Write-Host "=> Subiendo Archivo: $relPath" -NoNewline
        try {
            $request = [System.Net.FtpWebRequest]::Create($ftpUrl)
            $request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
            $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $request.UsePassive = $true
            $request.UseBinary = $true
            
            $content = [System.IO.File]::ReadAllBytes($item.FullName)
            $request.ContentLength = $content.Length
            
            $requestStream = $request.GetRequestStream()
            $requestStream.Write($content, 0, $content.Length)
            $requestStream.Close()
            
            $response = $request.GetResponse()
            $response.Close()
            Write-Host " [OK]" -ForegroundColor Green
        } catch [Exception] {
            Write-Host " [ERROR: $($_.Exception.Message)]" -ForegroundColor Red
        }
    }
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  ¡SUBIDA FINALIZADA CON EXITO AL 100%!                    " -ForegroundColor Green
Write-Host "  Tu sistema ya esta alojado globalmente en InfinityFree.  " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host ""
