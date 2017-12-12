<?php
/********************************************************************************
* BASE CONFIG
********************************************************************************/

// http://php.net/manual/en/function.error-reporting.php
error_reporting(E_ALL);// & ~E_NOTICE & ~E_WARNING);
ini_set("display_errors", 1);

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
define("LANGUAGE", "ja");


/********************************************************************************
* DATABASE
********************************************************************************/

define("DB_CONF_1", '{"ENGINE":"MySQL", "HOST":"localhost", "PORT":"3306", "DATABASE":"", "USER":"", "PASSWORD":""}');
define("ROW_COUNT", 50);


/********************************************************************************
* MAIL
********************************************************************************/

// Remove comment for select which mail send method for use.
//define("MAIL_METHOD", "PEAR_MAIL");
//define("MAIL_METHOD", "SENDGRID");

if (MAIL_METHOD == "PEAR_MAIL") {
    define("PEAR_MAIL", "Mail.php");
    define("SMTP_SERVER", "");
    define("SMTP_PORT", 587);
    define("SMTP_AUTH", true);
    define("SMTP_USER", "");
    define("SMTP_PASS", "");

} else if (MAIL_METHOD == "SENDGRID") {
    define("SENDGRID", "https://api.sendgrid.com/");
    define("SNEDGRID_USER", "");
    define("SNEDGRID_PASS", "");
}

define("MAIL_FROM", "no_replay@charpy.jp");


/********************************************************************************
 * CURL
 ********************************************************************************/

define("CURL_CACERT_URL", "https://curl.haxx.se/ca/cacert.pem");
define("CURL_CACERT_PATH", PRIVATE_PATH . "/file/cacert.pem");
define("CURL_CACERT_UPDATE", 86400);        // 24h * 60m * 60s = 86400s

?>