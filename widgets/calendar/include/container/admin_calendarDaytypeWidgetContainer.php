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
require_once($gEnvManager->getWidgetContainerPath('calendar') . '/admin_calendarBaseWidgetContainer.php');

class admin_calendarDaytypeWidgetContainer extends admin_calendarBaseWidgetContainer
{
	private $serialArray = array();		// 表示されている項目シリアル番号
	
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
		$task = $request->trimValueOf('task');
		if ($task == 'daytype_detail'){		// 詳細画面
			return 'admin_daytype_detail.tmpl.html';
		} else {			// 一覧画面
			return 'admin_daytype.tmpl.html';
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
		if ($task == 'daytype_detail'){	// 詳細画面
			return $this->createDetail($request);
		} else {			// 一覧画面
			return $this->createList($request);
		}
	}
	/**
	 * 一覧画面作成
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createList($request)
	{
		// パラメータの取得
		$task = $request->trimValueOf('task');		// 処理区分
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
			if ($this->getMsgCount() == 0 && count($delItems) > 0){
				$ret = self::$_mainDb->deleteDayType($delItems);
				if ($ret){		// データ削除成功のとき
					$this->setGuidanceMsg('データを削除しました');
				} else {
					$this->setAppErrorMsg('データ削除に失敗しました');
				}
			}
		}
		// 一覧作成
		self::$_mainDb->getDayTypeList(array($this, 'itemLoop'));
		if (empty($this->serialArray)) $this->tmpl->setAttribute('item_list', 'visibility', 'hidden');// 一覧非表示
		
		$this->tmpl->addVar("_widget", "serial_list", implode($this->serialArray, ','));// 表示項目のシリアル番号を設定
	}
	/**
	 * 詳細画面作成
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param								なし
	 */
	function createDetail($request)
	{
		$act = $request->trimValueOf('act');
		$dateTypeId = $request->trimValueOf('serial');		// 日付タイプID

		$name = $request->trimValueOf('item_name');		// 日付タイプ名
		$sortOrder = $request->trimIntValueOf('item_sort_order', '1');		// ソート順

		$replaceNew = false;		// データを再取得するかどうか
		if ($act == 'add'){		// 新規追加のとき
			// 入力チェック
			$this->checkInput($name, '名前');
			$this->checkNumeric($sortOrder, 'ソート順');
			
			// エラーなしの場合は、データを更新
			if ($this->getMsgCount() == 0){
				// ページIDの追加
				$ret = $this->db->updateMenuId($newMenuId, $name, $desc, $sortOrder);
				if ($ret){		// データ追加成功のとき
					$this->setMsg(self::MSG_GUIDANCE, 'データを追加しました');
					
					$replaceNew = true;			// データを再取得
				} else {
					$this->setMsg(self::MSG_APP_ERR, 'データの追加に失敗しました');
				}
			}
		} else if ($act == 'update'){		// 更新のとき
			// 入力チェック
			$this->checkInput($name, '名前');
			$this->checkNumeric($sortOrder, 'ソート順');
			
			// エラーなしの場合は、データを更新
			if ($this->getMsgCount() == 0){
				// ページIDの更新
				$ret = $this->db->updateMenuId($this->menuId, $name, $desc, $sortOrder);
				if ($ret){		// データ追加成功のとき
					$this->setMsg(self::MSG_GUIDANCE, 'データを更新しました');
					$replaceNew = true;			// データを再取得
				} else {
					$this->setMsg(self::MSG_APP_ERR, 'データ更新に失敗しました');
				}
			}
		} else if ($act == 'delete'){		// 削除のとき		
			// エラーなしの場合は、データを削除
			if ($this->getMsgCount() == 0){
				$ret = self::$_mainDb->deleteDayType(array($this->menuId));
				if ($ret){		// データ削除成功のとき
					$this->setMsg(self::MSG_GUIDANCE, 'データを削除しました');
				} else {
					$this->setMsg(self::MSG_APP_ERR, 'データ削除に失敗しました');
				}
			}
		} else {		// 初期状態
			$replaceNew = true;			// データを再取得
		}
		// 時間割一覧を取得
		self::$_mainDb->getTimePeriodList($dateTypeId, array($this, 'timePeriodLoop'));
		if (empty($this->serialArray)) $this->tmpl->setAttribute('time_list', 'visibility', 'hidden');
		
		// 表示データ再取得
		if ($replaceNew){
//			$ret = $this->db->getMenuId($this->menuId, $row);
			if ($ret){
				$name = $row['mn_name'];
				$sortOrder = $row['mn_sort_order'];
			}
		}
		
		// 入力フィールドの設定、共通項目のデータ設定
		if (empty($dateTypeId)){		// 新規追加のとき
			$this->tmpl->addVar('_widget', 'id', '新規');
			
			$this->tmpl->setAttribute('add_button', 'visibility', 'visible');	// 追加ボタン表示
		} else {
			$this->tmpl->addVar('_widget', 'id', $dateTypeId);
			
			// データ更新、削除ボタン表示
			$this->tmpl->setAttribute('delete_button', 'visibility', 'visible');
			$this->tmpl->setAttribute('update_button', 'visibility', 'visible');	// 更新ボタン表示
		}
		
		$this->tmpl->addVar("_widget", "name", $name);		// 日付タイプ名
		$this->tmpl->addVar("_widget", "sort_order", $sortOrder);		// ソート順
	}
	/**
	 * 日付一覧をテンプレートに設定する
	 *
	 * @param int $index			行番号(0～)
	 * @param array $fetchedRow		フェッチ取得した行
	 * @param object $param			未使用
	 * @return bool					true=処理続行の場合、false=処理終了の場合
	 */
	function itemLoop($index, $fetchedRow, $param)
	{
		$value = $this->convertToDispString($fetchedRow['dt_id']);
		$row = array(
			'name'		=> $this->convertToDispString($fetchedRow['dt_name']),			// 日付タイプ名
			'start_time'		=> $this->convertToDispTime($fetchedRow['to_start_time'], 1/*秒なし*/),			// 開始時間
			'sort_order'	=> $this->convertToDispString($fetchedRow['dt_sort_order'])	// ソート順
		);
		$this->tmpl->addVars('item_list', $row);
		$this->tmpl->parseTemplate('item_list', 'a');
		
		// 表示中項目のページサブIDを保存
		$this->serialArray[] = $value;
		return true;
	}
	/**
	 * 時間割一覧をテンプレートに設定する
	 *
	 * @param int $index			行番号(0～)
	 * @param array $fetchedRow		フェッチ取得した行
	 * @param object $param			未使用
	 * @return bool					true=処理続行の場合、false=処理終了の場合
	 */
	function timePeriodLoop($index, $fetchedRow, $param)
	{
		$serial = $fetchedRow['to_serial'];
		
		$row = array(
			'name'			=> $this->convertToDispString($fetchedRow['to_name']),			// 日付タイプ名
			'start_time'		=> $this->convertToDispTime($fetchedRow['to_start_time'], 1/*秒なし*/),			// 開始時間
			'minute'	=> $this->convertToDispString($fetchedRow['dt_minute'])	// 時間
		);
		$this->tmpl->addVars('time_list', $row);
		$this->tmpl->parseTemplate('time_list', 'a');
		
		// 表示中項目のページサブIDを保存
		$this->serialArray[] = $serial;
		return true;
	}
}
?>