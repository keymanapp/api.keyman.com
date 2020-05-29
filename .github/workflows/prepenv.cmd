echo $mssqlconninfo="sqlsrv:Server=(localdb)\keymanapi;Integrated Security=true;Database="; > tools\db\localenv.php
echo $mssqlconninfo_master="sqlsrv:Server=(localdb)\keymanapi;Integrated Security=true;Database=master"; >> tools\db\localenv.php
echo $mssqldb0 = 'keyboards'; >> tools\db\localenv.php
echo $mssqldb1 = 'keyboards_1'; >> tools\db\localenv.php
echo $mysqlpw = '' >> tools\db\localenv.php
echo $mysqluser = '' >> tools\db\localenv.php
