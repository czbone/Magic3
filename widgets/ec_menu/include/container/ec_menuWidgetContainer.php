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
 * @version    SVN: $Id: ec_menuWidgetContainer.php 5557 2013-01-16 11:29:26Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getContainerPath() . '/baseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/ec_menuDb.php');

class ec_menuWidgetContainer extends BaseWidgetContainer
{
	private $db;			// DB接続オブジェクト
	private $langId;		// 現在の言語
	private $paramObj;		// 定義取得用
	private $templateType;		// テンプレートのタイプ
	private $menuData = array();			// Joomla用のメニューデータ
	private $menuTree = array();			// Joomla用のメニュー階層データ
	private $categoryId;					// カテゴリーID
	private $selectedMenuItems = array();		// 選択中のメニュー項目階層
	const SEARCH_WIDGET = 'ec_disp';			// 商品表示用ウィジェット
	const DEFAULT_MENU_ID = 'ec_menu';			// デフォルトメニューID
	const DEFAULT_CONFIG_ID = 0;
	const MAX_MENU_TREE_LEVEL = 5;			// メニュー階層最大数
	const PARAM_CATEGORY_ID = 'category';	// カテゴリーIDキー
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		$this->db = new ec_menuDb();
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
		// Joomlaテンプレートのバージョンに合わせて出力
		$this->templateType = $this->gEnv->getCurrentTemplateType();
		if ($this->templateType == 0){			// Joomla!v1.0のとき
			return 'menu_old.tmpl.html';
		} else {
			return 'menu.tmpl.html';
		}
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
		$this->langId = $this->gEnv->getCurrentLanguage();
		
		// 定義ID取得
		$configId = $this->gEnv->getCurrentWidgetConfigId();
		if (empty($configId)) $configId = self::DEFAULT_CONFIG_ID;
		
		// パラメータオブジェクトを取得
		$targetObj = $this->getWidgetParamObjByConfigId($configId);
		if (empty($targetObj)){
			$useVerticalMenu = 1;		// 縦型メニューデザインを使用するかどうか
			$linkProduct	= 1;		// 商品詳細にカテゴリーを連動
		} else {
			$useVerticalMenu = $targetObj->useVerticalMenu;		// 縦型メニューデザインを使用するかどうか
			$linkProduct	= $targetObj->linkProduct;		// 商品詳細にカテゴリーを連動
		}
		
		// 縦型メニューデザイン使用の場合はJoomla用パラメータを設定
		if (!empty($useVerticalMenu)) $this->gEnv->setCurrentWidgetJoomlaParam(array('moduleclass_sfx' => 'art-vmenu'));

		$this->categoryId = $request->trimValueOf(self::PARAM_CATEGORY_ID);		// カテゴリー取得
		
		if (empty($this->categoryId) && !empty($linkProduct)){			// 商品詳細とカテゴリーを連動する場合
			$productId = $request->trimValueOf(M3_REQUEST_PARAM_PRODUCT_ID);		// 商品ID取得
			
			// 商品カテゴリー取得
			$ret = $this->db->getProductWithCategory($productId, $this->langId, $row);
			if ($ret) $this->categoryId = $row['pw_category_id'];
		}

		// メニュー作成
		$this->menuData['path'] = array();
		$this->menuData['active_id'] = 0;
		$parentTree = array();			// 選択されている項目までの階層パス
		$menuHtml = $this->createMenu(self::DEFAULT_MENU_ID, 0, 0, $tmp, $parentTree);
		
		if (!empty($menuHtml)) $this->tmpl->addVar("_widget", "menu_html", $menuHtml);
		
		// Joomla用のメニュー階層データを設定
		$this->menuData['tree'] = $this->menuTree;
		$this->gEnv->setJoomlaMenuData($this->menuData);
	}
	/**
	 * メニューツリー作成
	 *
	 * @param string	$menuId		メニューID
	 * @param int		$parantId	親メニュー項目ID
	 * @param int		$level		階層数
	 * @param bool		$hasSelectedChild	現在選択状態の子項目があるかどうか
	 * @param array     $parentTree	現在の階層パス
	 * @return string				ツリーメニュータグ
	 */
	function createMenu($menuId, $parantId, $level, &$hasSelectedChild, &$parentTree)
	{
		static $index = 0;		// インデックス番号
		$hasSelectedChild = false;

		// メニューの階層を制限
		if ($level >= self::MAX_MENU_TREE_LEVEL) return '';
		
		$treeHtml = '';
		if ($this->db->getChildMenuItems($menuId, $parantId, $rows)){
			$itemCount = count($rows);
			for ($i = 0; $i < $itemCount; $i++){
				$row = $rows[$i];
				$classArray = array();
				$linkClassArray = array();
				$attr = '';
				// Joomla用メニューデータ(デフォルト値)
				$menuItem = new stdClass;		// Joomla用メニューデータ
				$menuItem->type = 'alias';		// 内部リンク。外部リンク(url)
				$menuItem->id = $index + 1;
				$menuItem->level = $level + 1;
				$menuItem->active = false;
				$menuItem->parent = false;
				// 階層作成用
				$menuItem->deeper = false;
				$menuItem->shallower = false;
				$menuItem->level_diff = 0;
				$menuTreeCount = count($this->menuTree);
				if ($menuTreeCount > 0){		// 前データがあるときは取得
					$menuLastItem = $this->menuTree[$menuTreeCount -1];
					$menuLastItem->deeper = ($menuItem->level > $menuLastItem->level);
					$menuLastItem->shallower = ($menuItem->level < $menuLastItem->level);
					$menuLastItem->level_diff = $menuLastItem->level - $menuItem->level;
				}
									
				// 非表示のときは処理を飛ばす
				if (!$row['md_visible']) continue;
						
				// Joomla1.0対応
				if ($this->templateType == 0) $linkClassArray[] = 'mainlevel';
				
				// リンク先の作成
				$parsedParam = parseUrlParam($row['md_param']);
				if (empty($parsedParam[self::PARAM_CATEGORY_ID])){
					$linkUrl = '#';
				} else {
					$url = $this->gPage->getDefaultPageUrlByWidget(self::SEARCH_WIDGET, $row['md_param']);
					$linkUrl = $this->getUrl($url, true/*リンク用*/);
				}
				
				// メニュー項目を作成
				$name = $this->getCurrentLangString($row['md_name']);
				$title = $row['md_title'];		// タイトル(HTML可)
				if (empty($title)) $title = $name;
				if (empty($title)) continue;
				
				// メニュー項目タイトルを保存
				$selectedMenuItem = new stdClass;
				$selectedMenuItem->title = $name;
				$selectedMenuItem->url = $linkUrl;
				$this->selectedMenuItems[$level] = $selectedMenuItem;
				
				// 選択状態の設定
				if ($this->checkMenuItemUrl($linkUrl)){
					$attr = ' id="current"';		// メニュー項目を選択状態にする
					$classArray[] = 'active';
					$hasSelectedChild = true;
					
					// Joomla用メニュー階層データ
					$pathTree = $parentTree;			// パスを取得
					$pathTree[] = $index + 1;
					$this->menuData['path'] = $pathTree;
					$this->menuData['active_id'] = $index + 1;
					$menuItem->active = true;
					
					// 選択中のメニュー項目タイトルを作成。フォーマット:AAA-BBB-CCC。
					/*$selectedMenuTitle = '';
					for ($j = 0; $j < $level; $j++){
						$selectedMenuTitle .= $this->selectedMenuTitleArray[$j] . self::TITLE_SEPARATOR;
					}*/
					$menuItems = array();
					for ($j = 0; $j <= $level; $j++){
						$menuItems[] = $this->selectedMenuItems[$j];
					}
					$this->gEnv->setSelectedMenuItems($menuItems);
					//$selectedMenuTitle .= $name;
					//$this->gEnv->setCurrentMenuItem(array('title' => $selectedMenuTitle, 'url' => $linkUrl));
				}
				
				// リンクタイプに合わせてタグを生成
				$linkOption = '';
				if (count($linkClassArray) > 0) $linkOption .= 'class="' . implode(' ', $linkClassArray) . '"';
				switch ($row['md_link_type']){
					case 0:			// 同ウィンドウで開くリンク
					default:
						$menuItem->browserNav = 0;		// ウィンドウオープン方法(0=同じウィンドウ、1=別タブ、2=別ウィンドウ)
						break;
					case 1:			// 別ウィンドウで開くリンク
						$linkOption .= ' target="_blank"';
						$menuItem->browserNav = 1;		// ウィンドウオープン方法(0=同じウィンドウ、1=別タブ、2=別ウィンドウ)
						break;
				}
				
				// メニュータイトルの処理。タグが含まれていない場合は文字をエスケープする。
				$stripName = strip_tags($title);
				if (strlen($stripName) == strlen($title)) $title = $this->convertToDispString($title);		// 文字列長が同じとき
				
				$index++;		// インデックス番号更新
								
				switch ($row['md_type']){
					case 0:			// リンク項目のとき
						// Joomla用メニューデータ作成
						$menuItem->title = $title;
						$menuItem->flink = $linkUrl;
						
						// ##### Joomla用メニュー階層更新 #####
						$this->menuTree[] = $menuItem;
						
						// ##### タグ作成 #####
						if (count($classArray) > 0) $attr .= ' class="' . implode(' ', $classArray) . '"';
						$treeHtml .= '<li' . $attr . '><a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '" ' . $linkOption . '><span>' . $title . '</span></a></li>' . M3_NL;
						break;
					case 1:			// フォルダのとき
						// Joomla用メニューデータ作成
						$menuItem->title = $title;
						$menuItem->flink = $linkUrl;
						$menuItem->parent = true;
						// 階層作成用
						//$menuItem->deeper = true;
						//$menuItem->level_diff = 1;

						// ##### Joomla用メニュー階層更新 #####
						$this->menuTree[] = $menuItem;
					
						// 階層を更新
						//array_push($parentTree, $index + 1);
						array_push($parentTree, $index);
						
						// サブメニュー作成
						$menuText = $this->createMenu($menuId, $row['md_id'], $level + 1, $hasSelectedChild, $parentTree);
						
						// 階層を戻す
						array_pop($parentTree);
						
						// 子項目が選択中のときは「active」に設定
						if ($hasSelectedChild) $classArray[] = 'active';

						// 先頭に「parent」クラスを追加
						array_unshift($classArray, 'parent');
						
						// ##### タグ作成 #####
						if (count($classArray) > 0) $attr .= ' class="' . implode(' ', $classArray) . '"';
						$treeHtml .= '<li' . $attr . '><a href="' . $this->convertUrlToHtmlEntity($linkUrl) . '"><span>' . $title . '</span></a>' . M3_NL;
						if (!empty($menuText)){
							$treeHtml .= '<ul>' . M3_NL;
							$treeHtml .= $menuText;
							$treeHtml .= '</ul>' . M3_NL;
						}
						$treeHtml .= '</li>' . M3_NL;
						break;
					case 2:			// テキストのとき
						$treeHtml .= '<li><span>' . $title . '</span></li>' . M3_NL;
						break;
					case 3:			// セパレータのとき
						// Joomla用メニューデータ作成
						$menuItem->type = 'separator';
						$menuItem->title = $title;
						$menuItem->flink = '';
						
						// ##### Joomla用メニュー階層更新 #####
						$this->menuTree[] = $menuItem;
						
						// ##### タグ作成 #####
						$treeHtml .= '<li><span class="separator">' . $title . '</span></li>' . M3_NL;
						break;
				}
				
				if ($this->templateType == 0){			// Joomla!v1.0のとき
					$itemRow = array(
						'link_url' => $this->convertUrlToHtmlEntity($linkUrl),		// リンク
						'name' => $title,			// タイトル
						'attr' => $attr,			// liタグ追加属性
						'option' => $linkOption			// Aタグ追加属性
					);
					$this->tmpl->addVars('itemlist', $itemRow);
					$this->tmpl->parseTemplate('itemlist', 'a');
				}
				$menuTreeCount = count($this->menuTree);
				if ($menuTreeCount > 0){		// 前データがあるときは取得
					$menuLastItem = $this->menuTree[$menuTreeCount -1];
					$menuLastItem->deeper = (1 > $menuLastItem->level);
					$menuLastItem->shallower = (1 < $menuLastItem->level);
					$menuLastItem->level_diff = $menuLastItem->level - 1;
				}
			}
		}
		return $treeHtml;
	}
	/**
	 * メニュー項目の選択条件をチェック
	 *
	 * @param string $url	チェック対象のURL
	 * @return bool			true=アクティブ、false=非アクティブ
	 */
	function checkMenuItemUrl($url)
	{
		$currentUrl = $this->gEnv->getCurrentRequestUri();
		
		// 同じURLのとき
		if ($url == $currentUrl) return true;
		
		// URLを解析
		$queryArray = array();
		$parsedUrl = parse_url($url);
		if (!empty($parsedUrl['query'])) parse_str($parsedUrl['query'], $queryArray);		// クエリーの解析
		
		// ページサブIDとカテゴリーが同じとき
		if ($this->gEnv->getCurrentPageSubId() == $queryArray[M3_REQUEST_PARAM_PAGE_SUB_ID] &&
			$this->categoryId == $queryArray['category']) return true;					// カテゴリーが同じ
			
		return false;
	}
}
?>
