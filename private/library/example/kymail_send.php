<?php
$mail->template("sample");
$mail->assign("NAME", "xxx");
$mail->send("from@sample.com", "to@sample.com", "cc@sample.com");
?>