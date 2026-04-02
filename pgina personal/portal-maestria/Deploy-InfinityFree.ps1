param (
    [string]$FtpHost,
    [string]$FtpUser,
    [string]$FtpPass,
    [string]$SourceFolder
)

$Host.UI.RawUI.WindowTitle = "Despliegue a InfinityFree"
Write-Host "Iniciando proceso de despliegue FTP a $FtpHost..." -ForegroundColor Cyan

# Directorios a ignorar
$ExcludeItems = @(".git", ".vscode", "deploy.bat", "Deploy-InfinityFree.ps1", "docs", "start_portal.bat", "uploads", "codigo_admin_recibido.txt", "*.csv")

function Upload-FtpDirectory {
    param(
        [string]$LocalDir,
        [string]$RemoteDir
    )

    $items = Get-ChildItem -Path $LocalDir

    foreach ($item in $items) {
        $skip = $false
        foreach ($ex in $ExcludeItems) {
            if ($item.FullName -match "\\$ex(\\.*|$)") { $skip = $true; break }
        }
        if ($skip) { continue }

        $remotePath = "$RemoteDir/$($item.Name)"

        if ($item.PSIsContainer) {
            # Intentar crear directorio remoto
            try {
                $request = [System.Net.WebRequest]::Create($remotePath)
                $request.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
                $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $response = $request.GetResponse()
                $response.Close()
            } catch {
                # Ignorar error si el directorio ya existe
            }
            # Llamada recursiva
            Upload-FtpDirectory -LocalDir $item.FullName -RemoteDir $remotePath
        } else {
            # Subir archivo
            Write-Host "Subiendo: $($item.FullName) -> $remotePath" -ForegroundColor White
            try {
                $request = [System.Net.WebRequest]::Create($remotePath)
                $request.Credentials = New-Object System.Net.NetworkCredential($FtpUser, $FtpPass)
                $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                $request.UseBinary = $true
                $request.KeepAlive = $false

                $content = [System.IO.File]::ReadAllBytes($item.FullName)
                $request.ContentLength = $content.Length

                $rs = $request.GetRequestStream()
                $rs.Write($content, 0, $content.Length)
                $rs.Close()

                $response = $request.GetResponse()
                $response.Close()
            } catch {
                Write-Host "Error subiendo $($item.Name): $($_.Exception.Message)" -ForegroundColor Red
            }
        }
    }
}

try {
    # Cambiar 'htdocs' por el directorio adecuado si es necesario, InfinityFree usa htdocs
    Upload-FtpDirectory -LocalDir $SourceFolder -RemoteDir "ftp://$FtpHost/htdocs"
    Write-Host "`n¡Despliegue completado con éxito!" -ForegroundColor Green
} catch {
    Write-Host "`nOcurrió un error crítico: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`nPresiona cualquier tecla para salir..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
