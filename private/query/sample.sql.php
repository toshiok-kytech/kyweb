<?php
KYWeb::refuse_direct_access(".sql.php");

function qGET_SAMPLE($db, $params = NULL) {
	$user_id = $db->int($params["user_id"]);

	$sql = "SELECT * FROM `m_user` WHERE `user_id` = {$user_id}";
	
	return($sql);
}

?>