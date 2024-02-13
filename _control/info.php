<?php

require_once __DIR__ . '/../tools/autoload.php';
require_once __DIR__ . '/../tools/db/servervars.php';

use Keyman\Site\Common\KeymanHosts;

$kh = KeymanHosts::Instance();
$dci = new \DatabaseConnectionInfo();
$schema = $dci->getActiveSchema();
if(file_exists(__DIR__ . '/../.data/BUILDING')) $status = 'Building ' . $dci->getInactiveSchema();
else if(file_exists(__DIR__ . '/../.data/MUST_REBUILD')) $status .= 'Must Rebuild';
else $status = 'Ready';

$date = file_exists(__DIR__ . '/../.data/LAST_REBUILD_DATE') ?
  file_get_contents(__DIR__ . '/../.data/LAST_REBUILD_DATE') :
  'Unknown';

echo <<<END
<h1>api.keyman.com</h1>
<table border='1'>
<tr><td>host</td><td>{$kh->api_keyman_com_host}</td></tr>
<tr><td>tier</td><td>{$kh->Tier()}</td></tr>
<tr><td>schema</td><td>$schema</td></tr>
<tr><td>database build status</td><td>$status</td></tr>
<tr><td>last database build completed</td><td>$date</td></tr>
</table>
END;

echo "<p><a href='./alive'>Alive</a></p>";
echo "<p><a href='./ready'>Ready</a></p>";
