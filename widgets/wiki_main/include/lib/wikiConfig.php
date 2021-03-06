<?php
/**
 * Wiki定義クラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2008 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: wikiConfig.php 1166 2008-11-01 09:36:07Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getCurrentWidgetDbPath() .	'/wiki_mainDb.php');

class WikiConfig
{
	private static $db;		// DBオブジェクト
	private static $defaultPage;	// デフォルトページ名
	private static $authType;	// ユーザの認証方法
	private static $isShowToolbarForAllUser;	// 全ユーザ向けにツールバーを表示するかどうか
	private static $isShowPageTitle;				// ページタイトルを表示するかどうか
	private static $isShowPageRelated;				// 関連ページを表示するかどうか
	private static $isShowPageAttachFiles;				// 添付ファイルを表示するかどうか
	private static $isShowPageLastModified;				// 最終更新を表示するかどうか
	const SHOW_TOOLBAR_FOR_ALL_USER = 'show_toolbar_for_all_user';
	const AUTH_TYPE_ADMIN		= 'admin';		// 認証タイプ(管理権限ユーザ)
	const AUTH_TYPE_LOGIN_USER	= 'loginuser';		// 認証タイプ(ログインユーザ)
	const AUTH_TYPE_PASSWORD	= 'password';		// 認証タイプ(共通パスワード)
	const DEFAULT_PAGE = 'FrontPage';		// デフォルトのページ
	const CONFIG_KEY_AUTH_TYPE = 'auth_type';			// 認証タイプ(admin=管理権限ユーザ、loginuser=ログインユーザ、password=共通パスワード)
	const CONFIG_KEY_SHOW_PAGE_TITLE		= 'show_page_title';		// タイトル表示
	const CONFIG_KEY_SHOW_PAGE_RELATED		= 'show_page_related';// 関連ページ
	const CONFIG_KEY_SHOW_PAGE_ATTACH_FILES	= 'show_page_attach_files';// 添付ファイル
	const CONFIG_KEY_SHOW_PAGE_LAST_MODIFIED	= 'show_page_last_modified';// 最終更新
	const CONFIG_KEY_PASSWORD = 'password';		// 共通パスワード
	const CONFIG_KEY_DEFAULT_PAGE = 'default_page';		// デフォルトページ
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
	}
	/**
	 * オブジェクトを初期化
	 *
	 * @param object $db	DBオブジェクト
	 * @return				なし
	 */
	public static function init($db)
	{
		global $defaultpage;
		self::$db = $db;
		
		// 設定値を取得
		$value = self::$db->getConfig(self::SHOW_TOOLBAR_FOR_ALL_USER);		// 全ユーザ向けにツールバーを表示するかどうか
		if ($value == ''){
			self::$isShowToolbarForAllUser = true;		// デフォルトは表示
		} else {
			if (!empty($value)) self::$isShowToolbarForAllUser = true;
		}
		$value = self::$db->getConfig(self::CONFIG_KEY_SHOW_PAGE_TITLE);		// ページタイトルを表示するかどうか
		if ($value == ''){
			self::$isShowPageTitle = true;				// デフォルトは表示
		} else {
			if (!empty($value)) self::$isShowPageTitle = true;
		}
		$value = self::$db->getConfig(self::CONFIG_KEY_SHOW_PAGE_RELATED);		// 関連ページを表示するかどうか
		if ($value == ''){
			self::$isShowPageRelated = true;				// デフォルトは表示
		} else {
			if (!empty($value)) self::$isShowPageRelated = true;
		}
		$value = self::$db->getConfig(self::CONFIG_KEY_SHOW_PAGE_ATTACH_FILES);		// 添付ファイルを表示するかどうか
		if ($value == ''){
			self::$isShowPageAttachFiles = true;				// デフォルトは表示
		} else {
			if (!empty($value)) self::$isShowPageAttachFiles = true;
		}
		$value = self::$db->getConfig(self::CONFIG_KEY_SHOW_PAGE_LAST_MODIFIED);		// 最終更新を表示するかどうか
		if ($value == ''){
			self::$isShowPageLastModified = true;				// デフォルトは表示
		} else {
			if (!empty($value)) self::$isShowPageLastModified = true;
		}
		$value = self::$db->getConfig(self::CONFIG_KEY_DEFAULT_PAGE);// デフォルトページ
		if (empty($value)){
			self::$defaultPage = self::DEFAULT_PAGE;
		} else {
			self::$defaultPage = $value;
		}
		$defaultpage = self::$defaultPage;	// グローバル値にも設定
		
		// ユーザ認証方法
		$value = self::$db->getConfig(self::CONFIG_KEY_AUTH_TYPE);// ユーザの認証方法
		if (empty($value)){
			//self::$authType = self::AUTH_TYPE_ADMIN;		// デフォルトの認証タイプは管理権限
			self::$authType = self::AUTH_TYPE_PASSWORD;		// 認証タイプ(共通パスワード)
		} else {
			self::$authType = $value;
		}
	}
	/**
	 * デフォルトのページ名を取得
	 *
	 * @return string				デフォルトページ名
	 */
	public static function getDefaultPage()
	{
		return self::$defaultPage;
	}
	/**
	 * 「編集されたページ」のページ名を取得
	 *
	 * @return string		ページ名
	 */
	public static function getWhatsnewPage()
	{
		global $whatsnew;
		return $whatsnew;
	}
	/**
	 * インターWikiページ名を取得
	 *
	 * @return string				ページ名
	 */
	public static function getInterWikiPage()
	{
		global $interwiki;
		return $interwiki;
	}
	/**
	 * ヘルプページのページ名を取得
	 *
	 * @return string		ページ名
	 */
	public static function getHelpPage()
	{
		global $help_page;
		return $help_page;
	}
	/**
	 * メニューバーページのページ名を取得
	 *
	 * @return string		ページ名
	 */
	public static function getMenuBarPage()
	{
		global $menubar;
		return $menubar;
	}
	/**
	 * 現在日時を取得
	 *
	 * @return string		現在日時
	 */
	public static function getNow()
	{
		global $now;
		return $now;
	}
	/**
	 * プラグイン格納ディレクトリを取得
	 *
	 * @return string		プラグインディレクトリ
	 */
	public static function getPluginDir()
	{
		return PLUGIN_DIR;
	}
	/**
	 * 添付ファイルアップロードディレクトリを取得
	 *
	 * @return string		ディレクトリ
	 */
	public static function getAttachFileUploadDir()
	{
		return UPLOAD_DIR;
	}
	/**
	 * ライブラリディレクトリを取得
	 *
	 * @return string		ライブラリディレクトリ
	 */
	public static function getLibDir()
	{
		global $gEnvManager;
		return $gEnvManager->getCurrentWidgetLibPath() . '/';
	}
	/**
	 * DBディレクトリを取得
	 *
	 * @return string		DBディレクトリ
	 */
	public static function getDbDir()
	{
		global $gEnvManager;
		return $gEnvManager->getCurrentWidgetDbPath() . '/';
	}
	/**
	 * ページが編集可能かどうかを取得
	 *
	 * @return bool				true=編集可能、false=読み込みのみ
	 */
	public static function isPageEditable()
	{
		if (PKWK_READONLY){
			return false;
		} else {
			return true;
		}
	}
	/**
	 * ページがバックアップ可能かどうかを取得
	 *
	 * @return bool				true=可能、false=不可
	 */
	public static function isPageBackup()
	{
		global $do_backup;
		if ($do_backup){
			return true;
		} else {
			return false;
		}
	}
	/**
	 * ページが凍結可能かどうかを取得
	 *
	 * @return bool				true=可能、false=不可
	 */
	public static function isPageFreeze()
	{
		global $function_freeze;
		if ($function_freeze){
			return true;
		} else {
			return false;
		}
	}
	/**
	 * すべてのユーザ向けにツールバーを表示するかどうかを取得
	 *
	 * @return bool		true=すべてのユーザに表示、false=管理者のみ表示
	 */
	public static function isShowToolbarForAllUser()
	{
		return self::$isShowToolbarForAllUser;
	}
	/**
	 * タイトルを表示するかどうか
	 *
	 * @return bool		true=表示、false=非表示
	 */
	public static function isShowPageTitle()
	{
		return self::$isShowPageTitle;
	}
	/**
	 * 関連ファイル表示するかどうか
	 *
	 * @return bool		true=表示、false=非表示
	 */
	public static function isShowPageRelated()
	{
		return self::$isShowPageRelated;
	}
	/**
	 * 添付ファイルを表示するかどうか
	 *
	 * @return bool		true=表示、false=非表示
	 */
	public static function isShowPageAttachFiles()
	{
		return self::$isShowPageAttachFiles;
	}
	/**
	 * 最終更新を表示するかどうか
	 *
	 * @return bool		true=表示、false=非表示
	 */
	public static function isShowPageLastModified()
	{
		return self::$isShowPageLastModified;
	}
	/**
	 * 共通パスワードを取得
	 *
	 * @return string		共通パスワード
	 */
	public static function getPassword()
	{
		$value = self::$db->getConfig(self::CONFIG_KEY_PASSWORD);
		return $value;
	}
	/**
	 * アクセス中のユーザにデータ編集権限があるかを判断
	 *
	 * @return bool		true=権限あり、false=権限なし
	 */
	public static function isUserWithEditAuth()
	{
		global $gEnvManager;
		
		$ret = false;
		switch (self::$authType){
			case self::AUTH_TYPE_ADMIN:		// 認証タイプ(管理権限ユーザ)
				if ($gEnvManager->isSystemAdmin()) $ret = true;
				break;
			case self::AUTH_TYPE_LOGIN_USER:		// 認証タイプ(ログインユーザ)
				if ($gEnvManager->isCurrentUserLogined()) $ret = true;
				break;
			case self::AUTH_TYPE_PASSWORD:		// 認証タイプ(共通パスワード)
				break;
			default:
				break;
		}
		return $ret;
	}
	/**
	 * アクセス中のユーザにデータ参照権限があるかを判断
	 *
	 * @return bool		true=権限あり、false=権限なし
	 */
	public static function isUserWithReadAuth()
	{
		return true;
	}
	/**
	 * 認証方法がログイン認証であるかどうか
	 *
	 * @return bool		true=ログイン認証、false=ログイン認証以外
	 */
	public static function isPasswordAuth()
	{
		if (self::$authType == self::AUTH_TYPE_PASSWORD){		// 認証タイプ(共通パスワード)
			return true;
		} else {
			return false;
		}
	}
}
?>
