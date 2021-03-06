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
 * @copyright  Copyright 2006-2014 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id$
 * @link       http://www.magic3.org
 */
require_once($gEnvManager->getCurrentWidgetContainerPath() .	'/admin_mainBaseWidgetContainer.php');
require_once($gEnvManager->getCurrentWidgetDbPath() . '/admin_mainDb.php');

class admin_mainConditionBaseWidgetContainer extends admin_mainBaseWidgetContainer
{
	protected $_mainDb;
	protected $_openBy;				// ウィンドウオープンタイプ
	const TASK_BASE_NAME = '運用状況';			// 機能のベース名
	const TASK_CALC		= 'analyzecalc';		// 集計
	const TASK_GRAPH	= 'analyzegraph';		// グラフ表示
	const TASK_OPELOG	= 'opelog';			// 運用ログ一覧
	const TASK_OPELOG_DETAIL 	= 'opelog_detail';		// 運用ログ詳細
	const TASK_ACCESSLOG		= 'accesslog';				// アクセスログ一覧
	const TASK_ACCESSLOG_DETAIL	= 'accesslog_detail';		// アクセスログ詳細
	const TASK_SEARCHWORDLOG	= 'searchwordlog';				// 検索語ログ一覧
	const TASK_SEARCHWORDLOG_DETAIL	= 'searchwordlog_detail';		// 検索語ログ詳細
	const TASK_AWSTATS		= 'awstats';		// Awstats表示
	const DEFAULT_TOP_PAGE = 'accesslog';		// デフォルトのトップ画面
	const CF_AWSTATS_DATA_PATH = 'awstats_data_path';		// Awstatsデータパス
	
	/**
	 * コンストラクタ
	 */
	function __construct()
	{
		// 親クラスを呼び出す
		parent::__construct();
		
		$this->_mainDb = new admin_mainDb();
	}
	/**
	 * テンプレートに前処理
	 *
	 * _setTemplate()で指定したテンプレートファイルにデータを埋め込む。
	 *
	 * @param RequestManager $request		HTTPリクエスト処理クラス
	 * @param object         $param			任意使用パラメータ。_setTemplate()と共有。
	 * @return								なし
	 */
	function _preAssign($request, &$param)
	{
		$this->_openBy = $request->trimValueOf(M3_REQUEST_PARAM_OPEN_BY);		// ウィンドウオープンタイプ
		if (!empty($this->_openBy)) $this->addOptionUrlParam(M3_REQUEST_PARAM_OPEN_BY, $this->_openBy);
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
	function _postAssign($request, &$param)
	{
		if ($this->_openBy == 'simple') return;			// シンプルウィンドウのときはメニューを表示しない
		
		// 表示画面を決定
		$task = $request->trimValueOf(M3_REQUEST_PARAM_OPERATION_TASK);
		if (empty($task)) $task = self::DEFAULT_TOP_PAGE;
		
		// パンくずリストを作成
		switch ($task){
			case self::TASK_OPELOG:			// 運用ログ一覧
			case self::TASK_OPELOG_DETAIL:		// 運用ログ詳細
				$linkList = ' &gt;&gt; ログ &gt;&gt; 運用ログ';
				break;
			case self::TASK_ACCESSLOG:				// アクセスログ一覧
			case self::TASK_ACCESSLOG_DETAIL:		// アクセスログ詳細
				$linkList = ' &gt;&gt; ログ &gt;&gt; アクセスログ';
				break;
			case self::TASK_SEARCHWORDLOG:				// 検索語ログ一覧
			case self::TASK_SEARCHWORDLOG_DETAIL:		// 検索語ログ詳細
				$linkList = ' &gt;&gt; ログ &gt;&gt; 検索キーワード';
				break;
			case self::TASK_AWSTATS:		// Awstats表示
				$linkList = ' &gt;&gt; アクセス数 &gt;&gt; Awstats';
				break;
			case self::TASK_GRAPH:	// グラフ表示
				$linkList = ' &gt;&gt; アクセス数 &gt;&gt; グラフ表示';
				break;
			case self::TASK_CALC:	// 集計
				$linkList = ' &gt;&gt; アクセス数 &gt;&gt; 集計';
				break;
		}
				
		// ####### 上段メニューの作成 #######
		$menuText = '<div id="configmenu-upper">' . M3_NL;
		$menuText .= '<ul>' . M3_NL;

		// ### ログ ###
		$current = '';
		$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_ACCESSLOG;
		if ($task == self::TASK_ACCESSLOG ||				// アクセスログ一覧
			$task == self::TASK_ACCESSLOG_DETAIL ||		// アクセスログ詳細
			$task == self::TASK_OPELOG ||			// 運用ログ一覧
			$task == self::TASK_OPELOG_DETAIL ||		// 運用ログ詳細
			$task == self::TASK_SEARCHWORDLOG ||				// 検索語ログ一覧
			$task == self::TASK_SEARCHWORDLOG_DETAIL ||	// 検索語ログ詳細
			$task == self::TASK_AWSTATS){		// Awstats表示
			$current = 'id="current"';
		}
		// ヘルプを作成
		$helpText = '';
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>ログ</span></a></li>' . M3_NL;
		
		// ### アクセス数 ###
		$current = '';
		$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_GRAPH;
		if ($task == self::TASK_GRAPH ||	// グラフ表示
			$task == self::TASK_CALC){		// 集計
			$current = 'id="current"';
		}

		// ヘルプを作成
		$helpText = '';
		$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>アクセス数</span></a></li>' . M3_NL;
		
		// 上段メニュー終了
		$menuText .= '</ul>' . M3_NL;
		$menuText .= '</div>' . M3_NL;
		
		// ####### 下段メニューの作成 #######		
		$menuText .= '<div id="configmenu-lower">' . M3_NL;
		$menuText .= '<ul>' . M3_NL;

		if ($task == self::TASK_ACCESSLOG ||				// アクセスログ一覧
			$task == self::TASK_ACCESSLOG_DETAIL ||		// アクセスログ詳細
			$task == self::TASK_OPELOG ||			// 運用ログ一覧
			$task == self::TASK_OPELOG_DETAIL ||		// 運用ログ詳細
			$task == self::TASK_SEARCHWORDLOG ||				// 検索語ログ一覧
			$task == self::TASK_SEARCHWORDLOG_DETAIL ||		// 検索語ログ詳細
			$task == self::TASK_AWSTATS){		// Awstats表示
			
			// ### アクセスログ ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_ACCESSLOG;				// アクセスログ
			if ($task == self::TASK_ACCESSLOG || $task == self::TASK_ACCESSLOG_DETAIL) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_ACCESSLOG);
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>アクセスログ</span></a></li>' . M3_NL;
			
			// ### 運用ログ ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_OPELOG;			// 運用ログ
			if ($task == self::TASK_OPELOG || $task == self::TASK_OPELOG_DETAIL) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_OPELOG);
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>運用ログ</span></a></li>' . M3_NL;
			
			// ### 検索キーワード ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_SEARCHWORDLOG;			// 検索キーワード
			if ($task == self::TASK_SEARCHWORDLOG || $task == self::TASK_SEARCHWORDLOG_DETAIL) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_SEARCHWORDLOG);
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>検索キーワード</span></a></li>' . M3_NL;
			
			// ### Awstats ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_AWSTATS;			// Awstats
			if ($task == self::TASK_AWSTATS) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_AWSTATS);
			$isAwstats = $this->isExistsAwstats();
			if ($isAwstats){		// Awstatsが使用可能な場合
				$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>Awstats</span></a></li>' . M3_NL;
			} else {
				$menuText .= '<li ' . $current . '><a href="javascript:void(0);"><span ' . $helpText . '>Awstats</span></a></li>' . M3_NL;
			}
		} else if ($task == self::TASK_GRAPH ||	// グラフ表示
					$task == self::TASK_CALC){		// 集計

			// ### グラフ表示 ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_GRAPH;			// グラフ表示
			if ($task == self::TASK_GRAPH) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_GRAPH);
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>グラフ表示</span></a></li>' . M3_NL;
			
			// ### 集計 ###
			$current = '';
			$link = $this->gEnv->getDefaultAdminUrl() . '?' . 'task=' . self::TASK_CALC;			// 集計
			if ($task == self::TASK_CALC) $current = 'id="current"';
			// ヘルプを作成
			$helpText = $this->gInstance->getHelpManager()->getHelpText(self::TASK_CALC);
			$menuText .= '<li ' . $current . '><a href="'. $this->getUrl($link) .'"><span ' . $helpText . '>集計</span></a></li>' . M3_NL;
		}
		
		// 下段メニュー終了
		$menuText .= '</ul>' . M3_NL;
		$menuText .= '</div>' . M3_NL;
		
		// 作成データの埋め込み
		$linkList = '<div id="configmenu-top"><label>' . self::TASK_BASE_NAME . $linkList . '</div>';
		$outputText .= '<table width="90%"><tr><td>' . $linkList . $menuText . '</td></tr></table>' . M3_NL;
		$this->tmpl->addVar("_widget", "menu_items", $outputText);
	}
	/**
	 * Awstatsの作成データが参照できるかどうか
	 *
	 * @return bool		true=参照可、false=参照不可
	 */
	function isExistsAwstats()
	{
		$awstatsDataPath = $this->getAwstatsPath();
		if (is_dir($awstatsDataPath)){
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Awstatsの作成データのパスを取得
	 *
	 * @return string		パス
	 */
	function getAwstatsPath()
	{
		$path = $this->gSystem->getSystemConfig(self::CF_AWSTATS_DATA_PATH);
		if (empty($path)) return '';
		
		$awstatsDataPath = rel2abs($path, $this->gEnv->getSystemRootPath());
		return $awstatsDataPath;
	}
	/**
	 * Awstatsの作成データのURLを取得
	 *
	 * @return string		URL
	 */
	function getAwstatsUrl()
	{
		$path = $this->gSystem->getSystemConfig(self::CF_AWSTATS_DATA_PATH);
		if (empty($path)) return '';
		
		$awstatsDataPath = rel2abs($path, $this->gEnv->getRootUrl());
		return $awstatsDataPath;
	}
}
?>
