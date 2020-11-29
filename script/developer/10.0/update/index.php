<?php
  require_once('../../../../tools/base.inc.php');
  require_once('../../../../tools/onlineupdate.php');

  $u = new OnlineUpdate('developer', '/^keymandeveloper.+\.exe$/');
  $u->execute();
?>