<?php

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2 && !NOCACHE_MODE) {

    $smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
}

$_REQUEST['action'] = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($_REQUEST['action']) {
default:

    $tplFile = SCRIPT_NAME . '_list.dwt';

    /* 列表總數 */
    $size = 20;

    /* 當前頁碼 */
    $urlParams['page'] = !empty($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

    if (isset($_GET['tag'])) {

        $urlParams['tag'] = trim($_GET['tag']);
    }

    /* 緩存編號 */
    $cacheId = sprintf('%X', crc32(implode('-', array(SCRIPT_NAME, serialize($urlParams)))));

    /*------------------------------------------------------ */
    //-- 判斷是否存在緩存，如果存在則調用緩存，反之讀取相應內容
    /*------------------------------------------------------ */
    if (!$smarty->isCached($tplFile, $cacheId)) {

        /* default meta information */
        $smarty->assign('keywords', $_CFG['shop_keywords']);
        $smarty->assign('description', $_CFG['shop_desc']);

        $customPage = getCusPageInfo(SCRIPT_NAME);
        if (!empty($customPage)) {

            $smarty->assign('custom_page', $customPage);

            $customPage['meta_keywords'] && $smarty->assign('keywords', $customPage['meta_keywords']);
            $customPage['meta_description'] && $smarty->assign('description', $customPage['meta_description']);
        }

        $list = getListPage($size, $urlParams);
        $smarty->assign('item_arr', $list['item']);
        $smarty->assign('pager', $list['filter']);

        assignTemplate();
    }
    $smarty->display($tplFile, $cacheId);
    break;

case 'view':

    /*------------------------------------------------------ */
    //-- INPUT
    /*------------------------------------------------------ */
    $urlParams = array();
    if (!empty($_REQUEST['id'])) {

        $urlParams['id'] = (int) $_REQUEST['id'];
    }

    /*------------------------------------------------------ */
    //-- 判斷是否存在緩存，如果存在則調用緩存，反之讀取相應內容
    /*------------------------------------------------------ */
    $tplFile = SCRIPT_NAME . '_view.dwt';
    /* 緩存編號 */
    $cacheId = sprintf('%X', crc32(implode('-', array(SCRIPT_NAME, serialize($urlParams), date('Ymd')))));
    if (!$smarty->isCached($tplFile, $cacheId)) {

        /* default meta information */
        $smarty->assign('keywords', $_CFG['shop_keywords']);
        $smarty->assign('description', $_CFG['shop_desc']);

        $customPage = getCusPageInfo(SCRIPT_NAME);
        if (!empty($customPage)) {

            $smarty->assign('custom_page', $customPage);

            $customPage['meta_keywords'] && $smarty->assign('keywords', $customPage['meta_keywords']);
            $customPage['meta_description'] && $smarty->assign('description', $customPage['meta_description']);
        }

        /* 詳情 */
        $data = getInfoPage($urlParams);
        if (empty($data)) {

            showServerError(404, '很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
            exit;
        }

        $smarty->assign('data', $data);
        $smarty->assign('relation_song', getRelationSong($urlParams['id']));

        $data['meta_keywords'] && $smarty->assign('keywords', $data['meta_keywords']);
        $data['meta_description'] && $smarty->assign('description', $data['meta_description']);

        assignTemplate();
    }
    $smarty->display($tplFile, $cacheId);
    break;
}
exit;

/**
 * 獲得指定的詳細信息
 *
 * @param     array    $params
 *
 * @return    array
 */
function getInfoPage(array $params = array())
{
    $operator = ' 1 ';

    if (isset($params['id'])) {

        $songId = $params['id'];
    }

    if (empty($songId)) {

        return false;
    }

    $sql = 'SELECT mt.* ' .
           'FROM ' . $GLOBALS['db']->table('book_info') . ' AS mt ' .
           'WHERE ' . $operator . ' ' .
           'AND mt.book_id = ' . $songId;
    if ($data = $GLOBALS['db']->getRow($sql)) {

        $data['url'] = buildUri(
            SCRIPT_NAME,
            array(
                'action' => 'view',
                'id' => $data['book_id']
            )
        );

        $data['relation_tags'] = array();
    }

    return $data;
}

/**
 * 獲得列表
 *
 * @param     integer    $size      單頁資料顯示數量 (小於零則不分頁)
 * @param     array      $filter    額外過濾參數 (index 為參數名稱, value 為參數值)
 *
 * @return    array
 */
function getListPage($size = 10, array $filter = array())
{
    $time = $_SERVER['REQUEST_TIME'];

    $operator = 'mt.is_open = 1 ';

    if (isset($filter['tag'])) {

        $operator .= 'AND mt.book_id IN(SELECT DISTINCT brt.book_id ' .
                     'FROM ' . $GLOBALS['db']->table('book_relation_tag') . ' AS brt ' .
                     'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS ttd ' .
                     'ON ttd.tag_id = brt.tag_id ' .
                     'WHERE ttd.tag_name = "' . $filter['tag'] . '") ';
    }

    if ($size > 0) {

        $sql = 'SELECT COUNT(mt.book_id) AS NUM ' .
               'FROM ' . $GLOBALS['db']->table('book_info') . ' AS mt ' .
               'WHERE ' . $operator;
        $recordCount = $GLOBALS['db']->getOne($sql);

        $filter = getPager(SCRIPT_NAME, $recordCount, $size, array_filter($filter));

    } else {

        $search = $filter;
        $filter = array();
        $filter['search'] = $search;
    }

    $itemArr = array();
    if (!empty($recordCount) || !($size > 0)) {

        $sql = 'SELECT mt.* ' .
               'FROM ' . $GLOBALS['db']->table('book_info') . ' AS mt ' .
               'WHERE ' . $operator . ' ' .
               'ORDER BY mt.book_id DESC';
        $res = ($size > 0)
            ? $GLOBALS['db']->selectLimit($sql, $size, ($filter['page'] - 1) * $size)
            : $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'action' => 'view',
                    'id' => $row['book_id']
                )
            );

            $row['relation_tags'] = array();

            $itemArr[$row['book_id']] = $row;
        }

        $sql = 'SELECT brt.book_id, td.tag_name ' .
               'FROM ' . $GLOBALS['db']->table('book_relation_tag') . ' AS brt ' .
               'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS td ' .
               'ON td.tag_id = brt.tag_id ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($itemArr), 'brt.book_id') . ' ' .
               'ORDER BY brt.sort_order';
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'tag' => $row['tag_name']
                )
            );

            $itemArr[$row['book_id']]['relation_tags'][] = $row;
        }
    }

    return array('item' => $itemArr, 'filter' => $filter);
}

/**
 * 獲得指定歌本的關聯歌曲
 *
 * @param     integer    $bookId
 *
 * @return    array
 */
function getRelationSong($bookId)
{
    $itemArr = array();
    $sql = 'SELECT mt.*, lb.sort_order, lb.page_code ' .
           'FROM ' . $GLOBALS['db']->table('book_relation_song') . ' lb ' .
           'INNER JOIN ' . $GLOBALS['db']->table('song_info') . ' AS mt ' .
           'ON mt.song_id = lb.song_id ' .
           'AND mt.is_open = 1 ' .
           'WHERE lb.book_id = ' . $bookId . ' ' .
           'ORDER BY lb.sort_order';
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $row['url'] = buildUri(
            'song',
            array(
                'action' => 'view',
                'id' => $row['song_id']
            )
        );

        $row['relation_tags'] = array();

        $itemArr[$row['song_id']] = $row;
    }

    $sql = 'SELECT brt.song_id, td.tag_name ' .
           'FROM ' . $GLOBALS['db']->table('song_relation_tag') . ' AS brt ' .
           'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS td ' .
           'ON td.tag_id = brt.tag_id ' .
           'WHERE ' . $GLOBALS['db']->in(array_keys($itemArr), 'brt.song_id') . ' ' .
           'ORDER BY brt.sort_order';
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $row['url'] = buildUri(
            'song',
            array(
                'tag' => $row['tag_name']
            )
        );

        $itemArr[$row['song_id']]['relation_tags'][] = $row;
    }

    return $itemArr;
}
