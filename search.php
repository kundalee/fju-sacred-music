<?php

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));

require(dirname(__FILE__) . '/includes/init.php');

$tplFile = SCRIPT_NAME . '.dwt';

/* 列表總數 */
$size = 20;

/* 當前頁碼 */
$urlParams['page'] = !empty($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;

if (isset($_GET['q']) && $_GET['q'] != '') {

    $urlParams['q'] = trim($_GET['q']);
}
if (isset($_GET['o']) && is_array($_GET['o'])) {

    $urlParams['o'] = array_filter($_GET['o']);
}

/* default meta information */
$smarty->assign('keywords', $_CFG['shop_keywords']);

$customPage = getCusPageInfo(SCRIPT_NAME);
if (!empty($customPage)) {

    $smarty->assign('custom_page', $customPage);
}

$list = getListPage($size, $urlParams);
$smarty->assign('item_arr', $list['item']);
$smarty->assign('pager', $list['filter']);

assignTemplate();
$smarty->display($tplFile);


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

    $mSongQuery = [];

    $sOperator = $bOperator = '';
    if (isset($filter['q']) && $filter['q'] != '') {

        $queryString = $GLOBALS['db']->likeQuote($filter['q']);

        $mSongQuery[] = 'si.zh_song_title LIKE "%' . $queryString . '%"';
        $mSongQuery[] = 'si.en_song_title_1 LIKE "%' . $queryString . '%"';
        $mSongQuery[] = 'si.en_song_title_2 LIKE "%' . $queryString . '%"';
        $mSongQuery[] = 'si.composer_name LIKE "%' . $queryString . '%"';
        $mSongQuery[] = 'si.lyricist_name LIKE "%' . $queryString . '%"';

        $sOperator .= implode(' OR ', $mSongQuery);
        $bOperator .= 'bi.book_title LIKE "%' . $queryString . '%"';
    }


    $sOtherQuery = [];
    if (isset($filter['o']['tune']) && $filter['o']['tune'] != '') {

        $sOtherQuery[] = 'si.tune_name LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['tune']) . '%"';
    }
    if (isset($filter['o']['rhythm']) && $filter['o']['rhythm'] != '') {

        $sOtherQuery[] = 'si.rhythm LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['rhythm']) . '%"';
    }
    if (isset($filter['o']['prepared']) && $filter['o']['prepared'] != '') {

        $sOtherQuery[] = 'si.song_prepared LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['prepared']) . '%"';
    }
    if (isset($filter['o']['copyright']) && $filter['o']['copyright'] != '') {

        $sOtherQuery[] = 'si.copyright LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['copyright']) . '%"';
    }

    if (!empty($sOtherQuery)) {

        $sOperator != '' && $sOperator .= ' OR ';
        $sOperator .= implode(' OR ', $sOtherQuery);
    }

    $bOtherQuery = [];
    if (isset($filter['o']['author']) && $filter['o']['author'] != '') {

        $bOtherQuery[] = 'bi.author LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['author']) . '%"';
    }
    if (isset($filter['o']['print']) && $filter['o']['print'] != '') {

        $bOtherQuery[] = 'bi.quasi_print LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['print']) . '%"';
    }
    if (isset($filter['o']['publisher']) && $filter['o']['publisher'] != '') {

        $bOtherQuery[] = 'bi.publisher LIKE "%' . $GLOBALS['db']->likeQuote($filter['o']['publisher']) . '%"';
    }

    if (!empty($bOtherQuery)) {

        $bOperator != '' && $bOperator .= ' OR ';
        $bOperator .= implode(' OR ', $bOtherQuery);
    }


    $sOperator != '' && $sOperator .= ' OR ';
    $bOperator == '' && $bOperator .= ' 0 ';


    $operator = 'si.is_open = 1 AND (' . $sOperator .
                'si.song_id IN (' .
                    'SELECT brs.song_id FROM ' . $GLOBALS['db']->table('book_info') . ' AS bi ' .
                    'INNER JOIN ' . $GLOBALS['db']->table('book_relation_song') . ' AS brs ' .
                    'ON brs.book_id = bi.book_id ' .
                    'WHERE (' . $bOperator . ')' .
                '))';

    if ($size > 0) {

        $sql = 'SELECT COUNT(*) ' .
               'FROM ' . $GLOBALS['db']->table('song_info') . ' AS si ' .
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

        $sql = 'SELECT si.*, bi.book_title, bi.book_id ' .
               'FROM ' . $GLOBALS['db']->table('song_info') . ' AS si ' .
               'LEFT JOIN ' . $GLOBALS['db']->table('book_info') . ' AS bi ' .
               'ON bi.book_code = si.source_book ' .
               'WHERE ' . $operator . ' ' .
               'ORDER BY created_at';
        $res = ($size > 0)
            ? $GLOBALS['db']->selectLimit($sql, $size, ($filter['page'] - 1) * $size)
            : $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['song_url'] = buildUri(
                'song',
                array(
                    'action' => 'view',
                    'id' => $row['song_id']
                )
            );

            if ($row['book_id']) {

                $row['book_url'] = buildUri(
                    'book',
                    array(
                        'action' => 'view',
                        'id' => $row['book_id']
                    )
                );
            }

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
    }

    return array('item' => $itemArr, 'filter' => $filter);
}

