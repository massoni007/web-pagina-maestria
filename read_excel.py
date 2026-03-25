import sys
import subprocess
import json

try:
    import openpyxl
except ImportError:
    subprocess.check_call([sys.executable, '-m', 'pip', 'install', 'openpyxl', '--quiet'])
    import openpyxl

file_path = 'd:/antigravity/plan_nuevo.xlsx'
try:
    wb = openpyxl.load_workbook(file_path, data_only=True)
    sheet = wb.active
    data = []
    for row in sheet.iter_rows(values_only=True):
        data.append(row)
    print(json.dumps(data))
except Exception as e:
    print(json.dumps({"error": str(e)}))
