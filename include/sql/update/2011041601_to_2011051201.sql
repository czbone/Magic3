-- *
-- * バージョンアップ用スクリプト
-- *
-- * PHP versions 5
-- *
-- * LICENSE: This source file is licensed under the terms of the GNU General Public License.
-- *
-- * @package    Magic3 Framework
-- * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
-- * @copyright  Copyright 2006-2011 Magic3 Project.
-- * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
-- * @version    SVN: $Id: 2011041601_to_2011051201.sql 4129 2011-05-11 23:20:44Z fishbone $
-- * @link       http://www.magic3.org
-- *
-- --------------------------------------------------------------------------------------------------
-- バージョンアップ用スクリプト
-- --------------------------------------------------------------------------------------------------

-- *** バージョン2.0管理画面 ***
-- ページ定義マスター
DELETE FROM _page_def WHERE pd_id = 'admin_index';
INSERT INTO _page_def
(pd_id,         pd_sub_id,      pd_position_id, pd_index, pd_widget_id,   pd_config_id, pd_visible, pd_editable, pd_title_visible) VALUES
('admin_index', '',             'top',          1,        'admin_menu3',  0,            true,       false, false),
('admin_index', 'front',        'main',         1,        'admin_main',   0,            true,       false, false),
('admin_index', 'front',        'main',         2,        'admin/analytics',   0,            true,       true, false),
('admin_index', 'front',        'main',         3,        'admin/opelog',   0,            true,       true, false),
('admin_index', 'front',        'left',         1,        'admin/loginuser',   0,            true,       true, true),
('admin_index', 'content',      'main',         1,        'admin_main',   0,            true,       false, false);

-- ページ情報マスター
DELETE FROM _page_info WHERE pn_id = 'admin_index';
INSERT INTO _page_info
(pn_id,         pn_sub_id, pn_content_type, pn_use_ssl) VALUES
('admin_index', 'front',   'dboard',        false);

-- 管理画面ページデータ(デフォルトを変更)
UPDATE _page_id SET pg_default_sub_id = 'content' WHERE pg_id = 'admin_index' AND pg_type = 0;

-- テンプレート情報
DELETE FROM _templates WHERE tm_id = '_admin2';
INSERT INTO _templates
(tm_id,           tm_name,                    tm_type, tm_device_type, tm_mobile, tm_clean_type, tm_create_dt) VALUES
('_admin2',       '_admin2',                  1,       0,              false,     0,             now());

-- システム設定マスター(管理画面用デフォルトテンプレートを変更)
UPDATE _system_config SET sc_value = '_admin2' WHERE sc_id = 'admin_default_template';
