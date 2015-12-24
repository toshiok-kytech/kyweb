<html>
<body>
	<table>
		<!--[SAMPLE:TEST]-->
		<tr>
			<td>{VALUE1}</td>
			<td>{VALUE2}</td>
		</tr>
		<!--[SAMPLE:TEST]-->
	</table>
</body>
</html>

<?php
// Library objects
$page = KYWeb::page();

$test = $page->sample("TEST");

// Assign one by one
$item = $test->add();
$item->assign("VALUE1", "abc");
$item->assign("VALUE2", "xyz");

// Assign with array
$test->add(array(
	"VALUE1" => "abc", 
	"VALUE2" => "xyz"
));
?>