<?php
/**
 * index.php用コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    フリーレイアウトお問い合わせ
 * @author     株式会社 毎日メディアサービス
 * @copyright  Copyright 2009-2013 株式会社 毎日メディアサービス.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id$
 * @link       http://www.m-media.co.jp
 */
require_once($gEnvManager->getContainerPath() . '/baseWidgetContainer.php');

class contactus_freelayout3WidgetContainer extends BaseWidgetContainer
{
	private $fieldInfoArray = array();			// お問い合わせ項目情報
	private $valueArray;		// 項目入力値
	private $css;
	private $addScript;			// 追加スクリプト
	private $calcScript;		// 計算処理用スクリプト
	private $recalcScript;		// 計算処理用スクリプト
	private $confirmButtonId;		// 確認ボタンのタグID
	private $sendButtonId;		// 送信ボタンのタグID
	private $cancelButtonId;		// 送信キャンセルボタンのタグID
	private $resetButtonId;		// エリアリセットボタンのタグID
	private $useArtisteer;					// Artisteer対応デザイン
	private $pageTitle;			// 画面タイトル
	const DEFAULT_CONFIG_ID = 0;
	const CONTACTUS_FORM = 'contact_us';		// お問い合わせフォーム
	const DEFAULT_SEND_MESSAGE = 1;		// メール送信機能を使用するかどうか(デフォルト使用)
	const DEFAULT_TITLE_NAME = 'お問い合わせ';	// デフォルトのタイトル名
	const DEFAULT_STR_REQUIRED = '<font color="red">*必須</font>';		// 「必須」表示用テキスト
	const FIELD_HEAD = 'item';			// フィールド名の先頭文字列
	const LIST_MARK = '●';				// メール本文のフィールドタイトル用マーク
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
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
		// 定義ID取得
		$configId = $this->gEnv->getCurrentWidgetConfigId();
		if (empty($configId)) $configId = self::DEFAULT_CONFIG_ID;
	
		// パラメータオブジェクトを取得
		$targetObj = $this->getWidgetParamObjByConfigId($configId);
		if (empty($targetObj)){// 定義データが取得できないときは終了
			$this->cancelParse();		// テンプレート変換処理中断
			return;
		}
		
		// デフォルト値設定
		$inputEnabled = true;			// 入力の許可状態
		$now = date("Y/m/d H:i:s");	// 現在日時
		$this->langId	= $this->gEnv->getCurrentLanguage();		// 表示言語を取得
		$sendMessage = self::DEFAULT_SEND_MESSAGE;			// メール送信機能を使用するかどうか
		
		//$sendMessage = $targetObj->sendMessage;			// メール送信機能を使用するかどうか
		$emailReceiver = $targetObj->emailReceiver;		// メール受信者
		$emailSubject = $targetObj->emailSubject;		// メール件名
		$sendUserEmail = $targetObj->sendUserEmail;	// 入力ユーザ向けメールを送信するかどうか
		$userEmailReply = $targetObj->userEmailReply;					// 入力ユーザ向けメール返信先メールアドレス
		$userEmailSubject = $targetObj->userEmailSubject;				// 入力ユーザ向けメールタイトル
		$userEmailFormat = $targetObj->userEmailFormat;				// 入力ユーザ向けメール本文フォーマット
		$this->useArtisteer = $targetObj->useArtisteer;					// Artisteer対応デザイン
		$baseTemplate = $targetObj->baseTemplate;		// 入力エリア作成用ベーステンプレート
		$this->css		= $targetObj->css;		// CSS
		$this->confirmButtonId = $targetObj->confirmButtonId;		// 確認ボタンのタグID
		$this->sendButtonId = $targetObj->sendButtonId;		// 送信ボタンのタグID
		$this->cancelButtonId = $targetObj->cancelButtonId;		// 送信キャンセルボタンのタグID
		$this->resetButtonId = $targetObj->resetButtonId;		// エリアリセットボタンのタグID
		$sendFormId = $this->gEnv->getCurrentWidgetId() . '_' . $configId . '_form';		// 送信フォームのタグID
		$name	= $targetObj->name;// 名前
		$this->pageTitle = $targetObj->pageTitle;			// 画面タイトル
		if (empty($this->pageTitle)) $this->pageTitle = self::DEFAULT_TITLE_NAME;			// 画面タイトル
		if (!empty($targetObj->fieldInfo)) $this->fieldInfoArray = $targetObj->fieldInfo;			// お問い合わせフィールド情報
		
		// 入力値を取得
		$this->valueArray = array();
		$fieldCount = count($this->fieldInfoArray);
		for ($i = 0; $i < $fieldCount; $i++){
			$itemName = self::FIELD_HEAD . ($i + 1);
			$itemValue = $request->trimValueOf($itemName);
			$this->valueArray[] = $itemValue;
		}
		$sendStatus = intval($request->trimValueOf('sendstatus'));			// 送信ステータス
		if ($sendStatus < 0 || 2 < $sendStatus) $sendStatus = 0;
		$postTicket = $request->trimValueOf('ticket');		// POST確認用
		$act = $request->trimValueOf('act');
		
		if ($act == 'confirm' && $sendStatus == 0){				// 送信確認
			if (!empty($postTicket) && $postTicket == $request->getSessionValue(M3_SESSION_POST_TICKET)){		// 正常なPOST値のとき
				// 入力状況のチェック
				$isFirstUserEmail = false;		// 最初のEメールアドレスかどうか
				$userEmail = '';
				for ($i = 0; $i < $fieldCount; $i++){
					$infoObj = $this->fieldInfoArray[$i];
					$title = $infoObj->title;// タイトル名
					$type = $infoObj->type;		// 項目タイプ
					$required = $infoObj->required;		// 必須入力
					$def = $infoObj->def;		// 項目定義
					
					// 必須チェック
					if (!empty($required) && empty($this->valueArray[$i])){
						$this->setUserErrorMsg('「' . $title . '」は必須入力項目です');
					} else {
						// データタイプチェック
						switch ($type){
							case 'email':			// Eメール形式
								$ret = $this->checkMailAddress($this->valueArray[$i], '「' . $title . '」', true/*入力なしOK*/);
								
								// 確認用のEメールフィールドの場合は、値が同じかチェック
								if ($ret && !empty($def)){
									$refNo = 0;
									$defArray = explode(';', $def);
									for ($j = 0; $j < count($defArray); $j++){
										list($key, $value) = explode('=', $defArray[$j]);
										$key = trim($key);
										$value = trim($value);
										if (strcasecmp($key, 'ref') == 0){
											$refNo = intval($value);
											if ($refNo <= $fieldCount){			// 参照先の値を取得
												$refNo--;
												$refValue = $this->valueArray[$refNo];
												$refTitle = $this->fieldInfoArray[$refNo]->title;
												if ($this->valueArray[$i] != $refValue) $this->setUserErrorMsg('「' . $title . '」が「' . $refTitle . '」の内容と一致しません');
											}
											break;
										}
									}
								}
								break;
						}
					}
					
					// 確認メール送信用のEメールアドレスを取得
					if (!$isFirstUserEmail && $type == 'email'){
						$userEmail = $this->valueArray[$i];
						$isFirstUserEmail = true;		// 最初のEメールアドレスを取得
					}
				}

				// エラーなしの場合は送信画面へ遷移
				if ($this->getMsgCount() == 0){
					$this->setGuidanceMsg('入力内容をご確認の上「送信」ボタンを押してください');
				
					// 項目を入力不可に設定
					$inputEnabled = false;			// 入力の許可状態

					// 送信ステータスを更新
					$sendStatus = 1;
				}
				// ハッシュキー作成
				$postTicket = md5(time() . $this->gAccess->getAccessLogSerialNo());
				$request->setSessionValue(M3_SESSION_POST_TICKET, $postTicket);		// セッションに保存
				$this->tmpl->addVar("_widget", "ticket", $postTicket);				// 画面に書き出し
			} else {		// ハッシュキーが異常のとき
				// 送信ステータスを初期化
				$sendStatus = 0;
				
				$request->unsetSessionValue(M3_SESSION_POST_TICKET);		// セッション値をクリア
			}
		} else if ($act == 'send' && $sendStatus == 1){		// お問い合わせメール送信
			if (!empty($postTicket) && $postTicket == $request->getSessionValue(M3_SESSION_POST_TICKET)){		// 正常なPOST値のとき
				// 入力状況のチェック
				$isFirstUserEmail = false;		// 最初のEメールアドレスかどうか
				$userEmail = '';
				for ($i = 0; $i < $fieldCount; $i++){
					$infoObj = $this->fieldInfoArray[$i];
					$title = $infoObj->title;// タイトル名
					$type = $infoObj->type;		// 項目タイプ
					$required = $infoObj->required;		// 必須入力
					
					// 必須チェック
					if (!empty($required) && empty($this->valueArray[$i])){
						$this->setUserErrorMsg('「' . $title . '」は必須入力項目です');
					} else {
						// データタイプチェック
						switch ($type){
							case 'email':			// Eメール形式
								$this->checkMailAddress($this->valueArray[$i], '「' . $title . '」', true/*入力なしOK*/);
								
								// 確認用のEメールフィールドの場合は、値が同じかチェック
								if ($ret && !empty($def)){
									$refNo = 0;
									$defArray = explode(';', $def);
									for ($j = 0; $j < count($defArray); $j++){
										list($key, $value) = explode('=', $defArray[$j]);
										$key = trim($key);
										$value = trim($value);
										if (strcasecmp($key, 'ref') == 0){
											$refNo = intval($value);
											if ($refNo <= $fieldCount){			// 参照先の値を取得
												$refNo--;
												$refValue = $this->valueArray[$refNo];
												$refTitle = $this->fieldInfoArray[$refNo]->title;
												if ($this->valueArray[$i] != $refValue) $this->setUserErrorMsg('「' . $title . '」が「' . $refTitle . '」の内容と一致しません');
											}
											break;
										}
									}
								}
								break;
						}
					}
					// 確認メール送信用のEメールアドレスを取得
					if (!$isFirstUserEmail && $type == 'email'){
						$userEmail = $this->valueArray[$i];
						$isFirstUserEmail = true;		// 最初のEメールアドレスを取得
					}
				}

				// エラーなしの場合はメール送信
				if ($this->getMsgCount() == 0){
					$this->setGuidanceMsg('送信完了しました');
				
					// メール送信設定のときはメールを送信
					if ($sendMessage){
						// メール本文の作成
						$mailParam = array();
						$mailBody = '';
						for ($i = 0; $i < $fieldCount; $i++){
							$infoObj = $this->fieldInfoArray[$i];
							$title = $infoObj->title;// タイトル名
							$type = $infoObj->type;		// 項目タイプ
						
							$mailBody .= self::LIST_MARK . $title . "\n";		// タイトル
							if (is_array($this->valueArray[$i])){		// 配列データのとき
								for ($j = 0; $j < count($this->valueArray[$i]); $j++){
									$mailBody .= $this->valueArray[$i][$j] . "\n";		// 入力値
								}
								$value = implode(',', $this->valueArray[$i]);
							} else {
								$mailBody .= $this->valueArray[$i] . "\n";			// 入力値
								$value = $this->valueArray[$i];
							}
							$mailBody .= "\n";
							
							// 個別変換パラメータ
							$mailParam[M3_TAG_MACRO_ITEM_KEY . ($i + 1)] = $value;
						}
						$mailParam['BODY'] = $mailBody;		// デフォルトの出力フォーマット
							
						// 送信元、送信先
						$fromAddress = $this->gEnv->getSiteEmail();	// 送信元はサイト情報のEメールアドレス
						$toAddress = $this->gEnv->getSiteEmail();		// デフォルトのサイト向けEメールアドレス
						if (!empty($emailReceiver)) $toAddress = $emailReceiver;		// 受信メールアドレスが設定されている場合

						// メールを送信
						if (empty($toAddress)){
							$this->gOpeLog->writeError(__METHOD__, 'メール送信に失敗しました。基本情報のEメールアドレスが設定されていません。', 1100, 'body=[' . $mailBody . ']');
						} else {
							$email = '';		// 返信先は空にする(暫定)
							$ret = $this->gInstance->getMailManager()->sendFormMail(2/*手動送信*/, $this->gEnv->getCurrentWidgetId(), 
																					$toAddress, $fromAddress, $email, $emailSubject, self::CONTACTUS_FORM, $mailParam);
							// お問い合わせ送信の場合は、確認メールを送信する
							if ($ret && !empty($sendUserEmail)){
								// 送信先を取得
								if (!empty($userEmail)){
									// 返信先メールアドレスが設定されている場合は設定メールアドレスを使用
									$ret = $this->gInstance->getMailManager()->sendFormMail(2/*手動送信*/, $this->gEnv->getCurrentWidgetId(), 
																$userEmail, $fromAddress, $userEmailReply, $userEmailSubject, ''/*フォーム内容を指定*/, $mailParam, ''/*cc*/, ''/*bcc*/, $userEmailFormat);
								}
							}
						}
					}
					// 項目を入力不可に設定
					$inputEnabled = false;			// 入力の許可状態

					// 送信ステータスを更新
					$sendStatus = 2;
					
					$request->unsetSessionValue(M3_SESSION_POST_TICKET);		// セッション値をクリア
				} else {		// 送信時入力エラーの場合は初期画面に戻す
					// 送信ステータスを更新
					$sendStatus = 0;
					
					// ハッシュキー作成
					$postTicket = md5(time() . $this->gAccess->getAccessLogSerialNo());
					$request->setSessionValue(M3_SESSION_POST_TICKET, $postTicket);		// セッションに保存
					$this->tmpl->addVar("_widget", "ticket", $postTicket);				// 画面に書き出し
				}
			} else {		// ハッシュキーが異常のとき
				// 送信ステータスを初期化
				$sendStatus = 0;
					
				$request->unsetSessionValue(M3_SESSION_POST_TICKET);		// セッション値をクリア
			}
		} else if ($act == 'cancel' && $sendStatus == 1){		// メール送信キャンセルの場合
			if (!empty($postTicket) && $postTicket == $request->getSessionValue(M3_SESSION_POST_TICKET)){		// 正常なPOST値のとき
				// 送信ステータスを更新
				$sendStatus = 0;

				// ハッシュキー作成
				$postTicket = md5(time() . $this->gAccess->getAccessLogSerialNo());
				$request->setSessionValue(M3_SESSION_POST_TICKET, $postTicket);		// セッションに保存
				$this->tmpl->addVar("_widget", "ticket", $postTicket);				// 画面に書き出し
			} else {		// ハッシュキーが異常のとき
				// 送信ステータスを初期化
				$sendStatus = 0;
				
				$request->unsetSessionValue(M3_SESSION_POST_TICKET);		// セッション値をクリア
			}
		} else {
			// 送信ステータスを初期化
			$sendStatus = 0;
				
			// ハッシュキー作成
			$postTicket = md5(time() . $this->gAccess->getAccessLogSerialNo());
			$request->setSessionValue(M3_SESSION_POST_TICKET, $postTicket);		// セッションに保存
			$this->tmpl->addVar("_widget", "ticket", $postTicket);				// 画面に書き出し
		}
		
		// HTMLサブタイトルを設定
		$this->gPage->setHeadSubTitle($this->pageTitle);

		// Artisteerデザイン用のスクリプト
		if ($this->useArtisteer) $this->tmpl->setAttribute('show_art', 'visibility', 'visible');
		
		// お問い合わせフィールド作成
		$this->addScript = '';			// 追加スクリプト
		$this->calcScript = '';		// 計算処理用スクリプト
		$this->recalcScript = '';			// 計算処理用スクリプト
		$fieldOutput = $this->createFieldOutput($baseTemplate, $inputEnabled);
		$this->tmpl->addVar("_widget", "field_output", $fieldOutput);// お問い合わせ入力項目データ
		$this->tmpl->addVar("_widget", "field_count", $fieldCount);// お問い合わせ項目数
		$this->tmpl->addVar("_widget", "add_script", $this->addScript);// 追加スクリプト
		$this->tmpl->addVar("_widget", "calc_script", $this->calcScript);// 計算処理用スクリプト
		$this->tmpl->addVar("_widget", "recalc_script", $this->recalcScript);// 計算処理用スクリプト
		
		// その他データの画面埋め込み
		$this->tmpl->addVar("_widget", "status",	$sendStatus);			// 送信ステータス
		$this->tmpl->addVar("_widget", "confirm_button_id",	$this->confirmButtonId);	// 確認用ボタンのタグID
		$this->tmpl->addVar("_widget", "send_button_id",	$this->sendButtonId);		// 送信ボタンのタグID
		$this->tmpl->addVar("_widget", "cancel_button_id",	$this->cancelButtonId);		// 送信キャンセルボタンのタグID
		$this->tmpl->addVar("_widget", "reset_button_id",	$this->resetButtonId);		// エリアリセットボタンのタグID
		$this->tmpl->addVar("_widget", "send_form_id",	$sendFormId);		// 送信フォームのタグID
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
		return $this->pageTitle;
	}
	/**
	 * CSSデータをHTMLヘッダ部に設定
	 *
	 * CSSデータをHTMLのheadタグ内に追加出力する。
	 * _assign()よりも後に実行される。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。
	 * @return string 						CSS文字列。出力しない場合は空文字列を設定。
	 */
	function _addCssToHead($request, &$param)
	{
		return $this->css;
	}
	/**
	 * お問い合わせフィールド作成
	 *
	 * @param string $templateData	テンプレートデータ
	 * @param bool $enabled			項目の入力許可状態
	 * @return string				フィールドデータ
	 */
	function createFieldOutput($templateData, $enabled)
	{
		$fieldOutput = $templateData;
		
		$fieldCount = count($this->fieldInfoArray);
		for ($i = 0; $i < $fieldCount; $i++){
			$infoObj = $this->fieldInfoArray[$i];
			$title = $infoObj->title;// タイトル名
			$desc = $infoObj->desc;		// 説明
			$type = $infoObj->type;		// 項目タイプ
			$def = $infoObj->def;		// 項目定義
			$required = '';
			if (!empty($infoObj->required)) $required = '&nbsp;' . self::DEFAULT_STR_REQUIRED;// 必須表示
			$disabled	= $infoObj->disabled;			// 編集不可
			$titleVisible = $infoObj->titleVisible;		// タイトルを表示するかどうか
			$alphabet	= $infoObj->alphabet;			// 入力制限半角英字
			$number		= $infoObj->number;				// 入力制限半角数値
			$default		= $infoObj->default;				// デフォルト値
			$fieldId	= $infoObj->fieldId;		// フィールドID
			$calc		= $infoObj->calc;			// 計算式
			
			// 入力フィールドの作成
			$fieldId = self::FIELD_HEAD . ($i + 1);
			$fieldName = $fieldId;
			$inputValue = $this->valueArray[$i];		// 入力値
			$inputTag = '';
			switch ($type){
				case 'text':		// テキストボックス
				case 'email':		// テキストボックス(Eメール)
				case 'calc':		// テキストボックス(計算)
					$param = array();
					$paramStr = '';
					
					// フィールド内メッセージ
					if ($type == 'text' || $type == 'email'){
						if (!empty($default)) $param[] = 'title="' . $this->convertToDispString($default) . '"';
					}
					
					// 入力の制御
					if ($type == 'email' || (!empty($alphabet) || !empty($number))) $param[] = 'style="ime-mode:disabled;"';
					if ($type == 'calc') $param[] = 'style="ime-mode:disabled;"';
					
					$size = 0;
					$defArray = explode(';', $def);
					for ($j = 0; $j < count($defArray); $j++){
						list($key, $value) = explode('=', $defArray[$j]);
						$key = trim($key);
						$value = trim($value);
						if (strcasecmp($key, 'size') == 0){
							$size = intval($value);
							break;
						}
					}
					if ($size > 0) $param[] = 'size="' . $size . '"';
					
					// 値の修正
					// 計算フィールドのときは値がなければデフォルト値を設定
					if ($type == 'calc'){
						if ($inputValue == '') $inputValue = $default;
					}
					
					if ($inputValue != ''){		// 0の場合あり
						$param[] = 'value="' . $this->convertToDispString($inputValue) . '"';
					}
					if ($enabled){		// 入力状態のとき
						if ($disabled) $param[] = 'readonly';		// 使用不可
					} else {
						$param[] = 'disabled';		// 使用不可
					}
					//if (!$enabled) $param[] = 'disabled';		// 使用不可
					if (count($param) > 0) $paramStr = ' ' . implode($param, ' ');
					
					if ($enabled){		// 入力状態のとき
						$inputTag = '<input type="text" id="' . $fieldId . '" name="' . $fieldName . '"' . $paramStr . ' />' . M3_NL;
					} else {
						$inputTag = '<input type="hidden" name="' . $fieldName . '" value="' . $this->convertToDispString($inputValue) . '" />' . '<input type="text"' . $paramStr . ' />' . M3_NL;
					}
					break;
				case 'textarea':	// テキストエリア
					$param = array();
					$paramStr = '';
					
					// フィールド内メッセージ
					if (!empty($default)) $param[] = 'title="' . $this->convertToDispString($default) . '"';
					
					// 入力の制御
					if (!empty($alphabet) || !empty($number)) $param[] = 'style="ime-mode:disabled;"';
					
					$row = 0;
					$col = 0;
					$defArray = explode(';', $def);
					for ($j = 0; $j < count($defArray); $j++){
						list($key, $value) = explode('=', $defArray[$j]);
						$key = trim($key);
						$value = trim($value);
						if (strcasecmp($key, 'rows') == 0){
							$row = intval($value);
						} else if (strcasecmp($key, 'cols') == 0){
							$col = intval($value);
						}
					}
					if ($row > 0) $param[] = 'rows="' . $row . '"';
					if ($col > 0) $param[] = 'cols="' . $col . '"';
					if ($enabled){		// 入力状態のとき
						if ($disabled) $param[] = 'readonly';		// 使用不可
					} else {
						$param[] = 'disabled';		// 使用不可
					}
					//if (!$enabled) $param[] = 'disabled';		// 使用不可
					if (count($param) > 0) $paramStr = ' ' . implode($param, ' ');
					
					if ($enabled){		// 入力状態のとき
						$inputTag = '<textarea id="' . $fieldId . '" name="' . $fieldName . '"' . $paramStr . '>' . $this->convertToDispString($inputValue) . '</textarea>' . M3_NL;
					} else {
						$inputTag = '<input type="hidden" name="' . $fieldName . '" value="' . $this->convertToDispString($inputValue) . '" />' . 
									'<textarea' . $paramStr . '>' . $this->convertToDispString($inputValue) . '</textarea>' . M3_NL;
					}
					break;
				case 'select':	// セレクトメニュー
					$param = array();
					$paramStr = '';
					
					// フィールド内メッセージ
					if (!empty($default)) $param[] = 'title="' . $this->convertToDispString($default) . '"';
					
					if (!$enabled) $param[] = 'disabled';		// 使用不可
					if (count($param) > 0) $paramStr = ' ' . implode($param, ' ');
					
					if ($enabled){		// 入力状態のとき
						$inputTag = '<select id="' . $fieldId . '" name="' . $fieldName . '"'. $paramStr . '>' . M3_NL;
					} else {
						$inputTag = '<input type="hidden" name="' . $fieldName . '" value="' . $this->convertToDispString($inputValue) . '" />' . 
									'<select'. $paramStr . '>' . M3_NL;
					}
					$inputTag .= '<option value="">&nbsp;</option>' . M3_NL;
					$defArray = explode(';', $def);
					for ($j = 0; $j < count($defArray); $j++){
						$param = array();
						$paramStr = '';
						list($key, $value) = explode('=', $defArray[$j]);
						$key = trim($key);
						$value = trim($value);
						//if (empty($value)) $value = $key;
						//if (!empty($key)){
						if ($value == '') $value = $key;
						if ($key != ''){
							//if (!empty($inputValue) && strcmp($inputValue, $value) == 0) $param[] = 'selected';
							if ($inputValue != '' && strcmp($inputValue, $value) == 0) $param[] = 'selected';
							if (count($param) > 0) $paramStr = ' ' . implode($param, ' ');
							$inputTag .= '<option value="' . $this->convertToDispString($value) . '"' . $paramStr . '>' . $this->convertToDispString($key) . '</option>' . M3_NL;
						}
					}
					$inputTag .= '</select>' . M3_NL;
					break;
				case 'checkbox':	// チェックボックス
				case 'radio':	// ラジオボタン
					if ($type == 'checkbox') $fieldName .= '[]';	// チェックボックス
					$defArray = explode(';', $def);
					for ($j = 0; $j < count($defArray); $j++){
						$param = array();
						$paramStr = '';
						list($key, $value) = explode('=', $defArray[$j]);
						$key = trim($key);
						$value = trim($value);
						$checked = false;		// チェックされているかどうか
						//if (empty($value)) $value = $key;
						//if (!empty($key) && !empty($value)){
						if ($value == '') $value = $key;
						if ($key != '' && $value != ''){
							if (is_array($inputValue)){
								for ($k = 0; $k < count($inputValue); $k++){
									if ($inputValue[$k] != '' && strcmp($inputValue[$k], $value) == 0){
										$param[] = 'checked';
										$checked = true;		// チェックされているかどうか
										break;
									}
								}
							} else {
								if ($inputValue != '' && strcmp($inputValue, $value) == 0){
									$param[] = 'checked';
									$checked = true;		// チェックされているかどうか
								}
							}
							if (!$enabled) $param[] = 'disabled';		// 使用不可
							if (count($param) > 0) $paramStr = ' ' . implode($param, ' ');
							
							if ($enabled){		// 入力状態のとき
								if (empty($this->useArtisteer)){				
								//	$inputTag .= '<input type="' . $type . '" id="' . $fieldId . '" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '"' . $paramStr . ' />' . $this->convertToDispString($key) . M3_NL;
									$inputTag .= '<input type="' . $type . '" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '"' . $paramStr . ' />' . $this->convertToDispString($key) . M3_NL;
								} else {			// Artisteer対応デザインのとき
									if ($type == 'checkbox'){
										$inputTag .= '<label class="art-checkbox">';
									} else {
										$inputTag .= '<label class="art-radiobutton">';
									}
									//$inputTag .= '<input type="' . $type . '" id="' . $fieldId . '" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '"' . $paramStr . ' />' . $this->convertToDispString($key) . '</label>' .M3_NL;
									$inputTag .= '<input type="' . $type . '" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '"' . $paramStr . ' />' . $this->convertToDispString($key) . '</label>' .M3_NL;
								}
							} else {
								if (empty($this->useArtisteer)){
									// チェック項目のみタグを作成
									if ($checked) $inputTag .= '<input type="hidden" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '" />';
									$inputTag .= '<input type="' . $type . '"' . $paramStr . ' />' . $this->convertToDispString($key) . M3_NL;
								} else {		// Artisteer対応デザインのとき
									// チェック項目のみタグを作成
									if ($checked) $inputTag .= '<input type="hidden" name="' . $fieldName . '" value="' . $this->convertToDispString($value) . '" />';
									
									if ($type == 'checkbox'){
										$inputTag .= '<label class="art-checkbox">';
									} else {
										$inputTag .= '<label class="art-radiobutton">';
									}
									$inputTag .= '<input type="' . $type . '"' . $paramStr . ' />' . $this->convertToDispString($key) . '</label>' . M3_NL;
								}
							}
						} else {		// 空項目のときは改行を追加
							$inputTag .= '<br />' . M3_NL;
						}
					}
					break;
			}

			// 改行の設定
			$descBr = '';
			if (!empty($desc)) $descBr = '<br />';
			
			// 項目データ作成
			$fieldData = '';
			if (!empty($titleVisible) && !empty($title)){		// タイトル表示のとき
				$fieldData .= '<strong>' . $this->convertToDispString($title) . '</strong>';// タイトル名
				$fieldData .= $required;			// 必須表示
				$fieldData .= '<br />';
			}
			$fieldData .= $this->convertToDispString($desc);				// 説明
			$fieldData .= $descBr;			// 説明改行
			$fieldData .= $inputTag;			// 入力フィールド
			
			// テンプレートに埋め込む
			$keyTag = M3_TAG_START . M3_TAG_MACRO_ITEM_KEY . ($i + 1) . M3_TAG_END;
			$fieldOutput = str_replace($keyTag, $fieldData, $fieldOutput);
			
			// 入力制限用のスクリプトを設定
			switch ($type){
				case 'text':		// テキストボックス
				case 'email':		// テキストボックス(Eメール)
				case 'textarea':	// テキストエリア
					$script = '';

					// お問い合わせ項目メッセージ
					if (!empty($default)) $script .= M3_TB . '$("#' . $fieldId . '").formtips({tippedClass:\'tipped\'});' . M3_NL;
										
					// 入力制限
					$inputType = array();
					$inputTypeStr = '';
					if (empty($alphabet) || empty($number)){
						if (!empty($alphabet)) $inputType[] = 'alphabet';			// 入力制限半角英字
						if (!empty($number)) $inputType[] = 'decimal';				// 入力制限半角数値
						if (!empty($inputType)) $inputTypeStr = implode($inputType, ',');
						if (!empty($inputTypeStr)) $script .= M3_TB . '$("#' . $fieldId . '").format({type:"' . $inputTypeStr . '", autofix:true});' . M3_NL;
					}
					$this->addScript .= $script;
					break;
				case 'calc':		// テキストボックス(計算)
					// 数値のみ入力可能
					$script = '';

					// お問い合わせ項目メッセージ
					//if (!empty($default)) $script .= M3_TB . '$("#' . $fieldId . '").formtips({tippedClass:\'tipped\'});' . M3_NL;
										
					// 入力制限
					$inputType = array();
					$inputTypeStr = '';
					$inputType[] = 'decimal';				// 入力制限半角数値
					$inputTypeStr = implode($inputType, ',');
					$script .= M3_TB . '$("#' . $fieldId . '").format({type:"' . $inputTypeStr . '", autofix:true});' . M3_NL;
					$this->addScript .= $script;
					
					// ##### 計算用スクリプト #####
					$this->recalcScript .= M3_TB . '$("#' . $fieldId . '").bind("keyup", field_recalc);' . M3_NL;
					
					if ($calc != ''){		// 計算式が入力されている場合は計算式を解析
						$targetFilelds = array();		// 計算に必要になるフィールドID
						$length = strlen($calc);
						for ($j = 0; $j < $length; $j++){
							$char = $calc[$j];
							if (ctype_lower($char) && !in_array($char, $targetFilelds)){
								$targetFilelds[] = $char;
							}
						}
						// 計算用スクリプトを作成
						$script = '';
						$script .= M3_TB . '$("#' . $fieldId . '").calc(' . M3_NL;
						$script .= str_repeat(M3_TB, 2) . '"' . $calc . '", ' . M3_NL;
						
						$targetFieldCount = count($targetFilelds);
						if ($targetFieldCount > 0){
							$script .= str_repeat(M3_TB, 2) . '{' . M3_NL;
							for ($j = 0; $j < $targetFieldCount; $j++){
								// 対象の項目IDを取得
								for ($k = 0; $k < $fieldCount; $k++){
									$searchInfoObj = $this->fieldInfoArray[$k];
									$searchFieldId	= $searchInfoObj->fieldId;		// フィールドID
									if ($targetFilelds[$j] == $searchFieldId){
										$targetFieldId = self::FIELD_HEAD . ($k + 1);		// 値取得対象の項目
										$script .= str_repeat(M3_TB, 3) . $targetFilelds[$j] . ':$("#' . $targetFieldId . '")';
										break;
									}
								}
								if ($j < $targetFieldCount -1){
									$script .= ',' . M3_NL;
								} else {
									$script .= M3_NL;
								}
							}
							$script .= str_repeat(M3_TB, 2) . '},' . M3_NL;
						}
						$script .= str_repeat(M3_TB, 2) . 'function(s){' . M3_NL;
						$script .= str_repeat(M3_TB, 3) . 'return s;' . M3_NL;
						$script .= str_repeat(M3_TB, 2) . '},' . M3_NL;
						$script .= str_repeat(M3_TB, 2) . 'function($this){' . M3_NL;
						$script .= str_repeat(M3_TB, 3) . 'return;' . M3_NL;
						$script .= str_repeat(M3_TB, 2) . '}' . M3_NL;
						$script .= M3_TB . ');' . M3_NL;
						$this->calcScript .= $script;
					}
					break;
			}
		}
		return $fieldOutput;
	}
}
?>
