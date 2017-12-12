<?php
/**
 * KYWebクラス
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */

KYWeb::refuse_direct_access(".inc.php");

global $_STR;

// Clear cache
header("Expires: -1");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
clearstatcache();

// Require
require_once(PRIVATE_PATH . "/library/kycommon.inc.php");
require_once(PRIVATE_PATH . "/library/kyapi.inc.php");
require_once(PRIVATE_PATH . "/library/kydb.inc.php");
require_once(PRIVATE_PATH . "/library/kyfile.inc.php");
require_once(PRIVATE_PATH . "/library/kyhtml.inc.php");
require_once(PRIVATE_PATH . "/library/kymail.inc.php");
require_once(PRIVATE_PATH . "/library/kypage.inc.php");
require_once(PRIVATE_PATH . "/library/kysample.inc.php");

// Redirect to SSL
if (REDIRECT_SSL && $_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER['REQUEST_URI']);
    exit;
}

/**
 * KYWebクラスはフレームワーク内の他クラスをまとめたり、ページやAPIの処理を行い、その結果を取得します。
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */
class KYWeb {
    /** @var object|null KYWebのインスタンス */
    private static $_instance = null;

    /** @var string 共通htmlのパス */
    private $_common_html_path;

    /** @var string 共通phpのパス */
    private $_common_php_path;

    /** @var string ページhtmlのパス */
    private $_page_html_path;

    /** @var string ページphpのパス */
    private $_page_php_path;

    /** @var string APIのパス */
    private $_api_path;

    /** @var string ソースhtml */
    private $_source_html;

    /** @var string 結果html */
    private $_result_html;

    /**
     * KYWebクラスのシングルトンです。
     *
     * @return object KYWebのインスタンス
     *
     * {@source }
     * displays without a break in the flow
     *
     * @example private/library/example/instance.php
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * KYPageオブジェクトを返します。
     *
     * @return object KYPageオブジェクト
     */
    public static function page() {
        return KYPage::instance();
    }

    /**
     * KYFileオブジェクトを返します。
     *
     * @return object KYFileオブジェクト
     */
    public static function file() {
        return KYFile::instance();
    }

    /**
     * KYDBオブジェクトを返します。
     *
     * @return object KYDBオブジェクト
     */
    public static function db() {
        return KYDB::instance();
    }

    /**
     * KYMailオブジェクトを返します。
     *
     * @return object KYMailオブジェクト
     */
    public static function mail() {
        return KYMail::instance();
    }

    /**
     * KYApiオブジェクトを返します。
     *
     * @return object KYApiオブジェクト
     */
    public static function api() {
        return KYApi::instance();
    }

    /**
     * ページ(html + php)、または、APIの処理を実行します。
     *
     * 結果は `result` 関数で求めます。
     *
     * ※この関数は直接使用しません、公開フォルダにあります `page.php` から呼ばれます。
     *
     * @return object 自分自身(KYWebオブジェクト)
     */
    public function process() {
        if (CROSS_DOMAIN != "") {
            header("Access-Control-Allow-Origin: " . CROSS_DOMAIN);
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
            header("Access-Control-Allow-Headers: Authorization");
        }

        $page = KYPage::instance();

        $this->_source_html = "";
        $this->_result_html = "";

        //$http = ($page->is_ssl() ? "https://" : "http://");
        //$url  = $http . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        //$base_url = str_replace(WWW, "", $url);
        $base_url = $_SERVER["REQUEST_URI"];
        $base_url = str_replace(WWW, "", $base_url);
        $base_uri = explode("?", $base_url);
        $base_uri = explode("/", $base_uri[0]);
        $target   = $base_uri[1];

        if ($target == "page") {
            return $this->process_uri()->process_html()->process_php();
        } else if ($target == "api") {
            $this->_api_path = PRIVATE_PATH . "/api/" . $base_uri[2] . ".php";
            if (file_exists($this->_api_path) == true) {
                $this->_common_php_path = PRIVATE_PATH . "/php/common.php";
                if (file_exists($this->_common_php_path)) require_once($this->_common_php_path);

                require_once($this->_api_path);
                return $this;
            }
        }
		
        header("HTTP/1.1 404 Not Found");
        exit;
    }

    /**
     * ページ(html + php)の処理結果(html)を取得します。
     *
     * ※この関数は直接使用しません、公開フォルダにあります `page.php` から呼ばれます。
     *
     * @return string 処理結果(html)
     */
    public function result() {
        return $this->_result_html;
    }

    /**
     * Process URI
     *
     * @access private
     */
    private function process_uri() {
        // Process URI
        $uri      = explode("/", $_SERVER['REQUEST_URI']);
        $this_url = WWW . "/page";

        while (true) {
            $page_name = $uri[count($uri) - 1];
            $page_name = preg_split("/\?/", $page_name);
            $page_name = $page_name[0];

            KYPage::instance()->name($page_name);

            $this->_common_html_path = PUBLIC_PATH . "/html/common.html";
            $this->_common_php_path  = PRIVATE_PATH . "/php/common.php";
            $this->_page_html_path   = PUBLIC_PATH . "/html/{$page_name}.html";
            $page_html_mobile_path   = PUBLIC_PATH . "/html/{$page_name}_mobile.html";
            $this->_page_php_path    = PRIVATE_PATH . "/php/{$page_name}.php";

            // Is common html
            if ($page_name === "common") {
                header("Location: " . WWW);
                exit;
            }

            // Find html (mobile)
            if (KYWeb::page()->is_mobile() == true && file_exists($page_html_mobile_path) == true) {
                $this->_page_html_path = $page_html_mobile_path;
            }

            // Find html or php
            if (file_exists($this->_page_html_path) == true || file_exists($this->_page_php_path) == true) {
                if (file_exists($this->_page_html_path) == false) $this->_page_html_path = "";
                if (file_exists($this->_page_php_path) == false) $this->_page_php_path = "";
                return $this;
            }

            // Not find html and php
            if (implode("/", $uri) != $this_url) {
                array_pop($uri);
                if (count($uri) <= 0) {
                    header("Location: " . WWW);
                    exit;
                }
            } else {
                // Is root page
                header("Location: " . WWW);
                exit;
            }
        }
    }

    /**
     * Process HTML
     *
     * @access private
     */
    private function process_html() {
        if ($this->_page_html_path == "") {
            return $this;
        }

        // Load html page
        $this->_source_html = file_get_contents($this->_page_html_path);

        // ++: Remove html extention
        $this->_source_html = preg_replace("/page\/(.*)\.html/", "page/$1", $this->_source_html);

        // ++: Replace common source
        $common_html = "";
        if (file_exists($this->_common_html_path) == true) {
            $common_html = file_get_contents($this->_common_html_path);
        }

        if ($common_html != "") {
            $tags = array("COMMON");
            foreach ($tags as $tag) {
                preg_match_all("|<!--\[{$tag}:.*\]-->|U", $this->_source_html, $matches, PREG_PATTERN_ORDER);
                $match_tags = array_unique($matches[0]);
                $assign = array();

                foreach ($match_tags as $match_tag) {
                    $match_tag_original = $match_tag;

                    if (KYWeb::page()->is_logined() == true) {
                        $match_tagLogined = str_replace("]-->", "(LOGINED)]-->", $match_tag);
                        $pos1 = strpos($common_html, $match_tagLogined);
                        if ($pos1 !== false) {
                            $match_tag = $match_tagLogined;
                        }
                    }

                    $pos1 = strpos($common_html, $match_tag);
                    if ($pos1 !== false) {
                        $pos1 = $pos1 + strlen($match_tag);
                        $pos2 = strpos($common_html, $match_tag, $pos1);
                        if ($pos2 !== false) {
                            $assign[$match_tag_original] = trim( substr($common_html, $pos1, ($pos2 - $pos1)) );
                        }
                    }
                }
                $this->_source_html = strtr($this->_source_html, $assign);
                $this->_result_html = $this->_source_html;
            }
        } else {
            $this->_result_html = $this->_source_html;
        }

        return $this;
    }

    /**
     * Process PHP
     *
     * @access private
     */
    private function process_php() {
        KYWeb::page()->html($this->_result_html);
        if (file_exists($this->_common_php_path)) require_once($this->_common_php_path);
        if (file_exists($this->_page_php_path)) require_once($this->_page_php_path);
        $this->_result_html = KYWeb::page()->process()->result();
        return $this;
    }

    /**
     * phpファイルに直接アクセスを拒否するのに使用します。
     *
     * @param string $ext ファイルの拡張子
     *
     * @return void
     *
     * @example private/library/example/kyweb_refuse_direct_access.php
     */
    public static function refuse_direct_access($ext) {
        if (substr(basename($_SERVER["PHP_SELF"]), -strlen($ext)) == $ext) {
            header("HTTP/1.1 404 Not Found");
            exit;
        }
    }
}