$ftpServer = "ftp://ftpupload.net/atomica.gt.tc/htdocs"
$username = "if0_41416105"
$password = "ZbfpzMES6ia"
$localDir = "d:\antigravity\fisicaatomica20261"
$exclude = @(".git", "upload_ftp.ps1", "iniciar_servidor.bat", "task.md", "implementation_plan.md", ".gemini")

function Upload-FtpDirectory {
    param(
        [string]$localPath,
        [string]$remotePath
    )

    Get-ChildItem -Path $localPath | Where-Object { $exclude -notcontains $_.Name } | ForEach-Object {
        $item = $_
        $remoteItemPath = "$remotePath/$($item.Name)"

        if ($item.PSIsContainer) {
            try {
                $request = [System.Net.FtpWebRequest]::Create($remoteItemPath)
                $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
                $request.UsePassive = $true
                $response = $request.GetResponse()
                $response.Close()
                Write-Host "Created Directory: $remoteItemPath"
            } catch {
                # Ya existe
            }
            Upload-FtpDirectory -localPath $item.FullName -remotePath $remoteItemPath
        } else {
            Write-Host "Uploading File: $($item.FullName) a $remoteItemPath"
            try {
                $request = [System.Net.FtpWebRequest]::Create($remoteItemPath)
                $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
                $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
                $request.UsePassive = $true
                $request.UseBinary = $true
                
                $fileStream = [System.IO.File]::OpenRead($item.FullName)
                $ftpStream = $request.GetRequestStream()
                $fileStream.CopyTo($ftpStream)
                
                $ftpStream.Close()
                $fileStream.Close()
                
                $response = $request.GetResponse()
                $response.Close()
            } catch {
                Write-Host "Error uploading $($item.FullName)"
            }
        }
    }
}

Upload-FtpDirectory -localPath $localDir -remotePath $ftpServer
Write-Host "DONE"
