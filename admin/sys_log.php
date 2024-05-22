<?php
/**
 * 系統紀錄
 * ========================================================
 * 版權所有 (C) 2016 鉅潞科技網頁設計公司，並保留所有權利。
 * 網站地址: http://www.grnet.com.tw
 * ========================================================
 * Date: 2016-04-22 11:15
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '系統紀錄');
define('SYS_SAFE_CODE', true);

// 載入函式檔
require(dirname(__FILE__) . '/includes/init.php');

$_REQUEST['action'] = !empty($_REQUEST['action']) ? trim($_REQUEST['action']) : 'list'; // 初始化執行動作

switch ($_REQUEST['action']) {
//-- 列表
case 'list':

    adminPriv('sys'); // 檢查權限

    $smarty->assign('log_list', getLogList());

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    /* 顯示頁面 */
    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

//-- 瀏覽
case 'view':

    checkAuthzJson('sys'); // 檢查權限

    // 參數初始化
    $path = isset($_GET['token']) ? $dus->strDecode(rawurldecode(trim($_GET['token'])), SESS_ID) : '';

    if (is_file($path)) {

        $content = htmlspecialchars(file_get_contents($path), ENT_QUOTES);

        makeJsonResponse($content);

    } else {

        makeJsonError('檔案不存在');
    }
    break;

//-- 刪除指定項目
case 'remove':

    checkAuthzJson('sys'); // 檢查權限

    // 參數初始化
    $path = isset($_GET['id']) ? (array)$_GET['id'] : array();

    delRecord($path);

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'list';

    $dus->header('Location: ?' . http_build_query($queryString));
    break;

//-- 批次刪除
case 'batch_remove':

    adminPriv('sys'); // 檢查權限

    // 初始化參數
    $path = isset($_POST['checkboxes']) ? (array)$_POST['checkboxes'] : array();

    if (empty($path)) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $dropNum = delRecord($path);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list'));
    sysMsg(sprintf('您已經成功刪除 %d 個項目', $dropNum), 0, $links);
    break;
}
exit;

/**
 * -------------------
 *  PRIVATE FUNCTIONS
 * -------------------
 */

/**
 * 取得紀錄列表
 *
 * @return    array
 */
function getLogList($queryFile = '*')
{
    $logList = array();

    $directory = TEMP_PATH . 'log';

    foreach (glob(sprintf('%s/%s', $directory, $queryFile)) as $file) {

        $info = pathinfo($file);
        $info['filepath'] = $file;
        $info['fileatime'] = fileatime($file);
        $info['filectime'] = filectime($file);
        $info['filemtime'] = filemtime($file);
        $info['filetoken'] = rawurlencode($GLOBALS['dus']->strEncode($file, SESS_ID));

        $logList[crc32($file)] = $info;
    }

    return $logList;
}

/**
 * 刪除多筆紀錄
 *
 * @param     mixed      $filePath    檔案路徑(用 ',' 隔開, 或是陣列)
 *
 * @return    integer
 */
function delRecord($filePath)
{
    $dropCount = 0;

    !empty($filePath) && $filePath = array_map('rawurldecode', $filePath);

    foreach ($filePath as $fPath) {

        $fPath = $GLOBALS['dus']->strDecode($fPath, SESS_ID);

        if (strpos($fPath, TEMP_PATH) !== false && dropFile($fPath)) {

            /* 刪除後處理動作 */
            $dropCount++;
        }
    }

    return $dropCount;
}
