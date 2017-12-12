<?php
require_once(dirname(__FILE__) . "/require.inc.php");

header("Content-Type: text/html; charset=UTF-8");
echo KYWeb::instance()->process()->result();
?>