<?php

/**
 * 日誌分類程序
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '日誌分類');

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'includes/lib_category.php');

$exchange = new exchange(
    $db->table('bulletin_category'),
    $db,
    'cat_id',
    'cat_name'
);

/* 操作項的初始化 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'list';

switch ($_REQUEST['action']) {
//-- 分類列表
case 'list':
case 'query':

    /* 獲取分類列表 */
    $catList = getCategory(
        'bulletin_category',
        array(
            're_type' => false
        )
    );

    $smarty->assign('cat_info', $catList);

    if ($_REQUEST['action'] == 'list') {

        $smarty->assign('full_page', 1);

        /* 模板賦值 */
        $actionLink[] = array(
            'text' => '排序',
            'icon' => 'fas fa-sort-amount-down fa-fw',
            'sref' => buildUIRef('?action=sortable')
        );
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
            array());
    }
    break;

//-- 排序列表
case 'sortable':

    /* 獲取分類列表 */
    $catList = getCategory(
        'bulletin_category',
        array(
            're_type' => false
        )
    );
    $categoryTree = arrayToTree($catList, 'cat_id');

    $smarty->assign('html_tree', buildHtmlTree($categoryTree));

    /* 模板賦值 */
    $actionLink[] = array(
        'text' => '列表',
        'icon' => 'far fa-list-alt fa-fw',
        'sref' => buildUIRef('?action=list')
    );
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

    $smarty->display(SCRIPT_NAME . '_sortable.html');
    break;

//-- 儲存排序
case 'save_node':

    /* 檢查權限 */
    checkAuthzJson('bulletin_cat_manage');

    $readble = !empty($_POST['node']) ? parseTreeLayer((array) $_POST['node']) : array();
    foreach ($readble as $row) {

        $catId = !empty($row['id']) ? (int) $row['id'] : 0;

        $data = array();
        $data['sort_order'] = (int) $row['sortable'];
        $data['parent_id'] = !empty($row['parent_id']) ? (int) $row['parent_id'] : 0;

        $data['parent_ids'] = 0;
        if (!empty($data['parent_id'])) {

            $data['parent_ids'] = getCatParentIds('goods_category', $data['parent_id']);
            $data['parent_ids'] = array_reverse($data['parent_ids']);
            $data['parent_ids'] = implode(',', $data['parent_ids']);
        }

        $db->query(
            $db->buildSQL(
                'U',
                $db->table('bulletin_category'),
                $data,
                'cat_id = ' . $catId
            )
        );
    }

    /* 清除緩存*/
    clearCacheFiles();

    makeJsonResult(0, '排序更新成功');
    break;

//-- 添加分類
case 'add':
case 'edit':

    /* 檢查權限 */
    adminPriv('bulletin_cat_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 添加還是編輯的標識

    if ($isAdd) {

        $data = array(
            'cat_id' => 0,
            'parent_id' => '',
            'is_show' => 1,
            'sort_order' => 0,
            'is_readonly' => 0
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        $sql = 'SELECT * FROM ' . $db->table('bulletin_category') . ' ' .
               'WHERE cat_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }
    }

    $catList = '';
    if (CL_BULLETIN - 1) {

        $catList = getCategory(
            'bulletin_category',
            array(
                're_type' => 1,
                'selected' => $data['parent_id'],
                'level' => CL_BULLETIN - 1,
                'excl_cat_id' => $data['cat_id']
            )
        );
    }

    $smarty->assign('data', $data);
    $smarty->assign('cat_select', $catList);

    $smarty->assign('form_action', $isAdd ? 'insert' : 'update');

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list'),
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

//-- 分類添加時的處理
case 'insert':
case 'update':

    /* 權限檢查 */
    adminPriv('bulletin_cat_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $catId = !empty($_POST['cat_id']) ? (int) $_POST['cat_id'] : 0;

    $intData = $strData = array();

    $strData['cat_name'] = isset($_POST['cat_name']) ? $_POST['cat_name'] : '';

    $strData['page_title'] = isset($_POST['page_title']) ? $_POST['page_title'] : '';
    $strData['meta_keywords'] = isset($_POST['meta_keywords']) ? $_POST['meta_keywords'] : '';
    $strData['meta_description'] = isset($_POST['meta_description']) ? $_POST['meta_description'] : '';
    $strData['stats_code_head'] = isset($_POST['stats_code_head']) ? $_POST['stats_code_head'] : '';
    $strData['stats_code_body'] = isset($_POST['stats_code_body']) ? $_POST['stats_code_body'] : '';

    $intData['parent_id'] = !empty($_POST['parent_id']) ? $_POST['parent_id'] : 0;
    $intData['sort_order'] = !empty($_POST['sort_order']) ? $_POST['sort_order'] : 0;
    $intData['is_show'] = isset($_POST['is_show']) ? 1 : 0;
    foreach ($intData as $key => $val) {

        $intData[$key] = (int) $val;
    }
    $data = array_merge(array_map('trim', $strData), $intData);

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=edit&id=' . $catId)
    );

    if ($isInsert) {

        if (catExists($data['cat_name'], $data['parent_id'])) {

           /* 同級別下不能有重複的分類名稱 */
           sysMsg('已存在相同的分類名稱!', 0, $links);
        }

    } else {

        $oldCatList = getCategory(
            'bulletin_category',
            array(
                're_type' => false
            )
        );

        if (!isset($oldCatList[$catId])) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $oldCatData = $oldCatList[$catId];

        $oldCatName = isset($data['old_cat_name']) ? trim($data['old_cat_name']) : '';
        $newCatName = isset($data['cat_name']) ? trim($data['cat_name']) : '';

        /* 判斷分類名是否重複 */
        if ($newCatName != $oldCatName) {

            if (catExists($newCatName, $data['parent_id'], $catId)) {

                sysMsg('已存在相同的分類名稱!', 0, $links);
            }
        }

        /* 判斷上級目錄是否合法 */
        $oldChildren = getCategory(
            'bulletin_category',
            array(
                'cat_id' => $catId,
                're_type' => false
            )
        );

        // 獲得當前分類的所有下級分類
        if (in_array($data['parent_id'], array_keys($oldChildren))) {

            /* 選定的父類是當前分類或當前分類的下級分類 */
            sysMsg('所選擇的上層分類不能是目前分類或者目前分類的下層分類!', 0, $links);
        }

        $oldCatParentId = $oldCatData['parent_id'];
        if (!empty($data['parent_id']) && $data['parent_id'] != $oldCatParentId) {

            $oldChilLevel[] = 0;
            foreach ($oldChildren as $row) {

                $oldChilLevel[] = $row['level'];
            }

            $oldChilMaxLevel = max($oldChilLevel);
            $newCatLevel = $oldCatList[$data['parent_id']]['level'];
            if ($oldChilMaxLevel + $newCatLevel > CL_BULLETIN - 1) {

                sysMsg(sprintf('異動上層分類後，已超過目前系統所限制的分類層級 ( %s 層)', CL_BULLETIN), 1, $links, false);
            }
        }
    }

    $data['parent_ids'] = 0;
    if (!empty($data['parent_id'])) {

        $data['parent_ids'] = getCatParentIds('bulletin_category', $data['parent_id']);
        $data['parent_ids'] = array_reverse($data['parent_ids']);
        $data['parent_ids'] = implode(',', $data['parent_ids']);
    }

    /* 入庫的操作 */
    if ($isInsert) {

        $sql = $db->buildSQL('I', $db->table('bulletin_category'), $data);

    } else {

        if (empty($catId)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $sql = $db->buildSQL('U', $db->table('bulletin_category'), $data, 'cat_id = ' . $catId);
    }

    $db->query($sql);

    /* 編號 */
    $catId = $isInsert ? $db->insertId() : $catId;

    if (!$isInsert) {

        $params = array('cat_id' => $catId, 're_type' => false);
        $children = getCategory('bulletin_category', $params);
        $self = array_shift($children);

        if ($self['parent_id'] != $data['parent_id']) {

            foreach ($children as $row) {

                $parentIds = getCatParentIds('bulletin_category', $row['parent_id']);
                $parentIds = array_reverse($parentIds);
                $parentIds = implode(',', $parentIds);

                $exchange->edit(array('parent_ids' => $parentIds), $row['cat_id']);
            }
        }
    }

    /* 記錄日誌 */
    adminLog($data['cat_name'], $isInsert ? '新增' : '編輯', SCRIPT_TITLE);

    /* 清除緩存*/
    clearCacheFiles();

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $catId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add'));
    }

    /* 提示信息 */
    sysMsg('操作完成', 0, $links);
    break;

//-- 批量轉移分類頁面
case 'move':

    /* 權限檢查 */
    adminPriv('bulletin_cat_manage');

    $catId = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

    $select = getCategory(
        'bulletin_category',
        array(
            're_type' => 1,
            'selected' => $catId
        )
    );
    $smarty->assign('cat_select', $select);
    $smarty->assign('form_action', 'move_cat');

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list'),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '日誌管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_move.html');
    break;

//-- 處理批量轉移分類的處理程序
case 'move_cat':

    /* 權限檢查 */
    adminPriv('bulletin_cat_manage');

    $sourceCatId = !empty($_POST['cat_id']) ? (int) $_POST['cat_id'] : 0;
    $targetCatId = !empty($_POST['target_cat_id']) ? (int) $_POST['target_cat_id'] : 0;

    /* 分類不允許為空 */
    if ($sourceCatId == 0 || $targetCatId == 0) {

        $links[] = array(
            'text' => '返回上一頁',
            'sref' => buildUIRef('?action=move')
        );
        sysMsg('你沒有正確選擇分類!', 0, $links);
    }

    /* 更新分類 */
    $db->query(
        $db->buildSQL(
            'U',
            $db->table('bulletin'),
            array(
                'cat_id' => $targetCatId
            ),
            'cat_id = ' . $sourceCatId
        )
    );

    // 更新擴充分類
    $sql = 'SELECT * FROM ' . $db->table('bulletin_relation_category') . ' ' .
           'WHERE cat_id = ' . $sourceCatId;
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $row['cat_id'] = $targetCatId;
        $db->query(
            $db->buildSQL(
                'R',
                $db->table('bulletin_relation_category'),
                $row
            )
        );
    }

    $db->query(
        $db->buildSQL(
            'D',
            $db->table('bulletin_relation_category'),
            array(),
            'cat_id = ' . $sourceCatId
        )
    );

    /* 清除緩存 */
    clearCacheFiles();

    /* 提示信息 */
    $links[] = array('text' => '返回上一頁', 'sref' => buildUIRef('?action=list'));
    sysMsg('轉移分類已成功完成!', 0, $links);
    break;

//-- 編輯排序
case 'edit_sort_order':

    /* 檢查權限 */
    checkAuthzJson('bulletin_cat_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? (int) $_REQUEST['val'] : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('sort_order' => $val), $id)) {

        /* 記錄日誌 */
        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        /* 清除緩存 */
        clearCacheFiles();

        makeJsonResult($val);

    } else {

        makeJsonError(sprintf('%s 修改失敗！', $name));
    }
    break;

//-- 切換是否顯示
case 'toggle_show':

    /* 檢查權限 */
    checkAuthzJson('bulletin_cat_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? (int) $_REQUEST['val'] : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('is_show' => $val), $id)) {

        /* 記錄日誌 */
        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        /* 清除緩存 */
        clearCacheFiles();

        makeJsonResult($val);

    } else {

        makeJsonError(sprintf('%s 修改失敗！', $name));
    }
    break;

//-- 刪除分類
case 'remove':

    /* 檢查權限 */
    adminPriv('bulletin_cat_manage');

    /* 初始化分類ID並取得分類名稱 */
    $catId = !empty($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($oldData = getCatInfo('bulletin_category', $catId)) {

        /* 當前分類下是否存在 */
        $sql = 'SELECT COUNT(*) FROM ' . $db->table('bulletin') . ' WHERE cat_id = ' . $catId;
        $itemCount = $db->getOne($sql);

        /* 如果不存在下級子分類和問題，則刪除之 */
        if ($oldData['has_children'] == 0 && $itemCount == 0) {

            $sql = 'DELETE FROM ' . $db->table('bulletin_category') . ' WHERE cat_id = ' . $catId;
            $db->query($sql);

            $sql = 'DELETE FROM ' . $db->table('bulletin_relation_category') . ' WHERE cat_id = ' . $catId;
            $db->query($sql);

            /* 清除緩存 */
            clearCacheFiles();

            adminLog($oldData['cat_name'], '刪除', SCRIPT_TITLE);

        } else {

            makeJsonError($oldData['cat_name'] . ' 不是底層分類或項目還存在,不能刪除!');
        }
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
 * 檢查分類是否已經存在
 *
 * @param     string     $catName      分類名稱
 * @param     integer    $parentCat    上級分類
 * @param     integer    $exclude      排除的分類 ID
 *
 * @return    boolean
 */
function catExists($catName, $parentCat, $exclude = 0)
{
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('bulletin_category') . ' AS mt ' .
           'WHERE mt.parent_id = ' . $parentCat . ' ' .
           'AND mt.cat_name = "' . $catName . '" ' .
           'AND mt.cat_id <> ' . $exclude;
    return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
}

/**
 * 產生 JQuery 套件 (nestable) 的樹狀結構 HTML
 *
 * @param     array     $item           階層的樹狀陣列
 * @param     string    $handleClass    拖曳 class 名稱
 *
 * @return    string
 */
function buildHtmlTree(array $item, $handleClass = 'dd-handle')
{
    $html = '';

    if (!empty($item)) {

        $html .= '<ol class="dd-list">';

        foreach ($item as $row) {

            $html .= '<li class="dd-item dd3-item" '
                   . 'data-id="' . $row['cat_id'] . '" data-username="' . $row['cat_name'] . '">'
                   . '<div class="dd-handle dd3-handle">Drag</div>'
                   . '<div class="dd3-content">' . $row['cat_name'] . '</div>';
            if (isset($row['childrens'])) {

                $html .= buildHtmlTree($row['childrens'], $handleClass);
            }
            $html .= '</li>';
        }

        $html .= '</ol>';
    }

    return $html;
}

/**
 * 解析管理者樹狀層級
 *
 * @param     array      $layer       層級陣列
 * @param     integer    $parentId    上層 ID
 *
 * @return    array
 */
function parseTreeLayer(array $layer = array(), $parentId = 0)
{
    $res = array();
    if (!empty($layer)) {

        foreach ($layer as $key => $row) {

            $subLayer = array();
            if (isset($row['children'])) {

                $subLayer = parseTreeLayer($row['children'], $row['id']);
            }
            $res[] = array('id' => $row['id'], 'parent_id' => $parentId, 'sortable' => $key);
            $res = array_merge($res, $subLayer);
        }
    }
    return $res;
}
