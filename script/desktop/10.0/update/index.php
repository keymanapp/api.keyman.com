<?php
  require_once('../../../../tools/base.inc.php');
  require_once('../../../../tools/onlineupdate.php');

  $u = new OnlineUpdate('windows', '/^keyman(desktop)?.+\.exe$/');
  $u->execute();
?>