<html>
<body>
	{VAR1} {VAR2}
</body>
</html>

<?php
// Library objects
$page = KYWeb::page();


$page->assign("VAR1", "Hello");
$page->assign("VAR2", "World");

// or

$page->assign(array(
	"VAR1" => "Hello",
	"VAR2" => "World",
));
?>