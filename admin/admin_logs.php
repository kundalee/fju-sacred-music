<?php

/**
 * 記錄管理員操作日誌
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '操作紀錄');

/* 載入函式檔 */
require(dirname(__FILE__) . '/includes/init.php');

/* 初始化執行動作 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'list';

switch ($_REQUEST['action']) {

//-- 獲取所有日誌列表 & 排序、分頁、查詢
case 'list':
case 'query':

    /* 檢查權限 */
    adminPriv('logs_manage');

    $list = getListPage();

    $smarty->assign('item_arr', $list['item']);
    $smarty->assign('filter', $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count', $list['page_count']);

    $sortFlag = sortFlag($list['filter']);
    $smarty->assign($sortFlag['tag'], $sortFlag['icon']);

    if ($_REQUEST['action'] == 'list') {

        $adminIP = !empty($_REQUEST['ip']) ? $_REQUEST['ip'] : '';
        $logDate = !empty($_REQUEST['log_date']) ? $_REQUEST['log_date'] : '';

        /* 查詢IP地址列表 */
        $ip = array();
        $res = $db->query('SELECT DISTINCT ip_address FROM ' . $db->table('admin_log'));
        while ($row = $db->fetchAssoc($res)) {

            $ip[$row['ip_address']] = $row['ip_address'];
        }

        $smarty->assign('ip_list', $ip);
        $smarty->assign('admin_ip', $adminIP);

        $smarty->assign('full_page', 1);

        $extendArr[] = array('text' => '權限管理');
        $position = assignUrHere(SCRIPT_TITLE, $extendArr);
        $smarty->assign('page_title', $position['title']); // 頁面標題
        $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

        assignTemplate();
        assignQueryInfo();
        $smarty->display(SCRIPT_NAME . '.html');

    } else {

        $smarty->assign('full_page', 0);

        assignQueryInfo();
        makeJsonResult($smarty->fetch(SCRIPT_NAME . '.html'), '',
            array('filter' => $list['filter'], 'page_count' => $list['page_count']));
    }
    break;

//-- 批量刪除日誌記錄
case 'batch_drop':

    /* 檢查權限 */
    adminPriv('logs_drop');

    $dropTypeDate = isset($_POST['drop_type_date']) ? 1 : 0;

    /* 按日期刪除日誌 */
    if ($dropTypeDate) {

        if ($_POST['log_date'] == '0') {

            $links[] = array(
                'text' => '返回紀錄列表',
                'sref' => buildUIRef()
            );
            sysMsg('選擇清除的日期!', 0, $links);

        } elseif ($_POST['log_date'] > '0') {

            $oper = ' WHERE 1 ';
            switch ($_POST['log_date']) {
                case '1':
                    $aWeek = $_SERVER['REQUEST_TIME'] - (3600 * 24 * 7);
                    $oper .= ' AND log_time <= ' . $aWeek;
                    break;
                case '2':
                    $aMonth = $_SERVER['REQUEST_TIME'] - (3600 * 24 * 30);
                    $oper .= ' AND log_time <= ' . $aMonth;
                    break;
                case '3':
                    $threeMonth = $_SERVER['REQUEST_TIME'] - (3600 * 24 * 90);
                    $oper .= ' AND log_time <= ' . $threeMonth;
                    break;
                case '4':
                    $halfYear = $_SERVER['REQUEST_TIME'] - (3600 * 24 * 180);
                    $oper .= ' AND log_time <= ' . $halfYear;
                    break;
                case '5':
                    $aYear = $_SERVER['REQUEST_TIME'] - (3600 * 24 * 365);
                    $oper .= ' AND log_time <= ' . $aYear;
                    break;
            }
            $sql = 'DELETE FROM ' . $db->table('admin_log') . $oper;
            if ($res = $db->query($sql)) {

                // 一周之前 & 一個月之前 & 三個月之前 & 半年之前 & 一年之前
                $logDate = array('1' => '一周之前', '2' => '一個月之前', '3' => '三個月之前', '4' => '半年之前', '5' => '一年之前');

                // 刪除 & 操作紀錄
                adminLog($logDate[$_POST['log_date']], '刪除', SCRIPT_TITLE);

                // 返回紀錄列表
                $links[] = array(
                    'text' => '返回紀錄列表',
                    'sref' => buildUIRef()
                );

                // 操作成功!
                sysMsg('操作成功!', 1, $links);
            }
        }

    } else {

       if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

            sysMsg('您沒有選擇任何項目', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
       }

       /* 如果不是按日期來刪除, 就按ID刪除日誌 */
       $count = 0;
       foreach ($_POST['checkboxes'] as $key => $id) {

           $sql = 'DELETE FROM ' . $db->table('admin_log') . ' WHERE log_id = ' . $id;
           $result = $db->query($sql);

           $count++;
       }
       if ($result) {

           //  %d 筆紀錄 & 刪除 & 操作紀錄
           adminLog(sprintf('%d 筆紀錄', $count), '刪除', SCRIPT_TITLE);

           // 返回紀錄列表
           $links[] = array(
               'text' => '返回紀錄列表',
               'sref' => buildUIRef()
            );
           // 成功刪除了 %d 筆紀錄
           sysMsg(sprintf('成功刪除了 %d 筆紀錄', $count), 0, $links);
       }
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 獲取列表
 *
 * @retusign_logrn    array
 */
function getListPage()
{
    $adminId = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    $adminIP = !empty($_REQUEST['ip']) ? $_REQUEST['ip'] : '';

    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'al.log_time';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    // 查詢條件
    $operator = ' WHERE 1 ';
    if (!empty($adminId)) {

        $operator .= 'AND al.admin_id = ' . $adminId . ' ';

    } elseif (!empty($adminIP)) {

        $operator .= 'AND al.ip_address = "' . $adminIP . '" ';
    }
    if (adminPriv('sys', false) == false) {

        $operator .= 'AND al.admin_id <> 1 ';
    }
    /* 獲得總記錄數據 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('admin_log') . ' AS al ' . $operator;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    /* 獲取管理員日誌記錄 */
    $itemArr = array();

    if ($filter['record_count']) {

        $sql = 'SELECT al.*, u.admin_name ' .
               'FROM ' . $GLOBALS['db']->table('admin_log') . ' AS al ' .
               'LEFT JOIN ' . $GLOBALS['db']->table('admin_user') . ' AS u ' .
               'ON u.admin_id = al.admin_id ' .
               $operator .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['log_time'] = date('Y-m-d H:i:s', $row['log_time']);
            $itemArr[] = $row;
        }
    }

    return array(
        'item' => $itemArr,
        'filter' => $filter,
        'page_count' => $filter['page_count'],
        'record_count' => $filter['record_count']
    );
}
