<?php
/**
 * index.php用コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2013 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_commentBaseWidgetContainer.php 6117 2013-06-16 23:25:17Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getContainerPath() . '/baseAdminWidgetContainer.php');
require_once($gEnvManager->getWidgetContainerPath('comment') . '/commentCommonDef.php');
require_once($gEnvManager->getWidgetDbPath('comment') . '/commentDb.php');

class admin_commentBaseWidgetContainer extends BaseAdminWidgetContainer
{
	protected static $_mainDb;			// DB接続オブジェクト
	protected static $_configArray;		// ブログ定義値
	protected static $_task;			// 現在の画面
	protected $_openBy;				// ウィンドウオープンタイプ
	protected $_baseUrl;			// 管理画面のベースURL
	protected $_langId;			// 現在の言語
	protected $_userId;			// 現在のユーザ
	protected $_contentType;		// コンテンツタイプ
	const DEFAULT_COMMENT_LENGTH	= 300;				// デフォルトのコメント最大文字数
	const DEFAULT_CATEGORY_COUNT	= 2;				// デフォルトのカテゴリ数
	const REQUEST_PARAM_CONTENT_TYPE = 'content_type';
	
	// カレンダー用スクリプト
	const CALENDAR_SCRIPT_FILE = '/jscalendar-1.0/calendar.js';		// カレンダースクリプトファイル
	const CALENDAR_LANG_FILE = '/jscalendar-1.0/lang/calendar-ja.js';	// カレンダー言語ファイル
	const CALENDAR_SETUP_FILE = '/jscalendar-1.0/calendar-setup.js';	// カレンダーセットアップファイル
	const CALENDAR_CSS_FILE = '/jscalendar-1.0/calendar-win2k-1.css';		// カレンダー用CSSファイル
	
	// 画面
	const TASK_CONFIG			= 'config';				// 基本設定
	const TASK_CONFIG_LIST		= 'config_list';		// コンテンツ個別設定一覧
	const TASK_CONFIG_DETAIL	= 'config_detail';		// コンテンツ個別設定詳細
	const TASK_COMMENT			= 'comment';			// コメント一覧
	const TASK_COMMENT_DETAIL 	= 'comment_detail';		// コメント詳細
	const DEFAULT_TASK			= 'config';
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		if (!isset(self::$_mainDb)) self::$_mainDb = new commentDb();
		
		$this->_langId = $this->gEnv->getCurrentLanguage();			// 現在の言語
		$this->_userId = $this->gEnv->getCurrentUserId();		// 現在のユーザ
	}
	/**
	 * テンプレートに前処理
	 *
	 * _setTemplate()で指定したテンプレートファイルにデータを埋め込む。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。_setTemplate()と共有。
	 * @return								なし
	 */
	function _preAssign($request, &$param)
	{
		$this->_openBy = $request->trimValueOf(M3_REQUEST_PARAM_OPEN_BY);		// ウィンドウオープンタイプ
		if (!empty($this->_openBy)) $this->addOptionUrlParam(M3_REQUEST_PARAM_OPEN_BY, $this->_openBy);
		
		$this->_contentType = $request->trimValueOf(self::REQUEST_PARAM_CONTENT_TYPE);		// コンテンツタイプ
		$value = $request->trimValueOf('item_content_type');		// 選択中のコンテンツタイプ
		if (!empty($value)) $this->_contentType = $value;			// 画面からのPOST値で上書き
		if (empty($this->_contentType)) $this->_contentType = $this->getDefaultContentType();		// コンテンツタイプが取得できないときはデフォルトを取得
		
		if (!empty($this->_contentType)) $this->addOptionUrlParam(self::REQUEST_PARAM_CONTENT_TYPE, $this->_contentType);		// コンテンツタイプをURLに追加
		
		// 管理画面ペースURL取得
		$this->_baseUrl = $this->getAdminUrlWithOptionParam(true);		// ページ定義パラメータ付加
	}
	/**
	 * テンプレートにデータ埋め込む
	 *
	 * _setTemplate()で指定したテンプレートファイルにデータを埋め込む。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。_setTemplate()と共有。
	 * @return								なし
	 */
	function _postAssign($request, &$param)
	{
//		$openBy = $request->trimValueOf(M3_REQUEST_PARAM_OPEN_BY);		// ウィンドウオープンタイプ
//		if (!empty($openBy)) $this->addOptionUrlParam(M3_REQUEST_PARAM_OPEN_BY, $openBy);
		if ($this->_openBy == 'simple') return;			// シンプルウィンドウのときはメニューを表示しない
		
		// 表示画面を決定
	//	$task = $request->trimValueOf(M3_REQUEST_PARAM_OPERATION_TASK);
	//	if (empty($task)) $task = 'config';
		$task = self::$_task;			// 現在の画面を取得
		
		// パンくずリストを作成
		switch ($task){
			case self::TASK_COMMENT:		// コメント一覧
			case self::TASK_COMMENT_DETAIL:	// コメント詳細
				$linkList = ' &gt;&gt; コメント一覧';// パンくずリスト
				break;
			case self::TASK_CONFIG:		// 基本設定
			case self::TASK_CONFIG_LIST:		// コンテンツ個別設定一覧
			case self::TASK_CONFIG_DETAIL:		// コンテンツ個別設定詳細
				$linkList = ' &gt;&gt; 基本設定';// パンくずリスト
				break;
		}

		// ####### 上段メニューの作成 #######
		$menuText = '<div id="configmenu-upper">' . M3_NL;
		$menuText .= '<ul>' . M3_NL;
		
		$current = '';
//		$baseUrl = $this->getAdminUrlWithOptionParam();
		
		// コメント一覧
		$current = '';
		$link = $this->_baseUrl . '&task=' . self::TASK_COMMENT;
		if ($task == self::TASK_COMMENT || $task == self::TASK_COMMENT_DETAIL) $current = 'id="current"';
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span>コメント一覧</span></a></li>' . M3_NL;
		
		// 基本設定
		$current = '';
		$link = $this->_baseUrl . '&task=' . self::TASK_CONFIG;
		if ($task == self::TASK_CONFIG || $task == self::TASK_CONFIG_LIST || $task == self::TASK_CONFIG_DETAIL) $current = 'id="current"';
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span>基本設定</span></a></li>' . M3_NL;
		
		// 上段メニュー終了
		$menuText .= '</ul>' . M3_NL;
		$menuText .= '</div>' . M3_NL;
		
		// 作成データの埋め込み
		$linkList = '<div id="configmenu-top"><label>' . '汎用コメント' . $linkList . '</label></div>';
		$outputText .= '<table width="90%"><tr><td>' . $linkList . $menuText . '</td></tr></table>' . M3_NL;
		$this->tmpl->addVar("_widget", "menu_items", $outputText);
		
		// ##### コンテンツタイプメニューの設定 #####
		// 画面作成から呼ばれている場合は、コンテンツタイプメニューを変更不可にする
		if (!empty($this->_defSerial) && !empty($this->_contentType)){
			if ($this->tmpl->exists('content_type')){
				$this->tmpl->addVar("_widget", "content_type_disabled", 'disabled');
				$this->tmpl->setAttribute('content_type', 'visibility', 'visible');
				$this->tmpl->addVar('content_type', 'content_type', $this->_contentType);
			}
		}
	}
	/**
	 * デフォルトのコンテンツタイプを取得
	 *
	 * @return string						コンテンツタイプ
	 */
	function getDefaultContentType()
	{
		$contentType = '';
		if (!empty($this->_defSerial)){	// ページ作成からの遷移の場合
			$contentType = '';
			$ret = $this->_db->getPageDefBySerial($this->_defSerial, $row);
			if ($ret){
				$pageId = $row['pd_id'];
				$pageSubId = $row['pd_sub_id'];
				$pageInfo = $this->gPage->getPageInfo($pageId, $pageSubId);
				$contentType = $pageInfo['pn_content_type'];
			}
			if (empty($contentType)) $contentType = M3_VIEW_TYPE_CONTENT;				// 汎用コンテンツ
		}
		return $contentType;
	}
}
?>
