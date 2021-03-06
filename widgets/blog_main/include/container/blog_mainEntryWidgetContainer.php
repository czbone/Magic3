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
 * @version    SVN: $Id: blog_mainEntryWidgetContainer.php 5236 2012-09-21 01:38:04Z fishbone $
 * @link       http://www.magic3.org
 */
//require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/admin_blog_mainBaseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/blog_mainBaseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() .	'/blog_mainDb.php');

// このファイルはadmin_blog_mainEntryWidgetContainer.phpの内容と同じ。クラス名の定義のみ異なる。
//class admin_blog_mainEntryWidgetContainer extends admin_blog_mainBaseWidgetContainer
class blog_mainEntryWidgetContainer extends blog_mainBaseWidgetContainer
{
	private $serialNo;		// 選択中の項目のシリアル番号
	private $entryId;
	private $blogId;		// 所属ブログ
	private $langId;		// 現在の選択言語
	private $serialArray = array();		// 表示されている項目シリアル番号
	private $categoryListData;		// 全記事カテゴリー
	private $categoryArray;			// 選択中の記事カテゴリー
	private $categoryCount;			// カテゴリ数
	private $isMultiLang;			// 多言語対応画面かどうか
	private $fieldValueArray;		// ユーザ定義フィールド入力値
	const ICON_SIZE = 16;		// アイコンのサイズ
	const CONTENT_TYPE = 'bg';		// 記事参照数取得用
	const DEFAULT_LIST_COUNT = 20;			// 最大リスト表示数
	//const CATEGORY_COUNT = 2;				// 記事カテゴリーの選択可能数
	const CATEGORY_NAME_SIZE = 20;			// カテゴリー名の最大文字列長
	const CALENDAR_ICON_FILE = '/images/system/calendar.png';		// カレンダーアイコン
	const ACTIVE_ICON_FILE = '/images/system/active.png';			// 公開中アイコン
	const INACTIVE_ICON_FILE = '/images/system/inactive.png';		// 非公開アイコン
	const NO_BLOG_NAME = '所属なし';		// 所属ブログなし
	const FIELD_HEAD = 'item_';			// フィールド名の先頭文字列
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		$this->isMultiLang = $this->gEnv->isMultiLanguageSite();			// 多言語対応画面かどうか
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
		$task = $request->trimValueOf('task');
		
		if ($task == 'entry_detail'){		// 詳細画面
			return 'admin_entry_detail.tmpl.html';
		} else {			// 一覧画面
			return 'admin_entry.tmpl.html';
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
		$task = $request->trimValueOf('task');
		if ($task == 'entry_detail'){	// 詳細画面
			return $this->createDetail($request);
		} else {			// 一覧画面
			return $this->createList($request);
		}
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
	 * 一覧画面作成
	 *
	 * _setTemplate()で指定したテンプレートファイルにデータを埋め込む。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createList($request)
	{
		// ユーザ情報、表示言語
		$defaultLangId = $this->gEnv->getDefaultLanguage();
		
		$act = $request->trimValueOf('act');
		$this->langId = $request->trimValueOf('item_lang');				// 現在メニューで選択中の言語
		if (empty($this->langId)) $this->langId = $defaultLangId;			// 言語が選択されていないときは、デフォルト言語を設定
		if ($this->gEnv->isAdminDirAccess()){		// 管理画面へのアクセスの場合
			$this->blogId = null;	// デフォルトブログ(ブログID空)を含むすべてのブログ記事にアクセス可能
		} else {
			$this->blogId = $request->trimValueOf(M3_REQUEST_PARAM_BLOG_ID);		// 所属ブログ
		}
		
		// ##### 検索条件 #####
		$pageNo = $request->trimIntValueOf(M3_REQUEST_PARAM_PAGE_NO, '1');				// ページ番号
		
		// DBの保存設定値を取得
		$maxListCount = self::DEFAULT_LIST_COUNT;
		$serializedParam = $this->_db->getWidgetParam($this->gEnv->getCurrentWidgetId());
		if (!empty($serializedParam)){
			$dispInfo = unserialize($serializedParam);
			$maxListCount = $dispInfo->maxMemberListCountByAdmin;		// 会員リスト最大表示数
		}

		$search_startDt = $request->trimValueOf('search_start');		// 検索範囲開始日付
		if (!empty($search_startDt)) $search_startDt = $this->convertToProperDate($search_startDt);
		$search_endDt = $request->trimValueOf('search_end');			// 検索範囲終了日付
		if (!empty($search_endDt)) $search_endDt = $this->convertToProperDate($search_endDt);
		$search_categoryId = $request->trimValueOf('search_category0');			// 検索カテゴリー
		$search_keyword = $request->trimValueOf('search_keyword');			// 検索キーワード
		
		// カテゴリーを格納
		$this->categoryArray = array();
		if (!empty($search_categoryId)){		// 0以外の値を取得
			$this->categoryArray[] = $search_categoryId;
		}

		if ($act == 'delete'){		// 項目削除の場合
			$listedItem = explode(',', $request->trimValueOf('seriallist'));
			$delItems = array();
			for ($i = 0; $i < count($listedItem); $i++){
				// 項目がチェックされているかを取得
				$itemName = 'item' . $i . '_selected';
				$itemValue = ($request->trimValueOf($itemName) == 'on') ? 1 : 0;
				
				if ($itemValue){		// チェック項目
					$delItems[] = $listedItem[$i];
				}
			}
			if (count($delItems) > 0){
				// 削除するブログ記事の情報を取得
				$delEntryInfo = array();
				for ($i = 0; $i < count($delItems); $i++){
					$ret = self::$_mainDb->getEntryBySerial($delItems[$i], $row, $categoryRow);
					if ($ret){
						$newInfoObj = new stdClass;
						$newInfoObj->entryId = $row['be_id'];		// 記事ID
						$newInfoObj->name = $row['be_name'];		// 記事タイトル
						$newInfoObj->thumb = $row['be_thumb_filename'];		// サムネール
						$delEntryInfo[] = $newInfoObj;
					}
				}
				
				$ret = self::$_mainDb->delEntryItem($delItems);
				if ($ret){		// データ削除成功のとき
					$this->setGuidanceMsg('データを削除しました');
					
					// ##### サムネールの削除 #####
					for ($i = 0; $i < count($delEntryInfo); $i++){
						$infoObj = $delEntryInfo[$i];
						$ret = blog_mainCommonDef::removeThumbnail($infoObj->entryId);
						
						if (!empty($infoObj->thumb)){
							$oldFiles = explode(';', $infoObj->thumb);
							$this->gInstance->getImageManager()->delSystemDefaultThumb(M3_VIEW_TYPE_BLOG, blog_mainCommonDef::$_deviceType, $oldFiles);
						}
					}
					
					// キャッシュデータのクリア
					for ($i = 0; $i < count($delItems); $i++){
						$this->clearCacheBySerial($delItems[$i]);
					}
					
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow();
					
					// 運用ログを残す
					for ($i = 0; $i < count($delEntryInfo); $i++){
						$infoObj = $delEntryInfo[$i];
						$this->gOpeLog->writeUserInfo(__METHOD__, 'ブログ記事を削除しました。タイトル: ' . $infoObj->name, 2100, 'ID=' . $infoObj->entryId);
					}
				} else {
					$this->setAppErrorMsg('データ削除に失敗しました');
				}
			}
		} else if ($act == 'search'){		// 検索のとき
			if (!empty($search_startDt) && !empty($search_endDt) && $search_startDt > $search_endDt){
				$this->setUserErrorMsg('期間の指定範囲にエラーがあります。');
			}
			$pageNo = 1;		// ページ番号初期化
		} else if ($act == 'selpage'){			// ページ選択
		}
		// ###### 一覧の取得条件を作成 ######
		if (!empty($search_endDt)) $endDt = $this->getNextDay($search_endDt);
		
		// 総数を取得
		$totalCount = self::$_mainDb->getEntryItemCount($search_startDt, $endDt, $this->categoryArray, $search_keyword, $this->langId, $this->blogId);

		// 表示するページ番号の修正
		$pageCount = (int)(($totalCount -1) / $maxListCount) + 1;		// 総ページ数
		if ($pageNo < 1) $pageNo = 1;
		if ($pageNo > $pageCount) $pageNo = $pageCount;
		$this->firstNo = ($pageNo -1) * $maxListCount + 1;		// 先頭番号
		
		// ページング用リンク作成
		$pageLink = '';
		if ($pageCount > 1){	// ページが2ページ以上のときリンクを作成
			for ($i = 1; $i <= $pageCount; $i++){
				if ($i == $pageNo){
					$link = '&nbsp;' . $i;
				} else {
					$link = '&nbsp;<a href="#" onclick="selpage(\'' . $i . '\');return false;">' . $i . '</a>';
				}
				$pageLink .= $link;
			}
		}
		
		// 記事項目リストを取得
		self::$_mainDb->searchEntryItems($maxListCount, $pageNo, $search_startDt, $endDt, $this->categoryArray, $search_keyword, $this->langId, array($this, 'itemListLoop'), $this->blogId);
		if (count($this->serialArray) <= 0) $this->tmpl->setAttribute('itemlist', 'visibility', 'hidden');// 投稿記事がないときは、一覧を表示しない
		
		// カテゴリーメニューを作成
		self::$_mainDb->getAllCategory($this->langId, $this->categoryListData);
		$this->createCategoryMenu(1);		// メニューは１つだけ表示
		
		// 検索結果
		$this->tmpl->addVar("_widget", "page_link", $pageLink);
		$this->tmpl->addVar("_widget", "total_count", $totalCount);
		
		// 検索条件
		$this->tmpl->addVar("_widget", "search_start", $search_startDt);	// 開始日付
		$this->tmpl->addVar("_widget", "search_end", $search_endDt);	// 終了日付
		$this->tmpl->addVar("_widget", "search_keyword", $search_keyword);	// 検索キーワード

		// 非表示項目を設定
		$this->tmpl->addVar("_widget", "serial_list", implode($this->serialArray, ','));// 表示項目のシリアル番号を設定
		$this->tmpl->addVar("_widget", "page", $pageNo);	// ページ番号
		$this->tmpl->addVar("_widget", "list_count", $maxListCount);	// 一覧表示項目数
	}
	/**
	 * 詳細画面作成
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createDetail($request)
	{
		// デフォルト値
		$defaultLangId	= $this->gEnv->getDefaultLanguage();
		$regUserId		= $this->gEnv->getCurrentUserId();			// 記事投稿ユーザ
		//$regDt			= date("Y/m/d H:i:s");						// 投稿日時
		
		// ブログ定義値
		$useMultiBlog = self::$_configArray[blog_mainCommonDef::CF_USE_MULTI_BLOG];// マルチブログを使用するかどうか
		$this->categoryCount = self::$_configArray[blog_mainCommonDef::CF_CATEGORY_COUNT];			// カテゴリ数
		if (empty($this->categoryCount)) $this->categoryCount = self::DEFAULT_CATEGORY_COUNT;
		
		// コンテンツレイアウトを取得
		$contentLayout = array(self::$_configArray[blog_mainCommonDef::CF_LAYOUT_ENTRY_SINGLE], self::$_configArray[blog_mainCommonDef::CF_LAYOUT_ENTRY_LIST]);
		$fieldInfoArray = blog_mainCommonDef::parseUserMacro($contentLayout);
		
		// 入力値を取得
		$openBy = $request->trimValueOf(M3_REQUEST_PARAM_OPEN_BY);		// ウィンドウオープンタイプ
		$act = $request->trimValueOf('act');
		$this->langId = $request->trimValueOf('item_lang');				// 現在メニューで選択中の言語
		if (empty($this->langId)) $this->langId = $defaultLangId;			// 言語が選択されていないときは、デフォルト言語を設定	
		$this->entryId = $request->trimValueOf('entryid');		// 記事エントリーID
		$this->serialNo = $request->trimValueOf('serial');		// 選択項目のシリアル番号
//		if (empty($this->serialNo)) $this->serialNo = 0;
		$this->blogId = $request->trimValueOf(M3_REQUEST_PARAM_BLOG_ID);		// 所属ブログ
		$name = $request->trimValueOf('item_name');
		$entry_date = $request->trimValueOf('item_entry_date');		// 投稿日
		$entry_time = $request->trimValueOf('item_entry_time');		// 投稿時間
		$html = $request->valueOf('item_html');
		$html2 = $request->valueOf('item_html2');
		if (strlen($html2) <= 10){ // IE6のときFCKEditorのバグの対応(「続き」が空の場合でもpタグが送信される)
			$html2 = '';
		}
		$desc = $request->trimValueOf('item_desc');		// 簡易説明
		$status = $request->trimValueOf('item_status');		// エントリー状態(0=未設定、1=編集中、2=公開、3=非公開)
		$category = '';									// カテゴリー
		$showComment = ($request->trimValueOf('show_comment') == 'on') ? 1 : 0;				// コメントを表示するかどうか
		$receiveComment = ($request->trimValueOf('receive_comment') == 'on') ? 1 : 0;		// コメントを受け付けるかどうか
		$relatedContent = $request->trimValueOf('item_related_content');	// 関連コンテンツ
		
		// カテゴリーを取得
		$this->categoryArray = array();
		for ($i = 0; $i < $this->categoryCount; $i++){
			$itemName = 'item_category' . $i;
			$itemValue = $request->trimValueOf($itemName);
			if (!empty($itemValue)){		// 0以外の値を取得
				$this->categoryArray[] = $itemValue;
			}
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
		
		// ユーザ定義フィールド入力値取得
		$this->fieldValueArray = array();		// ユーザ定義フィールド入力値
		$fieldKeys = array_keys($fieldInfoArray);
		for ($i = 0; $i < count($fieldKeys); $i++){
			$fieldKey = $fieldKeys[$i];
			$itemName = self::FIELD_HEAD . $fieldKey;
			$itemValue = $this->cleanMacroValue($request->trimValueOf($itemName));
			if (!empty($itemValue)) $this->fieldValueArray[$fieldKey] = $itemValue;
		}
		
		$historyIndex = -1;	// 履歴番号
		$reloadData = false;		// データの再ロード
		if ($act == 'select'){		// 一覧から選択のとき
			$reloadData = true;		// データの再ロード
		} else if ($act == 'selectlang'){		// 項目選択の場合
			// 登録済みのコンテンツデータを取得
			$this->serialNo = self::$_mainDb->getEntrySerialNoByContentId($this->entryId, $this->langId);
			if (empty($this->serialNo)){
				// 取得できないときは一部初期化
				//$name = '';				// タイトル
				//$html = '';				// HTML
				$status = 0;				// エントリー状況
				$reg_user = '';				// 投稿者
				$update_user = '';// 更新者
				$update_dt = '';							
			} else {
				$reloadData = true;		// データの再ロード
			}
		} else if ($act == 'add' || $act == 'addlang'){		// 項目追加の場合
			// 入力チェック
			$this->checkInput($name, 'タイトル');
			$this->checkDate($entry_date, '投稿日付');
			$this->checkTime($entry_time, '投稿時間');
					
			// 期間範囲のチェック
			if (!empty($start_date) && !empty($end_date)){
				if (strtotime($start_date . ' ' . $start_time) >= strtotime($end_date . ' ' . $end_time)) $this->setUserErrorMsg('公開期間が不正です');
			}
			
			// エラーなしの場合は、データを登録
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
				$regDt = $this->convertToProperDate($entry_date) . ' ' . $this->convertToProperTime($entry_time);		// 投稿日時
				
				// サムネール画像を取得
				$thumbFilename = '';
				if (($this->isMultiLang && $this->langId == $this->gEnv->getDefaultLanguage()) || !$this->isMultiLang){		// // 多言語対応の場合はデフォルト言語が選択されている場合のみ処理を行う
					// 次の記事IDを取得
					$nextEntryId = self::$_mainDb->getNextEntryId();
				
					$thumbPath = $this->gInstance->getImageManager()->getFirstImagePath($html);
					if (empty($thumbPath) && !empty($html2)) $thumbPath = $this->gInstance->getImageManager()->getFirstImagePath($html2);		// 本文1に画像がないときは本文2を検索
					if (!empty($thumbPath)){
						$ret = $this->gInstance->getImageManager()->createSystemDefaultThumb(M3_VIEW_TYPE_BLOG, blog_mainCommonDef::$_deviceType, $nextEntryId, $thumbPath, $destFilename);
						if ($ret) $thumbFilename = implode(';', $destFilename);
					}
				}
				
				// 追加パラメータ
				$otherParams = array(	'be_description'		=> $desc,		// 簡易説明
										'be_thumb_filename'		=> $thumbFilename,		// サムネールファイル名
										'be_related_content'	=> $relatedContent,		// 関連コンテンツ
										'be_option_fields'		=> $this->serializeArray($this->fieldValueArray));				// ユーザ定義フィールド値
				//if ($act == 'add'){
				if (($this->isMultiLang && $this->langId == $this->gEnv->getDefaultLanguage()) || !$this->isMultiLang){		// 多言語でデフォルト言語、または単一言語のとき
					$ret = self::$_mainDb->addEntryItem($nextEntryId * (-1)/*次のコンテンツIDのチェック*/, $this->langId, $name, $html, $html2, $status, $this->categoryArray, $this->blogId, 
													$regUserId, $regDt, $startDt, $endDt, $showComment, $receiveComment, $newSerial, $otherParams);
				} else {
					$ret = self::$_mainDb->addEntryItem($this->entryId, $this->langId, $name, $html, $html2, $status, $this->categoryArray, $this->blogId, 
													$regUserId, $regDt, $startDt, $endDt, $showComment, $receiveComment, $newSerial, $otherParams);
				}
				if ($ret){
					$this->setGuidanceMsg('データを追加しました');
					
					// シリアル番号更新
					$this->serialNo = $newSerial;
					$reloadData = true;		// データの再ロード
					
					// ##### サムネールの作成 #####
					$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
					if ($ret){
						$entryId	= $row['be_id'];		// 記事ID
						$html		= $row['be_html'];				// HTML
						$updateDt	= $row['be_create_dt'];
						$status		= $row['be_status'];
				
						if ($status == 2){		// 公開の場合
							$ret = blog_mainCommonDef::createThumbnail($html, $entryId, $updateDt);
						} else {
							$ret = blog_mainCommonDef::removeThumbnail($entryId);
						}
					}
					
					// キャッシュデータのクリア
					$this->clearCacheBySerial($this->serialNo);
					
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow();
					
					// 運用ログを残す
					$statusStr = '';
					$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
					if ($ret){
						$this->entryId = $row['be_id'];		// 記事ID
						$name = $row['be_name'];		// コンテンツ名前
						
						// 公開状態
						switch ($row['be_status']){
							case 1:	$statusStr = '編集中';	break;
							case 2:	$statusStr = '公開';	break;
							case 3:	$statusStr = '非公開';	break;
						}
					}
					$this->gOpeLog->writeUserInfo(__METHOD__, 'ブログ記事を追加(' . $statusStr . ')しました。タイトル: ' . $name, 2100, 'ID=' . $this->entryId);
				} else {
					$this->setAppErrorMsg('データ追加に失敗しました');
				}
			}
		} else if ($act == 'update'){		// 項目更新の場合
			// 入力チェック
			$this->checkInput($name, 'タイトル');
			$this->checkDate($entry_date, '投稿日付');
			$this->checkTime($entry_time, '投稿時間');
			
			// 期間範囲のチェック
			if (!empty($start_date) && !empty($end_date)){
				if (strtotime($start_date . ' ' . $start_time) >= strtotime($end_date . ' ' . $end_time)) $this->setUserErrorMsg('公開期間が不正です');
			}
			
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
				$regDt = $this->convertToProperDate($entry_date) . ' ' . $this->convertToProperTime($entry_time);		// 投稿日時
				
				// サムネール画像を取得
				$thumbFilename = '';
				if (($this->isMultiLang && $this->langId == $this->gEnv->getDefaultLanguage()) || !$this->isMultiLang){		// // 多言語対応の場合はデフォルト言語が選択されている場合のみ処理を行う
					$thumbPath = $this->gInstance->getImageManager()->getFirstImagePath($html);
					if (empty($thumbPath) && !empty($html2)) $thumbPath = $this->gInstance->getImageManager()->getFirstImagePath($html2);		// 本文1に画像がないときは本文2を検索
					if (!empty($thumbPath)){
						$ret = $this->gInstance->getImageManager()->createSystemDefaultThumb(M3_VIEW_TYPE_BLOG, blog_mainCommonDef::$_deviceType, $this->entryId, $thumbPath, $destFilename);
						if ($ret) $thumbFilename = implode(';', $destFilename);
					}
				}

				// 追加パラメータ
				$otherParams = array(	'be_description'		=> $desc,		// 簡易説明
										'be_thumb_filename'		=> $thumbFilename,		// サムネールファイル名
										'be_related_content'	=> $relatedContent,		// 関連コンテンツ
										'be_option_fields'		=> $this->serializeArray($this->fieldValueArray));				// ユーザ定義フィールド値
										
				$ret = self::$_mainDb->updateEntryItem($this->serialNo, $name, $html, $html2, $status, $this->categoryArray, $this->blogId, 
													''/*投稿者そのまま*/, $regDt, $startDt, $endDt, $showComment, $receiveComment, $newSerial, $oldRecord, $otherParams);
				if ($ret){
					// コンテンツに画像がなくなった場合は、サムネールを削除
					if (empty($thumbFilename) && !empty($oldRecord['be_thumb_filename'])){
						$oldFiles = explode(';', $oldRecord['be_thumb_filename']);
						$this->gInstance->getImageManager()->delSystemDefaultThumb(M3_VIEW_TYPE_BLOG, blog_mainCommonDef::$_deviceType, $oldFiles);
					}
				}
				
				if ($ret){
					$this->setGuidanceMsg('データを更新しました');
					
					// シリアル番号更新
					$this->serialNo = $newSerial;
					$reloadData = true;		// データの再ロード
					
					// ##### サムネールの作成 #####
					$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
					if ($ret){
						$entryId	= $row['be_id'];		// 記事ID
						$html		= $row['be_html'];				// HTML
						$updateDt	= $row['be_create_dt'];
						$status		= $row['be_status'];
				
						if ($status == 2){		// 公開の場合
							$ret = blog_mainCommonDef::createThumbnail($html, $entryId, $updateDt);
						} else {
							$ret = blog_mainCommonDef::removeThumbnail($entryId);
						}
					}
					
					// キャッシュデータのクリア
					$this->clearCacheBySerial($this->serialNo);
					
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow();
					
					// 運用ログを残す
					$statusStr = '';
					$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
					if ($ret){
						$this->entryId = $row['be_id'];		// 記事ID
						$name = $row['be_name'];		// コンテンツ名前
						
						// 公開状態
						switch ($row['be_status']){
							case 1:	$statusStr = '編集中';	break;
							case 2:	$statusStr = '公開';	break;
							case 3:	$statusStr = '非公開';	break;
						}
					}
					$this->gOpeLog->writeUserInfo(__METHOD__, 'ブログ記事を更新(' . $statusStr . ')しました。タイトル: ' . $name, 2100, 'ID=' . $this->entryId);
				} else {
					$this->setAppErrorMsg('データ更新に失敗しました');
				}
			}				
		} else if ($act == 'delete'){		// 項目削除の場合
			if (empty($this->serialNo)){
				$this->setUserErrorMsg('削除項目が選択されていません');
			}
			// エラーなしの場合は、データを削除
			if ($this->getMsgCount() == 0){
				// 削除するブログ記事の情報を取得
				$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
				if ($ret){
					$this->entryId = $row['be_id'];		// 記事ID
					$name = $row['be_name'];		// コンテンツ名前
				}
					
				$ret = self::$_mainDb->delEntryItem(array($this->serialNo));
				if ($ret){		// データ削除成功のとき
					$this->setGuidanceMsg('データを削除しました');
					
					// ##### サムネールの削除 #####
					$ret = blog_mainCommonDef::removeThumbnail($this->entryId);
					
					// サムネールを削除
					if (!empty($row['be_thumb_filename'])){
						$oldFiles = explode(';', $row['be_thumb_filename']);
						$this->gInstance->getImageManager()->delSystemDefaultThumb(M3_VIEW_TYPE_BLOG, blog_mainCommonDef::$_deviceType, $oldFiles);
					}
						
					// キャッシュデータのクリア
					$this->clearCacheBySerial($this->serialNo);
					
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow();
					
					// 運用ログを残す
					$this->gOpeLog->writeUserInfo(__METHOD__, 'ブログ記事を削除しました。タイトル: ' . $name, 2100, 'ID=' . $this->entryId);
				} else {
					$this->setAppErrorMsg('データ削除に失敗しました');
				}
			}
		} else if ($act == 'deleteid'){		// ID項目削除の場合
			if (empty($this->serialNo)){
				$this->setUserErrorMsg('削除項目が選択されていません');
			}
			// エラーなしの場合は、データを削除
			if ($this->getMsgCount() == 0){
				// 削除するブログ記事の情報を取得
				$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
				if ($ret){
					$this->entryId = $row['be_id'];		// 記事ID
					$name = $row['be_name'];		// コンテンツ名前
				}
				
				$ret = self::$_mainDb->delEntryItemById($this->serialNo);
				if ($ret){		// データ削除成功のとき
					$this->setGuidanceMsg('データを削除しました');
					
					// ##### サムネールの削除 #####
					$ret = blog_mainCommonDef::removeThumbnail($this->entryId);
					
					// キャッシュデータのクリア
					$this->clearCacheBySerial($this->serialNo);
					
					// 親ウィンドウを更新
					$this->gPage->updateParentWindow();
					
					// 運用ログを残す
					$this->gOpeLog->writeUserInfo(__METHOD__, 'ブログ記事を削除しました。タイトル: ' . $name, 2100, 'ID=' . $this->entryId);
				} else {
					$this->setAppErrorMsg('データ削除に失敗しました');
				}
			}
		} else if ($act == 'get_history'){		// 履歴データの取得のとき
			$reloadData = true;		// データの再読み込み
		} else {	// 初期画面表示のとき
			// ##### ブログ記事IDが設定されているとき(他ウィジェットからの表示)は、データを取得 #####
			if (empty($this->entryId)){
				if (!empty($this->serialNo)){		// シリアル番号で指定の場合
					$reloadData = true;		// データの再読み込み
				}
			} else {
				// 多言語対応の場合は、言語を取得
				if ($this->isMultiLang){		// 多言語対応の場合
					$langId = $request->trimValueOf(M3_REQUEST_PARAM_OPERATION_LANG);		// lang値を取得
					if (!empty($langId)) $this->langId = $langId;
				}
		
				// ブログ記事を取得
				$ret = self::$_mainDb->getEntryItem($this->entryId, $this->langId, $row);
				if ($ret){
					$this->serialNo = $row['be_serial'];		// シリアル番号
					$reloadData = true;		// データの再読み込み
				} else {
					$this->serialNo = 0;
				}
			}
			if (empty($this->serialNo)){
				// 初期値設定
				// 所属ブログIDは親ウィンドウから引き継ぐ
				//$this->blogId = '';		// 所属ブログ
				$entry_date = date("Y/m/d");		// 投稿日
				$entry_time = date("H:i:s");		// 投稿時間
				$showComment = 1;				// コメントを表示するかどうか
				$receiveComment = 1;		// コメントを受け付けるかどうか
			}
		}
		
		// 設定データを再取得
		if ($reloadData){		// データの再ロード
			$ret = self::$_mainDb->getEntryBySerial($this->serialNo, $row, $categoryRow);
			if ($ret){
				$this->entryId = $row['be_id'];		// 記事ID
				$this->blogId = $row['be_blog_id'];		// 所属ブログ
				$name = $row['be_name'];				// タイトル
				$html = $row['be_html'];				// HTML
				$html = str_replace(M3_TAG_START . M3_TAG_MACRO_ROOT_URL . M3_TAG_END, $this->getUrl($this->gEnv->getRootUrl()), $html);// アプリケーションルートを変換
				$html2 = $row['be_html_ext'];				// HTML
				$html2 = str_replace(M3_TAG_START . M3_TAG_MACRO_ROOT_URL . M3_TAG_END, $this->getUrl($this->gEnv->getRootUrl()), $html2);// アプリケーションルートを変換
				$desc = $row['be_description'];		// 簡易説明
				$status = $row['be_status'];				// エントリー状況
				$reg_user = $row['reg_user_name'];				// 投稿者
				$entry_date = $this->timestampToDate($row['be_regist_dt']);		// 投稿日
				$entry_time = $this->timestampToTime($row['be_regist_dt']);		// 投稿時間
				$update_user = $this->convertToDispString($row['lu_name']);// 更新者
				$update_dt = $this->convertToDispDateTime($row['be_create_dt']);
				$start_date = $this->convertToDispDate($row['be_active_start_dt']);	// 公開期間開始日
				$start_time = $this->convertToDispTime($row['be_active_start_dt'], 1/*時分*/);	// 公開期間開始時間
				$end_date = $this->convertToDispDate($row['be_active_end_dt']);	// 公開期間終了日
				$end_time = $this->convertToDispTime($row['be_active_end_dt'], 1/*時分*/);	// 公開期間終了時間
				$showComment = $row['be_show_comment'];				// コメントを表示するかどうか
				$receiveComment = $row['be_receive_comment'];		// コメントを受け付けるかどうか
				$relatedContent = $row['be_related_content'];		// 関連コンテンツ
				
				// 記事カテゴリー取得
				$this->categoryArray = $this->getCategory($categoryRow);
				
				// 履歴番号
				if ($row['be_deleted']) $historyIndex = $row['be_history_index'];
				
				// ユーザ定義フィールド
				$this->fieldValueArray = $this->unserializeArray($row['be_option_fields']);
			}
		}
		// カテゴリーメニューを作成
		self::$_mainDb->getAllCategory($this->langId, $this->categoryListData);
		$this->createCategoryMenu($this->categoryCount);
		
		// ユーザ定義フィールドを作成
		$this->createUserFields($fieldInfoArray);
		
		// 所属ブログ
		if (empty($useMultiBlog)){
			$this->tmpl->setAttribute('show_blogid_area', 'visibility', 'visible');
			
			$blogName = $this->getBlogName($this->blogId);
			$this->tmpl->addVar("show_blogid_area", "blog_id", $this->blogId);	// 所属ブログID
			$this->tmpl->addVar("show_blogid_area", "blog_name", $blogName);	// 所属ブログ名
		} else {		// マルチブログを使用するとき
			$this->tmpl->setAttribute('select_blogid_area', 'visibility', 'visible');
			
			// ブログ選択メニュー作成
			$this->createBlogIdMenu();
		}
		
		// プレビュー用URL
		$previewUrl = $this->gEnv->getDefaultUrl() . '?' . M3_REQUEST_PARAM_BLOG_ENTRY_ID . '=' . $this->entryId;
		if ($historyIndex >= 0) $previewUrl .= '&' . M3_REQUEST_PARAM_HISTORY . '=' . $historyIndex;
		$previewUrl .= '&' . M3_REQUEST_PARAM_OPERATION_COMMAND . '=' . M3_REQUEST_CMD_PREVIEW;
		if ($this->isMultiLang) $previewUrl .= '&' . M3_REQUEST_PARAM_OPERATION_LANG . '=' . $this->langId;		// 多言語対応の場合は言語IDを付加
		$this->tmpl->addVar('_widget', 'preview_url', $previewUrl);// プレビュー用URL(一般画面)
		
		// ### 入力値を再設定 ###
		$this->tmpl->addVar('_widget', 'entryid', $this->entryId);
		$this->tmpl->addVar("_widget", "item_name", $this->convertToDispString($name));		// 名前
		$this->tmpl->addVar("_widget", "item_html", $html);		// HTML
		$this->tmpl->addVar("_widget", "item_html2", $html2);		// HTML(続き)
		$this->tmpl->addVar("_widget", "desc", $desc);		// 簡易説明
		switch ($status){
			case 1:	$this->tmpl->addVar("_widget", "selected_edit", 'selected');	break;
			case 2:	$this->tmpl->addVar("_widget", "selected_public", 'selected');	break;
			case 3:	$this->tmpl->addVar("_widget", "selected_closed", 'selected');	break;
		}	
		$this->tmpl->addVar("_widget", "entry_user", $reg_user);	// 投稿者
		$this->tmpl->addVar("_widget", "entry_date", $entry_date);	// 投稿日
		$this->tmpl->addVar("_widget", "entry_time", $entry_time);	// 投稿時
		$this->tmpl->addVar("_widget", "update_user", $update_user);	// 更新者
		$this->tmpl->addVar("_widget", "update_dt", $update_dt);	// 更新日時
		$this->tmpl->addVar("_widget", "start_date", $start_date);	// 公開期間開始日
		$this->tmpl->addVar("_widget", "start_time", $start_time);	// 公開期間開始時間
		$this->tmpl->addVar("_widget", "end_date", $end_date);	// 公開期間終了日
		$this->tmpl->addVar("_widget", "end_time", $end_time);	// 公開期間終了時間
		$checked = '';
		if ($showComment) $checked = 'checked';
		$this->tmpl->addVar("_widget", "show_comment", $checked);// コメントを表示するかどうか
		$checked = '';
		if ($receiveComment) $checked = 'checked';
		$this->tmpl->addVar("_widget", "receive_comment", $checked);// コメントを受け付けるかどうか
		$this->tmpl->addVar("_widget", "related_content", $relatedContent);	// 関連コンテンツ
		
		// 非表示項目を設定
		$this->tmpl->addVar("_widget", "serial", $this->serialNo);	// シリアル番号

		// 入力フィールドの設定、共通項目のデータ設定
		if (empty($this->entryId)){		// 記事IDが0のときは、新規追加モードにする
			// 記事ID
			$this->tmpl->addVar('_widget', 'id', '新規');
			
			$this->tmpl->setAttribute('add_button', 'visibility', 'visible');
			$this->tmpl->addVar('_widget', 'preview_btn_disabled', 'disabled');// プレビューボタン使用不可
			$this->tmpl->addVar('_widget', 'history_btn_disabled', 'disabled');// 履歴ボタン使用不可
			
			// デフォルト言語を最初に登録
			$this->tmpl->addVar("default_lang", "default_lang", $defaultLangName);
			$this->tmpl->setAttribute('default_lang', 'visibility', 'visible');
		} else {
			// 記事ID
			$itemId = $this->entryId;
			if ($historyIndex >= 0) $itemId .= '(' . ($historyIndex +1) . ')';// 履歴番号
			$this->tmpl->addVar('_widget', 'id', $itemId);
			
			if ($this->serialNo == 0){		// 未登録データのとき
				// データ追加ボタン表示
				$this->tmpl->setAttribute('add_button', 'visibility', 'visible');
			} else {
				// データ更新、削除ボタン表示
				$this->tmpl->setAttribute('delete_button', 'visibility', 'visible');// デフォルト言語以外はデータ削除
				$this->tmpl->setAttribute('update_button', 'visibility', 'visible');
			}
			// 言語選択メニュー作成
			//if (!empty($this->entryId)){	// コンテンツが選択されているとき
			//	self::$_mainDb->getAllLang(array($this, 'langLoop'));
			//	$this->tmpl->setAttribute('select_lang', 'visibility', 'visible');
			//}
		}

		// パス等を設定
		$this->tmpl->addVar('_widget', 'calendar_img', $this->getUrl($this->gEnv->getRootUrl() . self::CALENDAR_ICON_FILE));	// カレンダーアイコン
		
		// 閉じるボタンの表示制御
		if ($openBy == 'simple') $this->tmpl->setAttribute('cancel_button', 'visibility', 'hidden');		// 詳細画面のみの表示のときは戻るボタンを隠す
	}
	/**
	 * 取得したデータをテンプレートに設定する
	 *
	 * @param int $index			行番号(0～)
	 * @param array $fetchedRow		フェッチ取得した行
	 * @param object $param			未使用
	 * @return bool					true=処理続行の場合、false=処理終了の場合
	 */
	function itemListLoop($index, $fetchedRow, $param)
	{
		// シリアル番号
		$serial = $fetchedRow['be_serial'];

		// カテゴリーを取得
		$categoryArray = array();
		$ret = self::$_mainDb->getEntryBySerial($serial, $row, $categoryRow);
		if ($ret){
			for ($i = 0; $i < count($categoryRow); $i++){
				if (function_exists('mb_strimwidth')){
					$categoryArray[] = mb_strimwidth($categoryRow[$i]['bc_name'], 0, self::CATEGORY_NAME_SIZE, '…');
				} else {
					$categoryArray[] = substr($categoryRow[$i]['bc_name'], 0, self::CATEGORY_NAME_SIZE) . '...';
				}
			}
		}
		$category = implode(',', $categoryArray);
		
		// 公開状態
		switch ($fetchedRow['be_status']){
			case 1:	$status = '<font color="orange">編集中</font>';	break;
			case 2:	$status = '<font color="green">公開</font>';	break;
			case 3:	$status = '非公開';	break;
		}
		// 総参照数
		$totalViewCount = $this->gInstance->getAnalyzeManager()->getTotalContentViewCount(self::CONTENT_TYPE, $serial);
		
		// ユーザからの参照状況
		$now = date("Y/m/d H:i:s");	// 現在日時
		$startDt = $fetchedRow['be_active_start_dt'];
		$endDt = $fetchedRow['be_active_end_dt'];
		
		$isActive = false;		// 公開状態
		if ($fetchedRow['be_status'] == 2) $isActive = $this->isActive($startDt, $endDt, $now);// 表示可能
		
		if ($isActive){		// コンテンツが公開状態のとき
			$iconUrl = $this->gEnv->getRootUrl() . self::ACTIVE_ICON_FILE;			// 公開中アイコン
			$iconTitle = '公開中';
		} else {
			$iconUrl = $this->gEnv->getRootUrl() . self::INACTIVE_ICON_FILE;		// 非公開アイコン
			$iconTitle = '非公開';
		}
		$statusImg = '<img src="' . $this->getUrl($iconUrl) . '" width="' . self::ICON_SIZE . '" height="' . self::ICON_SIZE . '" border="0" alt="' . $iconTitle . '" title="' . $iconTitle . '" />';
		
		$row = array(
			'index' => $index,		// 項目番号
			'no' => $index + 1,													// 行番号
			'serial' => $serial,			// シリアル番号
			'id' => $this->convertToDispString($fetchedRow['be_id']),			// ID
			'name' => $this->convertToDispString($fetchedRow['be_name']),		// 名前
			'lang' => $lang,													// 対応言語
			'status_img' => $statusImg,												// ユーザからの参照状況
			'status' => $status,													// 公開状況
			'category' => $category,											// 記事カテゴリー
			'view_count' => $totalViewCount,									// 総参照数
			'reg_user' => $this->convertToDispString($fetchedRow['lu_name']),	// 投稿者
			'reg_date' => $this->convertToDispDateTime($fetchedRow['be_regist_dt']),	// 投稿日時
			'update_user' => $this->convertToDispString($fetchedRow['lu_name']),	// 更新者
			'update_date' => $this->convertToDispDateTime($fetchedRow['be_create_dt'])	// 更新日時
		);
		$this->tmpl->addVars('itemlist', $row);
		$this->tmpl->parseTemplate('itemlist', 'a');
		
		// 表示中項目のシリアル番号を保存
		$this->serialArray[] = $serial;
		return true;
	}
	/**
	 * 取得した言語をテンプレートに設定する
	 *
	 * @param int $index			行番号(0～)
	 * @param array $fetchedRow		フェッチ取得した行
	 * @param object $param			未使用
	 * @return bool					true=処理続行の場合、false=処理終了の場合
	 */
	function langLoop($index, $fetchedRow, $param)
	{
		$selected = '';
		if ($fetchedRow['ln_id'] == $this->langId){
			$selected = 'selected';
		}
		if ($this->gEnv->getCurrentLanguage() == 'ja'){		// 日本語表示の場合
			$name = $this->convertToDispString($fetchedRow['ln_name']);
		} else {
			$name = $this->convertToDispString($fetchedRow['ln_name_en']);
		}

		$row = array(
			'value'    => $this->convertToDispString($fetchedRow['ln_id']),			// 言語ID
			'name'     => $name,			// 言語名
			'selected' => $selected														// 選択中かどうか
		);
		$this->tmpl->addVars('lang_list', $row);
		$this->tmpl->parseTemplate('lang_list', 'a');
		return true;
	}
	/**
	 * 記事カテゴリー取得
	 *
	 * @param array  	$srcRows			取得行
	 * @return array						取得した行
	 */
	function getCategory($srcRows)
	{
		$destArray = array();
		$itemCount = 0;
		for ($i = 0; $i < count($srcRows); $i++){
			if (!empty($srcRows[$i]['bw_category_id'])){
				$destArray[] = $srcRows[$i]['bw_category_id'];
				$itemCount++;
				if ($itemCount >= $this->categoryCount) break;
			}
		}
		return $destArray;
	}
	/**
	 * 記事カテゴリーメニューを作成
	 *
	 * @param int  	$size			メニューの表示数
	 * @return なし						
	 */
	function createCategoryMenu($size)
	{
		for ($j = 0; $j < $size; $j++){
			// selectメニューの作成
			$this->tmpl->clearTemplate('category_list');
			for ($i = 0; $i < count($this->categoryListData); $i++){
				$categoryId = $this->categoryListData[$i]['bc_id'];
				$selected = '';
				if ($j < count($this->categoryArray) && $this->categoryArray[$j] == $categoryId){
					$selected = 'selected';
				}
				$menurow = array(
					'value'		=> $categoryId,			// カテゴリーID
					'name'		=> $this->categoryListData[$i]['bc_name'],			// カテゴリー名
					'selected'	=> $selected														// 選択中かどうか
				);
				$this->tmpl->addVars('category_list', $menurow);
				$this->tmpl->parseTemplate('category_list', 'a');
			}
			$itemRow = array(		
					'index'		=> $j			// 項目番号											
			);
			$this->tmpl->addVars('category', $itemRow);
			$this->tmpl->parseTemplate('category', 'a');
		}
	}
	/**
	 * ブログ名を取得
	 *
	 * @param string $blogId		ブログID
	 * @return string				ブログ名
	 */
	function getBlogName($blogId)
	{
		$ret = self::$_mainDb->getBlogInfoById($blogId, $row);
		if ($ret){
			return $row['bl_name'];
		} else {
			return self::NO_BLOG_NAME;
		}
	}
	/**
	 * ブログ選択メニューを作成
	 *
	 * @return なし						
	 */
	function createBlogIdMenu()
	{
		$selected = '';
		if (empty($this->blogId)) $selected ='selected';
		$row = array(
			'value'    => $this->convertToDispString(''),			// ブログID
			'name'     => $this->convertToDispString(self::NO_BLOG_NAME),			// ブログ名
			'selected' => $selected													// 選択中かどうか
		);
		$this->tmpl->addVars('blogid_list', $row);
		$this->tmpl->parseTemplate('blogid_list', 'a');
				
		$ret = self::$_mainDb->getAllBlogId($rows);
		if ($ret){
			for ($i = 0; $i < count($rows); $i++){
				$selected = '';
				if ($rows[$i]['bl_id'] == $this->blogId) $selected = 'selected';
				$row = array(
					'value'    => $this->convertToDispString($rows[$i]['bl_id']),			// ブログID
					'name'     => $this->convertToDispString($rows[$i]['bl_name']),			// ブログ名
					'selected' => $selected														// 選択中かどうか
				);
				$this->tmpl->addVars('blogid_list', $row);
				$this->tmpl->parseTemplate('blogid_list', 'a');
			}
		}
	}
	/**
	 * 期間から公開可能かチェック
	 *
	 * @param timestamp	$startDt		公開開始日時
	 * @param timestamp	$endDt			公開終了日時
	 * @param timestamp	$now			基準日時
	 * @return bool						true=公開可能、false=公開不可
	 */
	function isActive($startDt, $endDt, $now)
	{
		$isActive = false;		// 公開状態

		if ($startDt == $this->gEnv->getInitValueOfTimestamp() && $endDt == $this->gEnv->getInitValueOfTimestamp()){
			$isActive = true;		// 公開状態
		} else if ($startDt == $this->gEnv->getInitValueOfTimestamp()){
			if (strtotime($now) < strtotime($endDt)) $isActive = true;		// 公開状態
		} else if ($endDt == $this->gEnv->getInitValueOfTimestamp()){
			if (strtotime($now) >= strtotime($startDt)) $isActive = true;		// 公開状態
		} else {
			if (strtotime($startDt) <= strtotime($now) && strtotime($now) < strtotime($endDt)) $isActive = true;		// 公開状態
		}
		return $isActive;
	}
	/**
	 * キャッシュデータをクリア
	 *
	 * @param int $serial		削除対象のコンテンツシリアル番号
	 * @return					なし
	 */
	function clearCacheBySerial($serial)
	{
		$ret = self::$_mainDb->getEntryBySerial($serial, $row, $categoryRow);// 記事ID取得
		if ($ret){
			$entryId = $row['be_id'];		// 記事ID
			$urlParam = array();
			$urlParam[] = M3_REQUEST_PARAM_BLOG_ENTRY_ID . '=' . $entryId;		// 記事ID
			$urlParam[] = M3_REQUEST_PARAM_BLOG_ENTRY_ID_SHORT . '=' . $entryId;		// 記事ID略式
			$this->clearCache($urlParam);
		}
	}
	/**
	 * ユーザ定義フィールドを作成
	 *
	 * @param array $fields			フィールドID
	 * @return bool					true=成功、false=失敗
	 */
	function createUserFields($fields)
	{
		if (count($fields) == 0) return true;
		
		$this->tmpl->setAttribute('user_fields', 'visibility', 'visible');
		$keys = array_keys($fields);
		$fieldCount = count($keys);
		for ($i = 0; $i < $fieldCount; $i++){
			if ($i == 0) $this->tmpl->addVar('user_fields', 'type', 'first');		// 最初の行の場合
			
			// 入力値を取得
			$key = $keys[$i];
			$value = $this->fieldValueArray[$key];
			if (!isset($value)) $value = '';
			
			$row = array(
				'row_count'	=> $fieldCount,
				'field_id'	=> $this->convertToDispString($key),
				'value'		=> $this->convertToDispString($value)
			);
			$this->tmpl->addVars('user_fields', $row);
			$this->tmpl->parseTemplate('user_fields', 'a');
		}
		return true;
	}
}
?>
