<?php

/**
 *
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '郵件樣板');

require(dirname(__FILE__) . '/includes/init.php');

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('mail_templates'),
    $db,
    'template_id',
    'template_code'
);

/* 操作項的初始化 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'list';

switch ($_REQUEST['action']) {
//-- 列表 || 查詢
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

        if (true === adminPriv('sys', false)) {

            $actionLink[] = array(
                'text' => '新增',
                'icon' => 'far fa-edit fa-fw',
                'sref' => buildUIRef('?action=add')
            );
            $smarty->assign('action_link', $actionLink);
        }

        $extendArr[] = array('text' => '管理設定');
        $position = assignUrHere(SCRIPT_TITLE, $extendArr);
        $smarty->assign('page_title', $position['title']); // 頁面標題
        $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

        assignTemplate();
        assignQueryInfo();
        $smarty->display(SCRIPT_NAME . '_list.html');

    } else {

        $smarty->assign('full_page', 0);

        assignQueryInfo();
        makeJsonResult($smarty->fetch(SCRIPT_NAME . '_list.html'), '',
            array('filter' => $list['filter'], 'page_count' => $list['page_count']));
    }
    break;

//-- 新增 || 編輯
case 'add':
case 'edit':

    /* 檢查權限 */
    adminPriv('mail_templates');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    if ($isAdd) {

        /* 初始化 */
        $data = array(
            'template_id' => 0,
            'template_code' => ''
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('mail_templates') . ' ' .
               'WHERE template_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $data = $dus->urlDecode($data);
    }

    $smarty->assign('data', $data);

    $smarty->assign('form_action', $isAdd ? 'insert' : 'update');

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list', true),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_info.html');
    break;

//-- 新增 || 更新
case 'insert':
case 'update':

    /* 檢查權限 */
    adminPriv('mail_templates');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $templateId = !empty($_POST['template_id']) ? intval($_POST['template_id']) : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=add&id=' . $templateId)
    );

    $fields = array(
        'template_subject',
        'template_content'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val])
            ? trim($_POST[$val])
            : '';
    }

    // 檢查是否重複
    if (isset($_POST['template_code'])) {

        $data['template_code'] = trim($_POST['template_code']);

        if (!$exchange->isOnly('template_code', $data['template_code'], $templateId)) {

            sysMsg(sprintf('%s 已經存在', $data['template_code']), 1, $links);
        }
    }

    $data = $dus->urlEncode($data);

    $data['last_modify'] = $_SERVER['REQUEST_TIME'];

    if ($isInsert) {

        $sql = $db->buildSQL('I', $db->table('mail_templates'), $data);

    } else {

        if (empty($templateId)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $sql = $db->buildSQL('U', $db->table('mail_templates'), $data, 'template_id = ' . $templateId);
    }

    $db->query($sql);

    /* 編號 */
    $templateId = $isInsert ? $db->insertId() : $templateId;

    /* 記錄日誌 */
    adminLog($data['template_subject'], $isInsert ? '新增' : '編輯', SCRIPT_TITLE);

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $templateId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 刪除
case 'remove':

    checkAuthzJson('mail_templates');

    if (!empty($_REQUEST['id'])) {

        $id = intval($_REQUEST['id']);

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    handleItemDrop($id);

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'query';

    $dus->header('Location: ?' . http_build_query($queryString));
    exit;
    break;

//-- 批量刪除
case 'batch_remove':

    /* 檢查權限 */
    adminPriv('mail_templates');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $numDrop = handleItemDrop($_POST['checkboxes']);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    sysMsg(sprintf('您已經成功刪除 %d 筆', $numDrop), 0, $links);
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 刪除
 *
 * @param     string    $idsDrop
 *
 * @return    int
 */
function handleItemDrop($idsDrop)
{
    $dropItem = array();
    $sql = 'SELECT mt.template_subject, mt.template_id ' .
           'FROM ' . $GLOBALS['db']->table('mail_templates') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.template_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['template_id']] = $row['template_subject'];
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        /* 開始進行刪除 */
        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('mail_templates') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'template_id');
        $GLOBALS['db']->query($sql);

        foreach ($dropItem as $name) {

            adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
        }
    }

    return count($dropItem);
}

/**
 * 獲取列表
 *
 * @return    array
 */
function getListPage()
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'template_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';

    /* 總數 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('mail_templates') . ' AS mt ' .
           'WHERE 1 ' . $operator;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();

    if ($filter['record_count']) {

        /* 獲取數據 */
        $sql = 'SELECT mt.template_id, mt.template_code, mt.last_modify, mt.template_subject ' .
               'FROM ' . $GLOBALS['db']->table('mail_templates') . ' AS mt ' .
               'WHERE 1 ' . $operator . ' ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

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
