<?php
/**
 * 運用ログDBクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2011 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: admin_opelogDb.php 4119 2011-05-07 13:31:45Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getDbPath() . '/baseDb.php');

class admin_opelogDb extends BaseDb
{
	/**
	 * 運用ログ取得
	 *
	 * @param int		$level		取得ログのレベル
	 * @param int		$status		取得するデータの状況(0=すべて、1=未参照のみ、2=参照済みのみ)
	 * @param int		$limit		取得する項目数
	 * @param int		$page		取得するページ(1～)
	 * @param function	$callback	コールバック関数
	 * @return						なし
	 */
	function getOpeLogList($level, $status, $limit, $page, $callback)
	{
		// メッセージ種別
		// 通常メッセージ: info=情報,warn=警告,user_info=ユーザ操作
		// 参照必須メッセージ: error=通常エラー,fatal=致命的エラー,user_err=ユーザ操作エラー,user_access=不正アクセス,user_data=不正データ
		$offset = $limit * ($page -1);
		if ($offset < 0) $offset = 0;
		
		$queryStr = 'SELECT * FROM _operation_log LEFT JOIN _operation_type ON ol_type = ot_id ';
		$queryStr .= 'LEFT JOIN _access_log ON ol_access_log_serial = al_serial ';
		
		// 必須参照項目のみに限定
		$params = array();
		$addWhere = '';
		$addWhere .= 'WHERE ot_level >= ? ';
		$params[] = $level;

		// 参照状況を制限
		if ($status == 1){		// 未参照
			if (empty($addWhere)){
				$addWhere .= 'WHERE ';
			} else {
				$addWhere .= 'AND ';
			}
			$addWhere .= 'ol_checked = false ';
		} else if ($status == 2){	// 参照済み
			if (empty($addWhere)){
				$addWhere .= 'WHERE ';
			} else {
				$addWhere .= 'AND ';
			}
			$addWhere .= 'ol_checked = true ';
		}
		$queryStr .= $addWhere;
		$queryStr .=  'ORDER BY ol_serial DESC limit ' . $limit . ' offset ' . $offset;
		$this->selectLoop($queryStr, $params, $callback);
	}
	/**
	 * 運用ログ総数取得
	 *
	 * @param int		$level		取得ログのレベル
	 * @param int		$status		取得するデータの状況(0=すべて、1=未参照のみ、2=参照済みのみ)
	 * @return int					総数
	 */
	function getOpeLogCount($level, $status)
	{
		$queryStr = 'SELECT * FROM _operation_log LEFT JOIN _operation_type ON ol_type = ot_id ';
		
		// 必須参照項目のみに限定
		$params = array();
		$addWhere = '';
		$addWhere .= 'WHERE ot_level >= ? ';
		$params[] = $level;

		// 参照状況を制限
		if ($status == 1){		// 未参照
			if (empty($addWhere)){
				$addWhere .= 'WHERE ';
			} else {
				$addWhere .= 'AND ';
			}
			$addWhere .= 'ol_checked = false ';
		} else if ($status == 2){	// 参照済み
			if (empty($addWhere)){
				$addWhere .= 'WHERE ';
			} else {
				$addWhere .= 'AND ';
			}
			$addWhere .= 'ol_checked = true ';
		}
		$queryStr .= $addWhere;
		return $this->selectRecordCount($queryStr, $params);
	}
}
?>
