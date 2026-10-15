@echo off
setlocal
cd /d "%~dp0"

where git >nul 2>nul || (echo Git is required to update this project. & exit /b 1)

for /f "delims=" %%B in ('git branch --show-current') do set "BRANCH=%%B"
if not defined BRANCH (echo Could not detect the current Git branch. & exit /b 1)

set "STATUS_FILE=%TEMP%\skazkomir-git-status-%RANDOM%.tmp"
git status --porcelain > "%STATUS_FILE%"
for %%F in ("%STATUS_FILE%") do if %%~zF GTR 0 (del "%STATUS_FILE%" >nul 2>nul & echo Working tree has local changes. Commit or stash them before updating. & exit /b 1)
del "%STATUS_FILE%" >nul 2>nul

git fetch origin "%BRANCH%" || exit /b 1
git merge --ff-only "origin/%BRANCH%" || exit /b 1
if exist package.json (
  where npm >nul 2>nul && call npm run build || exit /b 1
)
echo Updated branch: %BRANCH%
endlocal
