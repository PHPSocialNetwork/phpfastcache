@echo off
setlocal

call vendor\bin\phpcbf lib/ --report=summary
call vendor\bin\phpcs lib/ --report=summary
call vendor\bin\phpmd lib/ ansi phpmd.xml
call vendor\bin\phpstan analyse lib/ -l 6 -c phpstan.neon 2>&1

endlocal
