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
require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/admin_mainInitwizardBaseWidgetContainer.php');

class admin_mainInitwizard_pageWidgetContainer extends admin_mainInitwizardBaseWidgetContainer
{
	private $idArray = array();			// 表示するページ
	const MENU_ID = 'admin_menu';		// メニュー変換対象メニューバーID
	const CF_SITE_PC_IN_PUBLIC			= 'site_pc_in_public';				// PC用サイトの公開状況
	const CF_SITE_MOBILE_IN_PUBLIC		= 'site_mobile_in_public';		// 携帯用サイトの公開状況
	const CF_SITE_SMARTPHONE_IN_PUBLIC	= 'site_smartphone_in_public';		// スマートフォン用サイトの公開状況
	
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
		return 'initwizard_page.tmpl.html';
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
		// デフォルト値取得
		$this->langId		= $this->gEnv->getDefaultLanguage();
		
		$act = $request->trimValueOf('act');
	
		$reloadData = false;		// データの再ロード
		if ($act == 'update'){		// 設定更新のとき
			$listedItem = explode(',', $request->trimValueOf('seriallist'));
			$delItems = array();
			for ($i = 0; $i < count($listedItem); $i++){
				// 項目がチェックされているかを取得
				$itemName = 'item' . $i . '_selected';
				$itemValue = ($request->trimValueOf($itemName) == 'on') ? 1 : 0;
				
				if ($itemValue){		// チェック項目
					$delItems[] = $listedItem[$i];
					
					// 削除可能かチェック
					$ret = $this->db->getPageIdRecord($this->pageIdType, $listedItem[$i], $row);
					if ($ret){
						if (!$row['pg_editable']) $this->setMsg(self::MSG_APP_ERR, 'このデータは削除不可データです。ID=' . $listedItem[$i]);
					} else {
						$this->setMsg(self::MSG_APP_ERR, '該当データが見つかりません');
					}
				}
			}
			if ($ret){
				// 次の画面へ遷移
				$this->_redirectNextTask();
			} else {
				$this->setMsg(self::MSG_APP_ERR, 'データ更新に失敗しました');			// データ更新に失敗しました
			}
		} else {
			$reloadData = true;
		}
		
		if ($reloadData){		// データ再取得のとき
//			$siteOpenPc			= $this->isActiveAccessPoint(0/*PC*/);			// PC用サイトの公開状況
//			$siteOpenSmartphone = $this->isActiveAccessPoint(2/*スマートフォン*/);	// スマートフォン用サイトの公開状況
//			$siteOpenMobile		= $this->isActiveAccessPoint(1/*携帯*/);	// 携帯用サイトの公開状況
		}

		$this->_mainDb->getPageIdList(array($this, 'pageLoop'), 1);
					
		$this->tmpl->addVar("_widget", "id_list", implode($this->idArray, ','));// 表示項目のIDを設定
	}
	/**
	 * ページサブID、取得したデータをテンプレートに設定する
	 *
	 * @param int $index			行番号(0～)
	 * @param array $fetchedRow		フェッチ取得した行
	 * @param object $param			未使用
	 * @return bool					true=処理続行の場合、false=処理終了の場合
	 */
	function pageLoop($index, $fetchedRow, $param)
	{
		$value = $this->convertToDispString($fetchedRow['pg_id']);
		
		// 使用するページかどうか
		$checked = '';
		if ($fetchedRow['pg_available']) $checked = 'checked';
		
		$row = array(
			'index'		=> $index,			// インデックス番号
			'value'		=> $value,			// ページID
			'name'		=> $this->convertToDispString($fetchedRow['pg_name']),			// ページ名
			'checked'	=> $checked,		// 使用するかどうか
			'disabled'	=> $disabled		// 変更可否制御
		);
		$this->tmpl->addVars('page_list', $row);
		$this->tmpl->parseTemplate('page_list', 'a');
		
		// 表示中項目のページサブIDを保存
		$this->idArray[] = $value;
		return true;
	}
}
?>
