<?php

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));

require(dirname(__FILE__) . '/includes/init.php');

if ((DEBUG_MODE & 2) != 2 && !NOCACHE_MODE) {

    $smarty->caching = Smarty::CACHING_LIFETIME_CURRENT;
}

$tplFile = SCRIPT_NAME . '.dwt';
/*------------------------------------------------------ */
//-- 判斷是否存在緩存，如果存在則調用緩存，反之讀取相應內容
/*------------------------------------------------------ */
/* 緩存編號 */
$cacheId = sprintf('%X', crc32(implode('-', array(SCRIPT_NAME, date('Ymd')))));
if (!$smarty->isCached($tplFile, $cacheId)) {

    /* default meta information */
    $smarty->assign('keywords', $_CFG['shop_keywords']);
    $smarty->assign('description', $_CFG['shop_desc']);

    $customPage = getCusPageInfo(SCRIPT_NAME);
    if (!empty($customPage)) {

        $smarty->assign('custom_page', $customPage);
    }

    $smarty->assign('popular_song', getPopularSong());
    assignTemplate();
}
$smarty->display($tplFile, $cacheId);
exit;

/**
 * 獲得列表
 *
 * @param     integer    $size      單頁資料顯示數量 (小於零則不分頁)
 *
 * @return    array
 */
function getPopularSong($size = 4)
{
    $operator = 'mt.is_open = 1 ';

        $sql = 'SELECT mt.* ' .
               'FROM ' . $GLOBALS['db']->table('song_info') . ' AS mt ' .
               'WHERE ' . $operator . ' ' .
               'ORDER BY mt.click_count DESC';
        $res = $GLOBALS['db']->selectLimit($sql, $size);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['url'] = buildUri(
                'song',
                array(
                    'action' => 'view',
                    'id' => $row['song_id']
                )
            );

            $itemArr[$row['song_id']] = $row;
        }


    return $itemArr;
}