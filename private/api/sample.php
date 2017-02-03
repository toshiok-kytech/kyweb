<?php
// Library objects
$page = KYWeb::page();
$file = KYWeb::file();
$db   = KYWeb::db();
$mail = KYWeb::mail();
$api  = KYWeb::api();

// GET, POST, ACCESS_TOKEN
//$param = $page->get_post("param");
//$access_token = $api->get_access_token();

// Process
if ($_SERVER["REQUEST_METHOD"] === "GET")
{
	echo json_encode(array(
		"result"  => true, 
		"message" => "Hello, the access method was GET."
	));
	exit;
} else {
	echo json_encode(array(
		"result"  => true, 
		"message" => "Hello, the access method was POST."
	));
	exit;
}

$page->request_header(404);
?>