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
* KYFileクラスはファイル操作に役立つ機能を持ちます。
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/
class KYFile {
	/** @var object|null KYFileのインスタンス */
	private static $_instance = null;
	
	/**
	* KYFileクラスのシングルトンです。
	*
	* @return object KYFileのインスタンス
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
	* 指定パスのディレクトリ一覧を取得します。
	*
	* @param string $path パス
	*
	* @return array ディレクトリ一覧
	*/
	public function file_list($path) {
		$list = array();
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$list[] = $file;
				}
			}
			closedir($handle);
		}
		return $list;
	}
	
	/**
	* 指定拡張子のMIMEタイプを取得します。
	*
	* @param string $extension 拡張子
	*
	* @return string MIMEタイプ
	*/
	public function file_type($extension) {
		$file_type = "";
		switch ($extension) {
			case "html" : $file_type = "text/html"; break;
			case "css"  : $file_type = "text/css"; break;
			case "csv"  : $file_type = "text/csv"; break;
			case "js"   : $file_type = "text/javascript"; break;
			
			case "jpg"  : $file_type = "image/jpeg"; break;
			case "jpeg" : $file_type = "image/jpeg"; break;
			case "png"  : $file_type = "image/png"; break;
			case "gif"  : $file_type = "image/gif"; break;
			case "bmp"  : $file_type = "image/bmp"; break;
			
			case "mp3"  : $file_type = "audio/mpeg"; break;
			case "m4a"  : $file_type = "audio/mp4"; break;
			case "wav"  : $file_type = "audio/wav"; break;
			case "mid"  : $file_type = "audio/midi"; break;
			case "midi" : $file_type = "audio/midi"; break;
			case "ogg"  : $file_type = "audio/ogg"; break;
			
			case "mpg"  : $file_type = "video/mpeg"; break;
			case "mpeg" : $file_type = "video/mpeg"; break;
			case "wmv"  : $file_type = "video/x-ms-wmv"; break;
			case "swf"  : $file_type = "application/x-shockwave-flash"; break;
			case "mp4"  : $file_type = "video/mp4"; break;
			case "3g2"  : $file_type = "video/3gpp2"; break;
			case "webm" : $file_type = "video/webm"; break;
			
			case "zip"  : $file_type = "application/zip"; break;
			case "lha"  : $file_type = "application/x-lzh"; break;
			case "lzh"  : $file_type = "application/x-lzh"; break;
			case "tar"  : $file_type = "application/x-tar"; break;
			case "tgz"  : $file_type = "application/x-tar"; break;
			
			case "pdf"  : $file_type = "application/pdf"; break;
			
			default     : $file_type = "application/octet-stream"; break;
		}
		return $file_type;
	}
}
?>