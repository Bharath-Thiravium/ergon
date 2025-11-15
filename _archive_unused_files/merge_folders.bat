@echo off
echo Merging duplicate ergon folder...

:: Copy all files from ergon\ergon to ergon (overwrite if newer)
xcopy "ergon\*" "." /E /Y /D

:: Remove the duplicate folder
rmdir "ergon" /S /Q

echo Merge completed!
pause