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
 * @copyright  Copyright 2006-2013 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id$
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/admin_mainConfigbasicBaseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/admin_mainDb.php');

class admin_mainConfigsiteWidgetContainer extends admin_mainConfigbasicBaseWidgetContainer
{
	private $db;	// DB接続オブジェクト
	private $langId;		// 選択中の言語
	private $permitMimeType;			// アップロードを許可する画像タイプ
	private $isMultiLang;			// 多言語対応画面かどうか
	
	const TEST_MAIL_FORM = 'test';		// テストメールフォーム
	const CF_SITE_LOGO_FILENAME = 'site_logo_filename';		// サイトロゴファイル
	const SD_HEAD_OTHERS	= 'head_others';		// ヘッダその他タグ
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DB接続オブジェクト作成
		$this->db = new admin_mainDb();
		
		$this->permitMimeType = array(	image_type_to_mime_type(IMAGETYPE_GIF),
										image_type_to_mime_type(IMAGETYPE_JPEG),
										image_type_to_mime_type(IMAGETYPE_PNG),
										image_type_to_mime_type(IMAGETYPE_BMP));			// アップロードを許可する画像タイプ
										
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
		return 'configsite.tmpl.html';
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
		return 'configsite';
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
		// ユーザ情報、表示言語
		$userInfo		= $this->gEnv->getCurrentUserInfo();
		$this->langId		= $this->gEnv->getDefaultLanguage();
//		$langId		= $this->gEnv->getCurrentLanguage();		// 現在の言語ID
		$siteLogoFiles = explode(';', $this->db->getSystemConfig(self::CF_SITE_LOGO_FILENAME));		// サイトロゴファイル名
		
		// 言語を取得
		if ($this->isMultiLang){		// 多言語対応の場合
			$langId = $request->trimValueOf('item_lang');				// 現在メニューで選択中の言語
			if (!empty($langId)) $this->langId = $langId;
		}
		
		$act = $request->trimValueOf('act');
		if ($act == 'update'){		// 設定更新のとき
			$siteName	= $request->trimValueOf('sitename');		// サイト名称
			$siteEmail	= trim($request->valueOf('siteemail'));		// サイトEメール
			$siteSlogan	= $request->trimValueOf('siteslogan');		// サイトスローガン
			$siteCopyRight	= $request->trimValueOf('sitecopyright');		// サイト著作権
			
			$siteTitle = $request->trimValueOf('site_title');		// 画面タイトル
			if (empty($siteTitle)) $siteTitle = $siteName;			// タイトル名が設定されていないときはデフォルトでサイト名称を設定
			$siteDesc = $request->trimValueOf('site_description');		// サイト要約
			$siteKeyword = $request->trimValueOf('site_keyword');		// サイトキーワード
			$metaOthers		= $request->valueOf('meta_others');		// ヘッダその他タグ
			
			$isErr = false;
			if (!$isErr){		// サイト名
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_NAME, $siteName)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_EMAIL, $siteEmail)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_SLOGAN, $siteSlogan)) $isErr = true;// サイトスローガン
			}
			if (!$isErr){		// 著作権
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_COPYRIGHT, $siteCopyRight)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_TITLE, $siteTitle)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_DESCRIPTION, $siteDesc)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, M3_TB_FIELD_SITE_KEYWORDS, $siteKeyword)) $isErr = true;
			}
			if (!$isErr){
				if (!$this->db->updateSiteDef($this->langId, self::SD_HEAD_OTHERS, $metaOthers)) $isErr = true;
			}
			if ($isErr){
				$this->setMsg(self::MSG_APP_ERR, $this->_('Failed in updating data.'));			// データ更新に失敗しました
			} else {
				$this->setMsg(self::MSG_GUIDANCE, $this->_('Data updated.'));		// データを更新しました
			}
			// システムパラメータを更新
			//$this->gEnv->loadSystemParams();
			
			// 値を再取得
			//$this->langId		= $this->gEnv->getDefaultLanguage();
		} else if ($act == 'testmail'){		// テストメール送信のとき
			//$email = $this->gEnv->getSiteEmail(true);
			$email = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_EMAIL);
			if (empty($email)){
				$this->setAppErrorMsg($this->_('Input email address.'));		// メールアドレスが設定されていません
			} else {
				$emailParam = array();
				$emailParam['BODY']  = $this->_('URL   :') . ' ' . $this->gEnv->getRootUrl() . M3_NL;		// URL     :
				$emailParam['BODY'] .= $this->_('Date  :') . ' ' . date("Y年m月d日 H時i分s秒") . M3_NL;		// 送信日時:
				$emailParam['BODY'] .= $this->_('Sender:') . ' ' . $this->gEnv->getCurrentUserName() . M3_NL;	// 送信者  :
				$ret = $this->gInstance->getMailManager()->sendFormMail(2/*手動送信*/, $this->gEnv->getCurrentWidgetId(), $email, $email, '', '', self::TEST_MAIL_FORM, $emailParam);
				if ($ret){
					$this->setMsg(self::MSG_GUIDANCE, $this->_('Email sent. To:') . ' ' . $email);// メールを送信しました。メールアドレス:
				} else {
					$this->setMsg(self::MSG_APP_ERR, $this->_('Failed in sending email. To:') . ' ' . $email);			// メール送信に失敗しました。メールアドレス:
				}
			}
		} else if ($act == 'upload'){		// 画像アップロードのとき
			// アップロードされたファイルか？セキュリティチェックする
			if (is_uploaded_file($_FILES['upfile']['tmp_name'])){
				// テンポラリディレクトリの書き込み権限をチェック
				if (!is_writable($this->gEnv->getWorkDirPath())){
					//$msg = '一時ディレクトリに書き込み権限がありません。ディレクトリ：' . $this->gEnv->getWorkDirPath();
					$msg = sprintf($this->_('You are not allowed to write temporary directory. (directory: %s)'), $this->gEnv->getWorkDirPath());	// 一時ディレクトリに書き込み権限がありません。(ディレクトリ：%s)
					$this->setAppErrorMsg($msg);
				}
				
				if ($this->getMsgCount() == 0){		// エラーが発生していないとき
					// ファイルを保存するサーバディレクトリを指定
					$tmpFile = tempnam($this->gEnv->getWorkDirPath(), M3_SYSTEM_WORK_UPLOAD_FILENAME_HEAD);
		
					// アップされたテンポラリファイルを保存ディレクトリにコピー
					$ret = move_uploaded_file($_FILES['upfile']['tmp_name'], $tmpFile);
					if ($ret){
						// ファイルの内容のチェック
						$imageSize = @getimagesize($tmpFile);// 画像情報を取得
						if ($imageSize){
							$imageWidth = $imageSize[0];
							$imageHeight = $imageSize[1];
							$imageType = $imageSize[2];
							$imageMimeType = $imageSize['mime'];	// ファイルタイプを取得

							// 受付可能なファイルタイプかどうか
							if (!in_array($imageMimeType, $this->permitMimeType)){
								$msg = 'アップロード画像のタイプが不正です。';
								$this->setAppErrorMsg($msg);
							}
						} else {
							$msg = 'アップロード画像が不正です。';
							$this->setAppErrorMsg($msg);
						}
				
						if ($this->getMsgCount() == 0){		// エラーが発生していないとき
							// サムネールを作成
							for ($i = 0; $i < count($siteLogoFiles); $i++){
								$siteLogoFilename = $siteLogoFiles[$i];
								$ret = preg_match('/.+_(\d+)(.*)\.(gif|png|jpg|jpeg|bmp)$/i', $siteLogoFilename, $matches);
								if ($ret){
									$thumbSize = $matches[1];
									$thumbAttr = strtolower($matches[2]);
									$ext = strtolower($matches[3]);
								
									$imageType = IMAGETYPE_JPEG;
									switch ($ext){
										case 'jpg':
										case 'jpeg':
											$imageType = IMAGETYPE_JPEG;
											break;
										case 'gif':
											$imageType = IMAGETYPE_GIF;
											break;
										case 'png':
											$imageType = IMAGETYPE_PNG;
											break;
										case 'bmp':
											$imageType = IMAGETYPE_BMP;
											break;
									}
									$thumbPath = $this->gEnv->getResourcePath() . '/etc/site/thumb/' . $siteLogoFilename;

									// サムネールの作成
									if ($thumbAttr == 'c'){		// 切り取りサムネールの場合
										$ret = $this->gInstance->getImageManager()->createThumb($tmpFile, $thumbPath, $thumbSize, $imageType, true);
									} else {
										$ret = $this->gInstance->getImageManager()->createThumb($tmpFile, $thumbPath, $thumbSize, $imageType, false);
									}
								}
							}
							if ($ret){
								$msg = $this->_('Image changed.');			// 画像を変更しました
								$this->setGuidanceMsg($msg);
							} else {
								$msg = $this->_('Failed in creating image.');			// 画像の作成に失敗しました
								$this->setAppErrorMsg($msg);
							}
						}
					} else {
						$msg = $this->_('Failed in uploading file.');		// ファイルのアップロードに失敗しました
						$this->setAppErrorMsg($msg);
					}
					// テンポラリファイル削除
					unlink($tmpFile);
				}
			} else {
				$msg = sprintf($this->_('Uploded file not found. (detail: The file may be over maximum size to be allowed to upload. Size %s bytes.'), $this->gSystem->getMaxFileSizeForUpload());	// アップロードファイルが見つかりません(要因：アップロード可能なファイルのMaxサイズを超えている可能性があります。%dバイト)
				$this->setAppErrorMsg($msg);
			}
		} else {		// 初期表示の場合

		}
		
		// 一覧の表示タイプを設定
		if ($this->isMultiLang){		// 多言語対応の場合
			$this->tmpl->setAttribute('show_multilang', 'visibility', 'visible');
			
			// 言語選択メニュー作成
			$this->createLangMenu();
			$this->tmpl->setAttribute('select_lang', 'visibility', 'visible');
		}
		
		// 画面にデータを埋め込む
		$siteName = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_NAME);		// サイト名
		$siteEmail = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_EMAIL);
		$siteSlogan = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_SLOGAN);// サイトスローガン
		$siteCopyRight = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_COPYRIGHT);		// 著作権
		$this->tmpl->addVar("_widget", "site_name", $this->convertToDispString($siteName));		// サイト名
		$this->tmpl->addVar("_widget", "site_email", $this->convertToDispString($siteEmail));
		$this->tmpl->addVar("_widget", "site_slogan", $this->convertToDispString($siteSlogan));		// サイトスローガン
		$this->tmpl->addVar("_widget", "site_copyright", $this->convertToDispString($siteCopyRight));	// 著作権
		
		// SEO
		$siteTitle	= $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_TITLE);		// 画面タイトル
		$siteDesc	= $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_DESCRIPTION);		// サイト要約
		$siteKeyword = $this->db->getSiteDef($this->langId, M3_TB_FIELD_SITE_KEYWORDS);		// サイトキーワード
		$metaOthers	= $this->db->getSiteDef($this->langId, self::SD_HEAD_OTHERS);		// ヘッダその他タグ
		$this->tmpl->addVar("_widget", "site_title", $this->convertToDispString($siteTitle));
		$this->tmpl->addVar("_widget", "site_desc", $this->convertToDispString($siteDesc));
		$this->tmpl->addVar("_widget", "site_keyword", $this->convertToDispString($siteKeyword));
		$this->tmpl->addVar("_widget", "meta_others", $this->convertToDispString($metaOthers));		// ヘッダその他タグ
		
		// サイトロゴ
		$siteLogoUrl = $this->gEnv->getResourceUrl() . '/etc/site/thumb/' . $siteLogoFiles[0] . '?' . date('YmdHis');		// サイトロゴファイル名
		$siteLogoImage = '<img src="' . $this->convertUrlToHtmlEntity($this->getUrl($siteLogoUrl)) . '" />';
		$this->tmpl->addVar("_widget", "logo_image", $siteLogoImage);
		
		// メール送信ボタン
		if (empty($siteEmail)) $this->tmpl->addVar("_widget", "test_mail_disabled", 'disabled');
		
		// テキストをローカライズ
		$localeText = array();
		$localeText['msg_update'] = $this->_('Update config?');		// 設定を更新しますか?
		$localeText['msg_send_email'] = $this->_('Send test email to default email address?');		// デフォルトメールアドレス宛にテストメールを送信しますか?
		$localeText['label_site_info'] = $this->_('Site Information');			// サイト情報
		$localeText['label_site_name'] = $this->_('Site Name');// サイト名
		$localeText['label_required'] = $this->_('Required');	// 必須
		$localeText['label_site_email'] = $this->_('Site Email');// メールアドレス
		$localeText['label_send_test_email'] = $this->_('Send Test Email');// テストメール送信
		$localeText['label_site_slogan'] = $this->_('Site Slogan');// サイトスローガン
		$localeText['label_site_copyright'] = $this->_('Site Copyright');// 著作権
		$localeText['label_header_info'] = $this->_('Page Header Info (Default)');// ページヘッダ情報(デフォルト値)
		$localeText['label_header_title'] = $this->_('Header Tilte');// タイトル名
		$localeText['label_header_desc'] = $this->_('Site Description');// サイト説明
		$localeText['label_header_keywords'] = $this->_('Header Keywords');// 検索キーワード
		$localeText['label_header_others'] = $this->_('Header Others (by Tag Style)');// その他タグ
		$localeText['label_update'] = $this->_('Update');// 更新
		$this->setLocaleText($localeText);
	}
	/**
	 * 言語選択メニューを作成
	 *
	 * @return 			なし
	 */
	function createLangMenu()
	{
		$ret = $this->db->getAvailableLang($rows);
		if ($ret){
			for ($i = 0; $i < count($rows); $i++){
				$langRow = $rows[$i];

				$selected = '';
				if ($langRow['ln_id'] == $this->langId) $selected = 'selected';

				if ($this->gEnv->getCurrentLanguage() == 'ja'){		// 日本語表示の場合
					$name = $this->convertToDispString($langRow['ln_name']);
				} else {
					$name = $this->convertToDispString($langRow['ln_name_en']);
				}

				$row = array(
					'value'    => $this->convertToDispString($langRow['ln_id']),			// 言語ID
					'name'     => $name,			// 言語名
					'selected' => $selected														// 選択中かどうか
				);
				$this->tmpl->addVars('lang_list', $row);
				$this->tmpl->parseTemplate('lang_list', 'a');
			}
		}
	}
}
?>
