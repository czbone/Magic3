<?php
/**
 * コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    フリーレイアウトお問い合わせ
 * @author     株式会社 毎日メディアサービス
 * @copyright  Copyright 2009-2013 株式会社 毎日メディアサービス.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_contactus_freelayout3WidgetContainer.php 5824 2013-03-14 05:45:38Z fishbone $
 * @link       http://www.m-media.co.jp
 */
require_once($gEnvManager->getContainerPath() . '/baseAdminWidgetContainer.php');

class admin_contactus_freelayout3WidgetContainer extends BaseAdminWidgetContainer
{
	private $sysDb;	// DB接続オブジェクト
	private $serialNo;		// 選択中の項目のシリアル番号
	private $serialArray = array();			// 表示中のシリアル番号
	private $langId;
	private $configId;		// 定義ID
	private $paramObj;		// パラメータ保存用オブジェクト
	private $typeArray;		// 項目タイプ
	private $fieldInfoArray = array();			// お問い合わせ項目情報
	private $confirmButtonId;		// 確認ボタンのタグID
	private $sendButtonId;		// 送信ボタンのタグID
	private $cancelButtonId;		// 送信キャンセルボタンのタグID
	private $resetButtonId;		// エリアリセットボタンのタグID
	const DEFAULT_NAME_HEAD = '名称未設定';			// デフォルトの設定名
	const DEFAULT_USER_EMAIL_SUBJECT = '送信内容ご確認(自動送信メール)';
	const DEFAULT_USER_EMAIL_FORMAT = "以下の内容でお問い合わせを送信しました。\n\n[#BODY#]";
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		$this->sysDb = $this->gInstance->getSytemDbObject();
		
		// お問い合わせ項目タイプ
		$this->typeArray = array(	array(	'name' => 'テキストボックス',			'value' => 'text'),
									array(	'name' => 'テキストボックス(Eメール)',	'value' => 'email'),
									array(	'name' => 'テキストボックス(計算)',		'value' => 'calc'),
									array(	'name' => 'テキストエリア',				'value' => 'textarea'),
									array(	'name' => 'セレクトメニュー',			'value' => 'select'),
									array(	'name' => 'チェックボックス',			'value' => 'checkbox'),
									array(	'name' => 'ラジオボタン',				'value' => 'radio'));
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
		if ($task == 'list'){		// 一覧画面
			return 'admin_list.tmpl.html';
		} else {			// 一覧画面
			return 'admin.tmpl.html';
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
		if ($task == 'list'){		// 一覧画面
			return $this->createList($request);
		} else {			// 詳細設定画面
			return $this->createDetail($request);
		}
	}
	/**
	 * 詳細画面作成
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createDetail($request)
	{
		// ページ定義IDとページ定義のレコードシリアル番号を取得
		$this->startPageDefParam($defSerial, $defConfigId, $this->paramObj);
		
		$userId		= $this->gEnv->getCurrentUserId();
		$this->langId	= $this->gEnv->getCurrentLanguage();		// 表示言語を取得
		$act = $request->trimValueOf('act');
		$this->serialNo = $request->trimValueOf('serial');		// 選択項目のシリアル番号
		$this->configId = $request->trimValueOf('item_id');		// 定義ID
		if (empty($this->configId)) $this->configId = $defConfigId;		// 呼び出しウィンドウから引き継いだ定義ID
		
		// 入力値を取得
		$name	= $request->trimValueOf('item_name');			// 定義名
		$pageTitle = $request->trimValueOf('item_page_title');			// 画面タイトル
		$baseTemplate = $request->valueOf('item_html');		// 入力エリア作成用ベーステンプレート
		$this->css	= $request->valueOf('item_css');		// 入力エリア作成用CSS
		$this->confirmButtonId = $request->trimValueOf('item_confirm_button');		// 確認ボタンのタグID
		$this->sendButtonId = $request->trimValueOf('item_send_button');		// 送信ボタンのタグID
		$this->cancelButtonId = $request->trimValueOf('item_cancel_button');		// 送信キャンセルボタンのタグID
		$this->resetButtonId = $request->trimValueOf('item_reset_button');		// エリアリセットボタンのタグID
		$fieldCount = intval($request->trimValueOf('fieldcount'));		// お問い合わせ項目数
		$titles = $request->trimValueOf('item_title');		// お問い合わせ項目タイトル
		$descs = $request->trimValueOf('item_desc');		// お問い合わせ項目説明
		$types	= $request->trimValueOf('item_type');		// お問い合わせ項目タイプ
		$defs = $request->trimValueOf('item_def');		// お問い合わせ項目定義
		$values = $request->trimValueOf('required');		// お問い合わせ項目必須入力
		$requireds = array();
		if (strlen($values) > 0) $requireds = explode(',', $values);
		$values = $request->trimValueOf('disabled');		// 編集不可
		$disableds = array();
		if (strlen($values) > 0) $disableds = explode(',', $values);
		$values = $request->trimValueOf('titlevisible');		// お問い合わせ項目タイトル表示制御
		$titleVisibles = array();
		if (strlen($values) > 0) $titleVisibles = explode(',', $values);
		$values = $request->trimValueOf('alphabet');		// 入力制限半角英字
		$alphabets = array();
		if (strlen($values) > 0) $alphabets = explode(',', $values);
		$values = $request->trimValueOf('number');		// 入力制限半角数値
		$numbers = array();
		if (strlen($values) > 0) $numbers = explode(',', $values);
		$defaults = $request->trimValueOf('item_default');		// お問い合わせ項目デフォルト値
		$fieldIds = $request->trimValueOf('item_field_id');		// お問い合わせ項目フィールドID
		$calcs = $request->trimValueOf('item_calc');		// お問い合わせ項目計算式
		$emailSubject = $request->trimValueOf('item_email_subject');		// メールタイトル
		$emailReceiver = trim($request->valueOf('item_email_receiver'));	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
		$sendUserEmail = ($request->trimValueOf('item_send_user_email') == 'on') ? 1 : 0;	// 入力ユーザ向けメールを送信するかどうか
		$userEmailReply = $request->trimValueOf('item_user_email_reply');					// 入力ユーザ向けメール返信先メールアドレス
		$userEmailSubject = $request->trimValueOf('item_user_email_subject');				// 入力ユーザ向けメールタイトル
		$userEmailFormat = $request->trimValueOf('item_user_email_format');					// 入力ユーザ向けメール本文フォーマット
		$useArtisteer = ($request->trimValueOf('item_use_artisteer') == 'on') ? 1 : 0;					// Artisteer対応デザイン
		
		// 入力データを取得
		$this->fieldInfoArray = array();
		for ($i = 0; $i < $fieldCount; $i++){
			$newInfoObj = new stdClass;
			$newInfoObj->title	= $titles[$i];
			$newInfoObj->desc	= $descs[$i];
			$newInfoObj->type	= $types[$i];
			$newInfoObj->def	= $defs[$i];
			$newInfoObj->required	= $requireds[$i];
			$newInfoObj->disabled	= $disableds[$i];
			$newInfoObj->titleVisible	= $titleVisibles[$i];
			$newInfoObj->alphabet	= $alphabets[$i];
			$newInfoObj->number		= $numbers[$i];
			$newInfoObj->default	= $defaults[$i];
			$newInfoObj->fieldId	= $fieldIds[$i];
			$newInfoObj->calc		= $calcs[$i];
			$this->fieldInfoArray[] = $newInfoObj;
		}
				
		// Pタグを除去
		$baseTemplate = $this->gInstance->getTextConvManager()->deleteTag($baseTemplate, 'p');
		
		$replaceNew = false;		// データを再取得するかどうか
		if ($act == 'add'){// 新規追加
			// 入力値のエラーチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (empty($titles[$i])){
					$this->setUserErrorMsg('タイトルが入力されていません');
					break;
				}
			}
			// フィールドIDのチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (!empty($fieldIds[$i])){
					if (preg_match("/[^a-z]/", $fieldIds[$i])){
						$this->setUserErrorMsg('フィールドIDは英小文字が使用可能です');
						break;
					}
				}
			}
			// 計算式のチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (!empty($calcs[$i])){
					if (preg_match("/[^0-9a-z-\+\*\/()]/", $calcs[$i])){
						$this->setUserErrorMsg('計算式はフィールドID、演算子「+-*/」、括弧「()」、数値が使用可能です');
						break;
					}
				}
			}
								
			// 設定名の重複チェック
			for ($i = 0; $i < count($this->paramObj); $i++){
				$targetObj = $this->paramObj[$i]->object;
				if ($name == $targetObj->name){		// 定義名
					$this->setUserErrorMsg('名前が重複しています');
					break;
				}
			}
			
			// 確認メール用の設定のチェック
			if (!empty($sendUserEmail)){
				$this->checkInput($userEmailSubject, '確認メール件名');
				$this->checkInput($userEmailFormat, '確認メール本文');
			}

			// エラーなしの場合は、データを登録
			if ($this->getMsgCount() == 0){
				// 追加オブジェクト作成
				$newObj = new stdClass;
				$newObj->name		= $name;// 表示名
				$newObj->pageTitle = $pageTitle;			// 画面タイトル
				$newObj->baseTemplate = $baseTemplate;		// 入力エリア作成用ベーステンプレート
				$newObj->css	= $this->css;					// 入力エリア用CSS
				$newObj->confirmButtonId = $this->confirmButtonId;		// 確認ボタンのタグID
				$newObj->sendButtonId	= $this->sendButtonId;		// 送信ボタンのタグID
				$newObj->cancelButtonId	= $this->cancelButtonId;		// 送信キャンセルボタンのタグID
				$newObj->resetButtonId	= $this->resetButtonId;		// エリアリセットボタンのタグID
				$newObj->emailSubject = $emailSubject;		// メールタイトル
				$newObj->emailReceiver = $emailReceiver;	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
				$newObj->sendUserEmail = $sendUserEmail;	// 入力ユーザ向けメールを送信するかどうか
				$newObj->userEmailReply = $userEmailReply;					// 入力ユーザ向けメール返信先メールアドレス
				$newObj->userEmailSubject = $userEmailSubject;				// 入力ユーザ向けメールタイトル
				$newObj->userEmailFormat = $userEmailFormat;				// 入力ユーザ向けメール本文フォーマット
				$newObj->useArtisteer = $useArtisteer;					// Artisteer対応デザイン
				$newObj->fieldInfo	= $this->fieldInfoArray;		// フィールド定義
				
				$ret = $this->addPageDefParam($defSerial, $defConfigId, $this->paramObj, $newObj);
				if ($ret){
					$this->setGuidanceMsg('データを追加しました');
					
					$this->configId = $defConfigId;		// 定義定義IDを更新
					$replaceNew = true;			// データ再取得
				} else {
					$this->setAppErrorMsg('データ追加に失敗しました');
				}
			}
		} else if ($act == 'update'){		// 設定更新のとき
			// 入力値のエラーチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (empty($titles[$i])){
					$this->setUserErrorMsg('タイトルが入力されていません');
					break;
				}
			}
			// フィールドIDのチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (!empty($fieldIds[$i])){
					if (preg_match("/[^a-z]/", $fieldIds[$i])){
						$this->setUserErrorMsg('フィールドIDは英小文字が使用可能です');
						break;
					}
				}
			}
			// 計算式のチェック
			for ($i = 0; $i < $fieldCount; $i++){
				if (!empty($calcs[$i])){
					if (preg_match("/[^0-9a-z-\+\*\/()]/", $calcs[$i])){
						$this->setUserErrorMsg('計算式はフィールドID、演算子「+-*/」、括弧「()」、数値が使用可能です');
						break;
					}
				}
			}
			
			// 確認メール用の設定のチェック
			if (!empty($sendUserEmail)){
				$this->checkInput($userEmailSubject, '確認メール件名');
				$this->checkInput($userEmailFormat, '確認メール本文');
			}

			if ($this->getMsgCount() == 0){			// エラーのないとき
				// 現在の設定値を取得
				$ret = $this->getPageDefParam($defSerial, $defConfigId, $this->paramObj, $this->configId, $targetObj);
				if ($ret){
					// ウィジェットオブジェクト更新
					$targetObj->pageTitle = $pageTitle;			// 画面タイトル
					$targetObj->baseTemplate = $baseTemplate;		// 入力エリア作成用ベーステンプレート
					$targetObj->css			= $this->css;					// 入力エリア作成用CSS
					$targetObj->confirmButtonId = $this->confirmButtonId;		// 確認ボタンのタグID
					$targetObj->sendButtonId	= $this->sendButtonId;		// 送信ボタンのタグID
					$targetObj->cancelButtonId	= $this->cancelButtonId;		// 送信キャンセルボタンのタグID
					$targetObj->resetButtonId	= $this->resetButtonId;		// エリアリセットボタンのタグID
					$targetObj->emailSubject = $emailSubject;		// メールタイトル
					$targetObj->emailReceiver = $emailReceiver;	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
					$targetObj->sendUserEmail = $sendUserEmail;	// 入力ユーザ向けメールを送信するかどうか
					$targetObj->userEmailSubject = $userEmailSubject;				// 入力ユーザ向けメールタイトル
					$targetObj->userEmailReply = $userEmailReply;					// 入力ユーザ向けメール返信先メールアドレス
					$targetObj->userEmailFormat = $userEmailFormat;				// 入力ユーザ向けメール本文フォーマット
					$targetObj->useArtisteer = $useArtisteer;					// Artisteer対応デザイン
					$targetObj->fieldInfo	= $this->fieldInfoArray;		// フィールド定義
				}
				
				// 設定値を更新
				if ($ret) $ret = $this->updatePageDefParam($defSerial, $defConfigId, $this->paramObj, $this->configId, $targetObj);
				if ($ret){
					$this->setMsg(self::MSG_GUIDANCE, 'データを更新しました');
					$replaceNew = true;			// データ再取得
				} else {
					$this->setMsg(self::MSG_APP_ERR, 'データ更新に失敗しました');
				}
			}
		} else if ($act == 'select'){	// 定義IDを変更
			$replaceNew = true;			// データ再取得
		} else {	// 初期起動時、または上記以外の場合
			// デフォルト値設定
			$this->configId = $defConfigId;		// 呼び出しウィンドウから引き継いだ定義ID
			$replaceNew = true;			// データ再取得
		}
		// 設定項目選択メニュー作成
		$this->createItemMenu();
				
		// 表示用データを取得
		if (empty($this->configId)){		// 新規登録の場合
			$this->tmpl->setAttribute('item_name_visible', 'visibility', 'visible');// 名前入力フィールド表示
			if ($replaceNew){		// データ再取得時
				$name = $this->createDefaultName();			// デフォルト登録項目名
				
				$pageTitle = '';			// 画面タイトル
				$this->css = $this->getParsedTemplateData('default.tmpl.css', array($this, 'makeCss'));// デフォルト用のCSSを取得
				$emailSubject = '';		// メールタイトル
				$emailReceiver = '';	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
				$sendUserEmail = 0;	// 入力ユーザ向けメールを送信するかどうか
				$userEmailReply = '';					// 入力ユーザ向けメール返信先メールアドレス
				$userEmailSubject = self::DEFAULT_USER_EMAIL_SUBJECT;				// 入力ユーザ向けメールタイトル
				$userEmailFormat = self::DEFAULT_USER_EMAIL_FORMAT;				// 入力ユーザ向けメール本文フォーマット
				$useArtisteer = 0;					// Artisteer対応デザイン
				$this->fieldInfoArray = array();			// お問い合わせ項目情報
				
				// デフォルトのテンプレート作成
				$tagHead = $this->createTagIdHead();
				$this->confirmButtonId = $tagHead . '_confirm';		// 確認ボタンのタグID
				$this->sendButtonId = $tagHead . '_send';		// 送信用ボタンのタグID
				$this->cancelButtonId	= $tagHead . '_cancel';		// 送信キャンセルボタンのタグID
				$this->resetButtonId = $tagHead . '_reset';		// エリアリセットボタンのタグID
				$baseTemplate = $this->getParsedTemplateData('default.tmpl.html', array($this, 'makeBaseTemplate'));// デフォルトの入力エリア作成用ベーステンプレート
			}
			$this->serialNo = 0;
		} else {
			if ($replaceNew){// データ再取得時
				$ret = $this->getPageDefParam($defSerial, $defConfigId, $this->paramObj, $this->configId, $targetObj);
				if ($ret){
					$name	= $targetObj->name;// 名前
					$pageTitle = $targetObj->pageTitle;			// 画面タイトル
					$baseTemplate = $targetObj->baseTemplate;		// 入力エリア作成用ベーステンプレート
					$this->css		= $targetObj->css;					// 入力エリア作成用CSS
					$this->confirmButtonId = $targetObj->confirmButtonId;		// 確認ボタンのタグID
					$this->sendButtonId = $targetObj->sendButtonId;		// 送信ボタンのタグID
					$this->cancelButtonId = $targetObj->cancelButtonId;		// 送信キャンセルボタンのタグID
					$this->resetButtonId = $targetObj->resetButtonId;		// エリアリセットボタンのタグID
					$emailSubject = $targetObj->emailSubject;		// メールタイトル
					$emailReceiver = $targetObj->emailReceiver;	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
					$sendUserEmail = $targetObj->sendUserEmail;	// 入力ユーザ向けメールを送信するかどうか
					$userEmailReply = $targetObj->userEmailReply;					// 入力ユーザ向けメール返信先メールアドレス
					$userEmailSubject = $targetObj->userEmailSubject;				// 入力ユーザ向けメールタイトル
					$userEmailFormat = $targetObj->userEmailFormat;				// 入力ユーザ向けメール本文フォーマット
					$useArtisteer = $targetObj->useArtisteer;					// Artisteer対応デザイン
					if (!empty($targetObj->fieldInfo)) $this->fieldInfoArray = $targetObj->fieldInfo;			// お問い合わせ項目情報
				}
			}
			$this->serialNo = $this->configId;
				
			// 新規作成でないときは、メニューを変更不可にする(画面作成から呼ばれている場合のみ)
			if (!empty($defConfigId) && !empty($defSerial)) $this->tmpl->addVar("_widget", "id_disabled", 'disabled');
		}
				
		// 追加用タイプメニュー作成
		$this->createTypeMenu1();
		
		// お問い合わせ項目一覧作成
		$this->createFieldList();
		if (empty($this->fieldInfoArray)) $this->tmpl->setAttribute('field_list', 'visibility', 'hidden');// お問い合わせ項目情報一覧
		
		// 画面にデータを埋め込む
		if (!empty($this->configId)) $this->tmpl->addVar("_widget", "id", $this->configId);		// 定義ID
		$this->tmpl->addVar("item_name_visible", "name",	$this->convertToDispString($name));
		$this->tmpl->addVar("_widget", "page_title",	$this->convertToDispString($pageTitle));			// 画面タイトル
		$this->tmpl->addVar("_widget", "html",	$baseTemplate);		// 入力エリア作成用ベーステンプレート
		$this->tmpl->addVar("_widget", "css",	$this->css);		// 入力エリア作成用CSS
		$this->tmpl->addVar("_widget", "confirm_button",	$this->confirmButtonId);		// 確認ボタンのタグID
		$this->tmpl->addVar("_widget", "send_button",	$this->sendButtonId);		// 送信ボタンのタグID
		$this->tmpl->addVar("_widget", "cancel_button",	$this->cancelButtonId);		// 送信キャンセルボタンのタグID
		$this->tmpl->addVar("_widget", "reset_button",	$this->resetButtonId);		// エリアリセットボタンのタグID
		$tagStr = $this->confirmButtonId . '(確認ボタンのID), ' . $this->sendButtonId . '(送信ボタンのID), ' . 
						$this->cancelButtonId . '(送信キャンセルボタンのID), ' . $this->resetButtonId . '(リセットボタンのID)';
		$this->tmpl->addVar("_widget", "tag_id_str", $tagStr);// タグIDの表示
		$this->tmpl->addVar("_widget", "email_subject",	$emailSubject);		// メールタイトル
		$this->tmpl->addVar("_widget", "email_receiver",	$emailReceiver);	// メール受信者(aaaa<xxx@xxx.xxx>形式が可能)
		$visibleStr = '';
		if (!empty($sendUserEmail)) $visibleStr = 'checked';	
		$this->tmpl->addVar("_widget", "send_user_email", $visibleStr);						// 入力ユーザ向けメールを送信するかどうか
		$this->tmpl->addVar("_widget", "user_email_reply",	$userEmailReply);		// 入力ユーザ向けメール返信先メールアドレス
		$this->tmpl->addVar("_widget", "user_email_subject",	$userEmailSubject);		// 入力ユーザ向けメールタイトル
		$this->tmpl->addVar("_widget", "user_email_format",	$userEmailFormat);		// 入力ユーザ向けメール本文フォーマット
		$checked = '';
		if (!empty($useArtisteer)) $checked = 'checked'; 					
		$this->tmpl->addVar("_widget", "use_artisteer",	$checked);// Artisteer対応デザイン
		$this->tmpl->addVar("_widget", "serial", $this->serialNo);// 選択中のシリアル番号、IDを設定
		$this->tmpl->addVar('_widget', 'tag_start', M3_TAG_START . M3_TAG_MACRO_ITEM_KEY);		// 置換タグ(前)
		$this->tmpl->addVar('_widget', 'tag_end', M3_TAG_END);		// 置換タグ(後)
		
		// ボタンの表示制御
		if (empty($this->serialNo)){		// 新規追加項目を選択しているとき
			$this->tmpl->setAttribute('add_button', 'visibility', 'visible');// 「新規追加」ボタン
			
			// プレビューボタン作成
			$this->tmpl->addVar("_widget", "preview_disabled", 'disabled ');// 「プレビュー」ボタン
		} else {
			$this->tmpl->setAttribute('update_button', 'visibility', 'visible');// 「更新」ボタン
			
			// このウィジェットがマップされているページサブIDを取得
			$subPageId = $this->gPage->getPageSubIdByWidget($this->gEnv->getDefaultPageId(), $this->gEnv->getCurrentWidgetId(), $defConfigId);
			$previewUrl = $this->gEnv->getDefaultUrl();
			if (!empty($subPageId)) $previewUrl .= '?sub=' . $subPageId;
			$this->tmpl->addVar("_widget", "preview_url", $this->getUrl($previewUrl));
			
			// ヘルプの追加
			$this->convertHelp('update_button');
		}
		
		// ページ定義IDとページ定義のレコードシリアル番号を更新
		$this->endPageDefParam($defSerial, $defConfigId, $this->paramObj);
	}
	/**
	 * 選択用メニューを作成
	 *
	 * @return なし						
	 */
	function createItemMenu()
	{
		for ($i = 0; $i < count($this->paramObj); $i++){
			$id = $this->paramObj[$i]->id;// 定義ID
			$targetObj = $this->paramObj[$i]->object;
			$name = $targetObj->name;// 定義名
			$selected = '';
			if ($this->configId == $id) $selected = 'selected';

			$row = array(
				'name' => $name,		// 名前
				'value' => $id,		// 定義ID
				'selected' => $selected	// 選択中の項目かどうか
			);
			$this->tmpl->addVars('title_list', $row);
			$this->tmpl->parseTemplate('title_list', 'a');
		}
	}
	/**
	 * お問い合わせ項目一覧を作成
	 *
	 * @return なし						
	 */
	function createFieldList()
	{
		$fieldCount = count($this->fieldInfoArray);
		for ($i = 0; $i < $fieldCount; $i++){
			$infoObj = $this->fieldInfoArray[$i];
			$title = $infoObj->title;// タイトル名
			$desc = $infoObj->desc;		// 説明
			$type = $infoObj->type;		// 項目タイプ
			$def = $infoObj->def;		// 項目定義
			$requiredCheck = '';
			if (!empty($infoObj->required)) $requiredCheck = 'checked';
			$disabledCheck = '';							// 編集不可
			if (!empty($infoObj->disabled)) $disabledCheck = 'checked';
			$titleVisibleCheck = '';
			if (!empty($infoObj->titleVisible)) $titleVisibleCheck = 'checked';
			$alphabetCheck = '';
			if (!empty($infoObj->alphabet)) $alphabetCheck = 'checked';
			$numberCheck = '';
			if (!empty($infoObj->number)) $numberCheck = 'checked';
			$default	= $infoObj->default;		// デフォルト値
			$fieldId	= $infoObj->fieldId;		// フィールドID
			$calc		= $infoObj->calc;			// 計算式
			
			// 行を作成
			$this->tmpl->clearTemplate('type_list2');
			
			for ($j = 0; $j < count($this->typeArray); $j++){
				$value = $this->typeArray[$j]['value'];
				$name = $this->typeArray[$j]['name'];

				$selected = '';
				if ($value == $type) $selected = 'selected';

				$tableLine = array(
					'value'    => $value,			// タイプ値
					'name'     => $this->convertToDispString($name),			// タイプ名
					'selected' => $selected			// 選択中かどうか
				);
				$this->tmpl->addVars('type_list2', $tableLine);
				$this->tmpl->parseTemplate('type_list2', 'a');
			}
			
			$row = array(
				'title' => $this->convertToDispString($title),	// タイトル名
				'desc' => $this->convertToDispString($desc),	// 説明
				'def' => $this->convertToDispString($def),		// 定義情報
				'required' => $requiredCheck,							// 必須入力
				'disabled' => $disabledCheck,							// 編集不可
				'title_visible' => $titleVisibleCheck,			// タイトル表示制御
				'alphabet' => $alphabetCheck,			// 入力制限半角英字
				'number' => $numberCheck,			// 入力制限半角数値
				'default' => $this->convertToDispString($default),	// デフォルト値
				'field_id' => $this->convertToDispString($fieldId),		// フィールドID
				'calc' => $this->convertToDispString($calc),			// 計算式
				'root_url' => $this->convertToDispString($this->getUrl($this->gEnv->getRootUrl()))
			);
			$this->tmpl->addVars('field_list', $row);
			$this->tmpl->parseTemplate('field_list', 'a');
		}
	}
	/**
	 * デフォルトの名前を取得
	 *
	 * @return string	デフォルト名						
	 */
	function createDefaultName()
	{
		$name = self::DEFAULT_NAME_HEAD;
		for ($j = 1; $j < 100; $j++){
			$name = self::DEFAULT_NAME_HEAD . $j;
			// 設定名の重複チェック
			for ($i = 0; $i < count($this->paramObj); $i++){
				$targetObj = $this->paramObj[$i]->object;
				if ($name == $targetObj->name){		// 定義名
					break;
				}
			}
			// 重複なしのときは終了
			if ($i == count($this->paramObj)) break;
		}
		return $name;
	}
	/**
	 * 一覧画面作成
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createList($request)
	{
		// ページ定義IDとページ定義のレコードシリアル番号を取得
		$this->startPageDefParam($defSerial, $defConfigId, $this->paramObj);
		
		$userId		= $this->gEnv->getCurrentUserId();
		$langId	= $this->gEnv->getCurrentLanguage();		// 表示言語を取得
		$act = $request->trimValueOf('act');
		
		if ($act == 'delete'){		// メニュー項目の削除
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
				$ret = $this->delPageDefParam($defSerial, $defConfigId, $this->paramObj, $delItems);
				if ($ret){		// データ削除成功のとき
					$this->setGuidanceMsg('データを削除しました');
				} else {
					$this->setAppErrorMsg('データ削除に失敗しました');
				}
			}
		}
		// 定義一覧作成
		$this->createItemList();
		
		$this->tmpl->addVar("_widget", "serial_list", implode($this->serialArray, ','));// 表示項目のシリアル番号を設定
		
		// ページ定義IDとページ定義のレコードシリアル番号を更新
		$this->endPageDefParam($defSerial, $defConfigId, $this->paramObj);
	}
	/**
	 * 定義一覧作成
	 *
	 * @return なし						
	 */
	function createItemList()
	{
		for ($i = 0; $i < count($this->paramObj); $i++){
			$id			= $this->paramObj[$i]->id;// 定義ID
			$targetObj	= $this->paramObj[$i]->object;
			$name = $targetObj->name;// 定義名
			$emailReceiver	= $targetObj->emailReceiver;		// 受信メールアドレス
		
			// 使用数
			$defCount = 0;
			if (!empty($id)){
				$defCount = $this->sysDb->getPageDefCount($this->gEnv->getCurrentWidgetId(), $id);
			}
			$operationDisagled = '';
			if ($defCount > 0) $operationDisagled = 'disabled';
			
			$row = array(
				'index' => $i,
				'id' => $id,
				'ope_disabled' => $operationDisagled,			// 選択可能かどうか
				'name' => $this->convertToDispString($name),		// 名前
				'email_receiver' => $this->convertToDispString($emailReceiver),	// 受信メールアドレス
				'def_count' => $defCount							// 使用数
			);
			$this->tmpl->addVars('itemlist', $row);
			$this->tmpl->parseTemplate('itemlist', 'a');
			
			// シリアル番号を保存
			$this->serialArray[] = $id;
		}
	}
	/**
	 * タイプ選択メニュー作成
	 *
	 * @return なし
	 */
	function createTypeMenu1()
	{
		for ($i = 0; $i < count($this->typeArray); $i++){
			$value = $this->typeArray[$i]['value'];
			$name = $this->typeArray[$i]['name'];
			
			$row = array(
				'value'    => $value,			// タイプ値
				'name'     => $this->convertToDispString($name),			// タイプ名
				'selected' => $selected			// 選択中かどうか
			);
			$this->tmpl->addVars('type_list1', $row);
			$this->tmpl->parseTemplate('type_list1', 'a');
		}
	}
	/**
	 * テンプレートデータ作成処理コールバック
	 *
	 * @param object         $tmpl			テンプレートオブジェクト
	 * @param								なし
	 */
	function makeBaseTemplate($tmpl)
	{
		$tmpl->addVar("_tmpl", "widget_url",	$this->gEnv->getCurrentWidgetRootUrl());		// ウィジェットのURL
		$tmpl->addVar("_tmpl", "confirm_button_id",	$this->confirmButtonId);	// 確認用ボタンのタグID
		$tmpl->addVar("_tmpl", "send_button_id",	$this->sendButtonId);		// 送信用ボタンのタグID
		$tmpl->addVar("_tmpl", "cancel_button_id",	$this->cancelButtonId);		// 送信キャンセル用ボタンのタグID
		$tmpl->addVar("_tmpl", "reset_button_id",	$this->resetButtonId);		// エリアリセットボタンのタグID
	}
	/**
	 * CSSデータ作成処理コールバック
	 *
	 * @param object         $tmpl			テンプレートオブジェクト
	 * @param								なし
	 */
	function makeCss($tmpl)
	{
	}
	/**
	 * inputタグID用のヘッダ文字列を作成
	 *
	 * @return string	ID
	 */
	function createTagIdHead()
	{
		return $this->gEnv->getCurrentWidgetId() . '_' . $this->getTempConfigId($this->paramObj);
	}
}
?>
