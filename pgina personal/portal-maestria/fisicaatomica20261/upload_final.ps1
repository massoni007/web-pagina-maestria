$ftpServer = "ftp://ftpupload.net"
$username = "if0_41416105"
$password = "ZbfpzMES6ia"
$localDir = "d:\antigravity\fisicaatomica20261"
$exclude = @(".git", "upload_ftp.ps1", "iniciar_servidor.bat", "task.md", "implementation_plan.md", "reorganize_ftp.ps1", "router.html", ".gemini", "upload_final.ps1")

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
                Write-Host "Creado: $remoteItemPath"
            } catch { }
            Upload-FtpDirectory -localPath $item.FullName -remotePath $remoteItemPath
        } else {
            Write-Host "Subiendo: $($item.Name) a $remoteItemPath"
            try {
                $req = [System.Net.FtpWebRequest]::Create($remoteItemPath)
                $req.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                $req.UsePassive = $true; $req.UseBinary = $true
                $f = [System.IO.File]::OpenRead($item.FullName)
                $s = $req.GetRequestStream(); $f.CopyTo($s)
                $s.Close(); $f.Close()
                $r = $req.GetResponse(); $r.Close()
            } catch { Write-Host "Error subiendo $($item.Name)" }
        }
    }
}
Upload-FtpDirectory -localPath $localDir -remotePath "$ftpServer/htdocs"
Write-Host "DONE_FINAL"
