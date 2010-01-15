<?php
/**
 * ImageCache2 - メンテナンス
 */

// {{{ p2基本設定読み込み&認証

define('P2_OUTPUT_XHTML', 1);

require_once './conf/conf.inc.php';

$_login->authorize();

if (!$_conf['expack.ic2.enabled']) {
    p2die('ImageCache2は無効です。', 'conf/conf_admin_ex.inc.php の設定を変えてください。');
}

// }}}
// {{{ 初期化

// ライブラリ読み込み
require_once 'HTML/Template/Flexy.php';
require_once P2EX_LIB_DIR . '/ic2/bootstrap.php';

// 設定読み込み
$ini = ic2_loadconfig();

// データベースに接続
$db = DB::connect($ini['General']['dsn']);
if (DB::isError($db)) {
    p2die($result->getMessage());
}

// テンプレートエンジン初期化
$_flexy_options = array(
    'locale' => 'ja',
    'charset' => 'cp932',
    'compileDir' => $_conf['compile_dir'] . DIRECTORY_SEPARATOR . 'ic2',
    'templateDir' => P2EX_LIB_DIR . '/ic2/templates',
    'numberFormat' => '', // ",0,'.',','" と等価
);

$flexy = new HTML_Template_Flexy($_flexy_options);

// }}}
// {{{ データベース操作・ファイル削除

if (isset($_POST['action'])) {
    switch ($_POST['action']) {

        case 'dropZero':
        case 'dropAborn':
            if ($_POST['action'] == 'dropZero') {
                $where = $db->quoteIdentifier('rank') . ' = 0';
                if (isset($_POST['dropZeroLimit'])) {
                    switch ($_POST['dropZeroSelectTime']) {
                        case '24hours': $expires = 86400; break;
                        case 'aday':    $expires = 86400; break;
                        case 'aweek':   $expires = 86400 * 7; break;
                        case 'amonth':  $expires = 86400 * 31; break;
                        case 'ayear':   $expires = 86400 * 365; break;
                        default: $expires = NULL;
                    }
                    if ($expires !== NULL) {
                        $operator = ($_POST['dropZeroSelectType'] == 'within') ? '>' : '<';
                        $where .= sprintf(' AND %s %s %d',
                            $db->quoteIdentifier('time'),
                            $operator,
                            time() - $expires);
                    }
                }
                $to_blacklist = !empty($_POST['dropZeroToBlackList']);
            } else {
                $where = $db->quoteIdentifier('rank') . ' < 0';
                $to_blacklist = TRUE;
            }

            $sql = sprintf('SELECT %s FROM %s WHERE %s;',
                $db->quoteIdentifier('id'),
                $db->quoteIdentifier($ini['General']['table']),
                $where);
            $result = $db->getAll($sql, NULL, DB_FETCHMODE_ORDERED | DB_FETCHMODE_FLIPPED);
            if (DB::isError($result)) {
                $_info_msg_ht .= $result->getMessage();
                break;
            }
            $target = $result[0];
            $removed_files = IC2_DatabaseManager::remove($target, $to_blacklist);
            $flexy->setData('toBlackList', $to_blacklist);
            break;

        case 'clearThumb':
            $thumb_dir2 = $ini['General']['cachedir'] . '/' . $ini['Thumb2']['name'];
            $thumb_dir3 = $ini['General']['cachedir'] . '/' . $ini['Thumb3']['name'];
            $result_files2 = P2Util::garbageCollection($thumb_dir2, -1, '', '', TRUE);
            $result_files3 = P2Util::garbageCollection($thumb_dir3, -1, '', '', TRUE);
            $removed_files = array_merge($result_files2['successed'], $result_files3['successed']);
            $failed_files = array_merge($result_files2['failed'], $result_files3['failed']);
            if (!empty($failed_files)) {
                $_info_msg_ht .= '<p>以下のファイルが削除できませんでした。</p>';
                $_info_msg_ht .= '<ul><li>';
                $_info_msg_ht .= implode('</li><li>', array_map('htmlspecialchars', $failed_files));
                $_info_msg_ht .= '</li></ul>';
            }
            break;

        case 'clearCache':
            if (file_exists($_conf['iv2_cache_db_path'])) {
                $kvs = P2KeyValueStore::getStore($_conf['iv2_cache_db_path'],
                                                 P2KeyValueStore::CODEC_SERIALIZING);
                if ($kvs->clear() === false) {
                    $_info_msg_ht .= '<p>一覧表示用のデータキャッシュを消去できませんでした。</p>';
                } else {
                    $_info_msg_ht .= '<p>一覧表示用のデータキャッシュを消去しました。</p>';
                }
            }
            $result_files = P2Util::garbageCollection($flexy->options['compileDir'], -1, '', '', TRUE);
            $removed_files = $result_files['successed'];
            if (!empty($result_files['failed'])) {
                $_info_msg_ht .= '<p>以下のファイルが削除できませんでした。</p>';
                $_info_msg_ht .= '<ul><li>';
                $_info_msg_ht .= implode('</li><li>', array_map('htmlspecialchars', $result_files['failed']));
                $_info_msg_ht .= '</li></ul>';
            }
            break;

        case 'clearErrorLog':
            $result = $db->query('DELETE FROM ' . $db->quoteIdentifier($ini['General']['error_table']));
            if (DB::isError($result)) {
                $_info_msg_ht .= $result->getMessage();
            } else {
                $_info_msg_ht .= '<p>エラーログを消去しました。</p>';
            }
            break;

        case 'clearBlackList':
            $result = $db->query('DELETE FROM ' . $db->quoteIdentifier($ini['General']['blacklist_table']));
            if (DB::isError($result)) {
                $_info_msg_ht .= $result->getMessage();
            } else {
                $_info_msg_ht .= '<p>ブラックリストを消去しました。</p>';
            }
            break;

        case 'vacuumDB':
            // SQLite2 の画像キャッシュデータベースをVACUUM
            if ($db->dsn['phptype'] == 'sqlite') {
                $result = $db->query('VACUUM');
                if (DB::isError($result)) {
                    $_info_msg_ht .= $result->getMessage();
                } else {
                $_info_msg_ht .= '<p>画像データベースを整理しました。</p>';
                }
            }

            // SQLite3 の一覧表示用データキャッシュをVACUUM
            if (file_exists($_conf['iv2_cache_db_path'])) {
                $kvs = P2KeyValueStore::getStore($_conf['iv2_cache_db_path'],
                                                 P2KeyValueStore::CODEC_SERIALIZING);
                $kvs->vacuum();
                unset($kvs);
                $_info_msg_ht .= '<p>一覧表示用のデータキャッシュを整理しました。</p>';
            }
            break;

        default:
            $_info_msg_ht .= '<p>不正なクエリ: ' . htmlspecialchars($_POST['action'], ENT_QUOTES) . '</p>';

    }
    if (isset($removed_files)) {
        $flexy->setData('removedFiles', $removed_files);
    }
}

// }}}
// {{{ 出力

$flexy->setData('skin', $skin_en);
$flexy->setData('php_self', $_SERVER['SCRIPT_NAME']);
$flexy->setData('info_msg', $_info_msg_ht);
$flexy->setData('pc', !$_conf['ktai']);
$flexy->setData('iphone', $_conf['iphone']);
$flexy->setData('doctype', $_conf['doctype']);
$flexy->setData('extra_headers',   $_conf['extra_headers_ht']);
$flexy->setData('extra_headers_x', $_conf['extra_headers_xht']);

P2Util::header_nocache();
$flexy->compile('ic2mng.tpl.html');
$flexy->output();

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
