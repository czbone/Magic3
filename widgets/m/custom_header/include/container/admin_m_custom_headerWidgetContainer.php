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
 * @copyright  Copyright 2006-2009 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_m_custom_headerWidgetContainer.php 1889 2009-05-13 02:43:00Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getContainerPath() . '/baseWidgetContainer.php');

class admin_m_custom_headerWidgetContainer extends BaseWidgetContainer
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
		return 'admin.tmpl.html';
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
		$act = $request->trimValueOf('act');
		if ($act == 'update'){		// 設定更新のとき
			// 入力値を取得
			$content	= $request->valueOf('item_content');			// ヘッダコンテンツ
			
			if ($this->getMsgCount() == 0){			// エラーのないとき
				$paramObj->content	= $content;		// ヘッダコンテンツ
				$ret = $this->updateWidgetParamObj($paramObj);
				if ($ret){
					$this->setMsg(self::MSG_GUIDANCE, 'データを更新しました');
				} else {
					$this->setMsg(self::MSG_APP_ERR, 'データ更新に失敗しました');
				}				
			}
			$this->gPage->updateParentWindow();// 親ウィンドウを更新
		} else {		// 初期表示の場合
			// デフォルト値設定
			$content = '<div align="center"><h1>サイト名</h1></div>' . M3_NL;// ヘッダコンテンツ
			$paramObj = $this->getWidgetParamObj();
			if (!empty($paramObj)){
				$content	= $paramObj->content;			// ヘッダコンテンツ
			}
		}
		
		// 画面にデータを埋め込む
		$this->tmpl->addVar("_widget", "content",	$content);
	}
}
?>
