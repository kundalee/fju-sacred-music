<?php

/**
 * 系統文件檢測
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '文件權限檢測');
define('SYS_SAFE_CODE', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限 */
adminPriv('sys');

switch ($_REQUEST['action'] ) {

case 'check':

    /* 要檢查目錄文件列表 */
    $imgDir = array();
    $folder = opendir(IMAGE_PATH);
    while ($dir = readdir($folder)) {

        if (is_dir(IMAGE_PATH . $dir) && preg_match('/^[0-9]{6}$/', $dir)) {

            $imgDir[] = 'data/images/' . $dir;
        }
    }
    closedir($folder);

    $dir[]                 = 'admin';

    $dirSubDir['data'][]   = 'data';
    $dirSubDir['data'][]   = 'data/files';
    $dirSubDir['data'][]   = 'data/flash';

    $dirSubDir['temp'][]   = 'temp';
    $dirSubDir['temp'][]   = 'temp/caches';
    $dirSubDir['temp'][]   = 'temp/caches/query';
    $dirSubDir['temp'][]   = 'temp/caches/static';
    $dirSubDir['temp'][]   = 'temp/compiled';
    $dirSubDir['temp'][]   = 'temp/compiled/admin';

    $tpl = 'themes/';

    $list = array();

    /* 檢查目錄 */
    foreach ($dir as $val) {

        $mark = fileModeInfo(ROOT_PATH . $val);
        $list[] = array('item' => $val . ' 目錄', 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4);
    }

    /* 檢查目錄及子目錄 */
    $keys = array_unique(array_keys($dirSubDir));
    foreach ($keys as $key) {

        $errMsg = array();
        $mark = checkFileInArray($dirSubDir[$key], $errMsg);
        $list[] = array('item' => $key . ' 目錄及其子目錄', 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4, 'err_msg' => $errMsg);
    }

    /* 檢查當前模板可寫性 */
    $dwt = @opendir(ROOT_PATH . $tpl);
    $tplFile = array(); // 獲取要檢查的文件
    while ($file = @readdir($dwt)) {

        if (is_file(ROOT_PATH . $tpl . $file) && strrpos($file, '.dwt') > 0) {

            $tplFile[] = $tpl . $file;
        }
    }
    @closedir($dwt);
    $lib = @opendir(ROOT_PATH . $tpl .'library/');
    while ($file = @readdir($lib)) {

        if (is_file(ROOT_PATH . $tpl . 'library/' . $file) && strrpos($file, '.lbi') > 0 ) {

             $tplFile[] = $tpl . 'library/' . $file;
        }
    }
    @closedir($lib);

    /* 開始檢查 */
    $errMsg = array();
    $mark = checkFileInArray($tplFile, $errMsg);
    $list[] = array('item' => $tpl . ' 下所有模板文件', 'r' => $mark & 1, 'w' => $mark & 2, 'm' => $mark & 4, 'err_msg' => $errMsg);

    /* 檢查 smarty 的緩存目錄和編譯目錄及 image 目錄是否有執行 rename() 函數的權限 */
    $tplList   = array();
    $tplDirs[] = 'temp/caches';
    $tplDirs[] = 'temp/compiled';
    $tplDirs[] = 'temp/compiled/admin';

    /* 將客房圖片目錄加入檢查範圍 */
    foreach ($imgDir as $val) {

        $tplDirs[] = $val;
    }

    foreach ($tplDirs as $dir) {

        $mask = fileModeInfo(ROOT_PATH . $dir);
        if (($mask & 4) > 0) {

            /* 之前已經檢查過修改權限，只有有修改權限才檢查 rename 權限 */
            if (($mask & 8) < 1) {

                $tplList[] = $dir;
            }
        }
    }
    $tplMsg = implode(', ', $tplList);

    $smarty->assign('list', $list);
    $smarty->assign('tpl_msg', $tplMsg);

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;
}
exit;

/**
 * 檢查數組中目錄權限
 *
 * @access  public
 * @param   array    $arr          要檢查的文件列表數組
 * @param   array    $errMsg       錯誤信息回饋數組
 *
 * @return int       $mark         文件權限掩碼
 */
function checkFileInArray($arr, &$errMsg)
{
    $read   = true;
    $writen = true;
    $modify = true;
    foreach ($arr as $val) {

        $mark = fileModeInfo(ROOT_PATH . $val);
        if (($mark & 1) < 1) {

            $read = false;
            $errMsg['r'][] = $val;
        }
        if (($mark & 2) < 1) {

            $writen = false;
            $errMsg['w'][] = $val;
        }
        if (($mark & 4) < 1) {

            $modify = false;
            $errMsg['m'][] = $val;
        }
    }

    $mark = 0;
    if ($read) {

        $mark ^= 1;
    }
    if ($writen) {

        $mark ^= 2;
    }
    if ($modify) {

        $mark ^= 4;
    }

    return $mark;
}
