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
define('SCRIPT_TITLE', '日誌標籤');

require(dirname(__FILE__) . '/includes/init.php');

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('tags'),
    $db,
    'tag_id',
    'tag_name'
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

        $actionLink[] = array(
            'text' => '新增',
            'icon' => 'far fa-edit fa-fw',
            'sref' => buildUIRef('?action=add')
        );
        $smarty->assign('action_link', $actionLink);

        $extendArr[] = array('text' => '日誌管理');
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

//-- 新增 || 編輯 ||　複製
case 'add':
case 'edit':

    /* 檢查權限 */
    adminPriv('tag_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    if ($isAdd) {

        /* 初始化 */
        $time = $_SERVER['REQUEST_TIME'];
        $data = array(
            'tag_id' => 0,
            'is_show' => 1,
            'sort_order' => 0
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('tags') . ' ' .
               'WHERE tag_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }
    }

    $smarty->assign('data', $data);

    $smarty->assign('form_action', $isAdd ? 'insert' : ($_REQUEST['action'] == 'edit' ? 'update' : 'insert'));

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list', true),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '日誌管理');
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
    adminPriv('tag_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $tagId = !empty($_POST['tag_id']) ? (int) $_POST['tag_id'] : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=edit&id=' . $tagId)
    );

    $fields = array(
        'tag_name'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val])
            ? trim($_POST[$val])
            : '';
    }

    $data['is_show'] = !empty($_POST['is_show']) ? 1 : 0;
    $data['is_show'] = !empty($_POST['is_show']) ? 1 : 0;
    $data['sort_order'] = !empty($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;

    if ($isInsert) {

        $sql = $db->buildSQL('I', $db->table('tags'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('tags') . ' ' .
               'WHERE tag_id = ' . $tagId;
        if ($tagId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $sql = $db->buildSQL('U', $db->table('tags'), $data, 'tag_id = ' . $tagId);
    }

    $db->query($sql);

    /* 編號 */
    $tagId = $isInsert ? $db->insertId() : $tagId;

    /* 清空緩存 */
    clearCacheFiles();

    /* 記錄日誌 */
    adminLog(
        $data['tag_name'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $tagId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 編輯排序
case 'edit_sort_order':

    checkAuthzJson('tag_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? (int) $_REQUEST['val'] : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('sort_order' => $val), $id)) {

        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        clearCacheFiles();
        makeJsonResult($val);

    } else {

        makeJsonError(sprintf('%s 修改失敗！', $name));
    }
    break;

//-- 切換是否顯示
case 'toggle_show':

    /* 檢查權限 */
    checkAuthzJson('tag_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? 1 : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('is_show' => $val), $id)) {

        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        clearCacheFiles();
        makeJsonResult($val);

    } else {

        makeJsonError($db->error());
    }
    break;

//-- 刪除
case 'remove':

    checkAuthzJson('tag_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

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
    adminPriv('tag_manage');

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
    $sql = 'SELECT mt.tag_id, mt.tag_name ' .
           'FROM ' . $GLOBALS['db']->table('tags') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.tag_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['tag_id']] = $row['tag_name'];
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('tags') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'tag_id');
        $GLOBALS['db']->query($sql);

        foreach ($dropItem as $name) {

            adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
        }

        clearCacheFiles();
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
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'tag_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';

    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND tag_name LIKE "%' . $queryString . '%" ';

        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    /* 總數 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('tags') . ' AS mt ' .
           'WHERE 1 ' . $operator;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();

    if ($filter['record_count'] > 0) {

        /* 獲取數據 */
        $sql = 'SELECT mt.tag_id, mt.sort_order, mt.is_show, mt.tag_name ' .
               'FROM ' . $GLOBALS['db']->table('tags') . ' AS mt ' .
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
