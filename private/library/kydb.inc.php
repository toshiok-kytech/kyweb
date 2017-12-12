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

    /** @var array サーバー接続情報の配列 (config.inc.php で定義) */
    private $_cnf_arr;

    /** @var string データベースエンジン名 (mysql, sqlsrv) */
    private $_engine;

    /** @var int MySQL リンク ID */
    private $_conn;

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
            $db->_conn = 0;
            $db->clear_db_conf();
            
            if (defined("DB_CONF")) {
                $db->add_db_conf( 1, constant("DB_CONF") );
            } else {
                $num = 1;
                while ( defined("DB_CONF_{$num}") ) {
                    $db->add_db_conf($num, constant("DB_CONF_{$num}") );
                    $num++;
                }
            }
        }

        return self::$_instance;
    }

    public function clear_db_conf() {
        $this->_cnf_arr = [[]];
    }

    public function add_db_conf($num, $conf) {
        $cnf_json = json_decode($conf, true);
        $this->_cnf_arr[$num] = array(
            "ENGINE"   => (isset($cnf_json["ENGINE"])      ? strtolower($cnf_json["ENGINE"]) : "mysql"),
            "HOST"     => (isset($cnf_json["HOST"])        ? $cnf_json["HOST"]     : "localhost"),
            "USER"     => (isset($cnf_json["USER"])        ? $cnf_json["USER"]     : ""),
            "PASSWORD" => (isset($cnf_json["PASSWORD"])    ? $cnf_json["PASSWORD"] : ""),
            "DATABASE" => (isset($cnf_json["DATABASE"])    ? $cnf_json["DATABASE"] : ""),
            "PORT"     => intval(isset($cnf_json["PORT"]) ? $cnf_json["PORT"]     : "3306")
        );
    }

    /**
     * データベースへの接続を行います。
     *
     * @param int $cnf_num 接続するデータベース設定Index (Optional default=1)
     *
     * @return mixed 接続結果、失敗の場合 false を返します
     */
    public function connect($cnf_num = 1) {
        $this->_errno = 0;
        $this->_error = "";

        if (isset($this->_cnf_arr[$cnf_num]) == false) {
            return (false);
        }

        $this->_engine = $this->_cnf_arr[$cnf_num]["ENGINE"];


        // Load query
        $query_path = PRIVATE_PATH . "/query";
        $file_list = KYFile::instance()->file_list($query_path);
        foreach ($file_list as $fileName) {
            if ($this->_engine == "mysql") {
                if (substr(basename($fileName), -10) == ".mysql.php") {
                    include_once($query_path . "/{$fileName}");
                }

            } else if ($this->_engine == "sqlsrv") {
                if (substr(basename($fileName), -11) == ".sqlsrv.php") {
                    include_once($query_path . "/{$fileName}");
                }
            }
        }



        if ($this->_engine == "mysql") {
            $this->_conn = @mysqli_connect(
                $this->_cnf_arr[$cnf_num]["HOST"],
                $this->_cnf_arr[$cnf_num]["USER"],
                $this->_cnf_arr[$cnf_num]["PASSWORD"],
                "",
                $this->_cnf_arr[$cnf_num]["PORT"]);

        } else if ($this->_engine == "sqlsrv") {
            $serverName = "tcp:" . $this->_cnf_arr[$cnf_num]["HOST"] . "," . $this->_cnf_arr[$cnf_num]["PORT"];
            $connectionInfo = array(
                "UID"          => $this->_cnf_arr[$cnf_num]["USER"],
                "pwd"          => $this->_cnf_arr[$cnf_num]["PASSWORD"],
                "Database"     => $this->_cnf_arr[$cnf_num]["DATABASE"],
                "LoginTimeout" => 30,
                "Encrypt"      => 1,
                "TrustServerCertificate" => 0,
                "CharacterSet" => "UTF-8");
            $this->_conn = sqlsrv_connect($serverName, $connectionInfo);


            /*
            try {
                $host = $this->_cnf_arr[$cnf_num]["HOST"];
                $port = $this->_cnf_arr[$cnf_num]["PORT"];
                $database = $this->_cnf_arr[$cnf_num]["DATABASE"];
                $username = $this->_cnf_arr[$cnf_num]["USER"];
                $password = $this->_cnf_arr[$cnf_num]["PASSWORD"];
                $this->_conn = new PDO("sqlsrv:server = tcp:{$host},{$port}; Database = {$database}", $username, $password);
                $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch (PDOException $e) {
                $this->_conn == false;
            }
            */
        }

        if ($this->_conn == false) {
            $this->_errno = 1;
            $this->_error = "Can't connect to database.";
            return (false);
        }

        if ($this->_engine == "mysql") {
            $result = @mysqli_select_db($this->_conn, $this->_cnf_arr[$cnf_num]["DATABASE"]);
            if ($result == false) {
                $this->_errno = 2;
                $this->_error = "Can't select to database.";
                return (false);
            }

        } else if ($this->_engine == "sqlsrv") {
            $result = true;
        }

        return ($result);
    }

    /**
     * データベースへの切断を行います。
     *
     * @return mixed 切断結果、失敗の場合 false を返します
     */
    public function close() {
        if ($this->_engine == "mysql") {
            return mysqli_close($this->_conn);

        } else if ($this->_engine == "sqlsrv") {
            return sqlsrv_close($this->_conn);
        }

        return (false);
    }

    /**
     * トランザクションを開始します。
     *
     * @return mixed 開始結果、失敗の場合 false を返します
     */
    public function begin() {
        $result = false;

        if ($this->_engine == "mysql") {
            $result = mysqli_query($this->_conn, "BEGIN");

        } else if ($this->_engine == "sqlsrv") {
            $result = sqlsrv_begin_transaction($this->_conn);
        }

        if ($result == false) {
            $this->_errno = mysqli_errno($this->_conn);
            $this->_error = mysqli_error($this->_conn);
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
    public function end($rollback = false) {
        if ($this->_errno == 0 && $this->_errno == "" && $rollback == false) {
            if ($this->_engine == "mysql") {
                return mysqli_query($this->_conn, "COMMIT");

            } else if ($this->_engine == "sqlsrv") {
                return sqlsrv_commit($this->_conn);
            }

        } else {
            if ($this->_engine == "mysql") {
                return mysqli_query($this->_conn, "ROLLBACK");

            } else if ($this->_engine == "sqlsrv") {
                return sqlsrv_rollback($this->_conn);
            }
        }

        return (false);
    }

    /**
     * 直近のクエリで生成された ID を取得する。
     *
     * @return int ID
     */
    public function insert_id() {
        if ($this->_errno != 0) { return false; }

        if ($this->_engine == "mysql") {
            return (mysqli_insert_id($this->_conn));

        } else if ($this->_engine == "sqlsrv") {
            $dbres = sqlsrv_query($this->_conn, "SELECT last_insert_id=@@identity");
            $dbrow = sqlsrv_fetch_array($dbres);
            $last_insert_id = $dbrow[0];
            return ($last_insert_id);
        }

        return (0);
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

        if ($this->_engine == "mysql") {
            return (mysqli_fetch_array($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_fetch_array($res));
        }

        return (false);
    }

    /**
     * 結果を添字配列として取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return array 取得された行に対応する文字列の配列を返します。もう行がない場合は、 false を返します (SQL Server では使用不可)
     */
    public function fetch_row($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;

        if ($this->_engine == "mysql") {
            return (mysqli_fetch_row($res));
        }

        return (false);
    }

    /**
     * 連想配列として結果の行を取得する。
     *
     * @param int $res クエリの実行リソース
     *
     * @return array 取得した行に対応する文字列の連想配列を返します。もう行がない場合は、 false を返します
     */
    public function fetch_assoc($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;

        if ($this->_engine == "mysql") {
            return (mysqli_fetch_assoc($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_fetch_array($res));
        }

        return (false);
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
     *  SQL Server の場合arrayを返します。キーは次の通りです。
     *  <ul>
     *		<li>Name - The name of the field.</li>
     *		<li>Type - The numeric value for the SQL type.</li>
     * 		<li>Size - The number of characters for fields of character type, the number of bytes for fields of binary type, or NULL for other types.</li>
     * 		<li>Precision - The precision for types of variable precision, NULL for other types.</li>
     * 		<li>Scale - The scale for types of variable scale, NULL for other types.</li>
     * 		<li>Nullable - An enumeration indicating whether the column is nullable, not nullable, or if it is not known.</li>
     *  </ul>
     */
    public function fetch_field($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;

        if ($this->_engine == "mysql") {
            return (mysqli_fetch_field($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_field_metadata($res));
        }

        return (false);
    }

    /**
     * 結果の任意の行にポインタを移動する。
     *
     * @param int $res クエリの実行リソース
     * @param int $pos ゼロから全行数 - 1 までの間。SQL Server の場合は次を使用できます。
     * <ul>
     *   <li>SQLSRV_SCROLL_NEXT</li>
     *   <li>SQLSRV_SCROLL_PRIOR</li>
     *   <li>SQLSRV_SCROLL_FIRST</li>
     *   <li>SQLSRV_SCROLL_LAST</li>
     *   <li>SQLSRV_SCROLL_ABSOLUTE</li>
     *   <li>SQLSRV_SCROLL_RELATIVE</li>
     * </ul>
     *
     * @return boolean 成功した場合に true を、失敗した場合に false を返します
     */
    public function data_seek($res = "", $pos = 0) {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        if ($this->num_rows($res) > 0) {
            if ($this->_engine == "mysql") {
                return (mysqli_data_seek($res, $pos));

            } else if ($this->_engine == "sqlsrv") {
                return (sqlsrv_fetch($res, $pos));
            }
        }
        return (false);
    }

    /**
     * 結果ポインタを、指定したフィールドオフセットに設定する。
     *
     * @param int $res クエリの実行リソース
     * @param int $num ゼロからフィールド数 - 1 までの間
     *
     * @return boolean 成功した場合に true を、失敗した場合に false を返します (SQL Server は使用不可)
     */
    public function field_seek($res = "", $num = 0) {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;
        if ($this->_engine == "mysql") {
            return (mysqli_field_seek($res, $num));
        }
        return (false);
    }

    /**
     * 一番最近の操作で変更された行の数を取得する。
     *
     * @return int 成功した場合に変更された行の数を、直近のクエリが失敗した場合に -1 を返します。
     */
    public function affected_rows($res = "") {
        if ($this->_errno != 0) { return false; }
        if ($res == "") $res = $this->_res;

        if ($this->_engine == "mysql") {
            return (mysqli_stmt_affected_rows($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_rows_affected($res));
        }

        return (false);
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

        if ($this->_engine == "mysql") {
            return mysqli_free_result($res);

        } else if ($this->_engine == "sqlsrv") {
            return sqlsrv_free_stmt($res);
        }

        return (false);
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

        if ($this->_engine == "mysql") {
            return (mysqli_num_fields($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_num_fields($res));
        }

        return (false);
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

        if ($this->_engine == "mysql") {
            return (mysqli_num_rows($res));

        } else if ($this->_engine == "sqlsrv") {
            return (sqlsrv_num_rows($res));
        }

        return (false);
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
        return count($this->_cnf_arr) - 1;
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

        if ($name == "SQL") {
            $this->_sql = $params;
        } else {
            try {
                $this->_sql = call_user_func_array("q{$name}", array($this, $params));
            } catch (Exception $err) {
                $this->_sql = "";
            }
        }

        $this->_res = NULL;
        if ($this->_sql != "") {
            $result = null;

            if ($this->_engine == "mysql") {
                mysqli_query($this->_conn, "SET NAMES utf8");
                $result = mysqli_multi_query($this->_conn, $this->_sql);

            } else if ($this->_engine == "sqlsrv") {
                $result = sqlsrv_query($this->_conn, $this->_sql);
            }

            if (!$result) {
                if ($this->_engine == "mysql") {
                    $this->_errno = mysqli_errno($this->_conn);
                    $this->_error = mysqli_error($this->_conn);

                } else if ($this->_engine == "sqlsrv") {
                    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
                    foreach ( $errors as $error ) {
                        $this->_errno = $error["code"];
                        $this->_error = $error["message"];
                    }
                }
                return (false);
            }

            while (true) {
                if ($this->_engine == "mysql") {
                    $result = mysqli_store_result($this->_conn);
                    if (mysqli_more_results($this->_conn) == false) {
                        break;
                    }
                    if ($result != null) $result->free();
                    mysqli_next_result($this->_conn);

                } else if ($this->_engine == "sqlsrv") {
                    break;
                }
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
     * @return int 数値 ($value == null の場合 null を返す)
     */
    public function int($value) {
        if ($value === null) { return null; }
        return (int) sprintf("%d", $value);
    }

    /**
     * 値の数値変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param float $value 数値
     *
     * @return float 数値 ($value == null の場合 null を返す)
     */
    public function float($value) {
        if ($value === null) { return null; }
        return sprintf("%f", $value);
    }

    /**
     * 値の文字列変換をします。
     * インジェクリョン対応に役立ちます。
     *
     * @param string $value 文字列
     *
     * @return string 文字列 ($value == null の場合 null を返す)
     */
    public function string($value) {
        if ($value === null) { return null; }
        //return mysqli_real_escape_string($this->_conn, $value);
        return $this->escape_string($value);
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

    /**
     * @param $data
     * @return mixed|string
     */
    public function escape_string($data) {
        if ( !isset($data) or empty($data) ) return '';
        if ( is_numeric($data) ) return $data;

        $non_displayables = array(
            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        );
        foreach ( $non_displayables as $regex )
            $data = preg_replace( $regex, '', $data );
        $data = str_replace("'", "''", $data );
        return $data;
    }
}