<?php
/**
 * KYFileクラス
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */

KYWeb::refuse_direct_access(".inc.php");

/**
 * KYDBクラスはMySQLデータベースを操作します。
 *
 * クエリは `/private/query` フォルダに `*.sql.php` として保存します。
 *
 * @license http://apache.org/licenses/LICENSE-2.0
 *
 * @copyright ©kyphone
 */
class KYDB {
    /** @var object|null KYDBのインスタンス */
    private static $_instance = null;

    /** @var int サーバー接続設定数 (config.inc.php で定義) */
    private $_cnf_cnt;

    /** @var array サーバー接続情報の配列 (config.inc.php で定義) */
    private $_cnf_arr;

    /** @var int MySQL リンク ID */
    private $_link;

    /** @var int エラー番号 */
    private $_errno;

    /** @var string エラー内容 */
    private $_error;

    /** @var string SQL文 */
    private $_sql;

    /** @var mixed クエリの実行リソース */
    private $_res;

    /**
     * コンストラクタ
     */
    private function __construct() {
        // Load query
        $query_path = PATH_PRIVATE . "/query";
        $file_list = KYFile::instance()->file_list($query_path);
        foreach ($file_list as $fileName) {
            if (substr(basename($fileName), -8) == ".sql.php") {
                include_once($query_path . "/{$fileName}");
            }
        }
    }

    /**
     * KYDBクラスのシングルトンです。
     *
     * @return object KYDBのインスタンス
     *
     * @example private/library/example/instance.php
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            $db = self::$_instance;
            $db->_link = 0;

            $db->_cnf_cnt = 0;
            $db->_cnf_arr = [];
            if (defined("DB_CONF")) {
                $cnf_str = constant("DB_CONF");
                $cnf_arr = json_decode($cnf_str);
                $db->_cnf_arr[$db->_cnf_cnt] = array(
                    "HOST"     => isset($cnf_arr["HOST"])        ? $cnf_arr["HOST"]     : "localhost",
                    "USER"     => isset($cnf_arr["USER"])        ? $cnf_arr["USER"]     : "",
                    "PASSWORD" => isset($cnf_arr["PASSWORD"])    ? $cnf_arr["PASSWORD"] : "",
                    "DATABASE" => isset($cnf_arr["DATABASE"])    ? $cnf_arr["DATABASE"] : "",
                    "PORT"     => intval(isset($cnf_arr["PORT"]) ? $cnf_arr["PORT"]     : "3306")
                );
                $db->_cnf_cnt++;
            } else {
                while ( defined("DB_CONF_{$db->_cnf_cnt}") ) {
                    $cnf_str = constant("DB_CONF_{$db->_cnf_cnt}");
                    $cnf_arr = json_decode($cnf_str);
                    $db->_cnf_arr[$db->_cnf_cnt] = array(
                        "HOST"     => isset($cnf_arr["HOST"])        ? $cnf_arr["HOST"]     : "localhost",
                        "USER"     => isset($cnf_arr["USER"])        ? $cnf_arr["USER"]     : "",
                        "PASSWORD" => isset($cnf_arr["PASSWORD"])    ? $cnf_arr["PASSWORD"] : "",
                        "DATABASE" => isset($cnf_arr["DATABASE"])    ? $cnf_arr["DATABASE"] : "",
                        "PORT"     => intval(isset($cnf_arr["PORT"]) ? $cnf_arr["PORT"]     : "3306")
                    );
                    $db->_cnf_cnt++;
                }
            }
        }

        return self::$_instance;
    }

    /**
     * データベースへの接続を行います。
     *
     * @param int $cnf_index 接続するデータベース設定Index (Optional default=0)
     *
     * @return mixed 接続結果、失敗の場合 false を返します
     */
    public function connect($cnf_index = 0) {
        $this->_errno = 0;
        $this->_error = "";

        $this->_link = mysqli_connect(
            $this->_cnf_arr[$cnf_index]["HOST"],
            $this->_cnf_arr[$cnf_index]["USER"],
            $this->_cnf_arr[$cnf_index]["PASSWORD"],
            "",
            $this->_cnf_arr[$cnf_index]["PORT"]);
        if ($this->_link == false) {
            $this->_errno = 1;
            $this->_error = "Can't connect to database.";
            return (false);
        }

        $result = mysqli_select_db($this->_link, $this->_cnf_arr[$cnf_index]["DATABASE"]);
        if ($result == false) {
            $this->_errno = 2;
            $this->_error = "Can't select to database.";
            return (false);
        }

        return ($result);
    }

    /**
     * データベースへの切断を行います。
     *
     * @return mixed 切断結果、失敗の場合 false を返します
     */
    public function close() {
        $result = mysqli_close($this->_link);
        if ($result == false) {
            return (false);
        }
        return ($result);
    }

    /**
     * トランザクションを開始します。
     *
     * @return mixed 開始結果、失敗の場合 false を返します
     */
    public function begin() {
        $result = mysqli_query($this->_link, "BEGIN");
        if ($result == false) {
            $this->_errno = mysqli_errno($this->_link);
            $this->_error = mysqli_error($this->_link);
        }
        return ($result);
    }

    /**
     * トランザクションを終了します。
     *
     * クエリ実行時に問題ない場合は `COMMIT` します。
     * エラーが発生した場合は `ROLLBACK` をします。
     *
     * @return mixed 終了結果、失敗の場合 false を返します
     */
    public function end() {
        if ($this->_errno == 0 && $this->_errno == "")
            $result = mysqli_query($this->_link, "COMMIT");
        else
            $result = mysqli_query($this->_link, "ROLLBACK");
        return ($result);
    }

    /**
     * 直近のクエリで生成された ID を取得する。
     *
     * @return int ID
     */
    public function insert_id() {
        if ($this->_errno != 0) { return false; }
        return (mysqli_insert_id($this->_link));
    }

    /**
     * クエリで発生したエラー番号を取得する。
     *
     * エラーがない場合、0 を返します。
     *
     * @return int エラー番号
     */
    public function errno() {
        return ($this->_errno);
    }

    /**
     * クエリで発生したエラー内容を取得する。
     *
     * エラーがない場合、空='' を返します。
     *
     * @return int エラー内容
     */
    public function error() {
        return ($this->_error);
    }

    /**
     * 連想配列、添字配列、またはその両方として結果の行を取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return array 取得した行をあらわす文字列の配列を返します。もし行が存在しない場合は false を返します
     */
    public function fetch_array($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_fetch_array($res));
    }

    /**
     * 結果を添字配列として取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return array 取得された行に対応する文字列の配列を返します。もう行がない場合は、 FALSE を返します
     */
    public function fetch_row($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_fetch_row($res));
    }

    /**
     * 連想配列として結果の行を取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return array 取得した行に対応する文字列の連想配列を返します。もう行がない場合は、 FALSE を返します
     */
    public function fetch_assoc($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_fetch_assoc($res));
    }

    /**
     * 連結果からカラム情報を取得し、オブジェクトとして返す。
     *
     * @param int $res クエリの実行リソース
     *
     * @return object フィールド情報を含むobjectを返します。オブジェクトの プロパティは次のとおりです。
     *	<ul>
     *		<li>name - カラム名</li>
     *		<li>table - カラムが属しているテーブルの名前。エイリアスを定義している場合はエイリアスの名前</li>
     *		<li>max_length - カラムの最大長</li>
     *		<li>not_null - カラムが NULL 値をとることができない場合 1</li>
     *		<li>primary_key - カラムが主キーであれば 1</li>
     *		<li>unique_key - カラムがユニークキーであれば 1</li>
     *		<li>multiple_key - カラムが非ユニークキーであれば 1</li>
     *		<li>numeric - カラムが数値(numeric)であれば 1</li>
     *		<li>blob - カラムがBLOBであれば 1</li>
     *		<li>type - カラムの型</li>
     *		<li>unsigned - カラムが符号無し(unsigned)であれば 1</li>
     *		<li>zerofill - カラムがゼロで埋められている(zero-filled)場合に 1</li>
     *	</ul>
     */
    public function fetch_field($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_fetch_field($res));
    }

    /**
     * 結果の任意の行にポインタを移動する。
     *
     * @param int $res クエリの実行リソース
     * @param int $pos ゼロから全行数 - 1 までの間
     *
     * @return boolean 成功した場合に true を、失敗した場合に false を返します
     */
    public function data_seek($res = "", $pos = 0) {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        if ($this->num_rows($res) > 0) {
            return (mysqli_data_seek($res, $pos));
        }
    }

    /**
     * 結果ポインタを、指定したフィールドオフセットに設定する。
     *
     * @param int $res クエリの実行リソース
     * @param int $num ゼロからフィールド数 - 1 までの間
     *
     * @return boolean 成功した場合に true を、失敗した場合に false を返します
     */
    public function field_seek($res = "", $num = 0) {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_field_seek($res, $num));
    }

    /**
     * 一番最近の操作で変更された行の数を取得する。
     *
     * @return int 成功した場合に変更された行の数を、直近のクエリが失敗した場合に -1 を返します。
     */
    public function affected_rows() {
        if ($this->_errno != 0) { return false; }
        return (mysqli_affected_rows($this->_link));
    }

    /**
     * 結果保持用メモリを開放する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return boolean 成功した場合に true を、失敗した場合に false を返します
     */
    public function free_result($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return mysqli_free_result($res);
    }

    /**
     * 結果におけるフィールドの数を取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return boolean 成功した場合フィールド数、失敗した場合に false を返します
     */
    public function num_fields($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_num_fields($res));
    }

    /**
     * 結果における行の数を取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return boolean 成功した場合行数、失敗した場合に false を返します
     */
    public function num_rows($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        return (mysqli_num_rows($res));
    }

    /**
     * クエリのSQL文を取得する。
     *
     * @return string SQL文
     */
    public function sql() {
        return ($this->_sql);
    }

    /**
     * サーバー接続設定数を取得する。(config.inc.php で定義)
     *
     * @return int 設定数
     */
    public function num_cnf() {
        return $this->_cnf_cnt;
    }

    /**
     * クエリを実行する。
     *
     * クエリは `/private/query` フォルダに `*.sql.php` として保存します。
     *
     * @param string $name クエリ名
     *
     * @param array $params クエリに渡すデータ、(キー名, 値)の配列
     *
     * @return int クエリの実行リソース、失敗の場合 false を返します
     */
    public function execute($name, $params = NULL) {
        if ($this->_errno != 0) { return (false); }

        try {
            $this->_sql = call_user_func_array("q{$name}", array($this, $params));
        } catch (Exception $err) {
            $this->_sql = "";
        }

        $this->_res = NULL;
        if ($this->_sql != "") {
            $result = mysqli_query($this->_link, "SET NAMES utf8");
            $result = mysqli_query($this->_link, $this->_sql);

            if (!$result) {
                $this->_errno = mysqli_errno($this->_link);
                $this->_error = mysqli_error($this->_link);
                return (false);
            }
            $this->_res = $result;
            return ($result);
        }

        $this->_error = "Query {$name} not found.";
        $this->_errno = 3;

        return (false);
    }

    /**
     * 値の数値変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param int $value 数値
     *
     * @return int 数値
     */
    public function int($value) {
        return (int) sprintf("%d", $value);
    }

    /**
     * 値の数値変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param float $value 数値
     *
     * @return float 数値
     */
    public function float($value) {
        return sprintf("%f", $value);
    }

    /**
     * 値の文字列変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param string $value 文字列
     *
     * @return string 文字列
     */
    public function string($value) {
        return mysqli_real_escape_string($this->_link, $value);
        return $value;
    }

    /**
     * 値がcsv形式の文字列を int, float, string 変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param string $value csv形式の文字列
     *
     * @param string $delimiter csvの区切り文字
     *
     * @param string $type 変換タイプ (int, integer, flt, float, str, string)
     *
     * @return string csv形式の文字列
     */
    public function csv($value, $delimiter, $type) {
        if ($value === "") { return $value; }
        $result = "";
        $values = explode($delimiter, $value);

        foreach ($values as $value) {
            if ($result != "") { $result .= $delimiter; }
            if (strtolower($type) === "int" || strtolower($type) === "integer") {
                $value = $this->int($value);
                $result .= "{$value}";
            } else if (strtolower($type) === "flt" || strtolower($type) === "float") {
                $value = $this->float($value);
                $result .= "{$value}";
            } else if (strtolower($type) === "str" || strtolower($type) === "string") {
                $value = $this->string($value);
                $result .= "{$value}";
            }
        }
        return $result;
    }

    /**
     * パラメータ配列にキーの存在確認をし値の変換をします。存在しない場合はデフォルト値を返します。
     * インジェクリョン対応に役立ちます。
     *
     * @param array $params パラメータ配列
     *
     * @param string $field キー名
     *
     * @param mixed $default デフォルト値
     *
     * @return int 数値
     */
    public function isset_int($params, $field, $default = NULL) {
        return ( isset($params[$field]) ? $this->int($params[$field]) : $default );
    }

    /**
     * パラメータ配列にキーの存在確認をし値の変換をします。存在しない場合はデフォルト値を返します。
     * インジェクリョン対応に役立ちます。
     *
     * @param array $params パラメータ配列
     *
     * @param string $field キー名
     *
     * @param mixed $default デフォルト値
     *
     * @return float 数値
     */
    public function isset_float($params, $field, $default = NULL) {
        return ( isset($params[$field]) ? $this->float($params[$field]) : $default );
    }

    /**
     * パラメータ配列にキーの存在確認をし値の変換をします。存在しない場合はデフォルト値を返します。
     * インジェクリョン対応に役立ちます。
     *
     * @param array $params パラメータ配列
     *
     * @param string $field キー名
     *
     * @param mixed $default デフォルト値
     *
     * @return string 文字列
     */
    public function isset_string($params, $field, $default = NULL) {
        return ( isset($params[$field]) ? $this->string($params[$field]) : $default );
    }

    /**
     * パラメータ配列にキーの存在確認をし値の変換をします。存在しない場合はデフォルト値を返します。
     * インジェクリョン対応に役立ちます。
     *
     * @param array $params パラメータ配列
     *
     * @param string $field キー名
     *
     * @param string $delimiter csvの区切り文字
     *
     * @param string $type 変換タイプ (int, integer, flt, float, str, string)
     *
     * @param mixed $default デフォルト値
     *
     * @return string csv形式文字列
     */
    public function isset_csv($params, $field, $delimiter, $type, $default = NULL) {
        return ( isset($params[$field]) ? $this->csv($params[$field], $delimiter, $type) : $default );
    }

    /**
     * config.inc.php の ROW_COUNT 設定にしたがってSQL文のLIMIT部分を返します。
     *
     * @param int $pnum ページ番号
     *
     * @return string SQL文のLIMIT部分
     */
    public function limit($pnum) {
        if ($pnum == NULL) return "";
        $offset = ($pnum - 1) * ROW_COUNT;
        return ( "LIMIT " . $offset . ", " . ROW_COUNT );
    }

}
?>