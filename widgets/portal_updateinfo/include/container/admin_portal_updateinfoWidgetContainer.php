<?php
/**
 * コンテナクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    ポータル用コンテンツ更新情報
 * @author     株式会社 毎日メディアサービス
 * @copyright  Copyright 2009 株式会社 毎日メディアサービス.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_portal_updateinfoWidgetContainer.php 2937 2010-03-17 14:20:16Z fishbone $
 * @link       http://www.m-media.co.jp
 */
require_once($gEnvManager->getCurrentWidgetContainerPath() . '/admin_portal_updateinfoBaseWidgetContainer.php');

class admin_portal_updateinfoWidgetContainer extends admin_portal_updateinfoBaseWidgetContainer
{
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
	}
	/**
	 * ディスパッチ処理(メインコンテナのみ実行)
	 *
     * HTTPリクエストの内容を見て処理をコンテナに振り分ける
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。そのまま_assign()に渡る
	 * @return bool 						このクラスの_setTemplate(), _assign()へ処理を継続するかどうかを返す。
	 *                                      true=処理を継続、false=処理を終了
	 */
	function _dispatch($request, &$param)
	{
		// 表示画面を決定
		$task = $request->trimValueOf(M3_REQUEST_PARAM_OPERATION_TASK);
		if (empty($task)) $task = self::DEFAULT_TOP_PAGE;
		
		// コンテナを起動
		$goWidget = false;		// サブウィジェットを実行するかどうか
		switch ($task){
			case 'news':		// 更新情報管理
			case 'news_detail':		// 更新情報管理(詳細)
				$task = 'news';
				$goWidget = true;		// サブウィジェットを実行するかどうか
				break;
			case 'other':		// その他設定
				$goWidget = true;		// サブウィジェットを実行するかどうか
				break;
			default:
				break;
		}
		if ($goWidget){		// サブウィジェットを実行するかどうか
			$this->gLaunch->goSubWidget($task, true);		// 管理者機能で呼び出し
			return false;
		} else {
			$this->SetMsg(self::MSG_APP_ERR, "画面が見つかりません");
			return true;
		}
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
		return 'message.tmpl.html';
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
	function _assign($request, &$param)
	{
	}
}
?>
