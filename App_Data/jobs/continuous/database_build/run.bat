@echo off
rem This script is a Kudu webjob. See README.md

:run
if exist \home\site\wwwroot\.data\MUST_REBUILD if not exist \home\site\wwwroot\.data\BUILDING goto rebuild
sleep 5 > nul
goto run

:rebuild
echo Triggering database rebuild
echo Building > \home\site\wwwroot\.data\BUILDING
del \home\site\wwwroot\.data\MUST_REBUILD
cd \home\site\wwwroot
call composer run-script --timeout=0 build
del \home\site\wwwroot\.data\BUILDING

rem We'll exit the script (Kudu will restart it) so we get logs for subsequent runs in Kudu
rem (only first 100 lines by default go to the kudu logs). This will cause a 60 second delay,
rem which is probably not a bad thing in any case ;-)
