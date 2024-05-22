<?php

/**
 * 群組隸屬管理
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '角色管理');

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限 */
adminPriv('admin_role_group');

/* 初始化數據交換對像 */
$exchange = new exchange($db->table('admin_role'), $db, 'role_id', 'role_name');

/* 初始化執行動作 */
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

        $actionLink[] = array(
            'text' => '新增',
            'icon' => 'far fa-edit fa-fw',
            'sref' => buildUIRef('?action=add')
        );
        $smarty->assign('action_link', $actionLink);

        $extendArr[] = array('text' => '權限管理');
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

case 'add':
case 'edit':

    $isAdd = $_REQUEST['action'] == 'add'; // 添加還是編輯的標識

    if ($isAdd) {

        $data = array(
            'role_id' => 0,
            'role_name' => '',
            'role_describe' => '',
            'action_list' => '',
            'admin_id' => 0
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

        $sql = 'SELECT * FROM ' . $db->table('admin_role') . ' ' .
               'WHERE role_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        if (true != adminPriv('sys', false)) {

            $status = $data['admin_id'] != 0;

            if (true == adminPriv('all', false)) {

                if ($data['admin_id'] > 0) {

                    $sql = 'SELECT au.admin_id ' .
                           'FROM ' . $db->table('admin_user') . ' AS au ' .
                           'WHERE FIND_IN_SET("sys", au.action_list)';
                    $status &= !in_array($data['admin_id'], (array)$db->getCol($sql));

                } elseif ($data['admin_id'] == 0) {

                    sysMsg('您不能對此角色的權限進行任何操作!');
                }

            } else {

                // 排除自身隸屬的角色群組
                $status &= !in_array($roleId, getAdminRelRoleIds($_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]));
                $status &= $data['admin_id'] == $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
            }
            if ($status == false) {

                sysMsg('對不起，您沒有執行此項操作的權限！');
            }
        }
    }

    /* 取得權限的分組資料 */
    $privArr = array();
    if (adminPriv('allot_priv', false)) {

        $sql = 'SELECT action_id, parent_id, action_code, action_name ' .
               'FROM ' . $db->table('admin_action') . ' ' .
               'WHERE parent_id = 0';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $privArr[$row['action_id']] = $row;
        }

        /* 獲得該管理員的權限 */
        $operatePriv = explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);
        if (in_array('all', $operatePriv) || in_array('sys', $operatePriv)) {

            $sql = 'SELECT action_id, parent_id, action_code, action_name, relevance ' .
                   'FROM ' . $db->table('admin_action') . ' ' .
                   'WHERE parent_id ' . $db->in(array_keys($privArr));

        } else {

            $sql = 'SELECT action_id, parent_id, action_code, action_name, relevance ' .
                   'FROM ' . $db->table('admin_action') . ' ' .
                   'WHERE parent_id ' . $db->in(array_keys($privArr)) . ' ' .
                   'AND action_code ' . $db->in($operatePriv);
        }

        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $privArr[$row['parent_id']]['priv'][$row['action_code']] = $row;
        }

        // 將同一組的權限使用 "," 連接起來，供 JS 全選
        $actionList = explode(',', $data['action_list']);
        foreach ($privArr as $id => $group) {

            if (!isset($group['priv'])) {

                unset($privArr[$id]);
                continue;

            } else {

                $privArr[$id]['priv_list'] = implode(',', @array_keys($group['priv']));
                foreach ($group['priv'] as $key => $val) {

                    $privArr[$id]['priv'][$key]['cando'] = (int) in_array($val['action_code'], $actionList);
                }
            }
        }
    }

    $smarty->assign('data', $data);
    $smarty->assign('priv_arr', $privArr);

    $smarty->assign('form_action', $isAdd ? 'insert' : 'updata');

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list', true),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '權限管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_info.html');
    break;

case 'insert':
case 'updata':

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : 0;

    $data['role_name'] = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
    $data['role_describe'] = isset($_POST['role_describe']) ? trim($_POST['role_describe']) : '';

    if (adminPriv('allot_priv', false)) {

        $data['action_list'] = !empty($_POST['action_code']) && is_array($_POST['action_code'])
            ? implode(',', $_POST['action_code'])
            : '';
    }

    if ($isInsert) {

        $data['admin_id'] = $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];

        $sql = $db->buildSQL('I', $db->table('admin_role'), $data);

    } else {

        if ($data['action_list'] != '') {

            /* 獲取權限群組代碼 */
            $sql = 'SELECT action_list FROM ' . $db->table('admin_role') . ' ' .
                   'WHERE role_id = ' . $roleId;
            $oldPriv = array();
            if ($privList = $db->getOne($sql)) {

                $oldPriv = array_unique(@explode(',', $privList));
            }

            /* 獲得該操作管理者的權限 */
            $operatePriv = array_unique(@explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]));
            if (in_array('all', $operatePriv) || in_array('sys', $operatePriv)) {

                $sql = 'SELECT action_code FROM ' . $db->table('admin_action') . ' ' .
                       'WHERE parent_id <> 0';
                $operatePriv = $db->getCol($sql);
            }

            $privDiff = array_diff($oldPriv, $operatePriv);
            $data['action_list'] = explode(',', $data['action_list']);
            $data['action_list'] = array_merge($privDiff, $data['action_list']);
            $data['action_list'] = array_unique($data['action_list']);
            $data['action_list'] = implode(',', $data['action_list']);
        }

        $sql = $db->buildSQL('U', $db->table('admin_role'), $data, 'role_id = ' . $roleId);
    }
    $db->query($sql);

    /* 編號 */
    $roleId = $isInsert ? $db->insertId() : $roleId;

    /* 提示頁面 */
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list'));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $roleId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add'));

        /* 記錄日誌 */
        adminLog($data['role_name'], '新增', SCRIPT_TITLE);

        sysMsg(sprintf('%s 新增成功', stripslashes($data['role_name'])), 0, $links);

    } else {

        /* 記錄日誌 */
        adminLog($data['role_name'], '編輯', SCRIPT_TITLE);

        sysMsg(sprintf('%s 成功編輯', stripslashes($data['role_name'])), 0, $links);
    }

    sysMsg('操作成功！', 0, $links);
    break;

case 'remove':

    if (!empty($_REQUEST['id'])) {

        $id = (int)$_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $name = $exchange->getName($id);
    if ($exchange->drop($id)) {

        /* 記錄日誌 */
        adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
    }

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'query';

    $dus->header('Location: ?' . http_build_query($queryString));
    exit;
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 獲取列表
 *
 * @return    array
 */
function getListPage()
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'role_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    if (true == adminPriv('sys', false)) {

        $oper = '1 ';

    } else {

        $oper = 'r.admin_id <> 0 ';

        // 排除自身隸屬的角色群組
        $roleIds = getAdminRelRoleIds($_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]);
        $oper .= ' AND r.role_id NOT ' . $GLOBALS['db']->in($roleIds);

        if (false == adminPriv('all', false)) {

            $oper .= ' AND r.admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
        }

        $oper .= ' AND NOT EXISTS( '
               . '  SELECT 1 '
               . '  FROM ' . $GLOBALS['db']->table('admin_user') . ' AS au '
               . '  WHERE au.admin_id = r.admin_id AND FIND_IN_SET("sys", au.action_list) '
               . ')';
    }

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('admin_role') . ' AS r ' .
           'WHERE ' . $oper;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分頁大小 */
    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();

    if ($filter['record_count']) {

        /* 查詢 */
        $sql = 'SELECT r.*, COUNT(au.role_id) AS role_num ' .
               'FROM ' . $GLOBALS['db']->table('admin_role') . ' AS r ' .
               'LEFT JOIN ' . $GLOBALS['db']->table('admin_relation_role') . ' AS au ' .
               'ON au.role_id = r.role_id ' .
               'WHERE ' . $oper .
               'GROUP BY r.role_id ' .
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
