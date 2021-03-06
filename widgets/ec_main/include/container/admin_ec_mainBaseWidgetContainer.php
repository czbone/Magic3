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
 * @copyright  Copyright 2006-2012 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_ec_mainBaseWidgetContainer.php 5440 2012-12-08 09:37:39Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getCurrentWidgetContainerPath() . '/ec_mainCommonDef.php');
require_once($gEnvManager->getContainerPath() . '/baseAdminWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/ec_mainDb.php');

class admin_ec_mainBaseWidgetContainer extends BaseAdminWidgetContainer
{
	protected static $_mainDb;			// DB接続オブジェクト
	protected static $_configArray;		// BBS定義値
	protected $_openBy;				// ウィンドウオープンタイプ
	protected $_baseUrl;			// 管理画面のベースURL
	protected $_langId;			// 現在の言語
	protected $_userId;			// 現在のユーザ
	const DEFAULT_TOP_TITLE = 'ショップ';
	const DEFAULT_TASK = 'product';		// デフォルトタスク
	
	// カレンダー用スクリプト
	const CALENDAR_SCRIPT_FILE = '/jscalendar-1.0/calendar.js';		// カレンダースクリプトファイル
	const CALENDAR_LANG_FILE = '/jscalendar-1.0/lang/calendar-ja.js';	// カレンダー言語ファイル
	const CALENDAR_SETUP_FILE = '/jscalendar-1.0/calendar-setup.js';	// カレンダーセットアップファイル
	const CALENDAR_CSS_FILE = '/jscalendar-1.0/calendar-win2k-1.css';		// カレンダー用CSSファイル
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		if (!isset(self::$_mainDb)) self::$_mainDb = new ec_mainDb();
			
		// ブログ定義を読み込む
		if (!isset(self::$_configArray)) self::$_configArray = photo_shopCommonDef::loadConfig(self::$_mainDb);
		
		// 変数初期化
		$this->_langId	= $this->gEnv->getCurrentLanguage();		// 表示言語を取得
		$this->_userId = $this->gEnv->getCurrentUserId();
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

		// 管理画面ペースURL取得
		$this->_baseUrl = $this->getAdminUrlWithOptionParam();
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
		if ($this->_openBy == 'simple') return;			// シンプルウィンドウのときはメニューを表示しない
		
		// 表示画面を決定
		$task = $request->trimValueOf(M3_REQUEST_PARAM_OPERATION_TASK);
		if (empty($task)) $task = self::DEFAULT_TASK;
		
		// パンくずリストを作成
		switch ($task){
			case 'member':		// 会員管理
			case 'member_detail':		// 会員管理(詳細)
				$linkList = ' &gt;&gt; 会員管理 &gt;&gt; 会員一覧';// パンくずリスト
				break;
			case 'order':		// 受注管理
			case 'order_detail':		// 受注管理(詳細)
				$linkList = ' &gt;&gt; 受注管理 &gt;&gt; 受注一覧';// パンくずリスト
				break;
			case 'product':		// 商品管理
			case 'product_detail':		// 商品管理(詳細)
				$linkList = ' &gt;&gt; 商品管理 &gt;&gt; 一般商品';// パンくずリスト
				break;
			case 'photoproduct':		// フォト関連商品
			case 'photoproduct_detail':		// フォト関連商品(詳細)
				$linkList = ' &gt;&gt; 商品管理 &gt;&gt; フォト関連商品';// パンくずリスト
				break;
			case 'productcategory':	// 商品カテゴリ管理
			case 'productcategory_detail':	// 商品カテゴリ管理(詳細)
				$linkList = ' &gt;&gt; 商品管理 &gt;&gt; カテゴリー一覧';// パンくずリスト
				break;
			case 'delivmethod':		// 配送方法
			case 'delivmethod_detail':		// 配送方法(詳細)
				$linkList = ' &gt;&gt; 基本設定 &gt;&gt; 配送方法';// パンくずリスト
				break;
			case 'paymethod':		// 支払方法
			case 'paymethod_detail':		// 支払方法(詳細)
				$linkList = ' &gt;&gt; 基本設定 &gt;&gt; 支払方法';// パンくずリスト
				break;
			case 'calcorder':				// 注文計算
			case 'calcorder_detail':		// 注文計算(詳細)
				$linkList = ' &gt;&gt; 基本設定 &gt;&gt; 注文計算';// パンくずリスト
				break;
			case 'other':		// その他設定
				$linkList = ' &gt;&gt; 基本設定 &gt;&gt; その他';// パンくずリスト
				break;
		}

		// ####### 上段メニューの作成 #######
		$menuText = '<div id="configmenu-upper">' . M3_NL;
		$menuText .= '<ul>' . M3_NL;
		
		// 受注管理
		$current = '';
		$link = $this->_baseUrl . '&task=order';
		if ($task == 'order' ||
			$task == 'order_detail'){
			$current = 'id="current"';
		}
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>受注管理</span></a></li>' . M3_NL;
		
		// 会員管理
		$current = '';
		$link = $this->_baseUrl . '&task=member';
		if ($task == 'member' ||
			$task == 'member_detail' ||
			$task == 'membercsv'	){			// 会員CSVデータ
			$current = 'id="current"';
		}
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>会員管理</span></a></li>' . M3_NL;
		
		// 商品管理
		$current = '';
		$link = $this->_baseUrl . '&task=product';
		if ($task == 'product' || 			// 商品一覧
			$task == 'product_detail' || 	// 商品詳細
			$task == 'productcategory' || 			// 商品カテゴリ
			$task == 'productcategory_detail' || 	// 商品カテゴリ詳細
			$task == 'photoproduct' || 		// フォト商品一覧
			$task == 'photoproduct_detail' || 	// フォト商品詳細
			$task == 'productcsv' ||		// CSVデータ
			$task == 'imageupload'){		// 画像アップロード
			$current = 'id="current"';
		}
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>商品管理</span></a></li>' . M3_NL;
		
		// 基本設定
		$current = '';
		$link = $this->_baseUrl . '&task=delivmethod';
		if ($task == 'delivmethod' ||		// 配送方法
			$task == 'delivmethod_detail' ||		// 配送方法(詳細)
			$task == 'paymethod' ||		// 支払方法
			$task == 'paymethod_detail' ||		// 支払方法(詳細)
			$task ==  'calcorder' ||				// 注文計算
			$task ==  'calcorder_detail' ||		// 注文計算(詳細)
			$task == 'other'){		// その他設定
			$current = 'id="current"';
		}
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>基本設定</span></a></li>' . M3_NL;
		
		// 上段メニュー終了
		$menuText .= '</ul>' . M3_NL;
		$menuText .= '</div>' . M3_NL;
		
		// ####### 下段メニューの作成 #######		
		$menuText .= '<div id="configmenu-lower">' . M3_NL;
		$menuText .= '<ul>' . M3_NL;

		if ($task == 'order' ||
			$task == 'order_detail'){	// 受注管理
			// 受注一覧
			$current = '';
			$link = $this->_baseUrl . '&task=order';
			if ($task == 'order' || $task == 'order_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>受注一覧</span></a></li>' . M3_NL;
		} else if ($task == 'member' ||
					$task == 'member_detail'){	// 会員管理
			// 会員一覧
			$current = '';
			$link = $this->_baseUrl . '&task=member';
			if ($task == 'member' || $task == 'member_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>会員一覧</span></a></li>' . M3_NL;
		} else if ($task == 'product' || 		// 商品一覧
			$task == 'product_detail' || 	// 商品詳細
			$task == 'productcategory' || 			// 商品カテゴリ
			$task == 'productcategory_detail' || 	// 商品カテゴリ詳細
			$task == 'photoproduct' || 		// フォト商品一覧
			$task == 'photoproduct_detail' || 	// フォト商品詳細
			$task == 'productcsv' ||	// CSVデータ
			$task == 'imageupload'){		// 画像アップロード
			// 商品一覧
			$current = '';
			$link = $this->_baseUrl . '&task=product';
			if ($task == 'product' || $task == 'product_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>一般商品</span></a></li>' . M3_NL;
		
			// 商品カテゴリー
			$current = '';
			$link = $this->_baseUrl . '&task=productcategory';
			if ($task == 'productcategory' || $task == 'productcategory_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>商品カテゴリー</span></a></li>' . M3_NL;
			
			// フォト商品
			if (self::$_mainDb->getConfig(photo_shopCommonDef::CF_SELL_PRODUCT_PHOTO)){		// フォト商品販売を行う場合
				$current = '';
				$link = $this->_baseUrl . '&task=photoproduct';
				if ($task == 'photoproduct' || $task == 'photoproduct_detail') $current = 'id="current"';
				$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>フォト商品</span></a></li>' . M3_NL;
			}
			// CSVデータ
//			$current = '';
//			$link = $this->_baseUrl . '&task=productcsv';
//			if ($task == 'productcsv') $current = 'id="current"';
//			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>CSVデータ</span></a></li>' . M3_NL;
			
			// 画像アップロード
//			$current = '';
//			$link = $this->_baseUrl . '&task=imageupload';
//			if ($task == 'imageupload') $current = 'id="current"';
//			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>画像アップロード</span></a></li>' . M3_NL;
		} else if ($task == 'delivmethod' ||		// 配送方法
			$task == 'delivmethod_detail' ||		// 配送方法(詳細)
			$task == 'paymethod' ||		// 支払方法
			$task == 'paymethod_detail' ||		// 支払方法(詳細)
			$task ==  'calcorder' ||				// 注文計算
			$task ==  'calcorder_detail' ||		// 注文計算(詳細)
			$task == 'other'){		// その他
			
			// 配送方法
			$current = '';
			$link = $this->_baseUrl . '&task=delivmethod';
			if ($task == 'delivmethod' || $task == 'delivmethod_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>配送方法</span></a></li>' . M3_NL;
			
			// 支払方法
			$current = '';
			$link = $this->_baseUrl . '&task=paymethod';
			if ($task == 'paymethod' || $task == 'paymethod_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>支払方法</span></a></li>' . M3_NL;
			
			// 注文計算
			$current = '';
			$link = $this->_baseUrl . '&task=calcorder';
			if ($task == 'calcorder' || $task == 'calcorder_detail') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>注文計算</span></a></li>' . M3_NL;
			
			// その他設定
			$current = '';
			$link = $this->_baseUrl . '&task=other';
			if ($task == 'other') $current = 'id="current"';
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link, true) .'"><span>その他</span></a></li>' . M3_NL;
		}
		
		// 下段メニュー終了
		$menuText .= '</ul>' . M3_NL;
		$menuText .= '</div>' . M3_NL;

		// 作成データの埋め込み
		$linkList = '<div id="configmenu-top"><label>' . self::DEFAULT_TOP_TITLE . $linkList . '</label></div>';
		$outputText .= '<table width="90%"><tr><td>' . $linkList . $menuText . '</td></tr></table>' . M3_NL;
		$this->tmpl->addVar("_widget", "menu_items", $outputText);
	}
	/**
	 * 定義値を取得
	 *
	 * @param string $key		定義キー
	 * @param string $default	デフォルト値
	 * @return string			値
	 */
	function _getConfig($key, $default = '')
	{
		$value = self::$_configArray[$key];
		if (!isset($value)) $value = $default;
		return $value;
	}
}
?>
