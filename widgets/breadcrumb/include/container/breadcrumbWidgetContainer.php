<?php
/**
 * index.php用コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    パンくずリスト
 * @author     株式会社 毎日メディアサービス
 * @copyright  Copyright 2010-2013 株式会社 毎日メディアサービス.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: breadcrumbWidgetContainer.php 5883 2013-03-31 12:22:13Z fishbone $
 * @link       http://www.m-media.co.jp
 */
require_once($gEnvManager->getContainerPath()		. '/baseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/breadcrumbDb.php');

class breadcrumbWidgetContainer extends BaseWidgetContainer
{
	private $db;	// DB接続オブジェクト
	private $currentMacroUrl;		// 現在のマクロ表記URL
	private $menuItems;		// メニュー項目情報
	private $iconTag;		// パンくずリストのアイコン
	const DEFAULT_TITLE = 'パンくずリスト';		// デフォルトのウィジェットタイトル名
	const DEFAULT_HOME_NAME = 'ホーム';			// デフォルトのルート階層名
	const DEFAULT_HOME_URL = '/';				// デフォルトのホームURL
	const MAX_MENU_TREE_LEVEL = 5;			// メニュー階層最大数
	const MENU_ITEM_NONAME = '名称未設定';	// メニュー項目に名前が設定されていない場合の名前
	const DEFAULT_ARROW_IMAGE_FILE = '/images/arrow.png';		// パンくずリスト用画像
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		$this->db = new breadcrumbDb();
	}
	/**
	 * テンプレートファイルを設定
	 *
	 * _assign()でデータを埋め込むテンプレートファイルのファイル名を返す。
	 * 読み込むディレクトリは、「自ウィジェットディレクトリ/include/template」に固定。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。そのまま_assign()に渡る
	 * @return string 						テンプレートファイル名。テンプレートライブラリを使用しない場合は空文字列「''」を返す。
	 */
	function _setTemplate($request, &$param)
	{	
		return 'index.tmpl.html';
	}
	/**
	 * テンプレートにデータ埋め込む
	 *
	 * _setTemplate()で指定したテンプレートファイルにデータを埋め込む。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。_setTemplate()と共有。
	 * @param								なし
	 */
	function _assign($request, &$param)
	{
		$currentUrl = $this->gEnv->getCurrentRequestUri();
		
		// このシステム外のアクセスの場合は終了
		if (!$this->gEnv->isSystemUrlAccess($currentUrl)) return;

		// ##### メニュー作成開始 #####
		// 表示設定を取得
		$visibleOnRoot = 1;				// トップページでリスト表示するかどうか
		$useHiddenMenu = 0;		// 非表示のメニューウィジェットの定義を使用する
		$separatorImgPath = '';		// 区切り画像パス
		$paramObj = $this->getWidgetParamObj();
		if (!empty($paramObj)){
			$visibleOnRoot	= $paramObj->visibleOnRoot;
			if (isset($paramObj->useHiddenMenu)) $useHiddenMenu = $paramObj->useHiddenMenu;		// 非表示のメニューウィジェットの定義を使用する
			$separatorImgPath = $paramObj->separatorImgPath;		// 区切り画像パス
		}
		
		// 現在表示中のメニューID取得
		$ret = $this->db->getMenuId($useHiddenMenu, $this->gEnv->getCurrentPageId(), $this->gEnv->getCurrentPageSubId(), $allMenu);
		if (!$ret) return;
		
		// パンくずリスト用アイコンを取得
/*		$iconUrl = '';
		$iconPath = $this->gEnv->getCurrentTemplatePath() . self::TEMPLATE_ARROW_IMAGE_FILE;
		if (file_exists($iconPath)) $iconUrl = $this->gEnv->getCurrentTemplateUrl() . self::TEMPLATE_ARROW_IMAGE_FILE;
		
		// テンプレートのアイコンがないときは、デフォルトのアイコンを使用
		if (empty($iconUrl)){
			$iconUrl = $this->gEnv->getCurrentWidgetRootUrl() . self::TEMPLATE_ARROW_IMAGE_FILE;
		}*/
		// 区切り画像
		if (empty($separatorImgPath)){
			$separatorImgUrl = $this->gEnv->getCurrentWidgetRootUrl() . self::DEFAULT_ARROW_IMAGE_FILE;		// デフォルトの画像
		} else {
			$separatorImgUrl = $this->gEnv->getRootUrl() . $separatorImgPath;		// ユーザ指定の画像
		}
		$this->iconTag = ' <img src="' . $this->getUrl($separatorImgUrl) . '" /> ';		// 両端にスペースを入れる
	
		// 重複しないメニューIDを作成
		$menuIdArray = array();
		for ($i = 0; $i < count($allMenu); $i++){
			$menuId = $allMenu[$i]['pd_menu_id'];
			if (!in_array($menuId, $menuIdArray)) $menuIdArray[] = $menuId;
		}

		// メニュー項目を取得
		$this->db->getMenuItems($menuIdArray, $menuItems);

		// ルート階層名を取得
		$homeName = self::DEFAULT_HOME_NAME;
		$homeUrl = M3_TAG_START . M3_TAG_MACRO_ROOT_URL . M3_TAG_END . self::DEFAULT_HOME_URL;
		for ($i = 0; $i < count($menuItems); $i++){
			$url = $menuItems[$i]['md_link_url'];			// マクロ表記URLを取得
			if ($this->isRootUrl($url)){		// ルートの場合
				$homeName = $this->getCurrentLangString($menuItems[$i]['md_name']);
				$homeUrl = $url;
				break;
			}
		}
		// 現在のURLがルートのときは終了
		$this->currentMacroUrl = $this->gEnv->getMacroPath($currentUrl);
		if ($this->isRootUrl($this->currentMacroUrl)){// ルート位置の場合の処理
			if ($visibleOnRoot){		// ルートのときリスト表示するとき
				$html = '<span class="breadcrumbs pathway">' . $this->convertToDispString($homeName) . '</span>';
				$this->tmpl->addVar("_widget", "link", $html);
			}
			return;
		}
		
		// 現在のURLパラメータを取得
		$this->currentQueryArray = array();
		list($tmp, $queryStr) = explode('?', $this->currentMacroUrl);
		list($queryStr, $tmp) = explode('#', $queryStr);
		if (!empty($queryStr)) parse_str($queryStr, $this->currentQueryArray);		// クエリーの解析
		
		// 現在のメニュー項目を取得
		$menuItemId = 0;
		for ($i = 0; $i < count($menuItems); $i++){
			$url = $menuItems[$i]['md_link_url'];			// マクロ表記URLを取得
			if ($this->isCurrentMenuItemUrl($url)){
				$menuItemId = $menuItems[$i]['md_id'];
				break;
			}
		}
		if (empty($menuItemId)){		// メニュー上にないURLのときは現在のページから作成
			// 現在のページがデフォルトのページのときはトップ時と同じにする
			$pageSubId = $this->gEnv->getCurrentPageSubId();
			if (count($this->currentQueryArray) == 1 && $this->currentQueryArray[M3_REQUEST_PARAM_PAGE_SUB_ID] == $this->gEnv->getDefaultPageSubId()){
				if ($visibleOnRoot){		// ルートのときリスト表示するとき
					$html = '<span class="breadcrumbs pathway">' . $this->convertToDispString($homeName) . '</span>';
					$this->tmpl->addVar("_widget", "link", $html);
					return;
				}
			} else {
				$pageName = '';
				$line = $this->gPage->getPageInfo($this->gEnv->getCurrentPageId(), $pageSubId);
				if (!empty($line) && !empty($line['pn_name'])) $pageName = $line['pn_name'];

				// ページ名が取得できないときは、ページIDの名前を取得
				if (empty($pageName)){
					$ret = $this->db->getPageRecord($pageSubId, $row);
					if ($ret) $pageName = $row['pg_name'];
				}

				// ページサブID以外のパラメータをもつ場合のみリンクを作成
				if (count($this->currentQueryArray) == 1 && isset($this->currentQueryArray[M3_REQUEST_PARAM_PAGE_SUB_ID])){		
					$html = $this->convertToDispString($pageName);
				} else {
					$pageUrl = $this->gEnv->createCurrentPageUrl();
					$linkUrl = $this->getUrl($pageUrl, true/*リンク用*/);
					$html = '<a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '" class="pathway">' . $this->convertToDispString($pageName) . '</a>';
					
					// getHeadSubTitle()はバージョン1.10.9以降利用可能
					if (version_compare(M3_SYSTEM_VERSION, '1.10.9') >= 0){
						// コンテンツ名が設定されている場合は出力
						$titleArray = $this->gPage->getHeadSubTitle();
						if (count($titleArray) > 0){
							$html .= $this->createTitleLink($titleArray);
						}
					}
				}
			}
		} else {
			$menuId = $menuItems[$i]['md_menu_id'];
		
			// メニュー項目IDの連想配列に変換
			$this->menuItems = array();
			for ($i = 0; $i < count($menuItems); $i++){
				$key = $menuItems[$i][md_id];
				$this->menuItems[$key] = $menuItems[$i];
			}

			// 階層を作成
			$menuPathArray = array();
			$ret = $this->createMenuPath($menuItemId, $menuPathArray);
			$menuPathArray = array_reverse($menuPathArray);		// パスを反転

			// ローカルメニューの場合とグローバルメニューの場合と処理を分ける
			$pageSubId = '';
			for ($i = 0; $i < count($allMenu); $i++){
				if ($menuId == $allMenu[$i]['pd_menu_id']){
					$pageSubId = $allMenu[$i]['pd_sub_id'];
					break;
				}
			}
			if (!empty($pageSubId)){	// ローカルメニューのとき
				// グローバルメニューの該当ページを取得
				$globalMenuItemId = 0;
				for ($i = 0; $i < count($menuItems); $i++){
					$url = $menuItems[$i]['md_link_url'];			// マクロ表記URLを取得
					if ($this->isPageUrl($url, $pageSubId)){
						$globalMenuItemId = $menuItems[$i]['md_id'];
						break;
					}
				}
				if (!empty($globalMenuItemId)){
					// グローバルメニュー階層を作成
					$pathArray = $menuPathArray;
					$menuPathArray = array();
					$ret = $this->createMenuPath($globalMenuItemId, $menuPathArray);
					$menuPathArray = array_reverse($menuPathArray);		// パスを反転
				
					// メニューを連結
					$menuPathArray = array_merge($menuPathArray, $pathArray);
				}
			}
			// リンク作成
			$html = $this->createLink($menuPathArray);
		}
		if (!empty($html)){		// リンクが空のときは表示しない
			$linkUrl = $this->getUrl($homeUrl, true/*リンク用*/);
			$html = '<a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '" class="pathway">' . $this->convertToDispString($homeName) . '</a>' . $this->iconTag . $html;// リンクの間にアイコンを挿入
			$html = '<span class="breadcrumbs pathway">' . $html . '</span>';
			$this->tmpl->addVar("_widget", "link", $html);
		}
	}
	/**
	 * ウィジェットのタイトルを設定
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。そのまま_assign()に渡る
	 * @return string 						ウィジェットのタイトル名
	 */
	function _setTitle($request, &$param)
	{
		return self::DEFAULT_TITLE;
	}
	/**
	 * メニューパス作成
	 *
	 * @param int	$menuItemId		メニュー項目ID
	 * @param array	$menuPathArray	メニューID
	 * @param int	$level			階層数
	 * @return bool					true=正常終了、false=異常終了
	 */
	function createMenuPath($menuItemId, &$menuPathArray, $level = 0)
	{
		// メニューの階層を制限
		if ($level >= self::MAX_MENU_TREE_LEVEL) return false;
		
		if (empty($menuItemId)) return true;		// メニューIDが0のときは終了
		
		// メニューパス追加
		$menuPathArray[] = $menuItemId;
		
		// メニュー項目情報を取得
		$menuItemInfo = $this->menuItems[$menuItemId];
		if (isset($menuItemInfo)){
			// 親メニュー項目のパスを取得
			$parentId = $menuItemInfo['md_parent_id'];
			$ret = $this->createMenuPath($parentId, $menuPathArray, $level + 1);
			return $ret;
		} else {
			return false;
		}
	}
	/**
	 * パンくずリスト作成
	 *
	 * @param array	$menuPathArray		メニューパス
	 * @return string					HTML出力
	 */
	function createLink($menuPathArray)
	{
		$outputHtml = '';
		
		$linkCount = count($menuPathArray);
		for ($i = 0; $i < $linkCount; $i++){
			$menuItemId = $menuPathArray[$i];
			
			// リンク先の作成
			$linkUrl = $this->menuItems[$menuItemId]['md_link_url'];
			$linkUrl = $this->getUrl($linkUrl, true/*リンク用*/);
			
			// メニュー項目を作成
			$name = $this->getCurrentLangString($this->menuItems[$menuItemId]['md_name']);
			if (empty($name)) $name = self::MENU_ITEM_NONAME;
			
			// リンクの間にアイコンを挿入
			if ($i > 0) $outputHtml .= $this->iconTag;
			
			if (empty($linkUrl) || $i == $linkCount -1){		// 最後の項目はリンク作成しない
				$outputHtml .= $this->convertToDispString($name) . M3_NL;
			} else {
				$outputHtml .= '<a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '" class="pathway">' . $this->convertToDispString($name) . '</a>' . M3_NL;
			}
		}
		return $outputHtml;
	}
	/**
	 * 画面タイトルからパンくずリスト作成
	 *
	 * @param array	$titleArray		タイトル情報
	 * @return string				HTML出力
	 */
	function createTitleLink($titleArray)
	{
		$outputHtml = '';
		
		$linkCount = count($titleArray);
		for ($i = 0; $i < $linkCount; $i++){
			$line = $titleArray[$i];
			
			// リンク先の作成
			$linkUrl = $this->getUrl($line['url'], true/*リンク用*/);
			
			// メニュー項目を作成
			$name = $line['title'];
			if (empty($name)) $name = self::MENU_ITEM_NONAME;
			
			// リンクの間にアイコンを挿入
			$outputHtml .= $this->iconTag;
			
			if (empty($linkUrl) || $i == $linkCount -1){		// 最後の項目はリンク作成しない
				$outputHtml .= $this->convertToDispString($name) . M3_NL;
			} else {
				$outputHtml .= '<a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '" class="pathway">' . $this->convertToDispString($name) . '</a>' . M3_NL;
			}
		}
		return $outputHtml;
	}
	/**
	 * メニュー項目のURLが現在のアクセス中のURLか判断
	 *
	 * @param string $url			チェック対象のURL
	 * @return bool					true=アクセス中、false=非アクセス
	 */
	function isCurrentMenuItemUrl($url)
	{
		// 同じURLのとき
		if ($url == $this->currentMacroUrl) return true;
		
		// URLを解析
		$queryArray = array();
		list($tmp, $queryStr) = explode('?', $url);
		list($queryStr, $tmp) = explode('#', $queryStr);
		if (!empty($queryStr)) parse_str($queryStr, $queryArray);		// クエリーの解析
		
		// URLパラメータの比較
	//	$diffArray = array_diff($this->currentQueryArray, $queryArray);
		$diffArray = array_diff_assoc($this->currentQueryArray, $queryArray);
		if (empty($diffArray)) return true;
		
		return false;
	}
	/**
	 * URLがルートを指しているかどうか取得
	 *
	 * @param string $url	チェック対象のURL(マクロ表記)
	 * @return bool			true=ルート、false=ルート以外
	 */
	function isRootUrl($url)
	{
		//$parsedUrl = parse_url($url);
		//if (empty($parsedUrl['query'])){		
		list($tmp, $query) = explode('?', $url);
		if (!empty($query)) list($query, $tmp) = explode('#', $query);
		if (empty($query)){				// クエリ文字列がないことが条件。「#」はあっても良い。
			// パスを解析
			$relativePath = str_replace(M3_TAG_START . M3_TAG_MACRO_ROOT_URL . M3_TAG_END, '', $url);		// ルートURLからの相対パスを取得
			if (empty($relativePath)){			// Magic3のルートURLの場合
				return true;
			} else if (strStartsWith($relativePath, '/') || strStartsWith($relativePath, '/' . M3_FILENAME_INDEX)){		// ルートURL配下のとき
				return true;
			}
		}
		return false;
	}
	/**
	 * URLが指定のページを示すかどうか判断
	 *
	 * @param string $url			チェック対象のURL
	 * @param string $pageSubId		ページサブID
	 * @return bool					true=指定のページを示す、false=指定のページ外
	 */
	function isPageUrl($url, $pageSubId)
	{
		// URLを解析
		$queryArray = array();
		list($tmp, $queryStr) = explode('?', $url);
		list($queryStr, $tmp) = explode('#', $queryStr);
		if (!empty($queryStr)) parse_str($queryStr, $queryArray);		// クエリーの解析

		// パラメータがサブページIDだけの場合はページサブIDで比較
		if (count($queryArray) == 1 && isset($queryArray[M3_REQUEST_PARAM_PAGE_SUB_ID])){
			if ($queryArray[M3_REQUEST_PARAM_PAGE_SUB_ID] == $pageSubId) return true;
		}
		return false;
	}
}
?>
