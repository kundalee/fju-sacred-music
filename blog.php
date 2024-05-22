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

    /*------------------------------------------------------ */
    //-- 判斷是否存在緩存，如果存在則調用緩存，反之讀取相應內容
    /*------------------------------------------------------ */
    $tplFile = SCRIPT_NAME . '_list.dwt';

    /* 列表總數 */
    $size = 12;

    /* 當前頁碼 */
    $urlParams['page'] = !empty($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

    $catNav = getCategory(
        'bulletin_category',
        array(
            're_type' => false,
            'is_show_all' => false,
            'lang_id' => $_CFG['lang_id'],
            'app' => SCRIPT_NAME
        )
    );

    if (isset($_GET['tag'])) {

        $urlParams['tag'] = trim($_GET['tag']);

    } elseif (isset($routerParams['tag'])) {

        $urlParams['tag'] = trim($routerParams['tag']);

    } else {

        if ($catNav) {

            $catId = 0;
            if (!empty($_GET['cid'])) {

                $catId = (int) $_GET['cid'];

            } elseif (isset($routerParams['id'])) {

                $catId = (int) $routerParams['id'];
            }

            if ($catId > 0 && !isset($catNav[$catId])) {

                showServerError(404, '很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
            }

            $urlParams['cid'] = $catId;
        }
    }

    if (isset($_GET['q']) || isset($urlParams['tag'])) {

        $smarty->caching = Smarty::CACHING_OFF;

        isset($_GET['q']) && $urlParams['q'] = trim($_GET['q']);

        if (isset($_GET['q'])) {

            $pageTitle = '搜尋';
            $metaKeywords = $urlParams['q'];

        } elseif (isset($urlParams['tag'])) {

            $pageTitle = $urlParams['tag'];
            $metaKeywords = $urlParams['tag'];
        }

        /* 緩存編號 */
        $cacheId = sprintf('%X', crc32(implode('-', array(SCRIPT_NAME, serialize($urlParams), date('Ymd')))));

        $customPage = getCusPageInfo(SCRIPT_NAME);
        if (!empty($customPage)) {

            $smarty->assign('custom_page', $customPage);
        }

        $smarty->assign('nav_tags', getListTags());

        $list = getListPage($size, $urlParams);

        $smarty->assign('item_arr', $list['item']);
        $smarty->assign('pager', $list['filter']);

        $smarty->assign('keywords', htmlspecialchars($metaKeywords));
        $smarty->assign('page_title', $pageTitle);

        assignTemplate();

    } else {

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

            // 獲得分類的相關信息
            if (!empty($urlParams['cid']) && $data = getCatInfo('bulletin_category', $urlParams['cid'], $_CFG['lang_id'])) {

                $data['url'] = $catNav[$urlParams['cid']]['url'];
                $smarty->assign('data', $data);

                $data['meta_keywords'] && $smarty->assign('keywords', $data['meta_keywords']);
                $data['meta_description'] && $smarty->assign('description', $data['meta_description']);

                $smarty->assign('cat_breadcrumb', getCatBreadcrumb('bulletin_category', $urlParams['cid']));
            }

            $smarty->assign('nav_tags', getListTags());

            $list = getListPage($size, $urlParams);
            $smarty->assign('item_arr', $list['item']);
            $smarty->assign('pager', $list['filter']);

            assignTemplate();
        }
    }

    $smarty->assign('recommend_bulletin', getRecommendBulletin('best', 6));
    $smarty->display($tplFile, $cacheId);
    break;

case 'view':

    /*------------------------------------------------------ */
    //-- INPUT
    /*------------------------------------------------------ */
    $urlParams = array();
    if (!empty($_REQUEST['id'])) {

        $urlParams['id'] = (int) $_REQUEST['id'];

    } elseif (isset($_REQUEST['title'])) {

        $urlParams['title'] = trim($_REQUEST['title']);
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

        $urlParams['cid'] = $data['cat_id'];

        // 取得上下筆資料
        $itemPrevNext = getPagePrevNext($data['bulletin_id'], $urlParams);

        $smarty->assign('data', $data);
        $smarty->assign('nav_tags', getListTags());
        $smarty->assign('item_prev_data', $itemPrevNext['prev_data']);
        $smarty->assign('item_next_data', $itemPrevNext['next_data']);

        $smarty->assign('related_category', getRelatedCategory($data['bulletin_id']));
        $smarty->assign('related_bulletin', getLinkedBulletin($data['bulletin_id'])); // 關聯文章

        $data['meta_keywords'] && $smarty->assign('keywords', $data['meta_keywords']);
        $data['meta_description'] && $smarty->assign('description', $data['meta_description']);

        $smarty->assign('cat_breadcrumb', getCatBreadcrumb('bulletin_category', $data['cat_id']));

        assignTemplate();
    }

    $smarty->assign('recommend_bulletin', getRecommendBulletin('best', 6, $urlParams['id']));
    $smarty->display($tplFile, $cacheId);
    break;
}
exit;

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
    $category = getCategory(
        'bulletin_category',
        array(
            're_type' => false,
            'is_show_all' => false,
            'lang_id' => $GLOBALS['_CFG']['lang_id'],
            'app' => SCRIPT_NAME
        )
    );

    $time = $_SERVER['REQUEST_TIME'];

    $operator = 'mt.is_open = 1 ' .
                'AND ((mt.is_time = 0 AND mt.release_start_time <= ' . $time . ') ' .
                'OR (mt.is_time = 1 AND mt.release_start_time <= ' . $time . ' AND mt.release_end_time >= ' . $time . ')) ';

    if (!empty($filter['cid'])) {

        $children = getChildrenCats('bulletin_category', $filter['cid'], 'mt.cat_id');
        $sql = 'SELECT bulletin_id FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' AS mt ' .
               'WHERE ' . $children;
        $queryIds = $GLOBALS['db']->getCol($sql);
        $operator .= 'AND (' . $children . ' OR ' . $GLOBALS['db']->in($queryIds, 'mt.bulletin_id') . ') ';
    }

    if (isset($filter['tag'])) {

        $operator .= 'AND mt.bulletin_id IN(SELECT DISTINCT brt.bulletin_id ' .
                     'FROM ' . $GLOBALS['db']->table('bulletin_relation_tag') . ' AS brt ' .
                     'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS ttd ' .
                     'ON ttd.tag_id = brt.tag_id ' .
                     'WHERE ttd.tag_name = "' . $filter['tag'] . '") ';
    }

    if (isset($filter['q'])) {

        $queryString = $GLOBALS['db']->likeQuote($filter['q']);

        $operator .= 'AND (mt.title LIKE "%' . $queryString . '%" OR mt.brief LIKE "%' . $queryString . '%")';
    }

    if ($size > 0) {

        $sql = 'SELECT COUNT(mt.bulletin_id) AS NUM ' .
               'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
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

        $sql = 'SELECT mt.*, ' .
               '(SELECT GROUP_CONCAT(bc.cat_id) ' .
               'FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' AS bc ' .
               'WHERE bc.bulletin_id = mt.bulletin_id ' .
               'ORDER BY bc.sort_order) AS relation_category ' .
               'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
               'WHERE ' . $operator . ' ' .
               'ORDER BY mt.show_type DESC, mt.release_start_time DESC, mt.sort_order ASC, mt.bulletin_id DESC';
        $res = ($size > 0)
            ? $GLOBALS['db']->selectLimit($sql, $size, ($filter['page'] - 1) * $size)
            : $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['cat_name'] = isset($category[$row['cat_id']]['cat_name'])
                ? $category[$row['cat_id']]['cat_name']
                : '';

            if ($row['link_url'] != '') {

                $row['url'] = $row['link_url'];
                $row['open_type'] = 1;

            } elseif ($row['open_type'] == 1 && $row['file_url'] != '') {

                $row['url'] = $GLOBALS['dus']->url() . $row['file_url'];

            } else {

                $row['url'] = buildUri(
                    SCRIPT_NAME,
                    array(
                        'action' => 'view',
                        'id' => $row['bulletin_id']
                    ),
                    array(
                        'append' => $row['title']
                    )
                );
            }

            $relationCategory = array();
            if ($row['relation_category'] != '') {

                foreach (explode(',', $row['relation_category']) as $val) {

                    if (isset($category[$val])) {

                        $relationCategory[] = $category[$val];
                    }
                }
            }
            $row['relation_category'] = $relationCategory;

            $row['relation_tags'] = array();

            $itemArr[$row['bulletin_id']] = $row;
        }

        $sql = 'SELECT brt.bulletin_id, td.tag_name FROM ' . $GLOBALS['db']->table('bulletin_relation_tag') . ' AS brt ' .
               'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS td ' .
               'ON td.tag_id = brt.tag_id ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($itemArr), 'brt.bulletin_id') . ' ' .
               'ORDER BY brt.sort_order';
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'tag' => $row['tag_name']
                )
            );

            $itemArr[$row['bulletin_id']]['relation_tags'][] = $row;
        }
    }

    return array('item' => $itemArr, 'filter' => $filter);
}

/**
 * 獲得指定的詳細信息
 *
 * @param     array    $params
 *
 * @return    array
 */
function getInfoPage(array $params = array())
{
    $time = $_SERVER['REQUEST_TIME'];

    $operator = 'mt.is_open = 1 ' .
                'AND ((mt.is_time = 0 AND mt.release_start_time <= ' . $time . ') ' .
                'OR (mt.is_time = 1 AND mt.release_start_time <= ' . $time . ' AND mt.release_end_time >= ' . $time . ')) ';

    if (isset($params['id'])) {

        $bulletinId = $params['id'];

    } elseif (isset($params['title'])) {

        $sql = 'SELECT DISTINCT bulletin_id ' .
               'FROM ' . $GLOBALS['db']->table('bulletin') . ' ' .
               'AND title = "' . $params['title'] . '"';
        $bulletinId = $GLOBALS['db']->getOne($sql);
    }

    if (empty($bulletinId)) {

        return false;
    }

    $sql = 'SELECT mt.* ' .
           'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'WHERE ' . $operator . ' ' .
           'AND mt.bulletin_id = ' . $bulletinId;
    if ($data = $GLOBALS['db']->getRow($sql)) {

        $category = getCategory(
            'bulletin_category',
            array(
                're_type' => false,
                'is_show_all' => false,
                'lang_id' => $GLOBALS['_CFG']['lang_id'],
                'app' => SCRIPT_NAME
            )
        );

        $data['cat_name'] = isset($category[$data['cat_id']]['cat_name'])
            ? $category[$data['cat_id']]['cat_name']
            : '';

        $data['url'] = buildUri(
            SCRIPT_NAME,
            array(
                'action' => 'view',
                'id' => $data['bulletin_id']
            ),
            array(
                'append' => $data['title']
            )
        );

        $data['relation_tags'] = array();
        $sql = 'SELECT td.tag_name FROM ' . $GLOBALS['db']->table('bulletin_relation_tag') . ' AS rt ' .
               'INNER JOIN ' . $GLOBALS['db']->table('tags') . ' AS td ' .
               'ON td.tag_id = rt.tag_id ' .
               'WHERE rt.bulletin_id = ' . $data['bulletin_id'] . ' ' .
               'ORDER BY rt.sort_order ASC';
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'tag' => $row['tag_name']
                )
            );

            $data['relation_tags'][] = $row;
        }

        $data['content'] = $GLOBALS['dus']->urlDecode($data['content']);
    }

    return $data;
}

/**
 * 取得上下筆資料
 *
 * @param     integer    $curId     目前的資料 ID
 * @param     array      $filter    額外過濾參數 (index 為參數名稱, value 為參數值)
 *
 * @return    array
 */
function getPagePrevNext($curId, array $filter = array())
{
    $prevArr = $nextArr = array();

    $dataArr = getListPage(-1, $filter);
    if (!empty($dataArr['item'])) {

        $itemIds = array_keys($dataArr['item']);

        if (in_array($curId, $itemIds)) {

            $currKey = array_search($curId, $itemIds);
            if (isset($itemIds[$currKey - 1])) {

                $prevArr = $dataArr['item'][$itemIds[$currKey - 1]];
            }

            if (isset($itemIds[$currKey + 1])) {

                $nextArr = $dataArr['item'][$itemIds[$currKey + 1]];
            }
        }
    }

    return array(
        'prev_data' => $prevArr,
        'next_data' => $nextArr
    );
}

/**
 * 獲得指定文章的關聯文章
 *
 * @param     integer    $bulletinId
 *
 * @return    array
 */
function getLinkedBulletin($bulletinId)
{
    $time = $_SERVER['REQUEST_TIME'];
    $itemArr = array();
    $sql = 'SELECT mt.* ' .
           'FROM ' . $GLOBALS['db']->table('bulletin_relation_link') . ' lb ' .
           'INNER JOIN ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'ON mt.bulletin_id = lb.bulletin_link_id ' .
           'AND ((mt.is_time = 0 AND mt.release_start_time <= ' . $time . ') ' .
           'OR (mt.is_time = 1 AND mt.release_start_time <= ' . $time . ' AND mt.release_end_time >= ' . $time . '))' .
           'WHERE lb.bulletin_id = ' . $bulletinId;
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $row['cat_name'] = isset($category[$row['cat_id']]['cat_name'])
            ? $category[$row['cat_id']]['cat_name']
            : '';

        if ($row['link_url'] != '') {

            $row['url'] = $row['link_url'];
            $row['open_type'] = 1;

        } elseif ($row['open_type'] == 1 && $row['file_url'] != '') {

            $row['url'] = $GLOBALS['dus']->url() . $row['file_url'];

        } else {

            $data['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'action' => 'view',
                    'id' => $data['bulletin_id']
                ),
                array(
                    'append' => $data['title']
                )
            );
        }

        $itemArr[$row['bulletin_id']] = $row;
    }

    return $itemArr;
}

/**
 * 取得指定文章的多分類清單
 *
 * @param     integer    $bulletinId    文章 ID
 *
 * @return    array
 */
function getRelatedCategory($bulletinId)
{
    $itemArr = array();
    $sql = 'SELECT td.cat_name, mt.* ' .
           'FROM ' . $GLOBALS['db']->table('bulletin_relation_category') . ' AS mt ' .
           'INNER JOIN ' . $GLOBALS['db']->table('bulletin_category') . ' AS td ' .
           'ON td.cat_id = mt.cat_id ' .
           'WHERE mt.bulletin_id = ' . $bulletinId;
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $row['url'] = '';

        $itemArr[] = $row;
    }

    return $itemArr;
}


function getListTags()
{
    $itemArr = array();

    $sql = 'SELECT mt.tag_name ' .
           'FROM ' . $GLOBALS['db']->table('tags') . ' AS mt ' .
           'WHERE mt.is_show = 1 ' .
           'ORDER BY mt.sort_order, mt.tag_id';
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $row['url'] = buildUri(
            SCRIPT_NAME,
            array(
                'tag' => $row['tag_name']
            )
        );

        $itemArr[] = $row;
    }

    return $itemArr;
}

/**
 * 推薦最新消息
 *
 * @param     string    $type    推薦類型 best, new, hot
 *
 * @return    array
 */
function getRecommendBulletin($type = '', $num = 6, $excludeId = 0)
{
    $itemArr = array();

    if (!in_array($type, array('best', 'new', 'hot'))) {

        return $itemArr;
    }

    $time = $_SERVER['REQUEST_TIME'];
    $sql = 'SELECT mt.*, RAND() AS rnd ' .
           'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'WHERE mt.is_open = 1 ' .
           'AND mt.is_best = 1 ' .
           'AND ((mt.is_time = 0 AND mt.release_start_time <= ' . $time . ') ' .
           'OR (mt.is_time = 1 AND mt.release_start_time <= ' . $time . ' AND mt.release_end_time >= ' . $time . '))';
    if ($excludeId > 0) {

        $sql .= 'AND bulletin_id != ' . $excludeId . ' ';
    }
    $sql .= 'ORDER BY rnd';

    $res = $GLOBALS['db']->selectLimit($sql, $num);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        if ($row['link_url'] != '') {

            $row['url'] = $row['link_url'];
            $row['open_type'] = 1;

        } elseif ($row['open_type'] == 1 && $row['file_url'] != '') {

            $row['url'] = $GLOBALS['dus']->url() . $row['file_url'];

        } else {

            $data['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'action' => 'view',
                    'id' => $data['bulletin_id']
                ),
                array(
                    'append' => $data['title']
                )
            );
        }

        $itemArr[] = $row;
    }

    return $itemArr;
}