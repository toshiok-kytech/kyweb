<?php
/** 
* KYSampleクラス
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/

KYWeb::refuse_direct_access(".inc.php");

/**
* KYSampleクラスはhtml内のサンプルコード <!--[SAMPLE:{タグ名}]--> を取得し繰り返し生成・処理します。
*
* @license http://apache.org/licenses/LICENSE-2.0
*
* @copyright ©kyphone
*/
class KYSample {
	/** @var string サンプルのタグ名 */
	private $_tag;
	
	/** @var string サンプルのhtml */
	private $_html;
	
	/** @var array 追加するアイテム(KYHtmlオブジェクト)配列 */
	private $_items;
	
	/** @var string 結果html */
	private $_result;
	
	/**
	* コンストラクタ
	*/
	public function __construct() {
		$this->_html = "";
		$this->_items  = array();
		$this->_result = "";
	}
	
	/**
	* サンップルhtmlに紐付いているタグ名を set, get します。
	*
	* ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
	*
	* @param string $tag タグ名 `<!--[SAMPLE:タグ名]-->` 、パラメータなしの場合 get になります
	*
	* @return mixed setの場合、自分自身(KYSampleオブジェクト) | getの場合、タグ名
	*/
	public function tag($tag = NULL) {
		if ($tag !== NULL) {
			// set
			$this->_tag = $tag;
			return $this;
		}
		// get
		return $this->_tag;
	}
	
	/**
	* サンップルhtmlを set, get します。
	*
	* ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
	*
	* @param string $html サンプルhtml、パラメータなしの場合 get になります
	*
	* @return mixed setの場合、自分自身(KYSampleオブジェクト) | getの場合、サンプルhtml
	*/
	public function html($html = NULL) {
		// set (html)
		if ($html !== NULL) {
			$this->_html = $html;
			return $this;
		}
		// get
		return $this->_html;
	}
	
	/**
	* サンプルの新しいアイテム(KYHtmlオブジェクト)を生成し追加します。
	* また、パラメータに書き換えるための配列を設定することもできます。
	*
	* @param array 書き換えるための `{タグ名}` と値の配列
	*
	* @return object 追加したKYHtmlオブジェクト
	*
	* @example private/library/example/kysample_add.php
	*/
	public function add($assign = NULL) {
		$item = new KYHtml();
		$item->html($this->_html);
		
		if ($assign != NULL) {
			$item->assign($assign);
		}
		
		$this->_items[] = $item;
		return $item;
	}
	
	/**
	* 追加した全てのアイテム(KYHtmlオブジェクト)の処理を実行します。
	*
	* 結果は `result` 関数で求めます。
	*
	* ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
	*
	* @return object 自分自身(KYSampleオブジェクト)
	*/
	public function process() {
		foreach ($this->_items as $item) {
			$item->process();
		}
		return $this;
	}
	
	/**
	* 追加した全てのアイテムの処理結果(html)を取得します。
	*
	* ※この関数は直接使用しません、フレームワーク内の他のクラスで使用されます。
	*
	* @return string 処理結果(html)
	*/
	public function result() {
		$this->_result = "";
		
		foreach ($this->_items as $item) {
			$this->_result .= $item->result();
		}
		
		return $this->_result;
	}
}
?>