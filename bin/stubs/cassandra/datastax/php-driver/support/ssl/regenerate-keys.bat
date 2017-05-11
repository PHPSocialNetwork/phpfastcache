@ECHO OFF
REM Copyright 2015-2016 DataStax
REM
REM Licensed under the Apache License, Version 2.0 (the "License");
REM you may not use this file except in compliance with the License.
REM You may obtain a copy of the License at
REM
REM http://www.apache.org/licenses/LICENSE-2.0
REM
REM Unless required by applicable law or agreed to in writing, software
REM distributed under the License is distributed on an "AS IS" BASIS,
REM WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
REM See the License for the specific language governing permissions and
REM limitations under the License.

REM Enable delayed expansion (multiple var assignments) and local variables
SETLOCAL ENABLEDELAYEDEXPANSION

SET BATCH_DIRECTORY=%~D0%~P0
SET ABSOLUTE_BATCH_DIRECTORY=%~DP0
SET BATCH_FILENAME=%~N0%~X0

REM Exit code constants
SET EXIT_CODE_MISSING_KEYTOOL=1
SET EXIT_CODE_MISSING_OPENSSL=2
SET EXIT_CODE_ERASE_FAILURE=3
SET EXIT_CODE_KEYTOOL_COMMAND_FAILURE=4
SET EXIT_CODE_OPENSSL_COMMAND_FAILURE=5

REM Constants for generating SSL keys
SET "SERVER_CERTIFICATE_PEM=!ABSOLUTE_BATCH_DIRECTORY!\cassandra.pem"
SET SERVER_IP_ADDRESS=127.0.0.1
SET "CLIENT_CERTIFICATE=!ABSOLUTE_BATCH_DIRECTORY!\driver.crt"
SET "CLIENT_CERTIFICATE_PEM=!ABSOLUTE_BATCH_DIRECTORY!\driver.pem"
SET CLIENT_IP_ADDRESS=127.0.0.1
SET "PRIVATE_KEY=!ABSOLUTE_BATCH_DIRECTORY!\driver.key"
SET PASSPHRASE=php-driver

SET "SERVER_KEYSTORE=!ABSOLUTE_BATCH_DIRECTORY!\.keystore"
SET SERVER_KEYSTORE_PASSPHRASE=php-driver
SET "SERVER_TRUSTSTORE=!ABSOLUTE_BATCH_DIRECTORY!\.truststore"
SET SERVER_TRUSTSTORE_PASSPHRASE=php-driver
SET "CLIENT_KEYSTORE=%TEMP%\driver.keystore"
SET CLIENT_KEYSTORE_PASSPHRASE=php-driver
SET "CLIENT_KEYSTORE_OPENSSL_P12=%TEMP%\driver-keystore.p12"

REM Determine if keytool exists
SET KEYTOOL=keytool.exe
CALL :GETFULLPATH "!KEYTOOL!" KEYTOOL_FOUND_IN_PATH
IF NOT DEFINED KEYTOOL_FOUND_IN_PATH (
  IF DEFINED JAVA_HOME (
    SET "PATH=!JAVA_HOME!\bin;!PATH!"
    CALL :GETFULLPATH "!KEYTOOL!" KEYTOOL_FOUND_IN_MODIFIED_PATH
    IF NOT DEFINED KEYTOOL_FOUND_IN_MODIFIED_PATH (
      ECHO Unable to Find !KEYTOOL!: Ensure Java is installed and JAVA_HOME is defined
      EXIT /B !EXIT_CODE_MISSING_KEYTOOL!
    )
  ) ELSE (
    ECHO Unable to Find !KEYTOOL!: Ensure Java is JAVA_HOME is defined
    EXIT /B !EXIT_CODE_MISSING_KEYTOOL!
  )
)

REM Determine if OpenSSL exists
SET OPENSSL=openssl.exe
CALL :GETFULLPATH "!OPENSSL!" OPENSSL_FOUND_IN_PATH
IF NOT DEFINED OPENSSL_FOUND_IN_PATH (
  SET "OPENSSL_BUILD_BINARY_DIRECTORY=!ABSOLUTE_BATCH_DIRECTORY!\..\..\ext\build\dependencies\libs\openssl\bin"
  IF EXIST "!OPENSSL_BUILD_BINARY_DIRECTORY!" (
    SET "PATH=!OPENSSL_BUILD_BINARY_DIRECTORY!;!PATH!"
    CALL :GETFULLPATH "!OPENSSL!" OPENSSL_FOUND_IN_BUILD_PATH
    IF NOT DEFINED OPENSSL_FOUND_IN_BUILD_PATH (
      ECHO Unable to Find !OPENSSL!: Ensure extension was built using VC_BUILD.BAT
      EXIT /B !EXIT_CODE_MISSING_OPENSSL!
    )
  ) ELSE (
    ECHO Unable to Find !OPENSSL!: Ensure extension was built using VC_BUILD.BAT
    EXIT /B !EXIT_CODE_MISSING_OPENSSL!
  )
)

ECHO | SET /P=Cleaning older SSL certificates ... 
ERASE /Q "!SERVER_CERTIFICATE_PEM!" ^
  "!CLIENT_CERTIFICATE!" ^
  "!CLIENT_CERTIFICATE_PEM!" ^
  "!PRIVATE_KEY!" ^
  "!SERVER_KEYSTORE!" ^
  "!SERVER_TRUSTSTORE!" ^
  "!CLIENT_KEYSTORE!" ^
  "!CLIENT_KEYSTORE_OPENSSL_P12!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Erase SSL Certificates: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_ERASE_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating server keystore ... 
!KEYTOOL! -genkeypair -noprompt ^
  -keyalg RSA ^
  -validity 36500 ^
  -alias cassandra ^
  -keystore "!SERVER_KEYSTORE!" ^
  -storepass "!SERVER_KEYSTORE_PASSPHRASE!" ^
  -keypass !PASSPHRASE! ^
  -ext SAN="IP:!SERVER_IP_ADDRESS!" ^
  -dname "CN=Cassandra Server, OU=PHP Driver Tests, O=DataStax Inc., L=Santa Clara, ST=California, C=US"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Server Keystore: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating server certificate ... 
!KEYTOOL! -exportcert -noprompt ^
  -rfc ^
  -alias cassandra ^
  -keystore "!SERVER_KEYSTORE!" ^
  -storepass "!SERVER_KEYSTORE_PASSPHRASE!" ^
  -file "!SERVER_CERTIFICATE_PEM!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Server Certificate: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating client keystore ... 
!KEYTOOL! -genkeypair -noprompt ^
  -keyalg RSA ^
  -validity 36500 ^
  -alias driver ^
  -keystore "!CLIENT_KEYSTORE!" ^
  -storepass "!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -keypass !PASSPHRASE! ^
  -ext SAN="IP:!CLIENT_IP_ADDRESS!" ^
  -dname "CN=PHP Driver, OU=PHP Driver Tests, O=DataStax Inc., L=Santa Clara, ST=California, C=US"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Client Keystore: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating client certificate ... 
!KEYTOOL! -exportcert -noprompt ^
  -alias driver ^
  -keystore "!CLIENT_KEYSTORE!" ^
  -storepass "!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -file "!CLIENT_CERTIFICATE!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Client Certificate: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Importing client certificate into server truststore ... 
!KEYTOOL! -import -noprompt ^
  -alias cassandra ^
  -keystore "!SERVER_TRUSTSTORE!" ^
  -storepass "!SERVER_TRUSTSTORE_PASSPHRASE!" ^
  -file "!CLIENT_CERTIFICATE!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Import Client Certificate: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating client PEM certificate ... 
!KEYTOOL! -exportcert -noprompt ^
  -rfc ^
  -alias driver ^
  -keystore "!CLIENT_KEYSTORE!" ^
  -storepass "!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -file "!CLIENT_CERTIFICATE_PEM!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Client PEM Certificate: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

ECHO | SET /P=Generating client private PEM key ... 
!KEYTOOL! -importkeystore -noprompt ^
  -srcalias certificatekey ^
  -deststoretype PKCS12 ^
  -srcalias driver ^
  -srckeystore "!CLIENT_KEYSTORE!" ^
  -srcstorepass "!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -storepass "!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -destkeystore "!CLIENT_KEYSTORE_OPENSSL_P12!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Client Private P12 Key: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)

!OPENSSL! pkcs12 -nomacver -nocerts ^
  -in "!CLIENT_KEYSTORE_OPENSSL_P12!" ^
  -password pass:"!CLIENT_KEYSTORE_PASSPHRASE!" ^
  -passout pass:"!PASSPHRASE!" ^
  -out "!PRIVATE_KEY!"> NUL 2>&1
IF NOT !ERRORLEVEL! EQU 0 (
  ECHO FAILED!
  ECHO Unable to Generate Client Private PEM Key: !ERRORLEVEL!
  EXIT /B !EXIT_CODE_KEYTOOL_COMMAND_FAILURE!
)
ECHO done.

REM Disable delayed expansion and local variables
ENDLOCAL

REM Exit the batch operation (Ensures below functions are skipped
GOTO :EOF

REM Get full path for a given executable in the system PATH
REM
REM @param executable Executable to search for in PATH
REM @param return Full path with executable
:GETFULLPATH [executable] [return]
  FOR %%A IN ("%~1") DO SET %~2=%%~$PATH:A
  EXIT /B
