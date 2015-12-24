<?php
// URLが次の場合　http://domain.com/folder?arg0=val0&arg1=val1

// Library objects
$page = KYWeb::page();
$url_info = $page->get_url_info();

// $url_infoの中身
/*
array (
	"dirname"  => "http://domain.com/folder"
	"basename" => "file?arg0=val0&arg1=val1"
	"filename" => "file?arg0=val0&arg1=val1"
	"scheme"   => "http"
	"host"     => "domain.com"
	"path"     => "/folder/file"
	"query"    => "arg0=val0&arg1=val1"
)
*/
?>