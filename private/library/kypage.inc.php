<?php
/**
 * KYPageクラス
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */

KYWeb::refuse_direct_access(".inc.php");

/**
 * KYPageクラスは指定ページ(html + php)の処理をし最終htmlを出力ます。
 *
 * ページの `html` デザイン部分は `public/html` `public/css` `publicimg` `public/js` フォルダに保存します。
 *
 * ページの `php` サーバ機能部分は `private/php` フォルダに保存します。
 *
 * また、ページのphp部分で使用できるユーティリティ関数が格納されています。
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */
class KYPage extends KYHtml {
    /** @var object|null KYPageのインスタンス */
    private static $_instance = null;

    /** @var string ページ名 */
    private $_name;

    /** @var array サンプル配列 */
    private $_samples;

    /** @var int タブレットブラウザ確認フラグ */
    private $_tablet_browser;

    /** @var int モバイルブラウザ確認フラグ */
    private $_mobile_browser;

    /**
     * コンストラクタ
     */
    public function __construct() {
        call_user_func_array('parent::' . __FUNCTION__, func_get_args());
        $this->_samples = array();
        $this->detect_device();

        // Load string
        $str_path = PRIVATE_PATH . "/string";
        $file_list = KYFile::instance()->file_list($str_path);
        foreach ($file_list as $fileName) {
            if (substr(basename($fileName), -7) == "." . LANGUAGE . ".php") {
                include_once($str_path . "/{$fileName}");
            }
        }
    }

    /**
     * KYPageクラスのシングルトンです。
     *
     * @return object KYPageのインスタンス
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
     * ページ名を set, get します。
     *
     * ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
     *
     * @param string $name ページ名、パラメータなしの場合 get になります
     *
     * @return mixed setの場合、自分自身(KYPageオブジェクト) | getの場合、ページ名
     */
    public function name($name = NULL) {
        if ($name !== NULL) {
            // set
            $this->_name = $name;
            return $this;
        }
        // get
        return $this->_name;
    }

    /**
     * ページhtml内にある `<!--[SAMPLE:タグ名]-->` を検索し、KYSampleオブジェクトとして返します。
     *
     * @param string $tag サンプルのタグ名
     *
     * @retrun object KYSampleオブジェクト
     *
     * @example private/library/example/kysample_add.php
     */
    public function sample($tag) {
        $tag = "<!--[SAMPLE:{$tag}]-->";
        $pos1 = strpos($this->_html, $tag);
        if ($pos1 !== false) {
            $pos2 = strpos($this->_html, $tag, $pos1 + strlen($tag));
            if ($pos2 !== false) {
                $pos2 += strlen($tag);
                $sample_html = substr($this->_html, $pos1, ($pos2 - $pos1));
                $this->_html = str_replace($sample_html, $tag, $this->_html);
                $sample_html = ltrim(str_replace($tag, "", $sample_html));

                $sample = new KYSample();
                $sample->tag($tag);
                $sample->html($sample_html);
                $this->_samples[] = $sample;
                return $sample;
            }
        }
        return NULL;
    }

    /**
     * html内で書き換える {タグ名} と値を指定します。
     *
     * @param string|array $param1 タグ名、もしくは、(タグ名, 値)の配列
     * @param string $param2 書き替える値、$param1 が配列の場合は必要ない
     *
     * @return object 自分自身(KYPageオブジェクト)
     *
     * @example private/library/example/kyhtml_assign.php
     */
    public function assign() {
        return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
    }

    /**
     * ページ(html + php)の処理を実行します。
     *
     * 結果は `result` 関数で求めます。
     *
     * ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
     *
     * @return object 自分自身(KYPageオブジェクト)
     */
    public function process() {
        call_user_func_array('parent::' . __FUNCTION__, func_get_args());

        $assign = array();

        foreach ($this->_samples as $sample) {
            $sample->process();
            $assign[$sample->tag()] = rtrim($sample->result());
        }

        $this->_result = strtr($this->_result, $assign);

        return $this;
    }

    /**
     * デバイスがタブレット、または、モバイルなのかを判定します。
     *
     * ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
     *
     * @return object 自分自身(KYPageオブジェクト)
     */
    private function detect_device() {
        $this->_tablet_browser = 0;
        $this->_mobile_browser = 0;
        $http_user_agent = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";

        if (preg_match("/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i", strtolower($http_user_agent))) {
            $this->_tablet_browser++;
        }

        if (preg_match("/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i", strtolower($http_user_agent))) {
            $this->_mobile_browser++;
        }

        $http_accept = "";
        if (isset($_SERVER["HTTP_ACCEPT"])) $http_accept = strtolower($_SERVER["HTTP_ACCEPT"]);

        if (strpos($http_accept,"application/vnd.wap.xhtml+xml") > 0
            or
            isset($_SERVER["HTTP_X_WAP_PROFILE"])
            or
            isset($_SERVER["HTTP_PROFILE"])
        )
        {
            $this->_mobile_browser++;
        }

        $mobile_ua = strtolower(substr($http_user_agent, 0, 4));
        $mobile_agents = array(
            "w3c ","acs-","alav","alca","amoi","audi","avan","benq","bird","blac",
            "blaz","brew","cell","cldc","cmd-","dang","doco","eric","hipt","inno",
            "ipaq","java","jigs","kddi","keji","leno","lg-c","lg-d","lg-g","lge-",
            "maui","maxo","midp","mits","mmef","mobi","mot-","moto","mwbp","nec-",
            "newt","noki","palm","pana","pant","phil","play","port","prox",
            "qwap","sage","sams","sany","sch-","sec-","send","seri","sgh-","shar",
            "sie-","siem","smal","smar","sony","sph-","symb","t-mo","teli","tim-",
            "tosh","tsm-","upg1","upsi","vk-v","voda","wap-","wapa","wapi","wapp",
            "wapr","webc","winw","winw","xda ","xda-");

        if (in_array($mobile_ua,$mobile_agents)) {
            $this->_mobile_browser++;
        }

        if (strpos(strtolower($http_user_agent),"opera mini") > 0) {
            $this->_mobile_browser++;
            //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER["HTTP_X_OPERAMINI_PHONE_UA"])?$_SERVER["HTTP_X_OPERAMINI_PHONE_UA"]:(isset($_SERVER["HTTP_DEVICE_STOCK_UA"])?$_SERVER["HTTP_DEVICE_STOCK_UA"]:""));
            if (preg_match("/(tablet|ipad|playbook)|(android(?!.*mobile))/i", $stock_ua)) {
                $this->_tablet_browser++;
            }
        }

        return $this;
    }

    /**
     * PUT, DELETE のパラメータをパースして連想配列として返します。
     *
     * ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
     *
     * @return array パースしたパラメータ
     */
    private function parse_put() {
        /* PUT data comes in on the stdin stream */
        $putdata = fopen("php://input", "r");

        /* Open a file for writing */
        // $fp = fopen("myputfile.ext", "w");

        $raw_data = '';

        /* Read the data 1 KB at a time
        and write to the file */
        while ($chunk = fread($putdata, 1024))
            $raw_data .= $chunk;

        /* Close the streams */
        fclose($putdata);

        // Fetch content and determine boundary
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        if(empty($boundary)){
            parse_str($raw_data,$data);
            return $data;
        }

        // Fetch each part
        $parts = array_slice(explode($boundary, $raw_data), 1);
        $data = array();

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") break;

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                $filename = null;
                $tmp_name = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                list(, $type, $name) = $matches;

                //Parse File
                if(isset($matches[4]) )
                {
                    //if labeled the same as previous, skip
                    if(isset($_FILES[$matches[2]]))
                    {
                        continue;
                    }

                    //get filename
                    $filename = $matches[4];

                    //get tmp name
                    $filename_parts = pathinfo($filename);
                    $tmp_name = tempnam(sys_get_temp_dir(), $filename_parts['filename']);

                    file_put_contents($tmp_name, $body);

                    //populate $_FILES with information, size may be off in multibyte situation

                    $_FILES[$matches[2]] = array(
                        'error'=>0,
                        'name'=>$filename,
                        'tmp_name'=>$tmp_name,
                        'size'=>strlen($body),
                        'type'=>$value
                    );

                    //place in temporary directory
                    file_put_contents($tmp_name, $body);
                }
                //Parse Field
                else
                {
                    $data[$name] = substr($body, 0, strlen($body) - 2);
                }
            }
        }
        return $data;
    }

    /********************************************************************************
     * Utilities Methods
     ********************************************************************************/

    /**
     * ユーティリティ: フォームから渡された変数を GET、POST の順に取得します。
     *
     * @param string $name 変数名
     *
     * @param mixed %default 変数名がない場合のデフォルト値
     *
     * @return mixed 変数値、または、デフォルト値
     */
    public function get_post($name, $default = "") {
        $value = isset($_GET[$name]) ? $_GET[$name] : NULL;
        if ($value == NULL) { $value = isset($_POST[$name]) ? $_POST[$name] : NULL; }
        if ($value == NULL) { $value = $default; }
        return ($value);
    }

    /**
     * ユーティリティ: フォームから渡された変数を POST、GET の順に取得します。
     *
     * @param string $name 変数名
     *
     * @param mixed %default 変数名がない場合のデフォルト値
     *
     * @return mixed 変数値、または、デフォルト値
     */
    public function post_get($name, $default = "") {
        $value = isset($_POST[$name]) ? $_POST[$name] : NULL;
        if ($value == NULL) { $value = isset($_GET[$name]) ? $_GET[$name] : NULL; }
        if ($value == NULL) { $value = $default; }
        return ($value);
    }

    /**
     * ユーティリティ: フォームから渡された PUT 変数を取得します。
     *
     * @param string $name 変数名
     *
     * @param mixed %default 変数名がない場合のデフォルト値
     *
     * @return mixed 変数値、または、デフォルト値
     */
    public function put_delete($name, $default = "") {
        static $params = null;
        if ($params == null) {
            $params = self::parse_put();
        }
        $value = isset($params[$name]) ? $params[$name] : $default;
        return ($value);
    }

    /**
     * ユーティリティ: URLをパーサし、現ページ名からのURLパラメータを取得します。
     *
     * @param int $index 取得したいパラメータ番号、パラメータなしの場合、全てのURLパラメータを配列として返します
     *
     * @return mixed パラメータ値、または、パラメータを配列
     */
    public function uri($index = NULL) {
        $uri_args = preg_replace("/.*.php/", "", $_SERVER["PHP_SELF"]);
        $uri_args = explode("/", $uri_args);
        array_shift($uri_args);
        if ($index === NULL) {
            return $uri_args;
        } else {
            return isset($uri_args[$index]) ? $uri_args[$index] : "";
        }
    }

    /**
     * セッション値を set, get します。
     *
     * @param string $name セッション名
     *
     * @param mixed $value セッション値、空白の場合セッション値を削除
     *
     * @return mixed setの場合、void | getの場合、セッション値
     *
     * ※セッション名が存在しないと `false` を返します
     */
    public function session($name, $value = NULL) {
        if ($value !== NULL) {
            // set
            if (isset($_SESSION) == false) {
                session_start();
            }

            if ($value == "") {
                unset($_SESSION[$name]);
            } else {
                $_SESSION[$name] = $value;
            }
        }
        // get
        return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
    }

    /**
     * セッション全てをクリアーします。
     *
     * @return void
     */
    public function clear_session() {
        unset($_SESSION);
        session_destroy();
    }

    /**
     * ログイン情報を set, get します。
     *
     * @param mixed $value ログイン情報
     *
     * @return mixed setの場合、void | getの場合、ログイン情報
     *
     * ※ログイン情報が存在しないと `false` を返します
     */
    public function login($value = NULL) {
        if ($value !== NULL) {
            // set
            $this->session("login", $value);
        }
        // get
        return $this->session("login");
    }

    /**
     * ログイン情報を削除します。
     *
     * @return void
     */
    public function logout() {
        $this->session("login", "");
    }

    /**
     * ログイン情報があるか確認します。
     *
     * @return boolean true=ログイン情報あり | false=ログイン情報なし
     */
    public function is_logined() {
        return ($this->session("login") != false);
    }

    /**
     * HTTPリクエストヘッダーを出力し、php処理を停止します。
     *
     * @param int $code コード(400=リクエスト不正, 401=認証失敗, 404=未検出, 500=サーバ内部エラー, その他)
     *
     * @param string $realm 認証領域
     *
     * @param string $error エラー
     *
     * @param string $error_description エラー内容
     *
     * @return void
     */
    public function request_header($code, $realm = "", $error = "",  $error_description = "") {
        if ($code === 400) {
            header("WWW-Authenticate: kyweb realm=\"{$realm}\", error=\"{$error}\", error_description=\"{$error_description}\"");
            header("HTTP/1.1 400 Bad Request");
            header("Content-type: text/html");
            exit;
        } else if ($code === 401) {
            header("WWW-Authenticate: kyweb realm=\"{$realm}\"");
            header("HTTP/1.1 401 Unauthorized");
            header("Content-type: text/html");
            exit;
        } else if ($code === 404) {
            header("HTTP/1.1 404 Not Found");
            header("Content-type: text/html");
            exit;
        } else if ($code === 500) {
            header("HTTP/1.1 500 Internal Server Error");
            header("Content-type: text/html");
            exit;
        } else {
            header("HTTP/1.1 {$code}");
            header("Content-type: text/html");
            exit;
        }
    }

    /**
     * ログ出力をします。メールアドレスが設定されていればメールを送ります。
     *
     * @param string $title タイトル名
     *
     * @param string $source ソース
     *
     * @param string $message メッセージ
     *
     * @param string $email メールアドレス (Optional)
     *
     * @return object 自分自身(KYPageオブジェクト)
     */
    public function write_log($title, $source, $message, $email = NULL) {
        $text = date("Y-m-d H:i:s") . " ... {$title} ({$source}) : \n{$message}\n\n";
        error_log($text, 3, PRIVATE_PATH . "/log/log_" . date("Y_m") . ".log");
        if ($email != NULL) {
            error_log($text, 1, $email);
        }
        return $this;
    }

    /**
     * URL情報を取得します。
     *
     * @return array URL情報
     *
     * @example private/library/example/kyweb_get_url_info.php
     */
    public function get_url_info() {
        $url = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $info = array_merge(pathinfo($url), parse_url($url));
        return $info;
    }

    /**
     * クライアントがタブレット端末かどうかを判定します。
     *
     * @return boolean 判定結果
     */
    public function is_tablet() {
        if ($this->_tablet_browser > 0) {
            return true;
        }
        return false;
    }

    /**
     * クライアントがマバイル端末かどうかを判定します。
     *
     * @return boolean 判定結果
     */
    public function is_mobile() {
        if ($this->_tablet_browser > 0) {
            return false;
        } else if ($this->_mobile_browser > 0) {
            return true;
        }
        return false;
    }

    /**
     * 通信がSSL上なのかを判定します。
     *
     * @return boolean 判定結果
     */
    public function is_ssl() {
        return (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || $_SERVER["SERVER_PORT"] == 443;
    }
}