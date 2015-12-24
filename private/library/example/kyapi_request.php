<?php
// Library objects
$api  = KYWeb::api();

$request_data = array(
	"url"          => "http://example.com/api/login",
	"method"       => "post",
	"format"       => "json",
	"access_token" => "ABCDEFGHIJKLMN1234567890",
	"values"       => array(
	    "arg1" => "val1",
	    "arg2" => "val2",
	),
);

$response_data = $api->request($request_data);
print_r($response_data["status"]);
print_r($response_data["headers"]);
print_r($response_data["body"]);
print_r($response_data["curl_info"]);
?>