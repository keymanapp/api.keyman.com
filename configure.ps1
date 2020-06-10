# Install Chocolatey
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))


# Install latest PHP in chocolatey
$installDir = "c:\tools\php"
choco install php --params="/InstallDir:$installDir"

# Install latest Composer in chocolatey
choco install composer --ia "/DEV=$installDir /PHP=$installDir"

# update path to extensions and enable curl and mbstring extensions, and enable php openssl extensions.
((Get-Content -path $installDir\php.ini -Raw) -replace ';extension=curl','extension=curl' -replace ';extension=mbstring','extension=mbstring' -replace ';extension_dir = "ext"','extension_dir = "ext"' -replace 'extension=";php_openssl.dll"','extension_dir = "php_openssl.dll"') | Set-Content -Path $installDir\php.ini

Invoke-WebRequest -outfile pdo.zip https://github.com/microsoft/msphpsql/releases/download/v5.8.0/Windows-7.4.zip
Expand-Archive pdo.zip -DestinationPath pdo\
copy pdo\Windows-7.4\x64\php_pdo_sqlsrv_74_nts.dll c:\tools\php\ext\
Add-Content -path c:\tools\php\php.ini -value '','extension=php_pdo_sqlsrv_74_nts.dll','mssql.secure_connection=Off','extension=php_intl.dll'

# Install SQL Server
choco install sql-server-2019 --no-progress --params "'/IGNOREPENDINGREBOOT /INSTANCEID=KEYMANAPI /INSTANCENAME=KEYMANAPI /SAPWD=Password1! /SECURITYMODE=SQL /UPDATEENABLED=FALSE /FEATURES=SQLENGINE,FULLTEXT'"
