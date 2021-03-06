-- *
-- * バージョンアップ用スクリプト
-- *
-- * PHP versions 5
-- *
-- * LICENSE: This source file is licensed under the terms of the GNU General Public License.
-- *
-- * @package    Magic3 Framework
-- * @author     平田直毅(Naoki Hirata) <naoki@aplo.co.jp>
-- * @copyright  Copyright 2006-2012 Magic3 Project.
-- * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
-- * @version    SVN: $Id: 2012121101_to_2013010901.sql 5549 2013-01-14 12:34:27Z fishbone $
-- * @link       http://www.magic3.org
-- *
-- --------------------------------------------------------------------------------------------------
-- バージョンアップ用スクリプト
-- --------------------------------------------------------------------------------------------------

-- *** システムベーステーブル ***
-- 汎用コンテンツマスター
ALTER TABLE content ADD cn_regist_dt         TIMESTAMP      DEFAULT '0000-00-00 00:00:00' NOT NULL;      -- 登録日時

-- 多言語対応文字列マスター
DELETE FROM _language_string WHERE ls_type = 10;
INSERT INTO _language_string
(ls_type, ls_id,                           ls_language_id, ls_value,             ls_description) VALUES
(10,      'COM_CONTENT_CREATED_DATE_ON',   'ja',           '作成日：%s',         ''),
(10,      'COM_CONTENT_LAST_UPDATED',      'ja',           '更新日：%s',         ''),
(10,      'COM_CONTENT_PUBLISHED_DATE_ON', 'ja',           '公開日：%s',         ''),
(10,      'COM_CONTENT_WRITTEN_BY',        'ja',           '作者：%s',           ''),
(10,      'COM_CONTENT_CATEGORY',          'ja',           'カテゴリー：%s',     ''),
(10,      'COM_CONTENT_ARTICLE_HITS',      'ja',           'アクセス数：%s',     ''),
(10,      'COM_CONTENT_READ_MORE_TITLE',   'ja',           'もっと読む',         ''),
(10,      'DATE_FORMAT_LC',                'ja',           'Y年Fd日（l）',       ''),
(10,      'DATE_FORMAT_LC1',               'ja',           'Y年Fd日（l）',       ''),
(10,      'DATE_FORMAT_LC2',               'ja',           'Y年Fd日（l）H:i',    ''),
(10,      'DATE_FORMAT_LC3',               'ja',           'Y年Fd日',            ''),
(10,      'DATE_FORMAT_LC4',               'ja',           'Y-m-d',              ''),
(10,      'DATE_FORMAT_JS1',               'ja',           'y-m-d',              ''),
(10,      'JANUARY_SHORT',                 'ja',           '1月',                ''),
(10,      'JANUARY',                       'ja',           '1月',                ''),
(10,      'FEBRUARY_SHORT',                'ja',           '2月',                ''),
(10,      'FEBRUARY',                      'ja',           '2月',                ''),
(10,      'MARCH_SHORT',                   'ja',           '3月',                ''),
(10,      'MARCH',                         'ja',           '3月',                ''),
(10,      'APRIL_SHORT',                   'ja',           '4月',                ''),
(10,      'APRIL',                         'ja',           '4月',                ''),
(10,      'MAY_SHORT',                     'ja',           '5月',                ''),
(10,      'MAY',                           'ja',           '5月',                ''),
(10,      'JUNE_SHORT',                    'ja',           '6月',                ''),
(10,      'JUNE',                          'ja',           '6月',                ''),
(10,      'JULY_SHORT',                    'ja',           '7月',                ''),
(10,      'JULY',                          'ja',           '7月',                ''),
(10,      'AUGUST_SHORT',                  'ja',           '8月',                ''),
(10,      'AUGUST',                        'ja',           '8月',                ''),
(10,      'SEPTEMBER_SHORT',               'ja',           '9月',                ''),
(10,      'SEPTEMBER',                     'ja',           '9月',                ''),
(10,      'OCTOBER_SHORT',                 'ja',           '10月',               ''),
(10,      'OCTOBER',                       'ja',           '10月',               ''),
(10,      'NOVEMBER_SHORT',                'ja',           '11月',               ''),
(10,      'NOVEMBER',                      'ja',           '11月',               ''),
(10,      'DECEMBER_SHORT',                'ja',           '12月',               ''),
(10,      'DECEMBER',                      'ja',           '12月',               ''),
(10,      'SUN',                           'ja',           '日',                 ''),
(10,      'SUNDAY',                        'ja',           '日曜',               ''),
(10,      'MON',                           'ja',           '月',                 ''),
(10,      'MONDAY',                        'ja',           '月曜',               ''),
(10,      'TUE',                           'ja',           '火',                 ''),
(10,      'TUESDAY',                       'ja',           '火曜',               ''),
(10,      'WED',                           'ja',           '水',                 ''),
(10,      'WEDNESDAY',                     'ja',           '水曜',               ''),
(10,      'THU',                           'ja',           '木',                 ''),
(10,      'THURSDAY',                      'ja',           '木曜',               ''),
(10,      'FRI',                           'ja',           '金',                 ''),
(10,      'FRIDAY',                        'ja',           '金曜',               ''),
(10,      'SAT',                           'ja',           '土',                 ''),
(10,      'SATURDAY',                      'ja',           '土曜',               ''),
(10,      'LAST_UPDATED2',                 'ja',           '更新日 %s',          '旧バージョン互換用'),
(10,      'COM_CONTENT_CREATED_DATE_ON',   'en',           'Created: %s',        ''),
(10,      'COM_CONTENT_LAST_UPDATED',      'en',           'Last Updated: %s',   ''),
(10,      'COM_CONTENT_PUBLISHED_DATE_ON', 'en',           'Published: %s',      ''),
(10,      'COM_CONTENT_WRITTEN_BY',        'en',           'Written by %s',      ''),
(10,      'COM_CONTENT_CATEGORY',          'en',           'Category: %s',       ''),
(10,      'COM_CONTENT_ARTICLE_HITS',      'en',           'Hits: %s',           ''),
(10,      'COM_CONTENT_READ_MORE_TITLE',   'en',           'Read more...',       ''),
(10,      'DATE_FORMAT_LC',                'en',           'l, d F Y',           ''),
(10,      'DATE_FORMAT_LC1',               'en',           'l, d F Y',           ''),
(10,      'DATE_FORMAT_LC2',               'en',           'l, d F Y H:i',       ''),
(10,      'DATE_FORMAT_LC3',               'en',           'd F Y',              ''),
(10,      'DATE_FORMAT_LC4',               'en',           'Y-m-d',              ''),
(10,      'DATE_FORMAT_JS1',               'en',           'y-m-d',              ''),
(10,      'JANUARY_SHORT',                 'en',           'Jan',                ''),
(10,      'JANUARY',                       'en',           'January',            ''),
(10,      'FEBRUARY_SHORT',                'en',           'Feb',                ''),
(10,      'FEBRUARY',                      'en',           'February',           ''),
(10,      'MARCH_SHORT',                   'en',           'Mar',                ''),
(10,      'MARCH',                         'en',           'March',              ''),
(10,      'APRIL_SHORT',                   'en',           'Apr',                ''),
(10,      'APRIL',                         'en',           'April',              ''),
(10,      'MAY_SHORT',                     'en',           'May',                ''),
(10,      'MAY',                           'en',           'May',                ''),
(10,      'JUNE_SHORT',                    'en',           'Jun',                ''),
(10,      'JUNE',                          'en',           'June',               ''),
(10,      'JULY_SHORT',                    'en',           'Jul',                ''),
(10,      'JULY',                          'en',           'July',               ''),
(10,      'AUGUST_SHORT',                  'en',           'Aug',                ''),
(10,      'AUGUST',                        'en',           'August',             ''),
(10,      'SEPTEMBER_SHORT',               'en',           'Sep',                ''),
(10,      'SEPTEMBER',                     'en',           'September',          ''),
(10,      'OCTOBER_SHORT',                 'en',           'Oct',                ''),
(10,      'OCTOBER',                       'en',           'October',            ''),
(10,      'NOVEMBER_SHORT',                'en',           'Nov',                ''),
(10,      'NOVEMBER',                      'en',           'November',           ''),
(10,      'DECEMBER_SHORT',                'en',           'Dec',                ''),
(10,      'DECEMBER',                      'en',           'December',           ''),
(10,      'SUN',                           'en',           'Sun',                ''),
(10,      'SUNDAY',                        'en',           'Sunday',             ''),
(10,      'MON',                           'en',           'Mon',                ''),
(10,      'MONDAY',                        'en',           'Monday',             ''),
(10,      'TUE',                           'en',           'Tue',                ''),
(10,      'TUESDAY',                       'en',           'Tuesday',            ''),
(10,      'WED',                           'en',           'Wed',                ''),
(10,      'WEDNESDAY',                     'en',           'Wednesday',          ''),
(10,      'THU',                           'en',           'Thu',                ''),
(10,      'THURSDAY',                      'en',           'Thursday',           ''),
(10,      'FRI',                           'en',           'Fri',                ''),
(10,      'FRIDAY',                        'en',           'Friday',             ''),
(10,      'SAT',                           'en',           'Sat',                ''),
(10,      'SATURDAY',                      'en',           'Saturday',           ''),
(10,      'LAST_UPDATED2',                 'en',           'Last Updated on %s', '旧バージョン互換用');


-- *** システム標準テーブル ***

