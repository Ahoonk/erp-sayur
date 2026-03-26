@echo off
setlocal
cd /d %~dp0

git status -sb
if errorlevel 1 (
  echo Git tidak ditemukan di PATH.
  exit /b 1
)

git add frontend
if errorlevel 1 exit /b 1

git commit -m "frontend update"
if errorlevel 1 (
  echo Tidak ada perubahan untuk di-commit.
  exit /b 0
)

git push origin main
endlocal