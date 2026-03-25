$excelFile = "d:\antigravity\plan_nuevo.xlsx"
$csvFile = "d:\antigravity\plan_nuevo.csv"
try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    $wb = $excel.Workbooks.Open($excelFile)
    $wb.SaveAs($csvFile, 6)
    $wb.Close($false)
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
    Write-Host "SUCCESS"
} catch {
    Write-Host "ERROR: $_"
}
