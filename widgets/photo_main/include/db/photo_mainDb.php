<?php
/**
 * DBクラス
 *
 * PHP versions 5
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2013 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id: photo_mainDb.php 5968 2013-04-29 11:08:36Z fishbone $
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getDbPath() . '/baseDb.php');

class photo_mainDb extends BaseDb
{
	const PRODUCT_CLASS_PHOTO	= 'photo';		// 商品クラス
	
	/**
	 * フォトギャラリー定義値を取得をすべて取得
	 *
	 * @param array  $rows			レコード
	 * @return bool					1行以上取得 = true, 取得なし= false
	 */
	function getAllConfig(&$rows)
	{
		$queryStr  = 'SELECT * FROM photo_config ';
		$queryStr .=   'ORDER BY hg_index';
		$retValue = $this->selectRecords($queryStr, array(), $rows);
		return $retValue;
	}
	/**
	 * フォトギャラリー定義値を取得
	 *
	 * @param string $key		キーとなる項目値
	 * @return string $value	値
	 */
	function getConfig($key)
	{
		$retValue = '';
		$queryStr = 'SELECT hg_value FROM photo_config ';
		$queryStr .=  'WHERE hg_id  = ?';
		$ret = $this->selectRecord($queryStr, array($key), $row);
		if ($ret) $retValue = $row['hg_value'];
		return $retValue;
	}
	/**
	 * フォトギャラリー定義値を更新
	 *
	 * @param string $key		キーとなる項目値
	 * @param string $value		値
	 * @return					true = 正常、false=異常
	 */
	function updateConfig($key, $value)
	{
		// データの確認
		$queryStr = 'SELECT hg_value FROM photo_config ';
		$queryStr .=  'WHERE hg_id  = ?';
		$ret = $this->isRecordExists($queryStr, array($key));
		if ($ret){
			$queryStr = "UPDATE photo_config SET hg_value = ? WHERE hg_id = ?";
			return $this->execStatement($queryStr, array($value, $key));
		} else {
			$queryStr = "INSERT INTO photo_config (hg_id, hg_value) VALUES (?, ?)";
			return $this->execStatement($queryStr, array($key, $value));
		}
	}
	/**
	 * ユーザリスト取得
	 *
	 * @param string   $userOption	検索用ユーザオプション
	 * @param int      $limit		取得する項目数
	 * @param int      $page		取得するページ(1～)
	 * @param function $callback	コールバック関数
	 * @return						なし
	 */
	function getAllUserList($userOption, $limit, $page, $callback)
	{
		$offset = $limit * ($page -1);
		if ($offset < 0) $offset = 0;
		
		$queryStr  = 'SELECT * FROM _login_user LEFT JOIN _login_log on lu_id = ll_user_id ';
		$queryStr .=   'WHERE lu_deleted = false ';// 削除されていない
		$queryStr .=     'AND lu_user_type_option LIKE \'%' . addslashes($userOption) . '%\' ';
		$queryStr .=  'ORDER BY lu_account limit ' . $limit . ' offset ' . $offset;
		$this->selectLoop($queryStr, array(), $callback);
	}
	/**
	 * ユーザ総数取得
	 *
	 * @param string   $userOption	検索用ユーザオプション
	 * @return int					総数
	 */
	function getAllUserListCount($userOption)
	{
		$queryStr  = 'SELECT * FROM _login_user ';
		$queryStr .=   'WHERE lu_deleted = false ';// 削除されていない
		$queryStr .=     'AND lu_user_type_option LIKE \'%' . addslashes($userOption) . '%\' ';
		return $this->selectRecordCount($queryStr, array());
	}
	/**
	 * メニュー用にすべてのユーザ取得
	 *
	 * @param string $userOption	検索用ユーザオプション
	 * @param array  $rows			レコード
	 * @return bool					1行以上取得 = true, 取得なし= false
	 */
	function getAllUserForMenu($userOption, &$rows)
	{
		$queryStr  = 'SELECT * FROM _login_user LEFT JOIN _login_log on lu_id = ll_user_id ';
		$queryStr .=   'WHERE lu_deleted = false ';// 削除されていない
		$queryStr .=     'AND lu_user_type_option LIKE \'%' . addslashes($userOption) . '%\' ';
		$queryStr .=  'ORDER BY lu_account';
		$retValue = $this->selectRecords($queryStr, array(), $rows);
		return $retValue;
	}
	/**
	 * ユーザ情報をシリアル番号で取得
	 *
	 * @param string	$serial				シリアル番号
	 * @param array     $row				レコード
	 * @return bool							取得 = true, 取得なし= false
	 */
	function getUserBySerial($serial, &$row)
	{
		$queryStr  = 'select * from _login_user ';
		$queryStr .=   'WHERE lu_serial = ? ';
		$ret = $this->selectRecord($queryStr, array($serial), $row);
		return $ret;
	}
	/**
	 * ユーザの削除
	 *
	 * @param array $serial			シリアルNo
	 * @return						true=成功、false=失敗
	 */
	function delUserBySerial($serial)
	{
		// 引数のエラーチェック
		if (!is_array($serial)) return false;
		if (count($serial) <= 0) return true;
		
		$now = date("Y/m/d H:i:s");	// 現在日時
		$userId = $this->gEnv->getCurrentUserId();	// 現在のユーザ
		
		// トランザクション開始
		$this->startTransaction();
		
		// 指定のシリアルNoのレコードが削除状態でないかチェック
		for ($i = 0; $i < count($serial); $i++){
			$queryStr  = 'SELECT * FROM _login_user ';
			$queryStr .=   'WHERE lu_deleted = false ';		// 未削除
			$queryStr .=     'AND lu_serial = ? ';
			$ret = $this->isRecordExists($queryStr, array($serial[$i]));
			// 存在しない場合は、既に削除されたとして終了
			if (!$ret){
				$this->endTransaction();
				return false;
			}
		}
		
		// レコードを削除
		$queryStr  = 'UPDATE _login_user ';
		$queryStr .=   'SET lu_deleted = true, ';	// 削除
		$queryStr .=     'lu_update_user_id = ?, ';
		$queryStr .=     'lu_update_dt = ? ';
		$queryStr .=   'WHERE lu_serial in (' . implode($serial, ',') . ') ';
		$this->execStatement($queryStr, array($userId, $now));
		
		// トランザクション確定
		$ret = $this->endTransaction();
		return $ret;
	}
	/**
	 * 新規の画像Noを取得
	 *
	 * @param int $userId	ユーザID
	 * @return int			画像番号(1～)
	 */
	function getNewPhotoNo($userId)
	{
		// 画像IDを決定する
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_owner_id = ? ';
		$queryStr .=     'AND ht_history_index = 0 ';
		return $this->selectRecordCount($queryStr, array($userId), $row) + 1;
	}
	/**
	 * 画像コードが存在するかチェック
	 *
	 * @param string $code	画像コード
	 * @return				true=存在する、false=存在しない
	 */
	function isExistsPhotoCode($code)
	{
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_code = ? ';
		return $this->isRecordExists($queryStr, array($code));
	}
	/**
	 * 画像が存在するかチェック(言語に関わらず)
	 *
	 * @param string $publicId	画像ID
	 * @return					true=存在する、false=存在しない
	 */
	function isExistsPhoto($publicId)
	{
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_public_id = ? ';
		return $this->isRecordExists($queryStr, array($publicId));
	}
	/**
	 * 画像情報の新規追加、更新
	 *
	 * @param bool    $limitedUser	制限ありのユーザかどうか
	 * @param int     $serial		シリアル番号。シリアル番号が0のときは新規追加。
	 * @param strin   $langId		言語ID
	 * @param string  $filename		ファイル名
	 * @param string  $dir			ディレクトリ
	 * @param string  $code			画像コード
	 * @param string  $mimeType		MIMEタイプ
	 * @param string  $imageSize	画像サイズ
	 * @param string  $originalName	元の画像名
	 * @param string  $filesize		ファイルサイズ
	 * @param string  $name			画像名
	 * @param string  $camera		カメラ
	 * @param string  $location		撮影場所
	 * @param timestamp  $date		撮影日
	 * @param string  $summary		画像概要
	 * @param string  $description	画像説明
	 * @param string  $license		ライセンス
	 * @param int     $ownerId		所有者ID
	 * @param string  $keyword		検索用キーワード
	 * @param bool    $visible		表示するかどうか
	 * @param int     $sortOrder	ソート順
	 * @param array   $category		画像カテゴリー
	 * @param string  $thumbFilename	サムネールファイル名
	 * @param int     $newSerial	新規シリアル番号
	 * @return bool					true = 成功、false = 失敗
	 */
	function updatePhotoInfo($limitedUser, $serial, $langId, $filename, $dir, $code, $mimeType, $imageSize, $originalName, $filesize, 
								$name, $camera, $location, $date, $summary, $description, $license, $ownerId, $keyword, $visible, $sortOrder, $category, $thumbFilename, &$newSerial)
	{
		$now = date("Y/m/d H:i:s");	// 現在日時
		$userId = $this->gEnv->getCurrentUserId();	// 現在のユーザ
						
		// トランザクション開始
		$this->startTransaction();
		
		// シリアル番号が0のときは新規追加
		if (empty($serial)){
			// 画像コードが存在すればエラー
			if ($this->isExistsPhotoCode($code)){
				$this->endTransaction();
				return false;
			}
			// 画像IDを決定する
			$queryStr = 'SELECT MAX(ht_id) AS mid FROM photo ';
			$ret = $this->selectRecord($queryStr, array(), $row);
			if ($ret){
				$photoId = $row['mid'] + 1;
			} else {
				$photoId = 1;
			}
			$historyIndex = 0;
			$registDt = $now;		// 画像アップロード日時
			$rateAverage = 0;		// 評価値
			$viewCount = 0;			// 参照数
			$sortOrder = $photoId * 2;		// ソート順
		} else {
			// 指定のシリアルNoのレコードが削除状態でないかチェック
			$historyIndex = 0;		// 履歴番号
			$queryStr  = 'SELECT * FROM photo ';
			$queryStr .=   'WHERE ht_serial = ? ';
			$ret = $this->selectRecord($queryStr, array($serial), $row);
			if ($ret){		// 既に登録レコードがあるとき
				if ($row['ht_deleted']){		// レコードが削除されていれば終了
					$this->endTransaction();
					return false;
				}
//				if ($limitedUser && $row['ht_owner_id'] != $ownerId){		// 制限ユーザの場合は所有者が異なるときは更新不可
//					$this->endTransaction();
//					return false;
//				}
				$photoId = $row['ht_id'];
				$langId = $row['ht_language_id'];
				$historyIndex = $row['ht_history_index'] + 1;
				// 変更しない値
				$registDt = $row['ht_regist_dt'];		// 画像アップロード日時
				$filename = $row['ht_public_id'];		// 公開ID
				$dir = $row['ht_dir'];					// 画像格納ディレクトリ
				$code = $row['ht_code'];				// 画像コード
				$mimeType = $row['ht_mime_type'];		// 画像MIMEタイプ
				$imageSize = $row['ht_image_size'];		// 画像縦横サイズ
				$originalName = $row['ht_original_filename'];		// 元のファイル名
				$thumbFilename = $row['ht_thumb_filename'];			// サムネールファイル名
				$filesize = $row['ht_file_size'];					// ファイルサイズ
				$ownerId = $row['ht_owner_id'];			// 所有者
				$rateAverage = $row['ht_rate_average'];			// 評価値
				$viewCount = $row['ht_view_count'];			// 参照数
			} else {		// 存在しない場合は終了
				$this->endTransaction();
				return false;
			}
			// 古いレコードを削除
			$queryStr  = 'UPDATE photo ';
			$queryStr .=   'SET ht_deleted = true, ';	// 削除
			$queryStr .=     'ht_update_user_id = ?, ';
			$queryStr .=     'ht_update_dt = ? ';
			$queryStr .=   'WHERE ht_serial = ?';
			$this->execStatement($queryStr, array($userId, $now, $serial));
		}
		
		// 新規レコード追加		
		$queryStr  = 'INSERT INTO photo ';
		$queryStr .=   '(ht_id, ';
		$queryStr .=   'ht_language_id, ';
		$queryStr .=   'ht_history_index, ';
		$queryStr .=   'ht_public_id, ';
		$queryStr .=   'ht_dir, ';
		$queryStr .=   'ht_code, ';
		$queryStr .=   'ht_mime_type, ';
		$queryStr .=   'ht_image_size, ';
		$queryStr .=   'ht_original_filename, ';
		$queryStr .=   'ht_thumb_filename, ';
		$queryStr .=   'ht_file_size, ';
		$queryStr .=   'ht_name, ';
		$queryStr .=   'ht_camera, ';
		$queryStr .=   'ht_location, ';
		$queryStr .=   'ht_date, ';
		$queryStr .=   'ht_summary, ';
		$queryStr .=   'ht_description, ';
		$queryStr .=   'ht_license, ';
		$queryStr .=   'ht_rate_average, ';
		$queryStr .=   'ht_view_count, ';
		$queryStr .=   'ht_owner_id, ';
		$queryStr .=   'ht_keyword, ';
		$queryStr .=   'ht_visible, ';
		$queryStr .=   'ht_sort_order, ';
		$queryStr .=   'ht_regist_dt, ';
		$queryStr .=   'ht_create_user_id, ';
		$queryStr .=   'ht_create_dt) ';
		$queryStr .= 'VALUES ';
		$queryStr .=   '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
		$this->execStatement($queryStr, array($photoId, $langId, $historyIndex, $filename, $dir, $code, $mimeType, $imageSize, $originalName, $thumbFilename, $filesize, 
												$name, $camera, $location, $date, $summary, $description, $license, $rateAverage, $viewCount, $ownerId, $keyword, intval($visible), $sortOrder, $registDt, $userId, $now));

		// 新規のシリアル番号取得
		$newSerial = 0;
		$queryStr = 'SELECT MAX(ht_serial) AS ns FROM photo ';
		$ret = $this->selectRecord($queryStr, array(), $row);
		if ($ret) $newSerial = $row['ns'];
		
		// 画像カテゴリーの更新
		for ($i = 0; $i < count($category); $i++){
			$ret = $this->updateCategory($newSerial, $i, $category[$i]);
			if (!$ret){
				$this->endTransaction();
				return false;
			}
		}
		
		// トランザクション確定
		$ret = $this->endTransaction();
		return $ret;
	}
	/**
	 * 画像情報の参照数を更新
	 *
	 * @param array   $serial		シリアルNo
	 * @return						true=成功、false=失敗
	 */
	function updatePhotoInfoViewCount($serial)
	{
		$now = date("Y/m/d H:i:s");	// 現在日時
		$userId = $this->gEnv->getCurrentUserId();	// 現在のユーザ
						
		// トランザクション開始
		$this->startTransaction();
		
		// 指定のシリアルNoのレコードが削除状態でないかチェック
		$historyIndex = 0;		// 履歴番号
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_serial = ? ';
		$ret = $this->selectRecord($queryStr, array($serial), $row);
		if ($ret){		// 既に登録レコードがあるとき
			$newCount = $row['ht_view_count'] + 1;
			
			// データを更新
			$queryStr  = 'UPDATE photo ';
			$queryStr .=   'SET ';
			$queryStr .=     'ht_view_count = ?, ';
			$queryStr .=     'ht_update_user_id = ?, ';
			$queryStr .=     'ht_update_dt = ? ';
			$queryStr .=   'WHERE ht_serial = ?';
			$this->execStatement($queryStr, array($newCount, $userId, $now, $serial));
		} else {		// 存在しない場合は終了
			$this->endTransaction();
			return false;
		}
			
		// トランザクション確定
		$ret = $this->endTransaction();
		return $ret;
	}
	/**
	 * 画像情報を取得(管理用)
	 *
	 * @param string $filename		ファイル名
	 * @param string $lang			言語ID
	 * @param array $row			取得データ
	 * @param array $categoryRows	画像カテゴリー
	 * @return						true=正常、false=異常
	 */
	function getPhotoInfo($filename, $lang, &$row, &$categoryRows = array())
	{
		$queryStr  = 'SELECT * FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_language_id = ? ';
		$queryStr .=     'AND ht_public_id = ? ';
		$ret = $this->selectRecord($queryStr, array($lang, $filename), $row);
		
		if ($ret){
			// 画像カテゴリー
			$queryStr  = 'SELECT * FROM photo_with_category LEFT JOIN photo_category ON hw_category_id = hc_id AND hc_deleted = false ';
			$queryStr .=   'WHERE hw_photo_serial = ? ';
			$queryStr .=   'ORDER BY hw_index ';
			$this->selectRecords($queryStr, array($row['ht_serial']), $categoryRows);
		}
		return $ret;
	}
	/**
	 * 画像情報を取得(管理用)
	 *
	 * @param string $publicId		公開画像ID
	 * @param string $productClass	商品クラス
	 * @param string $productType	製品ID
	 * @param string $priceType		価格タイプ
	 * @param string $lang			言語ID
	 * @param array $row			取得データ
	 * @return						true=正常、false=異常
	 */
	function getPhotoInfoWithPrice($publicId, $productClass, $productType, $priceType, $lang, &$row)
	{
		$queryStr  = 'SELECT * FROM photo RIGHT JOIN product_price ON (ht_id = pp_product_id OR pp_product_id = 0) AND ht_language_id = pp_language_id AND pp_deleted = false ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_language_id = ? ';
		$queryStr .=     'AND ht_public_id = ? ';
		$queryStr .=     'AND pp_product_class = ? ';
		$queryStr .=     'AND pp_product_type_id = ? ';
		$queryStr .=     'AND pp_price_type_id = ? ';
		$queryStr .=   'ORDER BY pp_product_id DESC';		// 商品価格マスターの商品ID
		$ret = $this->selectRecord($queryStr, array($lang, $publicId, $productClass, $productType, $priceType), $row);
		return $ret;
	}
	/**
	 * 画像情報を取得
	 *
	 * @param int $serial			シリアル番号
	 * @param array $row			取得データ
	 * @param array $categoryRows	画像カテゴリー
	 * @return						true=正常、false=異常
	 */
	function getPhotoInfoBySerial($serial, &$row, &$categoryRows = array())
	{
		$queryStr  = 'SELECT * FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		$queryStr .=   'WHERE ht_serial = ? ';
		$ret = $this->selectRecord($queryStr, array($serial), $row);
		
		if ($ret){
			// 画像カテゴリー
			$queryStr  = 'SELECT * FROM photo_with_category LEFT JOIN photo_category ON hw_category_id = hc_id AND hc_deleted = false ';
			$queryStr .=   'WHERE hw_photo_serial = ? ';
			$queryStr .=  'ORDER BY hw_index ';
			$this->selectRecords($queryStr, array($serial), $categoryRows);
		}
		return $ret;
	}
	/**
	 * 画像情報の削除
	 *
	 * @param array   $serial		シリアルNo
	 * @return						true=成功、false=失敗
	 */
	function delPhotoInfo($serial)
	{
		// 引数のエラーチェック
		if (!is_array($serial)) return false;
		if (count($serial) <= 0) return true;
		
		$now = date("Y/m/d H:i:s");	// 現在日時
		$userId = $this->gEnv->getCurrentUserId();	// 現在のユーザ
		
		// トランザクション開始
		$this->startTransaction();
		
		for ($i = 0; $i < count($serial); $i++){
			$queryStr  = 'SELECT * FROM photo ';
			$queryStr .=   'WHERE ht_deleted = false ';		// 未削除
			$queryStr .=     'AND ht_serial = ? ';
			$ret = $this->selectRecord($queryStr, array($serial[$i]), $row);
			if ($ret){		// 既に登録レコードがあるとき			
				// レコードを削除
				$queryStr  = 'UPDATE photo ';
				$queryStr .=   'SET ht_deleted = true, ';	// 削除
				$queryStr .=     'ht_update_user_id = ?, ';
				$queryStr .=     'ht_update_dt = ? ';
				$queryStr .=   'WHERE ht_serial = ?';
				$this->execStatement($queryStr, array($userId, $now, $serial[$i]));
			} else {// 指定のシリアルNoのレコードが削除状態のときはエラー
				$this->endTransaction();
				return false;
			}
		}
		// トランザクション確定
		$ret = $this->endTransaction();
		return $ret;
	}
	/**
	 * 画像数を取得(管理用)
	 *
	 * @param int $userId			ユーザID
	 * @param int $allCount			総数
	 * @param int $visbleCount		公開画像数
	 * @return						なし
	 */
	function getPhotoCount($userId, &$allCount, &$visbleCount)
	{
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_owner_id = ? ';
		$allCount = $this->selectRecordCount($queryStr, array($userId));
		
		$queryStr  = 'SELECT * FROM photo ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_visible = true ';
		$queryStr .=     'AND ht_owner_id = ? ';
		$visbleCount = $this->selectRecordCount($queryStr, array($userId));
	}
	/**
	 * 写真一覧を取得(公開用)
	 *
	 * @param int		$limit				取得する項目数
	 * @param int		$page				取得するページ(1～)
	 * @param string	$langId				言語
	 * @param timestamp	$startDt			期間(開始日)
	 * @param timestamp	$endDt				期間(終了日)
	 * @param array		$keywords			検索キーワード
	 * @param array		$category			カテゴリーID
	 * @param array		$author				撮影者
	 * @param string	$sortKey			ソートキー(index=表示順,date=日付,rate=評価,ref=参照数)
	 * @param int		$sortDirection		取得順(0=降順,1=昇順)
	 * @param function	$callback			コールバック関数
	 * @param string    $urlOthers			URL付加パラメータ
	 * @param array     $authCategory		参照可能なカテゴリー
	 * @return 			なし
	 */
	function searchPhotoItem($limit, $page, $langId, $startDt, $endDt, $keywords, $category, $author, $sortKey, $sortDirection, $callback, $urlOthers, $authCategory = null)
	{
		$offset = $limit * ($page -1);
		if ($offset < 0) $offset = 0;

		// クエリー文字列作成
		$queryStr = $this->_createSearchPhoto($startDt, $endDt, $keywords, $category, $author, $authCategory);
		
		// 日付範囲
		/*
		if (!empty($startDt)){
			$queryStr .=    'AND ? <= ht_regist_dt ';
			$params[] = $startDt;
		}
		if (!empty($endDt)){
			$queryStr .=    'AND ht_regist_dt < ? ';
			$params[] = $endDt;
		}*/
/*		$queryStr  = 'SELECT DISTINCT ht_public_id,ht_name,ht_rate_average,ht_view_count,ht_regist_dt FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		if (!empty($category) || !empty($authCategory)) $queryStr .=  'RIGHT JOIN photo_with_category ON ht_serial = hw_photo_serial ';
		$queryStr .=  'WHERE ht_deleted = false ';		// 未削除
		$queryStr .=    'AND ht_visible = true ';		// 公開中
		
		// カテゴリー
		if (!empty($category)){
			$idStr = '';
			for ($i = 0; $i < count($category); $i++){
				$idStr .= addslashes($category[$i]) . ',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND hw_category_id in (' . $idStr . ') ';
		}
		// 参照可能なカテゴリー
		if (!empty($authCategory)){
			$queryStr .=  'AND (';
			for ($i = 0; $i < count($authCategory); $i++){
				$queryStr .=  'hw_category_id = ' . addslashes($authCategory[$i]) . ' ';
				if ($i < count($authCategory) -1) $queryStr .=  'OR ';
			}
			$queryStr .=  ') ';
		}
		// キーワード検索
		if (!empty($keywords)){
			for ($i = 0; $i < count($keywords); $i++){
				$keyword = addslashes($keywords[$i]);// 「'"\」文字をエスケープ
				$queryStr .=    'AND (ht_public_id LIKE \'%' . $keyword . '%\' ';		// 公開用画像ID
				$queryStr .=    'OR ht_name LIKE \'%' . $keyword . '%\' ';		// 画像タイトル
				$queryStr .=    'OR ht_camera LIKE \'%' . $keyword . '%\' ';		// カメラ
				$queryStr .=    'OR ht_location LIKE \'%' . $keyword . '%\' ';		// 撮影場所
				$queryStr .=    'OR ht_description LIKE \'%' . $keyword . '%\' ';		// メモ
				$queryStr .=    'OR ht_keyword LIKE \'%' . $keyword . '%\' ';		// キーワード
				$queryStr .=    'OR lu_name LIKE \'%' . $keyword . '%\') ';	// 撮影者
			}
		}
		// 撮影者
		if (!empty($author)){
			$idStr = '';
			for ($i = 0; $i < count($author); $i++){
				$idStr .= '\'' . addslashes($author[$i]) . '\',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND lu_account in (' . $idStr . ') ';
		}*/
		// ソート順
		switch ($sortKey){
			case 'index':	// 表示順
			default:
				$orderKey = 'ht_sort_order ';
				if (empty($sortDirection)) $orderKey .= 'DESC ';
				$orderKey .= ',ht_regist_dt ';
				break;
			case 'date':		// 日付
				$orderKey = 'ht_regist_dt ';
				break;
			case 'rate':		// 評価
				$orderKey = 'ht_rate_average ';
				break;
			case 'ref':			// 参照数
				$orderKey = 'ht_view_count ';
				break;
		}
		$ord = '';
		if (empty($sortDirection)) $ord = 'DESC ';
		$queryStr .=  'ORDER BY ' . $orderKey . $ord . 'LIMIT ' . $limit . ' OFFSET ' . $offset;	// 画像アップロード日時順
		$this->selectLoop($queryStr, array(), $callback, $urlOthers);
	}
	/**
	 * 写真一覧を取得(公開用)
	 *
	 * @param int		$limit				取得する項目数
	 * @param int		$no					項目番号
	 * @param string	$langId				言語
	 * @param timestamp	$startDt			期間(開始日)
	 * @param timestamp	$endDt				期間(終了日)
	 * @param array		$keywords			検索キーワード
	 * @param array		$category			カテゴリーID
	 * @param array		$author				撮影者
	 * @param string	$sortKey			ソートキー(index=表示順,date=日付,rate=評価,ref=参照数)
	 * @param int		$sortDirection		取得順(0=降順,1=昇順)
	 * @param function	$callback			コールバック関数
	 * @param string    $urlOthers			URL付加パラメータ
	 * @param array     $authCategory		参照可能なカテゴリー
	 * @return 			なし
	 */
	function searchPhotoItemByNo($limit, $no, $langId, $startDt, $endDt, $keywords, $category, $author, $sortKey, $sortDirection, $callback, $urlOthers, $authCategory = null)
	{
		$offset = $no -1;
		if ($offset < 0) $offset = 0;

		// クエリー文字列作成
		$queryStr = $this->_createSearchPhoto($startDt, $endDt, $keywords, $category, $author, $authCategory);
		
		// 日付範囲
		/*
		if (!empty($startDt)){
			$queryStr .=    'AND ? <= ht_regist_dt ';
			$params[] = $startDt;
		}
		if (!empty($endDt)){
			$queryStr .=    'AND ht_regist_dt < ? ';
			$params[] = $endDt;
		}*/
/*		$queryStr  = 'SELECT DISTINCT ht_public_id,ht_name,ht_rate_average,ht_view_count,ht_regist_dt FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		if (!empty($category) || !empty($authCategory)) $queryStr .=  'RIGHT JOIN photo_with_category ON ht_serial = hw_photo_serial ';
		$queryStr .=  'WHERE ht_deleted = false ';		// 未削除
		$queryStr .=    'AND ht_visible = true ';		// 公開中
		
		// カテゴリー
		if (!empty($category)){
			$idStr = '';
			for ($i = 0; $i < count($category); $i++){
				$idStr .= addslashes($category[$i]) . ',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND hw_category_id in (' . $idStr . ') ';
		}
		// 参照可能なカテゴリー
		if (!empty($authCategory)){
			$queryStr .=  'AND (';
			for ($i = 0; $i < count($authCategory); $i++){
				$queryStr .=  'hw_category_id = ' . addslashes($authCategory[$i]) . ' ';
				if ($i < count($authCategory) -1) $queryStr .=  'OR ';
			}
			$queryStr .=  ') ';
		}
		// キーワード検索
		if (!empty($keywords)){
			for ($i = 0; $i < count($keywords); $i++){
				$keyword = addslashes($keywords[$i]);// 「'"\」文字をエスケープ
				$queryStr .=    'AND (ht_public_id LIKE \'%' . $keyword . '%\' ';		// 公開用画像ID
				$queryStr .=    'OR ht_name LIKE \'%' . $keyword . '%\' ';		// 画像タイトル
				$queryStr .=    'OR ht_camera LIKE \'%' . $keyword . '%\' ';		// カメラ
				$queryStr .=    'OR ht_location LIKE \'%' . $keyword . '%\' ';		// 撮影場所
				$queryStr .=    'OR ht_description LIKE \'%' . $keyword . '%\' ';		// メモ
				$queryStr .=    'OR ht_keyword LIKE \'%' . $keyword . '%\' ';		// キーワード
				$queryStr .=    'OR lu_name LIKE \'%' . $keyword . '%\') ';	// 撮影者
			}
		}
		// 撮影者
		if (!empty($author)){
			$idStr = '';
			for ($i = 0; $i < count($author); $i++){
				$idStr .= '\'' . addslashes($author[$i]) . '\',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND lu_account in (' . $idStr . ') ';
		}*/
		// ソート順
		switch ($sortKey){
			case 'index':	// 表示順
			default:
				$orderKey = 'ht_sort_order ';
				if (empty($sortDirection)) $orderKey .= 'DESC ';
				$orderKey .= ',ht_regist_dt ';
				break;
			case 'date':		// 日付
				$orderKey = 'ht_regist_dt ';
				break;
			case 'rate':		// 評価
				$orderKey = 'ht_rate_average ';
				break;
			case 'ref':			// 参照数
				$orderKey = 'ht_view_count ';
				break;
		}
		$ord = '';
		if (empty($sortDirection)) $ord = 'DESC ';
		$queryStr .=  'ORDER BY ' . $orderKey . $ord . 'LIMIT ' . $limit . ' OFFSET ' . $offset;	// 画像アップロード日時順
		$this->selectLoop($queryStr, array(), $callback, $urlOthers);
	}
	/**
	 * 写真一覧数を取得(公開用)
	 *
	 * @param string	$langId				言語
	 * @param timestamp	$startDt			期間(開始日)
	 * @param timestamp	$endDt				期間(終了日)
	 * @param array		$keywords			検索キーワード
	 * @param array		$category			カテゴリーID
	 * @param array		$author				撮影者
	 * @param array     $authCategory		参照可能なカテゴリー
	 * @return 			なし
	 */
	function searchPhotoItemCount($langId, $startDt, $endDt, $keywords, $category, $author, $authCategory = null)
	{
		// クエリー文字列作成
		$queryStr = $this->_createSearchPhoto($startDt, $endDt, $keywords, $category, $author, $authCategory);
		// 日付範囲
		/*
		if (!empty($startDt)){
			$queryStr .=    'AND ? <= ht_regist_dt ';
			$params[] = $startDt;
		}
		if (!empty($endDt)){
			$queryStr .=    'AND ht_regist_dt < ? ';
			$params[] = $endDt;
		}*/
		/*
		$queryStr  = 'SELECT DISTINCT ht_public_id,ht_name,ht_rate_average,ht_view_count,ht_regist_dt FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		if (!empty($category) || !empty($authCategory)) $queryStr .=  'RIGHT JOIN photo_with_category ON ht_serial = hw_photo_serial ';
		$queryStr .=  'WHERE ht_deleted = false ';		// 未削除
		$queryStr .=    'AND ht_visible = true ';		// 公開中
		
		// カテゴリー
		if (!empty($category)){
			$idStr = '';
			for ($i = 0; $i < count($category); $i++){
				$idStr .= addslashes($category[$i]) . ',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND hw_category_id in (' . $idStr . ') ';
		}
		// 参照可能なカテゴリー
		if (!empty($authCategory)){
			$queryStr .=  'AND (';
			for ($i = 0; $i < count($authCategory); $i++){
				$queryStr .=  'hw_category_id = ' . addslashes($authCategory[$i]) . ' ';
				if ($i < count($authCategory) -1) $queryStr .=  'OR ';
			}
			$queryStr .=  ') ';
		}
		// キーワード検索
		if (!empty($keywords)){
			for ($i = 0; $i < count($keywords); $i++){
				$keyword = addslashes($keywords[$i]);		// 「'"\」文字をエスケープ
				$queryStr .=    'AND (ht_public_id LIKE \'%' . $keyword . '%\' ';		// 公開用画像ID
				$queryStr .=    'OR ht_name LIKE \'%' . $keyword . '%\' ';		// 画像タイトル
				$queryStr .=    'OR ht_camera LIKE \'%' . $keyword . '%\' ';		// カメラ
				$queryStr .=    'OR ht_location LIKE \'%' . $keyword . '%\' ';		// 撮影場所
				$queryStr .=    'OR ht_description LIKE \'%' . $keyword . '%\' ';		// メモ
				$queryStr .=    'OR ht_keyword LIKE \'%' . $keyword . '%\' ';		// キーワード
				$queryStr .=    'OR lu_name LIKE \'%' . $keyword . '%\') ';	// 撮影者
			}
		}
		// 撮影者
		if (!empty($author)){
			$idStr = '';
			for ($i = 0; $i < count($author); $i++){
				$idStr .= '\'' . addslashes($author[$i]) . '\',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND lu_account in (' . $idStr . ') ';
		}*/
		return $this->selectRecordCount($queryStr, array());
	}
	/**
	 * 画像検索用クエリー作成
	 *
	 * @param timestamp	$startDt			期間(開始日)
	 * @param timestamp	$endDt				期間(終了日)
	 * @param array		$keywords			検索キーワード
	 * @param array		$category			カテゴリーID
	 * @param array		$author				撮影者
	 * @param array     $authCategory		参照可能なカテゴリー
	 * @return string				クエリー文字列
	 */
	function _createSearchPhoto($startDt, $endDt, $keywords, $category, $author, $authCategory)
	{
		// 日付範囲
		/*
		if (!empty($startDt)){
			$queryStr .=    'AND ? <= ht_regist_dt ';
			$params[] = $startDt;
		}
		if (!empty($endDt)){
			$queryStr .=    'AND ht_regist_dt < ? ';
			$params[] = $endDt;
		}*/
		$queryStr  = 'SELECT DISTINCT ht_public_id,ht_name,ht_rate_average,ht_view_count,ht_regist_dt FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		if (!empty($category) || !empty($authCategory)){
			$tableStr = 'photo_with_category AS ta ';
			$condStr = 'ht_serial = ta.hw_photo_serial ';
			for ($i = 0; $i < count($category); $i++){
				$categoryLine = $category[$i];
				if (empty($categoryLine)) break;
				$tableStr .= 'CROSS JOIN photo_with_category AS t' . $i . ' ';
				$condStr .= 'AND ht_serial = t' . $i . '.hw_photo_serial ';
			}
			//$queryStr .=  'RIGHT JOIN (photo_with_category AS ta CROSS JOIN photo_with_category AS t2) ON ht_serial = ta.hw_photo_serial AND ht_serial = t2.hw_photo_serial ';
			$queryStr .=  'RIGHT JOIN (' . $tableStr . ') ON '. $condStr;
		}
		$queryStr .=  'WHERE ht_deleted = false ';		// 未削除
		$queryStr .=    'AND ht_visible = true ';		// 公開中
		
		// ##### アクセス制限 #####
		// 参照可能なカテゴリー
		if (!empty($authCategory)){
			$queryStr .=  'AND (';
			for ($i = 0; $i < count($authCategory); $i++){
				$queryStr .=  'ta.hw_category_id = ' . addslashes($authCategory[$i]) . ' ';
				if ($i < count($authCategory) -1) $queryStr .=  'OR ';
			}
			$queryStr .=  ') ';
		}
		
		// ##### 検索条件 #####
		// カテゴリー
		if (!empty($category)){
/*			$idStr = '';
			for ($i = 0; $i < count($category); $i++){
				$idStr .= addslashes($category[$i]) . ',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND t2.hw_category_id in (' . $idStr . ') ';*/
			for ($i = 0; $i < count($category); $i++){
				$categoryLine = $category[$i];
				if (empty($categoryLine)) break;
				$idStr = '';
				for ($j = 0; $j < count($categoryLine); $j++){
					$idStr .= addslashes($categoryLine[$j]) . ',';
				}
				$idStr = rtrim($idStr, ',');
				$queryStr .=  'AND t' . $i . '.hw_category_id in (' . $idStr . ') ';
			}
		}
		// キーワード検索
		if (!empty($keywords)){
			for ($i = 0; $i < count($keywords); $i++){
				$keyword = addslashes($keywords[$i]);		// 「'"\」文字をエスケープ
				$queryStr .=    'AND (ht_public_id LIKE \'%' . $keyword . '%\' ';		// 公開用画像ID
				$queryStr .=    'OR ht_name LIKE \'%' . $keyword . '%\' ';		// 画像タイトル
				$queryStr .=    'OR ht_camera LIKE \'%' . $keyword . '%\' ';		// カメラ
				$queryStr .=    'OR ht_location LIKE \'%' . $keyword . '%\' ';		// 撮影場所
				$queryStr .=    'OR ht_description LIKE \'%' . $keyword . '%\' ';		// 説明
				$queryStr .=    'OR ht_summary LIKE \'%' . $keyword . '%\' ';		// 概要
				$queryStr .=    'OR ht_keyword LIKE \'%' . $keyword . '%\' ';		// キーワード
				$queryStr .=    'OR lu_name LIKE \'%' . $keyword . '%\') ';	// 撮影者
			}
		}
		// 撮影者
		if (!empty($author)){
			$idStr = '';
			for ($i = 0; $i < count($author); $i++){
				$idStr .= '\'' . addslashes($author[$i]) . '\',';
			}
			$idStr = rtrim($idStr, ',');
			$queryStr .=  'AND lu_account in (' . $idStr . ') ';
		}
		return $queryStr;
	}
	/**
	 * 画像情報を取得(公開用)
	 *
	 * @param string $filename		ファイル名
	 * @param string $lang			言語ID
	 * @param array $row			取得データ
	 * @param array $categoryRows	画像カテゴリー
	 * @return						true=正常、false=異常
	 */
	function getSearchPhotoInfo($filename, $lang, &$row, &$categoryRows = array())
	{
		$queryStr  = 'SELECT * FROM photo LEFT JOIN _login_user ON ht_owner_id = lu_id AND lu_deleted = false ';
		$queryStr .=   'WHERE ht_deleted = false ';
		$queryStr .=     'AND ht_visible = true ';		// 公開中
		$queryStr .=     'AND ht_public_id = ? ';
		$queryStr .=     'AND ht_language_id = ? ';
		$ret = $this->selectRecord($queryStr, array($filename, $lang), $row);
		
		if ($ret){
			// 画像カテゴリー
			$queryStr  = 'SELECT * FROM photo_with_category LEFT JOIN photo_category ON hw_category_id = hc_id AND hc_deleted = false ';
			$queryStr .=   'WHERE hw_photo_serial = ? ';
			$queryStr .=  'ORDER BY hw_index ';
			$this->selectRecords($queryStr, array($row['ht_serial']), $categoryRows);
		}
		return $ret;
	}
	/**
	 * 画像カテゴリー一覧を取得
	 *
	 * @param string	$langId				言語
	 * @param array		$rows				取得データ
	 * @return bool							true=取得、false=取得せず
	 */
	function getAllCategory($langId, &$rows)
	{
		$queryStr  = 'SELECT * FROM photo_category LEFT JOIN _login_user ON hc_create_user_id = lu_id AND lu_deleted = false ';
		$queryStr .=   'WHERE hc_language_id = ? ';
		$queryStr .=     'AND hc_deleted = false ';		// 削除されていない
		$queryStr .=   'ORDER BY hc_id';
		$retValue = $this->selectRecords($queryStr, array($langId), $rows);
		return $retValue;
	}
	/**
	 * 画像カテゴリーの更新
	 *
	 * @param int        $serial		写真情報シリアル番号
	 * @param int        $index			インデックス番号
	 * @param int        $categoryId	カテゴリーID
	 * @return bool		 true = 成功、false = 失敗
	 */
	function updateCategory($serial, $index, $categoryId)
	{
		// 新規レコード追加
		$queryStr  = 'INSERT INTO photo_with_category ';
		$queryStr .=   '(';
		$queryStr .=   'hw_photo_serial, ';
		$queryStr .=   'hw_index, ';
		$queryStr .=   'hw_category_id) ';
		$queryStr .=   'VALUES ';
		$queryStr .=   '(?, ?, ?)';
		$ret =$this->execStatement($queryStr, array($serial, $index, $categoryId));
		return $ret;
	}
	/**
	 * コンテンツ項目を外部用キーで取得
	 *
	 * @param string	$key				外部用キー
	 * @param string	$langId				言語ID
	 * @param array     $row				レコード
	 * @return bool							取得 = true, 取得なし= false
	 */
	function getContentByKey($key, $langId, &$row)
	{
		$queryStr  = 'SELECT * FROM content ';
		$queryStr .=   'WHERE cn_deleted = false ';	// 削除されていない
		$queryStr .=   'AND cn_type = ? ';
		$queryStr .=   'AND cn_key = ? ';
		$queryStr .=   'AND cn_language_id = ? ';
		$ret = $this->selectRecord($queryStr, array(''/*デフォルトコンテンツ*/, $key, $langId), $row);
		return $ret;
	}
	/**
	 * コンテンツのアクセス権を取得
	 *
	 * @param int	 $userId		ユーザID
	 * @param strin  $contentType	コンテンツタイプ
	 * @param int	 $contentId		コンテンツID
	 * @param array     $row				レコード
	 * @return bool							取得 = true, 取得なし= false
	 */
	function getContentAccess($userId, $contentType, $contentId, &$row)
	{
		$queryStr  = 'SELECT * FROM _content_access ';
		$queryStr .=   'WHERE cs_user_id = ? ';
		$queryStr .=   'AND cs_content_type = ? ';
		$queryStr .=   'AND cs_content_id = ? ';
		$ret = $this->selectRecord($queryStr, array($userId, $contentType, $contentId), $row);
		return $ret;
	}
	/**
	 * クライアント設定値を取得
	 *
	 * @param string $clientId		クライアントID
	 * @param string $widgetId		ウィジェットID
	 * @return string				値
	 */
	function getClientParam($clientId, $widgetId)
	{
		$retValue = '';
		$queryStr  = 'SELECT * FROM _client_param ';
		$queryStr .=   'WHERE cp_id  = ? ';
		$queryStr .=     'AND cp_widget_id = ? ';
		$ret = $this->selectRecord($queryStr, array($clientId, $widgetId), $row);
		if ($ret) $retValue = $row['cp_param'];
		return $retValue;
	}
	/**
	 * クライアント設定値を更新
	 *
	 * @param string $clientId		クライアントID
	 * @param string $widgetId		ウィジェットID
	 * @param string $value			値
	 * @return						true = 正常、false=異常
	 */
	function updateClientParam($clientId, $widgetId, $value)
	{
		$now = date("Y/m/d H:i:s");	// 現在日時
		$ip = $this->gRequest->trimServerValueOf('REMOTE_ADDR');
		
		// データの確認
		$queryStr  = 'SELECT cp_param FROM _client_param ';
		$queryStr .=   'WHERE cp_id  = ? ';
		$queryStr .=     'AND cp_widget_id = ? ';
		$ret = $this->isRecordExists($queryStr, array($clientId, $widgetId));
		if ($ret){
			$queryStr  = 'UPDATE _client_param ';
			$queryStr .=   'SET cp_param = ?, ';
			$queryStr .=     'cp_ip = ?, ';
			$queryStr .=     'cp_update_dt = ? ';
			$queryStr .=   'WHERE cp_id  = ? ';
			$queryStr .=     'AND cp_widget_id = ? ';
			$ret = $this->execStatement($queryStr, array($value, $ip, $now, $clientId, $widgetId));
			return $ret;
		} else {
			$queryStr  = 'INSERT INTO _client_param ';
			$queryStr .=   '(cp_id, cp_widget_id, cp_param, cp_ip, cp_update_dt) VALUES (?, ?, ?, ?, ?)';
			$ret = $this->execStatement($queryStr, array($clientId, $widgetId, $value, $ip, $now));
			return $ret;
		}
	}
	/**
	 * すべての商品一覧を取得
	 *
	 * @param string	$lang				言語
	 * @param function	$callback			コールバック関数
	 * @return 			なし
	 */
	function getAllProduct($lang, $callback)
	{
		$queryStr = 'SELECT * FROM photo_product ';
		$queryStr .=  'WHERE hp_deleted = false ';// 削除されていない
		$queryStr .=    'AND hp_language_id = ? ';
		$queryStr .=  'ORDER BY hp_sort_order';
		$this->selectLoop($queryStr, array($lang), $callback);
	}
	/**
	 * 商品をシリアル番号で取得
	 *
	 * @param int		$serial				シリアル番号
	 * @param array     $row				レコード
	 * @param array     $row2				商品価格
	 * @param array     $row3				商品画像
	 * @return bool							取得 = true, 取得なし= false
	 */
	function getProductBySerial($serial, &$row, &$row2, &$row3)
	{
		$queryStr  = 'SELECT * FROM photo_product ';
		$queryStr .=   'WHERE hp_serial = ? ';
		$ret = $this->selectRecord($queryStr, array($serial), $row);
		if ($ret){
			$queryStr  = 'SELECT * FROM product_price ';
			$queryStr .=   'WHERE pp_deleted = false ';// 削除されていない
			$queryStr .=     'AND pp_product_class = ? ';		// 商品クラス
			$queryStr .=     'AND pp_product_id = ? ';			// 商品ID(画像ID)
			$queryStr .=     'AND pp_product_type_id = ? ';		// 商品タイプ
			$queryStr .=     'AND pp_language_id = ? ';
			$this->selectRecords($queryStr, array(self::PRODUCT_CLASS_PHOTO/*フォト画像クラス*/, 0/*全画像対象*/, $row['hp_id'], $row['hp_language_id']), $row2);
			
			$queryStr  = 'SELECT * FROM product_image ';
			$queryStr .=   'WHERE im_deleted = false ';// 削除されていない
			$queryStr .=     'AND im_product_class = ? ';		// 商品クラス
			$queryStr .=     'AND im_type = 0 ';		// デフォルト画像タイプ
			$queryStr .=     'AND im_id = ? ';
			$queryStr .=     'AND im_language_id = ? ';
			$this->selectRecords($queryStr, array(self::PRODUCT_CLASS_PHOTO/*フォト画像クラス*/, $row['hp_id'], $row['hp_language_id']), $row3);
		}
		return $ret;
	}
	/**
	 * 商品を商品ID、言語IDで取得
	 *
	 * @param int		$id					商品ID
	 * @param string	$langId				言語ID
	 * @param array     $row				レコード
	 * @return bool							取得 = true, 取得なし= false
	 */
	function getProductByProductId($id, $langId, &$row)
	{
		$queryStr  = 'SELECT * FROM photo_product ';
		$queryStr .=   'WHERE hp_deleted = false ';	// 削除されていない
		$queryStr .=     'AND hp_id = ? ';
		$queryStr .=     'AND hp_language_id = ? ';
		$ret = $this->selectRecord($queryStr, array($id, $langId), $row);
		return $ret;
	}
}
?>
