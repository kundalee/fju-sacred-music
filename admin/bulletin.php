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
define('SCRIPT_TITLE', '聖樂日誌');

require(dirname(__FILE__) . '/includes/init.php');
require_once(BASE_PATH . 'lib_category.php');

$_CFG['bgcolor'] = '#FFFFFF';
$_CFG['picture_thumb'] = '800 x 450';

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('bulletin'),
    $db,
    'bulletin_id',
    'title'
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

        $params = array(
            'use_tree_name' => true,
            're_type' => false
        );
        if (!empty($_GET['category'])) {

            $params['selected'] = $_GET['category'];
        }
        $smarty->assign('category_options', getCategory('bulletin_category', $params)); // 分類選單

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
case 'copy':

    /* 檢查權限 */
    adminPriv('bulletin_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    $bulletinLinkList = array();

    if ($isAdd) {

        /* 初始化 */
        $time = $_SERVER['REQUEST_TIME'];
        $data = array(
            'bulletin_id' => 0,
            'img_url' => '',
            'show_type' => 0,
            'is_open' => 1,
            'is_best' => 0,
            'sort_order' => 0,
            'is_time' => 0,
            'release_start_time' => $time,
            'release_end_time' => $time + 3600 * 24 * 14
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('bulletin') . ' ' .
               'WHERE bulletin_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $data['content'] = $dus->urlDecode($data['content']);

        $tags = array();
        $sql = 'SELECT td.tag_name FROM ' . $db->table('bulletin_relation_tag') . ' AS rt ' .
               'INNER JOIN ' . $db->table('tags') . ' AS td ' .
               'ON td.tag_id = rt.tag_id ' .
               'WHERE rt.bulletin_id = ' . $_REQUEST['id'] . ' ' .
               'ORDER BY rt.sort_order ASC';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $tags[] = $row['tag_name'];
        }

        $data['tags'] = implode(',', $tags);

        // 取得分類清單
        $sql = 'SELECT cat_id FROM ' . $db->table('bulletin_relation_category') . ' ' .
               'WHERE bulletin_id = ' . $_REQUEST['id'] . ' ' .
               'ORDER BY sort_order ASC';
        $data['category'] = implode(',', $db->getCol($sql));

        // 取得關聯商品
        $bulletinLinkList = getBulletinLinked($_REQUEST['id']);
    }

    $categoryOptions = getCategory(
        'bulletin_category',
        array(
            'use_tree_name' => true,
            're_type' => false
        )
    );

    $tagOptions = array();
    $sql = 'SELECT * FROM ' . $db->table('tags');
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $tagOptions[] = array('text' => $row['tag_name'], 'value' => $row['tag_name']);
    }

    $smarty->assign('category_options', $categoryOptions);
    $smarty->assign('tag_options', $tagOptions);
    $smarty->assign('bulletin_link_list', $bulletinLinkList);

    $smarty->assign('data', $data);
    $smarty->assign('languages', getLanguage());
    $smarty->assign('picture_thumb', $_CFG['picture_thumb']);

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
    adminPriv('bulletin_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $bulletinId = !empty($_POST['bulletin_id']) ? (int) $_POST['bulletin_id'] : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=add&id=' . $bulletinId)
    );

    $fields = array(
        'title',
        'brief',
        'content',
        'link_url',
        'page_title',
        'meta_keywords',
        'meta_description',
        'stats_code_head',
        'stats_code_body'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val]) ? trim($_POST[$val]) : '';
    }

    $data['show_type'] = !empty($_POST['show_type']) ? (int) $_POST['show_type'] : 0;
    $data['sort_order'] = !empty($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
    $data['is_open'] = !empty($_POST['is_open']) ? 1 : 0;
    $data['is_best'] = isset($_POST['is_best']) ? 1 : 0;

    $data['is_time'] = !empty($_POST['is_time']) ? 1 : 0;
    $data['release_start_time'] = isset($_POST['release_start_time'])
        ? trim($_POST['release_start_time'])
        : date('Y-m-d');
    $data['release_end_time'] = isset($_POST['release_end_time'])
        ? trim($_POST['release_end_time'])
        : date('Y-m-d');

    // 修正结束時間小於開始時間
    $data['release_start_time'] = strtotime($data['release_start_time']);
    $data['release_end_time'] = strtotime($data['release_end_time']);
    if ($data['release_start_time'] > $data['release_end_time']) {

        $data['release_end_time'] = $data['release_start_time'];
    }
    $data['release_end_time'] += 86399;
    !$data['is_time'] && $data['release_end_time'] = 0;

    require_once(BASE_PATH . 'cls_image.php');
    $image = new cls_image($_CFG['bgcolor']); // 縮圖背景色

    $imageFields = array(
        'img_url' => array(
            'thumb' => 'auto_thumb',
            'size' => $_CFG['picture_thumb']
        )
    );
    foreach ($imageFields as $key => $row) {

        $handleAutoThumb = false;

        $imgUrl = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        if ($imgUrl != '' && preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $imgUrl)) {

            $oldFileUrl = ROOT_PATH . $imgUrl;
            $oldFileUrl = cIconv($oldFileUrl, getServerOsCharset(), 'AUTO');
            if (is_file($oldFileUrl)) {

                $image->open($oldFileUrl)->moveFile();
                if (!empty($image->error)) {

                    sysMsg($image->error[0]['error'], 1, $links, false);
                }
                $data[$key] = $image->full_path;

                $handleAutoThumb = true;
            }
        }

        // 未上傳，如果自動選擇生成，且上傳了圖片，生成所略圖
        if ($handleAutoThumb && isset($row['thumb']) && isset($_POST[$row['thumb']])) {

            // 如果設置縮略圖大小不為0，生成縮略圖
            list($width, $height) = explode(' x ', $row['size']);
            $image->makeThumb($width, $height, 1);
            $image->autoSave();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $thumbUrl = $image->full_path;
            dropFile($data[$key]);

            $data[$key] = $thumbUrl;
        }
    }

    $tempUploadFile = array(
        'file_url'
    );

    foreach ($tempUploadFile as $val) {

        if (isset($data[$val]) &&
            preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $data[$val])) {

            $oldFileUrl = ROOT_PATH . $data[$val];
            if (is_file(cIconv($oldFileUrl, getServerOsCharset(), 'AUTO'))) {

                preg_match(
                    '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                    $data[$val],
                    $pathinfo
                );

                $i = 0;
                do {

                    $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                    $data[$val] = DATA_DIR . '/files/' . $newFileName;
                    $i++;

                } while (!moveFile($oldFileUrl, ROOT_PATH . $data[$val], false));

            } else {

                $data[$val] = '';
            }
        }
    }

    /* 計算打開方式 */
    if (isset($data['file_url']) && $data['file_url'] == '') {

        $data['open_type'] = 0;

    } else {

        $data['open_type'] = $data['content'] == '' ? 1 : 2;
    }

    $data['content'] = $dus->urlEncode($data['content']);

    $data['update_time'] = $_SERVER['REQUEST_TIME'];

    if ($isInsert) {

        /* 插入數據 */
        $data['add_time'] = $data['update_time'];

        $sql = $db->buildSQL('I', $db->table('bulletin'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('bulletin') . ' ' .
               'WHERE bulletin_id = ' . $bulletinId;
        if ($bulletinId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        if (isset($data['img_url']) && $oldData['img_url'] != '') {

            dropFile($oldData['img_url']);
        }

        $sql = $db->buildSQL('U', $db->table('bulletin'), $data, 'bulletin_id = ' . $bulletinId);
    }

    $db->query($sql);

    /* 編號 */
    $bulletinId = $isInsert ? $db->insertId() : $bulletinId;

    handleRelationCategory(
        $bulletinId,
        !empty($_POST['cat_id']) ? $_POST['cat_id'] : array()
    );

    handleRelationTag(
        $bulletinId,
        !empty($_POST['tags']) ? $_POST['tags'] : ''
    );

    /* 清空緩存 */
    clearCacheFiles();

    /* 記錄日誌 */
    adminLog(
        $data['title'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $bulletinId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 編輯排序
case 'edit_sort_order':

    checkAuthzJson('bulletin_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? (int) $_REQUEST['val'] : 0;

    $name = $exchange->getName($id, 'title');
    if ($exchange->edit(array('sort_order' => $val, 'update_time' => $_SERVER['REQUEST_TIME']), $id)) {

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
    checkAuthzJson('bulletin_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? 1 : 0;

    $name = $exchange->getName($id, 'title');
    if ($exchange->edit(array('is_open' => $val, 'update_time' => $_SERVER['REQUEST_TIME']), $id)) {

        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        clearCacheFiles();
        makeJsonResult($val);

    } else {

        makeJsonError($db->error());
    }
    break;

//-- 刪除圖片
case 'cancel_picture':

    checkAuthzJson('bulletin_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    /* 刪除圖片文件 */
    if ($oldImgUrl = $exchange->getName($id, 'img_url')) {

        dropFile($oldImgUrl);

        $exchange->edit(
            array(
                'img_url' => '',
                'update_time' => $_SERVER['REQUEST_TIME']
            ),
            $id
        );

        clearCacheFiles();
    }

    makeJsonResult(0, '圖案刪除成功');
    break;

//-- 刪除
case 'remove':

    checkAuthzJson('bulletin_manage');

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
    adminPriv('bulletin_manage');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $numDrop = handleItemDrop($_POST['checkboxes']);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    sysMsg(sprintf('您已經成功刪除 %d 筆', $numDrop), 0, $links);
    break;

//-- 搜索商品，僅返回名稱及ID
case 'get_bulletin_list':

    $bulletinId = !empty($_POST['bulletin_id']) ? (int) $_POST['bulletin_id'] : 0;

    $operator = 'mt.bulletin_id <> ' . $bulletinId . ' ';
    if (isset($_POST['category'])
        && ($_POST['category'] = intval($_POST['category'])) != 0) {

        $cats = (array) $_REQUEST['category'];
        foreach ($cats as $val) {

            $params = array('cat_id' => $val, 're_type' => false);
            $cats = array_unique(array_merge($cats, array_keys(getCategory('bulletin_category', $params))));
        }

        $children = $db->in($cats, 'mt.cat_id');
        $sql = 'SELECT bulletin_id FROM ' . $db->table('bulletin_relation_category') . ' AS mt ' .
               'WHERE ' . $children;
        $ids = $db->getCol($sql);
        $operator .= 'AND (' . $children . ' OR ' . $db->in($ids, 'mt.bulletin_id') . ') ';
    }

    /* 關鍵字 */
    if (isset($_POST['keywords'])
        && ($_POST['keywords'] = trim($_POST['keywords'])) != '') {

        $operator .= 'AND mt.title LIKE "%' . $queryString . '%" ';
    }

    $options = array();
    $sql = 'SELECT mt.bulletin_id, mt.title ' .
           'FROM ' . $db->table('bulletin') . ' AS mt ' .
           'WHERE ' . $operator;
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $options[] = array(
            'value' => $row['bulletin_id'],
            'text' => $row['title'],
            'data' => ''
        );
    }

    makeJsonResult($options);
    break;

//-- 把商品加入關聯
case 'add_rel_bulletin':

    /* 檢查權限 */
    checkAuthzJson('bulletin_manage');

    $relGoodsIds = !empty($_POST['add_ids']) ? (array) $_POST['add_ids'] : array();
    $bulletinId = !empty($_POST['bulletin_id']) ? (int) $_POST['bulletin_id'] : 0;
    $isDouble = !empty($_POST['is_single']) ? 0 : 1;

    foreach ($relGoodsIds as $val) {

        if ($isDouble) {

            /* 雙向關聯 */
            $db->query(
                $db->buildSQL(
                    'I',
                    $db->table('bulletin_relation_link'),
                    array(
                        'bulletin_id' => $val,
                        'bulletin_link_id' => $bulletinId,
                        'is_double' => $isDouble,
                        'admin_id' => $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
                    )
                ),
                'SILENT'
            );
        }

        $db->query(
            $db->buildSQL(
                'I',
                $db->table('bulletin_relation_link'),
                array(
                    'bulletin_id' => $bulletinId,
                    'bulletin_link_id' => $val,
                    'is_double' => $isDouble,
                    'admin_id' => $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
                )
            ),
            'SILENT'
        );
    }

    $options = array();
    foreach (getBulletinLinked($bulletinId) as $val) {

        $options[] = array(
            'value' => $val['bulletin_id'],
            'text' => $val['title'],
            'data' => ''
        );
    }

    clearCacheFiles();
    makeJsonResult($options);
    break;

//-- 刪除關聯商品
case 'drop_rel_bulletin':

    /* 檢查權限 */
    checkAuthzJson('bulletin_manage');

    $relGoodsIds = !empty($_POST['drop_ids']) ? (array) $_POST['drop_ids'] : array();
    $bulletinId = !empty($_POST['bulletin_id']) ? (int) $_POST['bulletin_id'] : 0;
    $isDouble = !empty($_POST['is_single']) ? 0 : 1;

    if ($isDouble) {

        $sql = 'DELETE FROM ' . $db->table('bulletin_relation_link') . ' ' .
               'WHERE bulletin_link_id = ' . $bulletinId . ' ' .
               'AND bulletin_id ' . $db->in($relGoodsIds) . ' ';
    } else {

        $sql = 'UPDATE ' . $db->table('bulletin_relation_link') . ' SET ' .
               'is_double = 0 ' .
               'WHERE bulletin_link_id = ' . $bulletinId . ' ' .
               'AND bulletin_id ' . $db->in($relGoodsIds) . ' ';
    }
    if ($bulletinId == 0) {

        $sql .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }
    $db->query($sql);

    $sql = 'DELETE FROM ' .$db->table('bulletin_relation_link') . ' ' .
           'WHERE bulletin_id = ' . $bulletinId . ' ' .
           'AND bulletin_link_id ' . $db->in($relGoodsIds);
    if ($bulletinId == 0) {

        $sql .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }
    $db->query($sql);

    $options = array();
    foreach (getBulletinLinked($bulletinId) as $val) {

        $options[] = array(
            'value' => $val['bulletin_id'],
            'text'  => $val['title'],
            'data'  => ''
        );
    }

    clearCacheFiles();
    makeJsonResult($options);
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 獲得指定商品相關的商品
 *
 * @param     integer    $bulletinId
 *
 * @return    array
 */
function getBulletinLinked($bulletinId)
{
    $sql = 'SELECT lg.bulletin_link_id AS bulletin_id, lg.is_double, dg.title ' .
           'FROM ' . $GLOBALS['db']->table('bulletin_relation_link') . ' AS lg ' .
           'INNER JOIN ' . $GLOBALS['db']->table('bulletin') . ' AS dg ' .
           'ON dg.bulletin_id = lg.bulletin_link_id ' .
           'WHERE lg.bulletin_id = ' . $bulletinId . ' ';

    if ($bulletinId == 0) {

        $sql .= 'AND lg.admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }
    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row as $key => $val) {

        $linkedType = $val['is_double'] == 0 ? '單向關聯' : '雙向關聯';

        $row[$key]['title'] = $val['title'] . ' -- [' . $linkedType . ']';

        unset($row[$key]['is_double']);
    }

    return $row;
}


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
    $sql = 'SELECT mt.bulletin_id, mt.title, mt.img_url ' .
           'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.bulletin_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['bulletin_id']] = $row['title'];

        dropFile($row['img_url']);
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('bulletin') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'bulletin_id');
        $GLOBALS['db']->query($sql);

        foreach ($dropItem as $name) {

            adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
        }

        clearCacheFiles();
    }

    return count($dropItem);
}

/**
 * 保存擴展分類
 *
 * @param     int      $bulletinId     項目編號
 * @param     array    $catList        分類數組
 *
 * @return    void
 */
function handleRelationCategory($bulletinId, array $catList)
{
    $catList = array_unique($catList);

    // 用第一個當作商品主分類 ID
    $GLOBALS['db']->query(
        $GLOBALS['db']->buildSQL(
            'U',
            $GLOBALS['db']->table('bulletin'),
            array(
                'cat_id' => intval(current($catList))
            ),
            'bulletin_id = ' . $bulletinId
        )
    );

    /* 查詢現有的擴展分類 */
    $sql = 'SELECT cat_id FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' ' .
           'WHERE bulletin_id = ' . $bulletinId;
    $existList = $GLOBALS['db']->getCol($sql);

    /* 刪除不再有的分類 */
    $deleteList = array_diff($existList, $catList);
    if ($deleteList) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' ' .
               'WHERE bulletin_id = ' . $bulletinId . ' ' .
               'AND cat_id ' . $GLOBALS['db']->in($deleteList);
        $GLOBALS['db']->query($sql);
    }

    /* 添加新加的分類 */
    $addList = array_diff($catList, $existList, array(0));
    foreach ($addList as $catId) {

        // 插入記錄
        $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'R',
                $GLOBALS['db']->table('bulletin_relation_category'),
                array(
                    'bulletin_id' => $bulletinId,
                    'cat_id' => $catId
                )
            )
        );
    }
}

/**
 * 保存擴展標籤
 *
 * @param     int      $bulletinId    項目編號
 * @param     array    $tagList       標籤數組
 *
 * @return    void
 */
function handleRelationTag($bulletinId, $tagList)
{
    $relationList = array();

    if ($tagList != '') {

        $oldTags = array();

        /* 查詢現有的擴展分類 */
        $sql = 'SELECT tag_id, tag_name FROM ' . $GLOBALS['db']->table('tags');
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $oldTags[$row['tag_id']] = $row['tag_name'];
        }

        $tagList = explode(',', $tagList);
        $tagList = array_map('trim', $tagList);
        foreach ($tagList as $key => $val) {

            if (in_array($val, $oldTags)) {

                $tagId = array_search($val, $oldTags);

            } else {

                $GLOBALS['db']->query(
                    $GLOBALS['db']->buildSQL(
                        'I',
                        $GLOBALS['db']->table('tags'),
                        array(
                            'tag_name' => $val,
                            'add_time' => $_SERVER['REQUEST_TIME'],
                            'update_time' => $_SERVER['REQUEST_TIME']
                        )
                    )
                );

                $tagId = $GLOBALS['db']->insertId();
            }

            $relationList[] = $tagId;
        }
    }

    $relationList = array_unique($relationList);

    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('bulletin_relation_tag') . ' ' .
           'WHERE bulletin_id = ' . $bulletinId;
    $GLOBALS['db']->query($sql);

    foreach ($relationList as $key => $tagId) {

        $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'R',
                $GLOBALS['db']->table('bulletin_relation_tag'),
                array(
                    'bulletin_id' => $bulletinId,
                    'tag_id' => $tagId,
                    'sort_order' => $key
                )
            )
        );
    }
}

/**
 * 獲取列表
 *
 * @return    array
 */
function getListPage()
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'bulletin_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';
    if (!empty($_REQUEST['category']) && is_array($_REQUEST['category'])) {

        $cats = $_REQUEST['category'];
        foreach ($cats as $val) {

            $params = array('cat_id' => $val, 're_type' => false);
            $cats = array_unique(array_merge($cats, array_keys(getCategory('bulletin_category', $params))));
        }

        $children = $GLOBALS['db']->in($cats, 'mt.cat_id');
        $sql = 'SELECT bulletin_id FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' AS mt ' .
               'WHERE ' . $children;
        $queryIds = $GLOBALS['db']->getCol($sql);
        $operator .= 'AND (' . $children . ' OR ' . $GLOBALS['db']->in($queryIds, 'mt.bulletin_id') . ') ';
        $filter['category'] = $_REQUEST['category'];
    }

    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND title LIKE "%' . $queryString . '%" ';

        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    /* 總數 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'WHERE 1 ' . $operator;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();

    if ($filter['record_count']) {

        $category = getCategory(
            'bulletin_category',
            array(
                'use_tree_name' => true,
                're_type' => false
            )
        );

        /* 獲取數據 */
        $sql = 'SELECT mt.bulletin_id, mt.cat_id, mt.is_open, mt.sort_order, ' .
               'mt.add_time, mt.is_time, mt.release_start_time, mt.release_end_time, ' .
               'mt.title, ' .

               '(SELECT GROUP_CONCAT(bc.cat_id) ' .
               'FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' AS bc ' .
               'WHERE bc.bulletin_id = mt.bulletin_id ' .
               'ORDER BY bc.sort_order) AS relation_category ' .

               'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .

               'WHERE 1 ' . $operator . ' ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $relationCategory = array();
            if ($row['relation_category'] != '') {

                foreach (explode(',', $row['relation_category']) as $val) {

                    if (isset($category[$val])) {

                        $relationCategory[] = $category[$val]['tree_name'];
                    }
                }
            }
            $row['relation_category'] = $relationCategory;

            if (isset($category[$row['cat_id']])) {

                $row['cat_name'] = $category[$row['cat_id']]['cat_name'];
                $row['tree_name'] = $category[$row['cat_id']]['tree_name'];
            }

            $row['view_url'] = buildUri(
                'blog',
                array(
                    'action' => 'view',
                    'id' => $row['bulletin_id']
                ),
                array(
                    'append' => $row['title']
                ),
                $GLOBALS['_CFG']['enable_url_rewrite']
            );

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
