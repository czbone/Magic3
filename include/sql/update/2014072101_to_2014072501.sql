-- *
-- * バージョンアップ用スクリプト
-- *
-- * PHP versions 5
-- *
-- * LICENSE: This source file is licensed under the terms of the GNU General Public License.
-- *
-- * @package    Magic3 Framework
-- * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
-- * @copyright  Copyright 2006-2014 Magic3 Project.
-- * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
-- * @version    SVN: $Id$
-- * @link       http://www.magic3.org
-- *
-- --------------------------------------------------------------------------------------------------
-- バージョンアップ用スクリプト
-- --------------------------------------------------------------------------------------------------

-- *** システムベーステーブル ***
-- 追加クラスマスター
INSERT INTO _addons
(ao_id,     ao_class_name, ao_name,            ao_description, ao_opelog_hook) VALUES
('wikilib', 'wikiLib',     'Wikiライブラリ', '', false);

-- テンプレート情報
DELETE FROM _templates WHERE tm_id = 'bootstrap_cerulean_head';
INSERT INTO _templates
(tm_id,                           tm_name,                         tm_type, tm_device_type, tm_mobile, tm_use_bootstrap, tm_available, tm_clean_type, tm_create_dt) VALUES
('bootstrap_cerulean_head',             'bootstrap_cerulean_head',             10,       0,              false,     true,             true,        0,             now());

-- *** システム標準テーブル ***
