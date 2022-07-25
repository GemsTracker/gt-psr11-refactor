@echo off

if not exist ".git" goto exit_nogit 

setlocal enableextensions

rem set rundir = %cd%

cd vendor\gemstracker\gemstracker
git reset --hard HEAD
git clean -fd

cd ..\..\magnafacta\mutil
git reset --hard HEAD
git clean -fd

cd ..\zalt-loader
git reset --hard HEAD
git clean -fd

cd ..\..\..
git reset --hard HEAD
git clean -fd

php -f %~dp0\psrGather.php

endlocal

goto exit

:exit_nogit
    echo Currently not in a git directory.

:exit