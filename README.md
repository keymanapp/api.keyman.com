# api.keyman.com

## Configuration

## Prerequisites

* PHP 7.4

Using Chocolatey, installs to c:\tools\php74:

```
  choco install php
```

Assuming PHP is installed in C:\tools\php74 run the following powershell (admin):
```
  Invoke-WebRequest -outfile pdo.zip https://github.com/microsoft/msphpsql/releases/download/v5.8.0/Windows-7.4.zip
  Expand-Archive pdo.zip -DestinationPath pdo\
  copy pdo\Windows-7.4\x64\php_pdo_sqlsrv_74_nts.dll c:\tools\php74\ext\
  Add-Content -path c:\tools\php74\php.ini -value '','extension=php_pdo_sqlsrv_74_nts.dll','mssql.secure_connection=Off','extension=php_intl.dll'
```

* MS SQL Server including FullText

```
choco --no-progress install sql-server-2019 --params "'/INSTANCEID=KEYMANAPI /INSTANCENAME=KEYMANAPI /SAPWD=Password1! /SECURITYMODE=SQL /UPDATEENABLED=FALSE /FEATURES=SQLENGINE,FULLTEXT'"
```

