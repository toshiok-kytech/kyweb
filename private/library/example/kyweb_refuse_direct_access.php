<?php
// ファイル名が example.php の場合
KYWeb::refuse_direct_access(".php");

// ファイル名が example.inc.php の場合
KYWeb::refuse_direct_access(".inc.php");

// ファイル名が example.sql.php の場合
KYWeb::refuse_direct_access(".sql.php");
?>