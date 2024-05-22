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
define('SCRIPT_TITLE', '網站樣版');

require(dirname(__FILE__) . '/includes/init.php');

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('template'),
    $db,
    'tpl_id',
    'tpl_name'
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

        if (adminPriv('sys', false)) {

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

        /* 列表頁面 */
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
    adminPriv('web_template');

    $isAdd = $_REQUEST['action'] == 'add'; // 添加還是編輯的標識

    if ($isAdd) {

        adminPriv('sys');

        /* 初始化 */
        $data = array(
            'tpl_id' => 0,
            'tpl_name' => '',
            'tpl_code' => '',
            'is_show' => 1
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('template') . ' ' .
               'WHERE tpl_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }
    }
    $smarty->assign('data', $data);
    $smarty->assign('languages', getLanguage());

    $smarty->assign('group_list', getSettings($data['tpl_id']));

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
    adminPriv('web_template');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $defaultLangId = $_CFG['default_lang_id'];
    $tplId = !empty($_POST['tpl_id']) ? intval($_POST['tpl_id']) : 0;

    $data['tpl_name'] = isset($_POST['tpl_name']) ? trim($_POST['tpl_name']) : '';
    $data['tpl_code'] = isset($_POST['tpl_code']) ? trim($_POST['tpl_code']) : '';

    if (adminPriv('sys', false)) {

        $data['tpl_code'] = isset($_POST['tpl_code']) ? trim($_POST['tpl_code']) : '';
    }

    if (!isset($data['tpl_code']) || $data['tpl_code'] == '') {

        $data['tpl_code'] = sprintf('%X', crc32(uniqid()));
    }

    if (!$exchange->isOnly('tpl_code', $data['tpl_code'], $tplId)) {

        sysMsg(sprintf('版面代碼 %s 已經存在', $data['tpl_code']));
    }

    $data['update_time'] = $_SERVER['REQUEST_TIME'];

    if ($isInsert) {

        $data['add_time'] = $data['update_time'];

        $sql = $db->buildSQL('I', $db->table('template'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('template') . ' ' .
               'WHERE tpl_id = ' . $tplId;
        if ($tplId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $sql = $db->buildSQL('U', $db->table('template'), $data, 'tpl_id = ' . $tplId);
    }

    $db->query($sql);

    /* 編號 */
    $tplId = $isInsert ? $db->insertId() : $tplId;

    $sql = 'SELECT COUNT(*) FROM ' .  $db->table('template_config') . ' ' .
           'WHERE tpl_id = ' . $tplId;
    if (!$db->getOne($sql)) {

        $fields = array(
            'lang_id',
            'id',
            'parent_id',
            'code',
            'name',
            'desc',
            'type',
            'value_range',
            'field_size',
            'upload_dir',
            'value',
            'sort_order',
            'is_display'
        );

        $sql = 'REPLACE INTO ' . $db->table('template_config') . ' '
             . '(`' . implode('`, `', array_merge(array('tpl_id'), $fields)) . '`) '
             . 'SELECT ' . $tplId . ', `' . implode('`, `', $fields) . '` '
             . 'FROM ' . $db->table('template_config') . ' '
             . 'WHERE tpl_id = 0';
        $db->query($sql);
    }

    $sql = 'SELECT sc.code FROM ' . $db->table('template_config') . ' AS sc ' .
           'WHERE sc.tpl_id = ' . $tplId . ' ' .
           'AND sc.type = "password"';
    $passwordFields = $db->getCol($sql);
    $passwordFields = array_unique($passwordFields);

    /* 處理上傳檔案 */
    $fileVarList = $fileFields = array();
    $sql = 'SELECT sc.* ' .
           'FROM ' . $db->table('template_config') . ' AS sc ' .
           'WHERE tpl_id = ' . $tplId . ' ' .
           'AND sc.type IN("file", "image")';
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $fileVarList[$row['lang_id']][$row['code']] = $row;
        $fileFields[] = $row['code'];
    }
    $fileFields = array_unique($fileFields);

    // 載入圖片處理類別
    require_once(BASE_PATH . 'cls_image.php');

    if (!empty($_POST['config']['value'])) {

        foreach ($_POST['config']['value'] as $lKey => $lData) {

            $lKey = (int) $lKey;

            foreach ($lData as $code => $val) {

                $val = trim($val);
                $code = trim($code);

                if ($val != '' && in_array($code, $passwordFields)) {

                    $val = $dus->compilePassword($val, 'ENCODE');

                } elseif ($val != '' && in_array($code, $fileFields) && preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $val)) {

                    $oldFileUrl = ROOT_PATH . $val;
                    $oldFileUrl = cIconv($oldFileUrl, getServerOsCharset(), 'AUTO');

                    if (is_file($oldFileUrl)) {

                        if (
                            $fileVarList[$lKey][$code]['type'] == 'image'
                        ) {

                            $bgColor = '';
                            $thumbWidth = $thumbHeight = $thumbType = 0;

                            if ($fileVarList[$lKey][$code]['field_size'] != '') {

                                $tmpVar = explode('|', $fileVarList[$lKey][$code]['field_size']);

                                isset($tmpVar[0]) && $thumbWidth = (int) $tmpVar[0];
                                isset($tmpVar[1]) && $thumbHeight = (int) $tmpVar[1];
                                isset($tmpVar[2]) && $thumbType = (int) $tmpVar[2];
                                isset($tmpVar[3]) && $bgColor = (int) $tmpVar[3];
                            }

                            $image = new cls_image($bgColor); // 建立圖片物件
                            $image->open($oldFileUrl);
                            if ($thumbWidth > 0 && $thumbHeight > 0) {

                                $image->makeThumb($thumbWidth, $thumbHeight, $thumbType);

                            } elseif ($thumbWidth > 0) {

                               $image->makeThumbWidth($thumbWidth);

                            } elseif ($thumbHeight > 0) {

                                $image->makeThumbHeight($thumbHeight);
                            }

                            $image->autoPathSave(ROOT_PATH . $fileVarList[$lKey][$code]['upload_dir']);
                            if (!empty($image->error)) {

                                sysMsg($image->error[0]['error']);
                            }

                            dropFile($oldFileUrl);

                            $val = $image->full_path;

                            if (!empty($fileVarList[$lKey][$code]['value'])) {

                                dropFile(ROOT_PATH . $fileVarList[$lKey][$code]['value']);
                            }

                        } else {

                            preg_match(
                                '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                                $val,
                                $pathinfo
                            );

                            $i = 0;
                            do {

                                $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                                $val = DATA_DIR . '/files/' . $newFileName;
                                $i++;

                            } while (!moveFile($oldFileUrl, ROOT_PATH . $val, false));
                        }

                    } else {

                        $val = '';
                    }
                }

                $db->query(
                    $db->buildSQL(
                        'U',
                        $db->table('template_config'),
                        array(
                            'value' => $dus->urlEncode($val),
                            'is_display' => !empty($_POST['config']['is_display'][$lKey][$code]) ? 1 : 0
                        ),
                        'tpl_id = ' . $tplId . ' AND ' .
                        'lang_id = ' . (int) $lKey . ' AND ' .
                        'code = "' . $code . '"'
                    )
                );
            }
        }
    }

    /* 記錄日誌 */
    adminLog(
        $data['tpl_name'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 清空緩存 */
    clearCacheFiles();

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $tplId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 刪除上傳文件
case 'del':

    checkAuthzJson('web_template');

    /* 取得參數 */
    if (isset($_POST['code'])) {

        $code = trim($_POST['code']);

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $langId = !empty($_POST['lang_id']) ? (int) $_POST['lang_id'] : 0;

    $sql = 'SELECT sc.name, sc.value ' .
           'FROM ' . $db->table('template_config') . ' AS sc ' .
           'WHERE sc.type IN("file", "image") ' .
           'AND sc.code = "' . $code . '" ' .
           'AND sc.lang_id = "' . $langId . '"';
    if ($oldFile = $db->getRow($sql)) {

        if (true == updateConfigure($langId, $code, '')) {

            clearCacheFiles(); // 清空暫存檔

            // 刪除檔案
            dropFile($oldFile['value']);

            makeJsonResult('', '檔案刪除成功');
        }
    }

    makeJsonError('檔案刪除失敗');
    break;

//-- 刪除
case 'remove':

    checkAuthzJson('sys');

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
    adminPriv('sys');

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
    $sql = 'SELECT mt.tpl_id, mt.tpl_name ' .
           'FROM ' . $GLOBALS['db']->table('template') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.tpl_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['tpl_id']] = $row['tpl_name'];
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('template') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'tpl_id');
        $GLOBALS['db']->query($sql);

        foreach ($dropItem as $name) {

            adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
        }

        clearCacheFiles();
    }

    return count($dropItem);
}

/**
 * 設置系統設置
 *
 * @param     integer   $langId
 * @param     string    $code
 * @param     string    $val
 *
 * @return    boolean
 */
function updateConfigure($tplId, $langId, $code, $val = '')
{
    if (!empty($code)) {

        return $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'U',
                $GLOBALS['db']->table('template_config'),
                array(
                    'value' => $val
                ),
                'tpl_id = ' . $tplId . ' AND ' .
                'lang_id = ' . (int) $langId . ' AND ' .
                'code = "' . $code . '"'
            )
        );
    }

    return true;
}

/**
 * 取得設定資訊
 *
 * @param     array    $groups      需要取得的設定群組
 * @param     array    $excludes    不需要取得的設定群組
 *
 * @return    array
 */
function getSettings($tplId, $groups = null, $excludes = null)
{
    $item = array();

    if (!empty($groups)) {

        foreach ($groups as $key => $val) {

            !is_numeric($val) && $item[] = $val;
        }
    }
    if (!empty($excludes)) {

        foreach ($excludes as $key => $val) {

            !is_numeric($val) && $item[] = $val;
        }
    }

    if (!empty($item)) {

        $sql = 'SELECT sc.id, sc.code FROM ' . $GLOBALS['db']->table('template_config') . ' AS sc ' .
               'WHERE tpl_id = ' . $tplId . ' ' .
               'AND ' . $GLOBALS['db']->in($item, 'sc.code');
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $code[$row['code']] = $row['id'];
        }
    }

    /**
     * 取出全部設定陣列
     */
    $oper = 'tpl_id = ' . $tplId . ' AND type <> "hidden" ';

    // 如果非系統管理者
    if (false == adminPriv('sys', false)) {

        $oper .= 'AND is_display = 1 ';
    }
    // 取出需要設定群組
    if (!empty($groups)) {

        foreach ($groups as $key => $val) {

            if (is_numeric($val)) {

                $oper .= 'AND (sc.id = ' . $val . ' OR sc.parent_id = ' . $val . ') ';

            } elseif (isset($code[$val])) {

                $oper .= 'AND (sc.code = "' . $val . '" OR sc.parent_id = ' . $code[$val] . ') ';
            }
        }
    }
    // 排除不需要設定群組
    if (!empty($excludes)) {

        foreach ($excludes as $key => $val) {

            if (is_numeric($val)) {

                $oper .= 'AND (sc.id <> ' . $val . ' AND sc.parent_id <> ' . $val . ') ';

            } elseif (isset($code[$val])) {

                $oper .= 'AND (sc.code <> "' . $val . '" AND sc.parent_id <> ' . $code[$val] . ') ';
            }
        }
    }

    // 重構設定陣列
    $groupList = array();

    $sql = 'SELECT * FROM ' . $GLOBALS['db']->table('template_config') . ' AS sc '
         . 'WHERE ' . $oper . ' '
         . 'ORDER BY sc.parent_id ASC, sc.sort_order ASC, sc.id ASC';
    $res = $GLOBALS['db']->query($sql);
    while ($item = $GLOBALS['db']->fetchAssoc($res)) {

        $item['value'] = $GLOBALS['dus']->urlDecode($item['value']);
        $item['name'] = !empty($item['name']) ? $item['name'] : $item['code'];

        if ($item['parent_id'] == 0) {

            // 設定群組
            if ($item['type'] == 'group') {

                $groupList[$item['id']] = $item;
            }

        } else {

            // 設定值
            if (!isset($groupList[$item['parent_id']])) {

                continue;
            }

            switch ($item['type']) {
                // 文字區域
                case 'textarea':

                    if ($item['field_size'] != '') {

                        list($sizeCols, $sizeRows) = array_map('intval', explode('|', $item['field_size']));
                        unset($item['field_size']);

                        $item['field_size'] = array('cols' => $sizeCols, 'rows' => $sizeRows);
                    }
                    break;

                // 選擇項
                case 'select':
                case 'options':

                    if (strlen($item['value_range']) > 0) {

                        foreach (explode(',', $item['value_range']) as $k => $v) {

                            list(
                                $item['store_options'][$k]['value'],
                                $item['store_options'][$k]['name']
                            ) = explode('|', $v, 2);
                        }
                    }
                    break;

                // 滑塊
                case 'slider':

                    $item['config'] = sprintf(
                        '{%s}',
                        htmlentities(str_replace('"', '\'', $item['value_range']), ENT_QUOTES, 'UTF-8')
                    );
                    break;

                // 密碼欄位
                case 'password':

                    $item['value'] = $GLOBALS['dus']->compilePassword($item['value']);
                    break;
            }

            if (isset($item['lang_id']) && $item['lang_id'] > 0) {

                $groupList[$item['parent_id']]['langs'][$item['lang_id']][] = $item;

            } else {

                $groupList[$item['parent_id']]['vars'][] = $item;
            }
        }
    }

    // 刪除空的設定群組
    foreach ($groupList as $key => $val) {

        if (!(isset($val['vars']) || isset($val['langs']))) {

            unset($groupList[$key]);
        }
    }

    return $groupList;
}

/**
 * 獲取列表
 *
 * @return    array
 */
function getListPage()
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'tpl_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';
    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND mt.tpl_name LIKE "%' . $queryString . '%" ';
        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    /* 總數 */
    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('template') . ' AS mt ' .
           'WHERE 1 ' . $operator;
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    $filter = pageAndSize($filter);
    unset($_SESSION[SHOP_SESS]['filter']);
    $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;

    $itemArr = array();
    if ($filter['record_count']) {

        /* 獲取數據 */
        $sql = 'SELECT mt.tpl_id, mt.tpl_name ' .
               'FROM ' . $GLOBALS['db']->table('template') . ' AS mt ' .
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
