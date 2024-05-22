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
define('SCRIPT_TITLE', '重點歌曲');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require(dirname(__FILE__) . '/includes/init.php');

$_CFG['bgcolor'] = '#FFFFFF';
$_CFG['picture_large'] = '800 x 800';
$_CFG['picture_thumb'] = '200 x 200';

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('focus_info'),
    $db,
    'song_id',
    'song_code'
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

        $position = assignUrHere(SCRIPT_TITLE);
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
    adminPriv('focus_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    $songLinkList = array();

    if ($isAdd) {

        /* 初始化 */
        $time = $_SERVER['REQUEST_TIME'];
        $data = array(
            'song_id' => 0,
            'show_img' => 1
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('focus_info') . ' ' .
               'WHERE song_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $tags = array();
        $sql = 'SELECT td.tag_name FROM ' . $db->table('focus_relation_tag') . ' AS rt ' .
               'INNER JOIN ' . $db->table('tags') . ' AS td ' .
               'ON td.tag_id = rt.tag_id ' .
               'WHERE rt.song_id = ' . $_REQUEST['id'] . ' ' .
               'ORDER BY rt.sort_order ASC';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $tags[] = $row['tag_name'];
        }

        $data['tags'] = implode(',', $tags);
    }

    $tagOptions = array();
    $sql = 'SELECT * FROM ' . $db->table('tags');
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $tagOptions[] = array('text' => $row['tag_name'], 'value' => $row['tag_name']);
    }
    $smarty->assign('tag_options', $tagOptions);

    $smarty->assign('data', $data);
    $smarty->assign('picture_thumb', $_CFG['picture_thumb']);
    $smarty->assign('picture_large', $_CFG['picture_large']);

    $smarty->assign('form_action', $isAdd ? 'insert' : ($_REQUEST['action'] == 'edit' ? 'update' : 'insert'));

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list', true),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $position = assignUrHere(SCRIPT_TITLE);
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
    adminPriv('focus_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=edit&id=' . $songId)
    );

    $fields = array(
        'song_code',
        'zh_song_title',
        'en_song_title_1',
        'en_song_title_2',
        'tune_name',
        'first_melody',
        'first_lyric',
        'rhythm',
        'reference',
        'copyright',
        'chinese_lyrics',

        'composer_name',
        'lyricist_name',

        'file_url_1',
        'file_url_2',
        'file_url_3',
        'file_url_4',
        'file_url_5',

        'reference_link_1',
        'reference_link_2',
        'reference_link_3',
        'reference_link_4',
        'reference_link_5',
        'reference_link_6',

        'song_theme',
        'song_prepared',
        'song_remark',
        'page_title',
        'meta_keywords',
        'meta_description',
        'page_code_head',
        'page_code_body'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val]) ? trim($_POST[$val]) : '';
    }

    $data['is_open'] = isset($_POST['is_open']) ? 1 : 0;
    $data['show_img'] = isset($_POST['show_img']) ? 1 : 0;

    /* 檢查編號是否重複 */
    if ($data['song_code'] != '') {

        $sql = 'SELECT COUNT(*) FROM ' . $db->table('focus_info') . ' ' .
               'WHERE song_code = "' . $data['song_code'] . '" ' .
               'AND song_id <> ' . $songId;
        if ($db->getOne($sql) > 0) {

            sysMsg('您輸入的編號已存在，請換一個', 1, array(), false);
        }
    }

    /* 如果沒有輸入編號則自動生成一個編號 */
    if ($data['song_code'] == '') {

        $sql = 'SELECT IFNULL(MAX(song_id), 0) + 1 FROM ' . $db->table('focus_info');
        $maxId = $isInsert ? $db->getOne($sql) : $songId;

        $data['song_code'] = generateCode($maxId, 'song');
    }

    require_once(BASE_PATH . 'cls_image.php');
    $image = new cls_image($_CFG['bgcolor']); // 縮圖背景色

    $imgUrl = isset($_POST['picture_large']) ? trim($_POST['picture_large']) : '';
    if ($imgUrl != '' && preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $imgUrl)) {

        $oldFileUrl = ROOT_PATH . $imgUrl;
        $oldFileUrl = cIconv($oldFileUrl, getServerOsCharset(), 'AUTO');
        if (is_file($oldFileUrl)) {

            $image->open($oldFileUrl)->moveFile();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $oldImg = $image->full_path;

            // 如果設置縮略圖大小不為0，生成縮略圖
            list($width, $height) = explode(' x ', $_CFG['picture_large']);
            $image->open(ROOT_PATH . $oldImg)->makeThumb($width, $height, 1);
            $image->autoSave();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $data['picture_large'] = $image->full_path;

            // 如果設置縮略圖大小不為0，生成縮略圖
            list($width, $height) = explode(' x ', $_CFG['picture_thumb']);
            $image->open(ROOT_PATH . $oldImg)->makeThumb($width, $height, 1);
            $image->autoSave();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $data['picture_thumb'] = $image->full_path;

            dropFile($oldImg);
        }
    }

    $tempUploadFile = array(
        'file_url_1',
        'file_url_2',
        'file_url_3',
        'file_url_4'
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

                $uploadPath = DATA_DIR . '/files/' . date('Ym') . '/';

                if (!makeDir(ROOT_PATH . $uploadPath)) {

                    sysMsg(sprintf('目錄 % 不存在或不可寫', $uploadPath), 1, $links, false);
                }

                $i = 0;
                do {

                    $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                    $data[$val] = $uploadPath . $newFileName;
                    $i++;

                } while (!moveFile($oldFileUrl, ROOT_PATH . $data[$val], false));

            } else {

                $data[$val] = '';
            }
        }
    }

    if ($isInsert) {

        /* 插入數據 */
        $sql = $db->buildSQL('I', $db->table('focus_info'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('focus_info') . ' ' .
               'WHERE song_id = ' . $songId;
        if ($songId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        if (isset($data['picture_thumb']) && $oldData['picture_thumb'] != '') {

            dropFile(array(
                $oldData['picture_large'],
                $oldData['picture_thumb']
            ));
        }

        $sql = $db->buildSQL('U', $db->table('focus_info'), $data, 'song_id = ' . $songId);
    }

    $db->query($sql);

    /* 編號 */
    $songId = $isInsert ? $db->insertId() : $songId;

    if ($isInsert) {

        $db->query(
            $db->buildSQL(
                'U',
                $db->table('focus_relation_song'),
                array(
                    'song_id' => $songId
                ),
                'song_id = 0 AND ' .
                'admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
            )
        );
    }

    handleRelationTag(
        $songId,
        isset($_POST['tags']) ? $_POST['tags'] : ''
    );

    /* 清空緩存 */
    clearCacheFiles();

    /* 記錄日誌 */
    adminLog(
        $data['song_code'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $songId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 搜尋歌曲
case 'search_song':

    $bookId = !empty($_POST['book_id']) ? (int) $_POST['book_id'] : 0;

    /* 搜尋欄位 */
    $operator = '1 ';

    // 關鍵字
    if (isset($_POST['search_keyword'])
        && ($_POST['search_keyword'] = trim($_POST['search_keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['search_keyword']);

        $operator .= 'AND (mt.song_code LIKE "%' . $queryString . '%" ' .
                     'OR mt.zh_song_title LIKE "%' . $queryString . '%" ' .
                     'OR mt.en_song_title_1 LIKE "%' . $queryString . '%" ' .
                     'OR mt.en_song_title_2 LIKE "%' . $queryString . '%") ';
    }

    /* 取得歌曲 */
    $itemArr = array();
    $sql = 'SELECT * ' .
           'FROM ' . $GLOBALS['db']->table('song_info') . ' AS mt ' .
           'WHERE ' . $operator;
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        /* 資料陣列 */
        $itemArr[$row['song_id']] = $row;
    }

    $smarty->assign('tpl', 'search');
    $smarty->assign('item_arr', $itemArr);

    makeJsonResult(
        $smarty->fetch(SCRIPT_NAME . '_relation_song.html'),
        '',
        array(
            'find_define_song' => true
        )
    );
    break;

//-- 取得商品
case 'load_define_song':

    $focusId = !empty($_POST['focus_id']) ? (int) $_POST['focus_id'] : 0;

    $operator = 'bs.focus_id = ' . $focusId . ' ';
    if ($focusId <= 0) {

        $operator .= 'AND bs.admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }


    /* 取得商品 */
    $itemArr = array();
    $sql = 'SELECT si.song_id, si.song_code, ' .
           'si.zh_song_title, si.en_song_title_1, si.en_song_title_2, ' .
           'si.picture_large, si.picture_thumb, ' .
           'bs.page_code, bs.sort_order ' .
           'FROM ' . $db->table('focus_relation_song') . ' AS bs ' .
           'INNER JOIN ' . $db->table('song_info') . ' AS si ' .
           'ON bs.song_id = si.song_id ' .
           'WHERE ' . $operator . ' ' .
           'ORDER BY bs.page_code, bs.sort_order';
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        /* 資料陣列 */
        $itemArr[$row['song_id']] = $row;
    }

    $smarty->assign('tpl', 'relation');
    $smarty->assign('item_arr', $itemArr);

    makeJsonResult(
        $smarty->fetch(SCRIPT_NAME . '_relation_song.html'),
        '',
        array(
            'load_define_song' => true
        )
    );
    break;

//-- 新增歌曲
case 'add_define_song':

    /* 字串處理 */
    $focusId = !empty($_POST['focus_id']) ? (int) $_POST['focus_id'] : 0;

    if (!empty($_POST['join_song']) && is_array($_POST['join_song'])) {

        foreach ($_POST['join_song'] as $val) {

            $db->query(
                $db->buildSQL(
                    'I',
                    $db->table('focus_relation_song'),
                    array(
                        'focus_id' => $focusId,
                        'song_id' => (int) $val,
                        'admin_id' => $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
                    )
                ),
                'SILENT'
            );
        }
    }

    makeJsonResult(
        '',
        '',
        array(
            'reload_define_song' => true
        )
    );
    break;

//-- 移除歌曲
case 'drop_define_song':

    $focusId = !empty($_POST['focus_id']) ? (int) $_POST['focus_id'] : 0;

    if (!empty($_POST['update_song']) && is_array($_POST['update_song'])) {

        /* 移除歌曲 */
        $sql = 'DELETE FROM ' . $db->table('focus_relation_song') . ' ' .
               'WHERE focus_id = ' . $focusId . ' ' .
               'AND ' . $db->in($_POST['update_song'], 'song_id');
        if ($focusId <= 0) {

            $sql .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
        }
        $db->query($sql);
    }

    makeJsonResult(
        '',
        '',
        array(
            'reload_define_song' => true
        )
    );
    break;

case 'relation_edit_sort_code':

    checkAuthzJson('focus_manage');

    $sortCode = !empty($_POST['val']) ? (int) $_POST['val'] : 0;
    $focusId = !empty($_POST['focus_id']) ? (int) $_POST['focus_id'] : 0;
    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $operator = 'song_id = ' . $songId . ' ';
    if ($focusId > 0) {
        $operator .= 'AND focus_id = ' . $focusId . ' ';
    } else {
        $operator .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . ' ';
    }

    $db->query(
        $db->buildSQL(
            'U',
            $db->table('focus_relation_song'),
            array(
                'sort_order' => $sortCode
            ),
            $operator
        )
    );

    clearCacheFiles();
    makeJsonResult($sortCode);
    break;

case 'relation_edit_page_code':

    checkAuthzJson('focus_manage');

    $pageCode = !empty($_POST['val']) ? (int) $_POST['val'] : 0;
    $focusId = !empty($_POST['focus_id']) ? (int) $_POST['focus_id'] : 0;
    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $operator = 'song_id = ' . $songId . ' ';
    if ($focusId > 0) {
        $operator .= 'AND focus_id = ' . $focusId . ' ';
    } else {
        $operator .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . ' ';
    }

    $db->query(
        $db->buildSQL(
            'U',
            $db->table('focus_relation_song'),
            array(
                'page_code' => $pageCode
            ),
            $operator
        )
    );

    clearCacheFiles();
    makeJsonResult($pageCode);
    break;

//-- 刪除圖片
case 'cancel_picture':

    checkAuthzJson('focus_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $sql = 'SELECT * FROM ' . $db->table('focus_info') . ' ' .
           'WHERE song_id = ' . $id;

    /* 刪除圖片文件 */
    if ($oldData = $db->getRow($sql)) {

        dropFile(array(
            $oldData['picture_large'],
            $oldData['picture_thumb']
        ));

        $exchange->edit(
            array(
                'picture_large' => '',
                'picture_thumb' => '',
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

    checkAuthzJson('focus_manage');

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
    adminPriv('focus_manage');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $numDrop = handleItemDrop($_POST['checkboxes']);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    sysMsg(sprintf('您已經成功刪除 %d 筆', $numDrop), 0, $links);
    break;

//-- 切換是否顯示
case 'toggle_show':

    /* 檢查權限 */
    checkAuthzJson('focus_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? 1 : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('is_open' => $val), $id)) {

        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        clearCacheFiles();
        makeJsonResult($val);

    } else {

        makeJsonError($db->error());
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 為某生成唯一的編號
 *
 * @param   int     $tableId   商品編號
 *
 * @return  string  唯一的編號
 */
function generateCode($tableId, $tableKey)
{
    $GLOBALS['_CFG']['sn_prefix'] = 'F';
    $tableCode = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 10 - strlen($tableId)) . $tableId;

    $sql = 'SELECT ' . $tableKey . '_code FROM ' . $GLOBALS['db']->table($tableKey . '_info') . ' ' .
           'WHERE ' . $tableKey . '_code LIKE "' . $GLOBALS['db']->likeQuote($tableCode) . '%" ' .
           'AND ' . $tableKey . '_id <> ' . $tableId . ' ' .
           'ORDER BY LENGTH(' . $tableKey . '_code) DESC';
    $codeList = $GLOBALS['db']->getCol($sql);
    if (in_array($tableCode, $codeList)) {

        $max = pow(10, strlen($codeList[0]) - strlen($tableCode) + 1) - 1;
        $newCode = $tableCode . mt_rand(0, $max);
        while (in_array($newCode, $codeList)) {

            $newCode = $tableCode . mt_rand(0, $max);
        }
        $tableCode = $newCode;
    }

    return $tableCode;
}

/**
 * 保存擴展標籤
 *
 * @param     int      $songId     項目編號
 * @param     array    $tagList    標籤數組
 *
 * @return    void
 */
function handleRelationTag($songId, $tagList)
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

    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('focus_relation_tag') . ' ' .
           'WHERE song_id = ' . $songId;
    $GLOBALS['db']->query($sql);

    foreach ($relationList as $key => $tagId) {

        $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'R',
                $GLOBALS['db']->table('focus_relation_tag'),
                array(
                    'song_id' => $songId,
                    'tag_id' => $tagId,
                    'sort_order' => $key
                )
            )
        );
    }
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
    $sql = 'SELECT mt.song_id, mt.song_code, mt.picture_large, mt.picture_thumb ' .
           'FROM ' . $GLOBALS['db']->table('focus_info') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.song_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['song_id']] = $row['song_code'];

        dropFile(array(
            $row['picture_large'],
            $row['picture_thumb']
        ));
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('focus_relation_song') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'focus_id');
        $GLOBALS['db']->query($sql);

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('focus_info') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'song_id');
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
 * @param     boolean    $isExport    是否匯出使用
 *
 * @return    array
 */
function getListPage($isExport = false)
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'song_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';
    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND (zh_song_title LIKE "%' . $queryString . '%" ';
        $operator .= 'OR en_song_title_1 LIKE "%' . $queryString . '%" ';
        $operator .= 'OR en_song_title_2 LIKE "%' . $queryString . '%") ';
        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    if (empty($isExport)) {

        /* 總數 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('focus_info') . ' AS mt ' .
               'WHERE 1 ' . $operator;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = pageAndSize($filter);
        unset($_SESSION[SHOP_SESS]['filter']);
        $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;
    }

    $itemArr = array();

    if (!empty($isExport) || $filter['record_count'] > 0) {

        /* 獲取數據 */
        $sql = 'SELECT mt.* ' .
               'FROM ' . $GLOBALS['db']->table('focus_info') . ' AS mt ' .
               'WHERE 1 ' . $operator . ' ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = !empty($isExport)
            ? $GLOBALS['db']->query($sql)
            : $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $itemArr[] = $row;
        }
    }

    if (!empty($isExport)) {

        return array('item' => $itemArr);
    }

    return array(
        'item' => $itemArr,
        'filter' => $filter,
        'page_count' => $filter['page_count'],
        'record_count' => $filter['record_count']
    );
}
