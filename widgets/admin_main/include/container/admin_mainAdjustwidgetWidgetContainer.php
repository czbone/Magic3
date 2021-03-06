<?php
/**
 * コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2012 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_mainAdjustwidgetWidgetContainer.php 5467 2012-12-13 14:10:24Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/admin_mainBaseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/admin_mainDb.php');

class admin_mainAdjustwidgetWidgetContainer extends admin_mainBaseWidgetContainer
{
	private $db;	// DB接続オブジェクト
	private $paramObj;		// パラメータ保存用オブジェクト
	private $itemAlignArray;	// 表示位置
	private $align;		// 表示位置
	private $subIdRecords;		// サブページID
	private $exceptPageArray;		// 例外ページ
	const CALENDAR_ICON_FILE = '/images/system/calendar.png';		// カレンダーアイコン
	const ITEM_HEAD_EXCEPT_PAGE = 'item_except_';			// 例外ページサブIDの項目名ヘッダ
	const WIDGET_CSS_CLASS_HEAD = 'm3_';			// ウィジェットCSSクラスのヘッダ部
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		$this->db = new admin_mainDb();
		
		// 表示位置
		$this->itemAlignArray = array(	array(	'name' => $this->_('Not selected'),	'value' => ''),		// 指定なし
										array(	'name' => $this->_('Left'),			'value' => 'left'),		// 左寄せ
										array(	'name' => $this->_('Center'),		'value' => 'center'),	// 中央
										array(	'name' => $this->_('Right'),		'value' => 'right'));	// 右寄せ
	}
	/**
	 * ヘルプデータを設定
	 *
	 * ヘルプの設定を行う場合はヘルプIDを返す。
	 * ヘルプデータの読み込むディレクトリは「自ウィジェットディレクトリ/include/help」に固定。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。そのまま_assign()に渡る
	 * @return string 						ヘルプID。ヘルプデータはファイル名「help_[ヘルプID].php」で作成。ヘルプを使用しない場合は空文字列「''」を返す。
	 */
	function _setHelp($request, &$param)
	{	
		return 'adjustwidget';
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
		return 'adjustwidget.tmpl.html';
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
		// ページ定義IDとページ定義のレコードシリアル番号を取得
		$this->startPageDefParam($defSerial, $defConfigId, $this->paramObj);
		
		// ページIDとページサブIDを取得。ページIDとページサブIDは固定値。
		$pageId = $request->trimValueOf(M3_REQUEST_PARAM_DEF_PAGE_ID);		// ページID(画面編集用)
		$pageSubId = $request->trimValueOf(M3_REQUEST_PARAM_DEF_PAGE_SUB_ID);		// ページサブID(画面編集用)

		$lang	= $this->gEnv->getDefaultLanguage();

		$this->align	= $request->trimValueOf('item_align');	// 表示位置
		$marginTop = $request->trimValueOf('item_top');			// 上マージン
		$marginBottom = $request->trimValueOf('item_bottom');			// 下マージン
		$marginLeft = $request->trimValueOf('item_left');			// 左マージン
		$marginRight = $request->trimValueOf('item_right');			// 右マージン
		$title = $request->trimValueOf('item_title');			// タイトル名
		$titleVisible = ($request->trimValueOf('item_title_visible') == 'on') ? 1 : 0;		// タイトルを表示するかどうか
		$useRender = ($request->trimValueOf('item_use_render') == 'on') ? 1 : 0;		// Joomla!の描画処理を使用するかどうか
		$widgetId = $request->trimValueOf('widget_id');			// ウィジェットID
		$topContent = $request->valueOf('item_top_content');			// 補助コンテンツ(上)
		$bottomContent = $request->valueOf('item_bottom_content');			// 補助コンテンツ(下)
		$showReadmore = ($request->trimValueOf('item_show_readmore') == 'on') ? 1 : 0;		// もっと読むボタンを表示するかどうか
		$readmoreTitle = $request->trimValueOf('item_readmore_title');			// もっと読むボタンタイトル
		$readmoreUrl = $request->trimValueOf('item_readmore_url');			// もっと読むリンク先URL
		
		// 「その他」設定
		$shared = ($request->trimValueOf('item_shared') == 'on') ? 1 : 0;		// 共通属性があるかどうか
		$viewControlType = $request->trimValueOf('item_view_type');			// 表示制御タイプ
		$cssClassSuffix = $request->trimValueOf('item_css_class_suffix');			// 追加CSSクラスサフィックス
		
		// 例外ページ
		$this->db->getPageIdRecords(1/*ページサブID*/, $this->subIdRecords, true/*メニュー表示可能項目のみ取得*/);
		$subIdCount = count($this->subIdRecords);
		$this->exceptPageArray = array();
		for ($i = 0; $i < $subIdCount; $i++){
			$subId = $this->subIdRecords[$i]['pg_id'];
			$itemName = self::ITEM_HEAD_EXCEPT_PAGE . $subId;
			$itemValue = ($request->trimValueOf($itemName) == 'on') ? 1 : 0;
			if ($itemValue) $this->exceptPageArray[] = $subId;
		}

		// 公開期間を取得
		$start_date = $request->trimValueOf('item_start_date');		// 公開期間開始日付
		if (!empty($start_date)) $start_date = $this->convertToProperDate($start_date);
		$start_time = $request->trimValueOf('item_start_time');		// 公開期間開始時間
		if (empty($start_date)){
			$start_time = '';					// 日付が空のときは時刻も空に設定する
		} else {
			if (empty($start_time)) $start_time = '00:00';		// 日付が入っているときは時間にデフォルト値を設定
		}
		if (!empty($start_time)) $start_time = $this->convertToProperTime($start_time, 1/*時分フォーマット*/);
		
		$end_date = $request->trimValueOf('item_end_date');		// 公開期間終了日付
		if (!empty($end_date)) $end_date = $this->convertToProperDate($end_date);
		$end_time = $request->trimValueOf('item_end_time');		// 公開期間終了時間
		if (empty($end_date)){
			$end_time = '';					// 日付が空のときは時刻も空に設定する
		} else {
			if (empty($end_time)) $end_time = '00:00';		// 日付が入っているときは時間にデフォルト値を設定
		}
		if (!empty($end_time)) $end_time = $this->convertToProperTime($end_time, 1/*時分フォーマット*/);
		
		$act = $request->trimValueOf('act');
		$replaceNew = false;		// データを再取得するかどうか
		if ($act == 'update'){		// 行更新のとき
			// 入力チェック
			$this->checkNumeric($marginTop, $this->_('Margin Top'), true);		// マージン(上)
			$this->checkNumeric($marginBottom, $this->_('Margin Bottom'), true);		// マージン(下)
			$this->checkNumeric($marginLeft, $this->_('Margin Left'), true);		// マージン(左)
			$this->checkNumeric($marginRight, $this->_('Margin Right'), true);		// マージン(右)
			$this->checkInput($widgetId, $this->_('Widget ID'));		// ウィジェットID
			
			// エラーなしの場合は、データを更新
			if ($this->getMsgCount() == 0){
				// CSSを作成
				$style = '';
				if (!empty($this->align)) $style .= 'text-align:' . $this->align . ';';
				if (!empty($marginTop)) $style .= 'padding-top:' . $marginTop . 'px;';
				if (!empty($marginBottom)) $style .= 'padding-bottom:' . $marginBottom . 'px;';
				if (!empty($marginLeft)) $style .= 'padding-left:' . $marginLeft . 'px;';
				if (!empty($marginRight)) $style .= 'padding-right:' . $marginRight . 'px;';
				
				$ret = $this->db->updatePageDefInfo($defSerial, $style, $title, $titleVisible, $useRender, $topContent, $bottomContent, $showReadmore, $readmoreTitle, $readmoreUrl);
				if ($ret){		// データ追加成功のとき
					$this->setMsg(self::MSG_GUIDANCE, $this->_('Configration updated.'));		// データを更新しました
					$replaceNew = true;			// データを再取得
					
					// キャッシュをクリア
					$this->gCache->clearCacheByWidgetConfigId($widgetId, $defConfigId);
		
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow($defSerial);
				} else {
					$this->setMsg(self::MSG_APP_ERR, $this->_('Failed in updating configration.'));			// データ更新に失敗しました
				}
			}
		} else if ($act == 'update_other'){		// 「その他」更新のとき
			// 期間範囲のチェック
			if (!empty($start_date) && !empty($end_date)){
				if (strtotime($start_date . ' ' . $start_time) >= strtotime($end_date . ' ' . $end_time)) $this->setUserErrorMsg($this->_('Invalid view term.'));		// 表示期間が不正です
			}
			$this->checkSingleByte($cssClassSuffix, '追加CSSクラス', true);			// 追加CSSクラスサフィックス
			
			// エラーなしの場合は、データを更新
			if ($this->getMsgCount() == 0){
				// 保存データ作成
				if (empty($start_date)){
					$startDt = $this->gEnv->getInitValueOfTimestamp();
				} else {
					$startDt = $start_date . ' ' . $start_time;
				}
				if (empty($end_date)){
					$endDt = $this->gEnv->getInitValueOfTimestamp();
				} else {
					$endDt = $end_date . ' ' . $end_time;
				}
				
				// 表示期間
				$updateData = array();
				$updateData['pd_active_start_dt'] = $startDt;
				$updateData['pd_active_end_dt'] = $endDt;
				$updateData['pd_view_control_type'] = $viewControlType;		// 表示制御タイプ
				
				// 例外ページ
				$exceptPageStr = '';
				if (!empty($this->exceptPageArray)) $exceptPageStr = implode(',', $this->exceptPageArray);
				$updateData['pd_except_sub_id'] = $exceptPageStr;
				
				// 追加CSSクラス
				$updateData['pd_suffix'] = $cssClassSuffix;			// 追加CSSクラスサフィックス
				
				$ret = $this->db->updatePageDefRecord($defSerial, $updateData);
				
				// ページ共通属性を更新
				if ($ret) $ret = $this->db->toggleSharedWidget($pageId, $pageSubId, $defSerial, $shared);
				
				if ($ret){		// データ追加成功のとき
					$this->setMsg(self::MSG_GUIDANCE, $this->_('Configration updated.'));		// データを更新しました
					$replaceNew = true;			// データを再取得
					
					// キャッシュをクリア
					$this->gCache->clearCacheByWidgetConfigId($widgetId, $defConfigId);
		
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow($defSerial);
				} else {
					$this->setMsg(self::MSG_APP_ERR, $this->_('Failed in updating configration.'));		// データ更新に失敗しました
				}
			}
			// タブを選択
			$activeTab = 'widget_other';
		} else {		// 初期状態
			// 初期値設定
			$this->align = '';
			$marginTop = '';			// 上マージン
			$marginBottom = '';			// 下マージン
			$marginLeft = '';			// 左マージン
			$marginRight = '';			// 右マージン
			$title = '';			// タイトル名
			$titleVisible = 1;		// タイトルを表示するかどうか
			$useRender = 1;		// Joomla!の描画処理を使用するかどうか
			$widgetId = '';	// ウィジェットID
			$topContent = '';			// 補助コンテンツ(上)
			$bottomContent = '';			// 補助コンテンツ(下)
			$showReadmore = 0;		// もっと読むボタンを表示するかどうか
			$readmoreTitle = '';			// もっと読むボタンタイトル
			$readmoreUrl = '';			// もっと読むリンク先URL
		
			// 「その他」設定
			$shared = 0;		// 共通属性があるかどうか
			$viewControlType = 0;		// 表示制御タイプ
			$this->exceptPageArray = array();		// 例外ページ
			$start_date = '';	// 公開期間開始日
			$start_time = '';	// 公開期間開始時間
			$end_date = '';	// 公開期間終了日
			$end_time = '';	// 公開期間終了時間
			$cssClassSuffix = '';			// 追加CSSクラスサフィックス
				
			$replaceNew = true;
		}
		// 表示データ再取得
		if ($replaceNew){
			$ret = $this->db->getPageDef($defSerial, $row);
			if ($ret){
				$style = $row['pd_style'];
				if (!empty($style)){
					$lines = explode(';', $style);
					for ($i = 0; $i < count($lines); $i++){
						$keyValue = explode(':', $lines[$i]);
						$key = strtolower(trim($keyValue[0]));
						$value = strtolower(trim($keyValue[1]));
						switch ($key){
							case 'text-align':
								$this->align = $value;
								break;
							case 'padding-top':
								$marginTop = str_replace('px', '', $value);
								break;
							case 'padding-bottom':
								$marginBottom = str_replace('px', '', $value);
								break;
							case 'padding-left':
								$marginLeft = str_replace('px', '', $value);
								break;
							case 'padding-right':
								$marginRight = str_replace('px', '', $value);
								break;
						}
					}
				}
				$title = $row['pd_title'];			// タイトル名
				$titleVisible = $row['pd_title_visible'];			// タイトルを表示するかどうか
				$useRender = $row['pd_use_render'];		// Joomla!の描画処理を使用するかどうか
				$widgetId = $row['pd_widget_id'];	// ウィジェットID
				$topContent = $row['pd_top_content'];			// 補助コンテンツ(上)
				$bottomContent = $row['pd_bottom_content'];			// 補助コンテンツ(下)
				$showReadmore = $row['pd_show_readmore'];		// もっと読むボタンを表示するかどうか
				$readmoreTitle = $row['pd_readmore_title'];			// もっと読むボタンタイトル
				$readmoreUrl = $row['pd_readmore_url'];			// もっと読むリンク先URL
			
				$shared = 0;		// 共通属性があるかどうか
				if (empty($row['pd_sub_id'])) $shared = 1;	// 共通ウィジェットのとき
				$viewControlType = $row['pd_view_control_type'];		// 表示制御タイプ
				$start_date = $this->convertToDispDate($row['pd_active_start_dt']);	// 公開期間開始日
				$start_time = $this->convertToDispTime($row['pd_active_start_dt'], 1/*時分*/);	// 公開期間開始時間
				$end_date = $this->convertToDispDate($row['pd_active_end_dt']);	// 公開期間終了日
				$end_time = $this->convertToDispTime($row['pd_active_end_dt'], 1/*時分*/);	// 公開期間終了時間
				$cssClassSuffix = $row['pd_suffix'];			// 追加CSSクラスサフィックス
				
				//例外ページ
				$this->exceptPageArray = array();
				if (!empty($row['pd_except_sub_id'])) $this->exceptPageArray = explode(',', $row['pd_except_sub_id']);
			}
		}
		
		// 表示位置選択メニュー作成
		$this->createItemAlignMenu();
		
		// ページID選択チェックボックス作成
		$this->createPageSubIdList();
		
		// ナビゲーションタブ作成
		$tabDef = array();
		$tabItem = new stdClass;
		$tabItem->name	= $this->_('Basic');		// 基本
		$tabItem->task	= '';
		$tabItem->url	= '#widget_config';
		$tabItem->parent	= 0;
		$tabItem->active	= false;
		$tabDef[] = $tabItem;
		$tabItem = new stdClass;
		$tabItem->name	= $this->_('Others');
		$tabItem->task	= '';
		$tabItem->url	= '#widget_other';			// その他
		$tabItem->parent	= 0;
		$tabItem->active	= false;
		$tabDef[] = $tabItem;
		$tabHtml = $this->gDesign->createConfigNavTab($tabDef);
		$this->tmpl->addVar("_widget", "nav_tab", $tabHtml);
		if (empty($activeTab)){		// タブの選択
			$this->tmpl->addVar('_widget', 'active_tab', 'widget_config');
		} else {
			$this->tmpl->addVar('_widget', 'active_tab', $activeTab);
		}
		
		$this->tmpl->addVar("_widget", "top", $marginTop);				// 上マージン
		$this->tmpl->addVar("_widget", "bottom", $marginBottom);		// 下マージン
		$this->tmpl->addVar("_widget", "left", $marginLeft);			// 左マージン
		$this->tmpl->addVar("_widget", "right", $marginRight);			// 右マージン
		$this->tmpl->addVar("_widget", "title", $this->convertToDispString($title));				// タイトル名
		if (!empty($titleVisible)) $this->tmpl->addVar("_widget", "title_visible", 'checked');		// タイトルを表示するかどうか
		if (!empty($useRender)) $this->tmpl->addVar("_widget", "use_render", 'checked');		// Joomla!の描画処理を使用するかどうか
		$this->tmpl->addVar("_widget", "widget_id", $widgetId);				// ウィジェットID
		$this->tmpl->addVar("_widget", "top_content", $topContent);			// 補助コンテンツ(上)
		$this->tmpl->addVar("_widget", "bottom_content", $bottomContent);			// 補助コンテンツ(下)
		if (!empty($showReadmore)) $this->tmpl->addVar("_widget", "show_readmore_checked", 'checked');		// もっと読むボタンを表示するかどうか
		$this->tmpl->addVar("_widget", "readmore_title", $readmoreTitle);			// もっと読むボタンタイトル
		$this->tmpl->addVar("_widget", "readmore_url", $readmoreUrl);			// もっと読むリンク先URL
			
		// 「その他」設定
		$checked = '';
		if ($shared) $checked = 'checked';		// 共通属性があるかどうか
		switch ($viewControlType){		// 表示制御タイプ
			case 0:		// 常時表示
			default:
				$this->tmpl->addVar("_widget", "checked_always", 'checked');
				break;
			case 1:		// ログイン時のみ表示
				$this->tmpl->addVar("_widget", "checked_login", 'checked');
				break;
			case 2:		// 非ログイン時のみ表示
				$this->tmpl->addVar("_widget", "checked_no_login", 'checked');
				break;
		}
		$this->tmpl->addVar("_widget", "shared_checked", $checked);
		$this->tmpl->addVar("_widget", "start_date", $start_date);	// 公開期間開始日
		$this->tmpl->addVar("_widget", "start_time", $start_time);	// 公開期間開始時間
		$this->tmpl->addVar("_widget", "end_date", $end_date);	// 公開期間終了日
		$this->tmpl->addVar("_widget", "end_time", $end_time);	// 公開期間終了時間
		
		$widgetOuterClass = self::WIDGET_CSS_CLASS_HEAD . str_replace('/', '_', $widgetId);
		$this->tmpl->addVar("_widget", "css_class", $this->convertToDispString($widgetOuterClass));	// ウィジェットCSSクラス
		$this->tmpl->addVar("_widget", "css_class_suffix", $this->convertToDispString($cssClassSuffix));			// 追加CSSクラスサフィックス
		
		// パス等を設定
		$this->tmpl->addVar('_widget', 'calendar_img', $this->getUrl($this->gEnv->getRootUrl() . self::CALENDAR_ICON_FILE));	// カレンダーアイコン
		
		// テキストをローカライズ
		$this->localizeText();
		
		// ページ定義IDとページ定義のレコードシリアル番号を更新
		$this->endPageDefParam($defSerial, $defConfigId, $this->paramObj);
	}
	/**
	 * JavascriptファイルをHTMLヘッダ部に設定
	 *
	 * JavascriptファイルをHTMLのheadタグ内に追加出力する。
	 * _assign()よりも後に実行される。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。
	 * @return string 						Javascriptファイル。出力しない場合は空文字列を設定。
	 */
	function _addScriptFileToHead($request, &$param)
	{
		$scriptArray = array($this->getUrl($this->gEnv->getScriptsUrl() . self::CALENDAR_SCRIPT_FILE),		// カレンダースクリプトファイル
							$this->getUrl($this->gEnv->getScriptsUrl() . self::CALENDAR_LANG_FILE),	// カレンダー言語ファイル
							$this->getUrl($this->gEnv->getScriptsUrl() . self::CALENDAR_SETUP_FILE));	// カレンダーセットアップファイル
		return $scriptArray;

	}
	/**
	 * CSSファイルをHTMLヘッダ部に設定
	 *
	 * CSSファイルをHTMLのheadタグ内に追加出力する。
	 * _assign()よりも後に実行される。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。
	 * @return string 						CSS文字列。出力しない場合は空文字列を設定。
	 */
	function _addCssFileToHead($request, &$param)
	{
		return $this->getUrl($this->gEnv->getScriptsUrl() . self::CALENDAR_CSS_FILE);
	}
	/**
	 * 表示位置選択メニュー作成
	 *
	 * @return なし
	 */
	function createItemAlignMenu()
	{
		for ($i = 0; $i < count($this->itemAlignArray); $i++){
			$value = $this->itemAlignArray[$i]['value'];
			$name = $this->itemAlignArray[$i]['name'];
			
			$selected = '';
			if ($value == $this->align) $selected = 'selected';
			
			$row = array(
				'value'    => $value,			// 値
				'name'     => $name,			// 名前
				'selected' => $selected														// 選択中かどうか
			);
			$this->tmpl->addVars('item_align_list', $row);
			$this->tmpl->parseTemplate('item_align_list', 'a');
		}
	}
	/**
	 * ページサブIDの一覧を作成
	 *
	 * @return なし
	 */
	function createPageSubIdList()
	{
		for ($i = 0; $i < count($this->subIdRecords); $i++){
			$value = $this->subIdRecords[$i]['pg_id'];
			$name = $this->subIdRecords[$i]['pg_name'];
			
			$checked = '';
			if (in_array($value, $this->exceptPageArray)) $checked = 'checked';
			
			$row = array(
				'value'		=> $value,			// 値
				'name'		=> $this->convertToDispString($name),			// 名前
				'checked'	=> $checked														// 選択中かどうか
			);
			$this->tmpl->addVars('sub_id_list', $row);
			$this->tmpl->parseTemplate('sub_id_list', 'a');
		}
	}
	/**
	 * テキストをローカライズ
	 *
	 * @return なし
	 */
	function localizeText()
	{
		$localeText = array();
		$localeText['msg_update'] = $this->_('Update config?');		// 設定を更新しますか?
		$localeText['label_widget_common_config'] = $this->_('Widget Common Config');			// ウィジェット共通設定
//		$localeText['label_config_basic'] = $this->_('Basic');			// 基本
//		$localeText['label_config_other'] = $this->_('Others');			// その他
		$localeText['label_adjust_widget'] = $this->_('Adjust Widget Title and Contents');			// ウィジェットタイトル、位置調整
		$localeText['label_title'] = $this->_('Title');			// タイトル名
		$localeText['label_visible'] = $this->_('Visible');			// 表示
		$localeText['label_margin'] = $this->_('Margin');			// マージン
		$localeText['label_top'] = $this->_('Top:');			// 上：
		$localeText['label_bottom'] = $this->_('Bottom:');			// 下：
		$localeText['label_left'] = $this->_('Left:');			// 左：
		$localeText['label_right'] = $this->_('Right:');			// 右：
		$localeText['label_position'] = $this->_('Contents Position');			// テキスト表示位置
		$localeText['label_render'] = $this->_('Render');// 描画処理
		$localeText['label_use_joomla_render'] = $this->_('Render by Joomla! style');// Joomla!スタイルの描画処理を使用
		$localeText['label_top_content'] = $this->_('Additional Top Content');// 補助コンテンツ(上)
		$localeText['label_bottom_content'] = $this->_('Additional Bottom Content');// 補助コンテンツ(下)
		$localeText['label_readmore'] = $this->_('Readmore Button');// 「もっと読む」ボタン
		$localeText['label_readmore_title'] = $this->_('Title:');// タイトル：
		$localeText['label_readmore_url'] = $this->_('Url:');// URL：
		
		$localeText['label_view_control'] = $this->_('View Control');// 表示制御
		$localeText['label_shared_attr'] = $this->_('Page Shared Attribute');// ページ共通属性
		$localeText['label_on'] = $this->_('On');// オン
		$localeText['label_view_term'] = $this->_('View Term');// 表示期間
		$localeText['label_except_page'] = $this->_('Except Page');		// 例外ページ
		$localeText['label_start_date'] = $this->_('Start Date:');		// 開始日
		$localeText['label_end_date'] = $this->_('End Date:');		// 終了日
		$localeText['label_hour'] = $this->_('Hour:');		// 時間
		$localeText['label_calendar'] = $this->_('Calendar');		// カレンダー
		$localeText['label_view_option'] = $this->_('View Option');		// 表示オプション
		$localeText['label_view_type'] = $this->_('View Type');		// 表示表示タイプ
		$localeText['label_always'] = $this->_('Always');		// 常時表示
		$localeText['label_login'] = $this->_('When user in login');		// ログイン時のみ表示
		$localeText['label_no_login'] = $this->_('When user not in login');		// 非ログイン時のみ表示
		$localeText['label_css_class'] = $this->_('Additional CSS Class');		// 追加CSSクラス
		$localeText['label_update'] = $this->_('Update');// 更新
		$this->setLocaleText($localeText);
	}
}
?>
