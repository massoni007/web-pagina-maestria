$ftpServer = "ftp://ftpupload.net"
$username = "if0_41416105"
$password = "ZbfpzMES6ia"

function Create-FtpDir($path) {
    try {
        $req = [System.Net.FtpWebRequest]::Create("$ftpServer/htdocs/$path")
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $req.UsePassive = $true
        $res = $req.GetResponse(); $res.Close()
        Write-Host "Directorio creado: /htdocs/$path"
    } catch { }
}

Create-FtpDir "maestria"
Create-FtpDir "fisicaatomica"

# MUDAR ARCHIVOS ACTUALES A "maestria/"
$request = [System.Net.FtpWebRequest]::Create("$ftpServer/htdocs/")
$request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
$request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$request.UsePassive = $true
$response = $request.GetResponse()
$reader = New-Object System.IO.StreamReader($response.GetResponseStream())
$items = $reader.ReadToEnd() -split "`n" | Where-Object { $_ -match "\S" } | ForEach-Object { $_.Trim() }
$reader.Close(); $response.Close()

foreach ($item in $items) {
    $name = $item -replace ".*/", ""
    # Ignorar carpetas nuevas y "." ".." 
    if ($name -match '^\.+$' -or $name -eq "maestria" -or $name -eq "fisicaatomica") { continue }
    
    Write-Host "Moviendo $name a /htdocs/maestria/$name"
    try {
        $req = [System.Net.FtpWebRequest]::Create("$ftpServer/htdocs/$name")
        $req.Method = [System.Net.WebRequestMethods+Ftp]::Rename
        $req.RenameTo = "/htdocs/maestria/$name"
        $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $req.UsePassive = $true
        $res = $req.GetResponse(); $res.Close()
    } catch {
        Write-Host "Advertencia: no se pudo mover $name"
    }
}

# SUBIR ROUTER A LA RAÍZ
try {
    $req = [System.Net.FtpWebRequest]::Create("$ftpServer/htdocs/index.html")
    $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $req.UsePassive = $true
    $req.UseBinary = $true
    $content = [System.IO.File]::OpenRead("d:\antigravity\fisicaatomica20261\router.html")
    $stream = $req.GetRequestStream()
    $content.CopyTo($stream)
    $stream.Close(); $content.Close()
    $res = $req.GetResponse(); $res.Close()
    Write-Host "Router subido."
} catch {}

# FUNCION RECURSIVA SUBIDA DE FISICA ATOMICA
$localDir = "d:\antigravity\fisicaatomica20261"
$exclude = @(".git", "upload_ftp.ps1", "iniciar_servidor.bat", "task.md", "implementation_plan.md", "reorganize_ftp.ps1", "router.html", ".gemini")

function Upload-FtpDirectory {
    param([string]$localPath, [string]$remotePath)
    Get-ChildItem -Path $localPath | Where-Object { $exclude -notcontains $_.Name } | ForEach-Object {
        $item = $_
        $remoteItemPath = "$remotePath/$($item.Name)"
        if ($item.PSIsContainer) {
            try {
                $req = [System.Net.FtpWebRequest]::Create($remoteItemPath)
                $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $req.UsePassive = $true
                $res = $req.GetResponse(); $res.Close()
            } catch { }
            Upload-FtpDirectory -localPath $item.FullName -remotePath $remoteItemPath
        } else {
            Write-Host "Subiendo: $($item.FullName)"
            try {
                $req = [System.Net.FtpWebRequest]::Create($remoteItemPath)
                $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                $req.UsePassive = $true; $req.UseBinary = $true
                $f = [System.IO.File]::OpenRead($item.FullName)
                $s = $req.GetRequestStream(); $f.CopyTo($s)
                $s.Close(); $f.Close()
                $r = $req.GetResponse(); $r.Close()
            } catch {}
        }
    }
}

Upload-FtpDirectory -localPath $localDir -remotePath "$ftpServer/htdocs/fisicaatomica"
Write-Host "PROCESO FINALIZADO"
