<?php

/**
 *
 * ============================================================================
 *
 * ============================================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '郵件佇列');

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限 */
adminPriv('email_queue');

/* 操作项的初始化 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'list';

switch ($_REQUEST['action']) {

case 'list':
case 'query':

    $list = getListPage();
    $smarty->assign('item_arr', $list['item']);
    $smarty->assign('filter', $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count', $list['page_count']);

    $sortFlag = sortFlag($list['filter']);
    $smarty->assign($sortFlag['tag'], $sortFlag['icon']);

    if ($_REQUEST['action'] == 'list') {

        $smarty->assign('full_page', 1);

        $extendArr[] = array('text' => '郵件管理');
        $position = assignUrHere(SCRIPT_TITLE, $extendArr);
        $smarty->assign('page_title', $position['title']); // 頁面標題
        $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

        assignTemplate();
        assignQueryInfo();
        $smarty->display(SCRIPT_NAME . '.html');

    } else {

        $smarty->assign('full_page', 0);

        makeJsonResult($smarty->fetch(SCRIPT_NAME . '.html'), '',
            array('filter' => $list['filter'], 'page_count' => $list['page_count']));
    }
    break;

//-- 刪除
case 'remove':

    if (!empty($_REQUEST['id'])) {

        $id = (int)$_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $sql = 'DELETE FROM ' . $db->table('email_queue') . ' ' .
           'WHERE id = ' . $id . ' ' .
           'LIMIT 1';
    $db->query($sql);

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'query';

    $dus->header('Location: ?' . http_build_query($queryString));
    exit;
    break;

//-- 批量刪除 || 發送次數歸零
case 'batch_remove':
case 'batch_afresh':

    $links[] = array('text' => '郵件佇列', 'sref' => buildUIRef('?action=list', true));

    $checkboxes = !empty($_POST['checkboxes']) ? (array)$_POST['checkboxes'] : array();

    if (empty($checkboxes)) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    switch ($_REQUEST['action']) {
        // 批量刪除
        case 'batch_remove':

            $sql = 'DELETE FROM ' . $db->table('email_queue') . ' ' .
                   'WHERE id ' . $db->in($checkboxes);
            $db->query($sql);

            sysMsg('批次刪除成功!', 0, $links);
            break;

        // 發送次數歸零
        case 'batch_afresh':

            $sql = 'UPDATE ' . $db->table('email_queue') . ' SET ' .
               'error = 0 ' .
               'WHERE id ' . $db->in($checkboxes);
            $db->query($sql);

            sysMsg('批次重新發送次數歸零!', 0, $links);
            break;
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 獲取列表
 * @return  array
 */
function getListPage()
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'pri';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('email_queue') . ' e ';
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分頁大小 */
    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();

    if ($filter['record_count'] > 0) {

        /* 查詢 */
        $sql = 'SELECT e.* ' .
               'FROM ' . $GLOBALS['db']->table('email_queue') . ' e ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['email'] = $GLOBALS['dus']->dataDecode($row['email']);
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
