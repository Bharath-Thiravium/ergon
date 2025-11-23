@echo off
echo Starting Finance API Server...
cd /d "c:\laragon\www\ergon"
python -m pip install -r requirements.txt
python finance_api.py
pause