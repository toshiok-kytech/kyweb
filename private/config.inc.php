<?php
/********************************************************************************
* BASE CONFIG
********************************************************************************/

// http://php.net/manual/en/function.error-reporting.php
error_reporting(E_ALL);

// http://php.net/manual/en/timezones.php
date_default_timezone_set("Asia/Tokyo");


/********************************************************************************
* SESSION
********************************************************************************/

// session.gc_maxlifetime : 1h x 60m x 60s = 3600s
ini_set("session.gc_maxlifetime", 3600);

// Probability that the garbage collection process is started on every session 
// initialization. ( gc_probability / gc_divisor ... 1 / 1 = 100% )
ini_set("session.gc_probability", 1);
ini_set("session.gc_divisor", 1);

session_start();


/********************************************************************************
* WEB
********************************************************************************/

define("WWW", "/sample.com");
define("INDEX", WWW. "/page/top");
define("PATH_PUBLIC", dirname(__FILE__) . "/../public");
define("PATH_PRIVATE", dirname(__FILE__) . "/../private");
define("REDIRECT_SSL", false);
define("CROSS_DOMAIN", "*");


/********************************************************************************
* DATABASE
********************************************************************************/

// Multiple database server
//define("DB_CONF_0", '{"HOST":"localhost", "PORT":"3306", "DATABASE":"", "USER":"", "PASSWORD":""}');
//define("DB_CONF_1", '{"HOST":"localhost", "PORT":"3306", "DATABASE":"", "USER":"", "PASSWORD":""}');

// Single database server
define("DB_CONF", '{"HOST":"localhost", "PORT":"3306", "DATABASE":"", "USER":"", "PASSWORD":""}');

define("ROW_COUNT", 50);

/********************************************************************************
* MAIL
********************************************************************************/

define("PEAR_MAIL", "Mail.php");
define("SMTP_SERVER", "localhost");
define("SMTP_PORT", 25);
define("SMTP_AUTH", false);
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("SMTP_FROM", "");

?>