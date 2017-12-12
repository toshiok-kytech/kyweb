<?php
/** 
* KYMailクラス
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/

KYWeb::refuse_direct_access(".inc.php");

// Require
require_once(PRIVATE_PATH . "/library/kycommon.inc.php");

/**
* KYMailクラスはテンプレートからメールを作成、送信します。
*
* メールのテンプレートは `/private/mail` フォルダに `*.txt` として保存します。
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/
class KYMail {
	/** @var object|null KYMailのインスタンス */
	private static $_instance = null;
	
	/** @var string テンプレート */
	private $_template;

    /** @var string テンプレート (HTML) */
    private $_template_html;
	
	/** @var array 書き換え配列 */
	private $_assign;

	/**
	* KYMailクラスのシングルトンです。
	*
	* @return object KYMailのインスタンス
	*
	* @example private/library/example/instance.php
	*/
	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self;
			
			$page = self::$_instance;
			$page->_template = "";
            $page->_template_html = "";
			$page->_assign = array();
			$page->_result = "";
		}
		return self::$_instance;
	}
	
	/**
	* メールのテンプレート名を指定します。
	*
	* テンプレート名は拡張子 `.txt` なしで指定します。 
	*
	* テンプレートは `/private/mail` に保存します。
	*
	* @param string $name テンプレート名
	*
	* @return object 自分自身(KYMailオブジェクト) 
	*
	* @example private/library/example/kymail_template.php
	*/
	public function template($name) {
		$mail_path = PRIVATE_PATH . "/mail/{$name}.txt";
		$this->_template = mb_convert_encoding(file_get_contents($mail_path), "UTF-8", "auto");
        $this->_template = str_replace("\r\n", "\n", $this->_template);

        $mail_path_html = PRIVATE_PATH . "/mail/{$name}.html";
        if (file_exists($mail_path_html)) {
            $this->_template_html = mb_convert_encoding(file_get_contents($mail_path_html), "UTF-8", "auto");
            $this->_template_html = str_replace("\r\n", "\n", $this->_template_html);
        }

		return $this;
	}
	
	/**
	* テンプレート内で書き換える {タグ名} と値を指定します。
	*
	* @param string|array $param1 タグ名、もしくは、(タグ名, 値)の配列
	* @param string $param2 書き換える値、$param1 が配列の場合は必要ない
	*
	* @return object 自分自身(KYMailオブジェクト) 
	*
	* @example private/library/example/kymail_assign.php
	*/
	public function assign($param1, $param2 = NULL) {
		if (is_array($param1)) {
			foreach ($param1 as $tag => $value) {
				$this->assign($tag, $value);
			}
		} else if ($param1 != NULL && $param2 != NULL && !is_array($param1) && !is_array($param2)) {
			$tag = $param1;
			$value = $param2;
			$this->_assign["{{$tag}}"] = $value;
		}
		return $this;
	}
	
	/**
     * テンプレートを読み込み、入れ替え部分を処理し、メールを送信します。
     *
     * @param string $to 送信先メールアドレス
     * @param string $cc Ccメールアドレス
     * @param string $from 送信元メールアドレス
     *
     * @return 成功した場合は true、失敗した場合はエラーメッセージ
     *
     * @example private/library/example/kymail_send.php
     **/
	function send($to, $cc = "", $from = "") {
        if ($from === "") { $from = MAIL_FROM; }

	    // Prepare data
        $temp = $this->_template;
        $temp_html = $this->_template_html;
		if ($this->_assign != NULL) {
            $temp = strtr($this->_template, $this->_assign);
            $temp_html = strtr($this->_template_html, $this->_assign);
		}

		$data = explode("\n", $temp);
        $subject = $data[0]; array_shift($data); array_shift($data);
        $body = implode("\n", $data);

        $data_html = explode("\n", $temp_html);
        $subject_html = $data_html[0]; array_shift($data_html); array_shift($data_html);
        $body_html = implode("\n", $data_html);

        // For send japanese email
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");
        $subject = mb_encode_mimeheader($subject);

        $result = true;

        if (MAIL_METHOD == "PEAR_MAIL") {
            $body = mb_convert_encoding($body, "ISO-2022-JP", "auto");

            // PEAR::Mail
            require_once(PEAR_MAIL);

            // Prepare SMTP info
            if (SMTP_AUTH) {
                $params = array(
                    "host" => SMTP_SERVER,
                    "port" => SMTP_PORT,
                    "auth" => SMTP_AUTH,
                    "username" => SMTP_USER,
                    "password" => SMTP_PASSWORD
                );
            } else {
                $params = array(
                    "host" => SMTP_SERVER,
                    "port" => SMTP_PORT,
                    "auth" => SMTP_AUTH
                );
            }

            // Create PEAR::Mail object
            $mail_object = Mail::factory("smtp", $params);

            // Set to & cc email address
            $address = array($to);
            if ($cc != "" && $cc != NULL) { $address[] = $cc; }

            // Create mail head info
            $headers = array(
                "To"	  => $to,
                "From"	  => $from,
                "Subject" => mb_encode_mimeheader($subject)
            );
            if ($cc != "") { $headers["Cc"] = $cc; }

            // Send
            $result = $mail_object->send($address, $headers, $body);
            if (PEAR::isError($result)) {
                $result = $result->getMessage();
            }

        } else if (MAIL_METHOD == "SENDGRID") {
            $body = mb_convert_encoding($body, "UTF-8");
            $body_html = mb_convert_encoding($body_html, "UTF-8");

            $body_key = (strpos($body, "<html") === 0 ? "html" : "text");

            $p1 = strpos($to, "<");
            $p2 = strpos($to, ">");
            if ($p1 !== false && $p2 !== false) {
                $toname = substr($to, 0, $p1);
                $to = substr($to, $p1 + 1, $p2 - $p1 - 1);
            }

            $p1 = strpos($cc, "<");
            $p2 = strpos($cc, ">");
            if ($p1 !== false && $p2 !== false) {
                $ccname = substr($cc, 0, $p1);
                $cc = substr($cc, $p1 + 1, $p2 - $p1 - 1);
            }

            $p1 = strpos($from, "<");
            $p2 = strpos($from, ">");
            if ($p1 !== false && $p2 !== false) {
                $fromname = substr($from, 0, $p1);
                $from = substr($from, $p1 + 1, $p2 - $p1 - 1);
            }

            $params = array(
                'api_user' => SNEDGRID_USER,
                'api_key'  => SNEDGRID_PASSWORD,
                'to'	   => $to,
                'cc'       => $cc,
                'subject'  => $subject,
                'text'     => $body,
                'from'	   => $from,
            );

            if (isset($toname)) {
                $params["toname"] = $toname;
            }

            if (isset($ccname)) {
                $params["ccname"] = $ccname;
            }

            if (isset($fromname)) {
                $params["fromname"] = $fromname;
            }

            if (!empty($this->_template_html)) {
                $params["html"] = $body_html;
            }

            $request = SENDGRID . "api/mail.send.json";

            // Generate curl request
            $session = curl_init($request);

            // Tell curl to use HTTP POST
            curl_setopt ($session, CURLOPT_POST, true);

            // Tell curl that this is the body of the POST
            curl_setopt ($session, CURLOPT_POSTFIELDS, $params);

            // Tell curl not to return headers, but do return the response
            curl_setopt($session, CURLOPT_HEADER, false);
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

            // Obtain response
            $response = curl_exec_cacert($session);

            if (curl_errno($session)) {
                $result = curl_error($session);
            } else {
                $result = true;
            }

            curl_close($session);

        } else {
            $result = "Error MAIL_METHOD";
        }

        return $result;
	}
}