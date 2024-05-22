<?php

/**
 * 資料庫管理
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '資料表優化');
define('SYS_SAFE_CODE', true);

require(dirname(__FILE__) . '/includes/init.php');

@ini_set('memory_limit', '512M');

switch ($_REQUEST['action']) {

//-- 優化頁面
case 'optimize':

    /* 初始化數據 */
    adminPriv('sys');

    $dbVer = $db->version();
    $ret = $db->query('SHOW TABLE STATUS LIKE "' . $db->likeQuote($db->tb_prefix) . '%"');

    $list = array();
    $num = $total = 0;
    while ($row = $db->fetchAssoc($ret)) {

        $type = $dbVer >= '4.1' ? $row['Engine'] : $row['Type'];
        $charset = $dbVer >= '4.1' ? $row['Collation'] : 'N/A';

        if (in_array(strtoupper($type), array(null, 'MEMORY'))) {

            $res['Msg_text'] = 'Ignore';
            $row['Data_free'] = 'Ignore';

        } else {

            $res = $db->getRow('CHECK TABLE ' . $row['Name']);
            $num += $row['Data_free'];
            $total += $row['Data_length'] + $row['Index_length'];
        }

        $list[] = array(
            'table' => $row['Name'],
            'type' => $type,
            'rec_rows' => $row['Rows'],
            'rec_data_len' => formatByte($row['Data_length'], 1),
            'rec_index_len' => formatByte($row['Index_length'], 1),
            'rec_data_free' => formatByte($row['Data_free'], 1),
            'rec_update_time' => strtotime($row['Update_time']),
            'rec_check_time' => strtotime($row['Check_time']),
            'status' => $res['Msg_text'],
            'charset' => $charset
        );
    }
    unset($ret);

    $list = arraySortByMultifields($list, array('type' => SORT_DESC, 'table' => SORT_ASC));

    $smarty->assign('list', $list);
    $smarty->assign('num', formatByte($num, 1));
    $smarty->assign('total', formatByte($total, 1));

    $actionLink[] = array(
        'text' => '優化',
        'icon' => 'far fa-play-circle fa-fw',
        'sref' => buildUIRef('?action=run_optimize'),
        'style' => 'btn-danger'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display('optimize.html');
    break;

case 'run_optimize':

    adminPriv('sys');

    $num = 0;

    $tables = $db->getCol('SHOW TABLES LIKE "' . $db->likeQuote($db->tb_prefix) . '%"');
    foreach ($tables as $table) {

        if ($row = $db->getRow('OPTIMIZE TABLE ' . $table)) {

            /* 優化出錯，嘗試修復 */
            if (false !== stripos($row['Msg_type'], 'Error') && false !== stripos($row['Msg_text'], 'Repair')) {

                $db->query('REPAIR TABLE ' . $table);
            }
            $num++;
        }
    }

    sysMsg(sprintf('資料表優化成功，共清理碎片 %s', $num), 0,
        array(array('text' => '返回上一頁', 'sref' => buildUIRef('?action=optimize'))));
    break;
}
exit;
