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
 * @copyright  Copyright 2006-2009 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: newsWidgetContainer.php 2261 2009-08-28 01:57:12Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getContainerPath() . '/baseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/newsDb.php');

class newsWidgetContainer extends BaseWidgetContainer
{
	private $db;			// DB接続オブジェクト
	private $langId;		// 現在の言語
	const DEFAULT_CONFIG_ID = 0;
	const CONTENT_TYPE = 'news';			// コンテンツタイプ
		
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		// DBオブジェクト作成
		$this->db = new newsDb();
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
		return 'main.tmpl.html';
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
		$paramObj = $this->getWidgetParamObj();
		
		// コンテンツIDを取得
		$contentId = 0;
		$name = '';
		for ($i = 0; $i < count($paramObj); $i++){
			$targetObj = $paramObj[$i];
			$id = $targetObj->id;// 定義ID
			if ($id == $configId){
				$contentId = $targetObj->contentId;
				$name = $targetObj->name;// 定義名
				break;
			}
		}
		
		// コンテンツを取得
		$ret = $this->db->getContentByContentId(self::CONTENT_TYPE, $contentId, $this->langId, $row);
		if ($ret){
			$html = str_replace(M3_TAG_START . M3_TAG_MACRO_ROOT_URL . M3_TAG_END, $this->getUrl($this->gEnv->getRootUrl()), $row['cn_html']);// アプリケーションルートを変換
		}
		
		// コンテンツを設定
		$this->tmpl->addVar("_widget", "content", $html);
	}
}
?>
