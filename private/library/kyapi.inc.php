<?php
/** 
* KYApiクラス
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/

KYWeb::refuse_direct_access(".inc.php");

/**
* KYApiクラスはAPI機能の実行を行うためのクラスです。
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/
class KYApi {
	/** @var object|null KYApiのインスタンス */
	private static $_instance = null;
	
	/**
	* KYApiクラスのシングルトンです。
	*
	* @return object KYApiのインスタンス
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
	* 32バイトのアクセストークンを生成します。
	*
	* @param string $seed アクセストークンを生成する種。(Optional)
	*
	*	種を指定時MD5アルゴリズムを使用します。
	*
	* @return string 32バイトのアクセストークン
	*/
	public function generate_token($seed = NULL) {
		if ($seed === NULL) {
			return md5($seed);
		}
		$token_length = 16; // 16 * 2 = 32 byte
		$bytes = openssl_random_pseudo_bytes($token_length);
		return bin2hex($bytes);
	}
	
	
	/**
	* クライアントヘッダー情報からアクセストークンを取得する。
	*
	* @return string アクセストークン
	*
	* @example private/library/example/kyapi_get_headers_token.php
	*/
	public function get_headers_token() {
		$headers = apache_request_headers();
		if (isset($headers["Authorization"])) {
			if (stristr($headers["Authorization"], "x-kyweb-token") != false) {
				$token = trim(str_ireplace("x-kyweb-token", "", $headers["Authorization"]));
				return $token;
			}
		}
		return "";
	}
	
	/**
	* CURLリクエストを開始します。
	*
	* @param array $params リクエストデータ
	*	<ul>
	*		<li>url: URL</li>
	*		<li>method: [POST | GET | DELETE | PUT]</li>
	*		<li>format: [json | text]</li>
	*		<li>access_token: アクセストークン</li>
	*		<li>values: (キー, 値)の配列</li>
	*	</ul>
	*
	* @return array リスポンスデータ
	*	<ul>
	*		<li>status: HTTPステータス 200 など</li>
	*		<li>headers: array ヘッダー情報</li>
	*		<li>body: array ボディ情報</li>
	*		<li>curl_info: array CURL情報</li>
	*	</ul>
	*
	* @example private/library/example/kyapi_request.php
	*/
	public function request($params) {
		if (!isset($params["url"]))			return false;
		if (!isset($params["method"]))		return false;
		if (!isset($params["format"]))		$params["format"] = "json";
		if (!isset($params["access_token"]))	$params["access_token"] = "";
		if (!isset($params["values"]))		$params["values"] = array();
		if (!is_array($params["values"]))		return false;
		
		$url          = $params["url"];
		$method       = strtoupper($params["method"]);
		$format       = strtoupper($params["format"]);
		$access_token = $params["access_token"];
		$body         = http_build_query($params["values"]);
		$url_parts    = parse_url($url);
		
		$header = array(
			"Host: ".$url_parts["host"],
			"Accept-Charset: utf-8",
			"Accept-Encoding: gzip,deflate,sdch",
			"Cache-Control: max-age=0",
			"Connection: keep-alive"
		);
		
		if ($access_token != NULL && $access_token != "") {
			$header[] = "Authorization: x-kyweb-token {$access_token}";
		}
		
		$curl_handler = curl_init($url);
		$curl_pointer = tmpfile();
		
		curl_setopt($curl_handler, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_handler, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl_handler, CURLOPT_HEADER, false);
		curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handler, CURLOPT_ENCODING, "gzip");
        curl_setopt($curl_handler, CURLOPT_WRITEHEADER, $curl_pointer);

		switch($method) {
			case 'GET':
				curl_setopt($curl_handler, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($curl_handler, CURLOPT_POST, true);
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $body);
				break;
			case 'HEAD':
				curl_setopt($curl_handler, CURLOPT_NOBODY, true);
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $body);
				break;
			default:
				curl_setopt($curl_handler, CURLOPT_CUSTOMREQUEST, $method);
				curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $body);
		}
		
		// Start request
		
		$body = NULL;
		
		if ($format == "XML") {
			$xml  = curl_exec($curl_handler);
			$data = simplexml_load_string($xml);
			$body = json_decode(json_encode($data), true);
		} else {
			$body = json_decode(curl_exec($curl_handler), true);
		}
		$encode = mb_convert_variables("UTF-8", "auto", $body);
		
		fseek($curl_pointer, 0);
		$_headers = explode("\n", trim(fread($curl_pointer, 10240)));
		
		if (preg_match("!([0-9]{3})!", $_headers[0], $matches)) {
			$status = (int) $matches[1];
			unset($_headers[0]);
		}
		
		$headers = array();
		foreach ($_headers as $header) {
			if (preg_match("!^(.+?): (.+)$!", $header, $matches)) {
				$headers[$matches[1]] = trim($matches[2]);
			}
		}
		
		$result = array();
		$result["status"]    = $status;
		$result["headers"]   = $headers;
		$result["body"]      = $body;
		$result["curl_info"] = array(
			CURLINFO_EFFECTIVE_URL				=> var_export(curl_getinfo($curl_handler, CURLINFO_EFFECTIVE_URL), true),
			CURLINFO_HTTP_CODE					=> var_export(curl_getinfo($curl_handler, CURLINFO_HTTP_CODE), true),
			CURLINFO_FILETIME					=> var_export(curl_getinfo($curl_handler, CURLINFO_FILETIME), true),
			CURLINFO_TOTAL_TIME					=> var_export(curl_getinfo($curl_handler, CURLINFO_TOTAL_TIME), true),
			CURLINFO_NAMELOOKUP_TIME			=> var_export(curl_getinfo($curl_handler, CURLINFO_NAMELOOKUP_TIME), true),
			CURLINFO_CONNECT_TIME				=> var_export(curl_getinfo($curl_handler, CURLINFO_CONNECT_TIME), true),
			CURLINFO_PRETRANSFER_TIME			=> var_export(curl_getinfo($curl_handler, CURLINFO_PRETRANSFER_TIME), true),
			CURLINFO_STARTTRANSFER_TIME			=> var_export(curl_getinfo($curl_handler, CURLINFO_STARTTRANSFER_TIME), true),
			CURLINFO_REDIRECT_COUNT				=> var_export(curl_getinfo($curl_handler, CURLINFO_REDIRECT_COUNT), true),
			CURLINFO_REDIRECT_TIME				=> var_export(curl_getinfo($curl_handler, CURLINFO_REDIRECT_TIME), true),
			CURLINFO_SIZE_UPLOAD				=> var_export(curl_getinfo($curl_handler, CURLINFO_SIZE_UPLOAD), true),
			CURLINFO_SIZE_DOWNLOAD				=> var_export(curl_getinfo($curl_handler, CURLINFO_SIZE_DOWNLOAD), true),
			CURLINFO_SPEED_DOWNLOAD				=> var_export(curl_getinfo($curl_handler, CURLINFO_SPEED_DOWNLOAD), true),
			CURLINFO_SPEED_UPLOAD				=> var_export(curl_getinfo($curl_handler, CURLINFO_SPEED_UPLOAD), true),
			CURLINFO_HEADER_SIZE				=> var_export(curl_getinfo($curl_handler, CURLINFO_HEADER_SIZE), true),
			CURLINFO_HEADER_OUT					=> var_export(curl_getinfo($curl_handler, CURLINFO_HEADER_OUT), true),
			CURLINFO_REQUEST_SIZE				=> var_export(curl_getinfo($curl_handler, CURLINFO_REQUEST_SIZE), true),
			CURLINFO_SSL_VERIFYRESULT			=> var_export(curl_getinfo($curl_handler, CURLINFO_SSL_VERIFYRESULT), true),
			CURLINFO_CONTENT_LENGTH_DOWNLOAD	=> var_export(curl_getinfo($curl_handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD), true),
			CURLINFO_CONTENT_LENGTH_UPLOAD		=> var_export(curl_getinfo($curl_handler, CURLINFO_CONTENT_LENGTH_UPLOAD), true),
			CURLINFO_CONTENT_TYPE				=> var_export(curl_getinfo($curl_handler, CURLINFO_CONTENT_TYPE), true)
		);
		
		curl_close($curl_handler);
		fclose($curl_pointer);
		
		return $result;
    }
}

?>