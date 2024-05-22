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
$cacheId = sprintf('%X', crc32(implode('-', array(SCRIPT_NAME))));
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

    assignTemplate();
}
$smarty->display($tplFile, $cacheId);
exit;