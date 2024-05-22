<?php

/**
 * 管理權限檢測
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '管理權限檢測');
define('SYS_SAFE_CODE', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限 */
adminPriv('sys');

switch ($_REQUEST['action']) {

case 'check':

    require_once(BASE_PATH . 'cls_files.php');
    $fileObj = new cls_files();

    $dir = dirname(__FILE__);
    $phpArr = $fileObj->getDirList($dir);
    $privArr = array();
    foreach ($phpArr as $file) {

        if (strcasecmp(substr($file, -4, 4), '.php') == 0 && is_file($dir . '/' . $file)) {

            $phpFile = file_get_contents($dir . '/' . $file);
            preg_match_all('/(adminPriv|checkAuthzJson)\(["|\'](.*)["|\']/', $phpFile, $m1);
            $privArr[$file] = array_unique(array_diff($m1[2], array('sys')));
            if (empty($privArr[$file])) {

                unset($privArr[$file]);
            }
        }
    }
    ksort($privArr);

    $filePriv = array();
    foreach ($privArr as $val => $arr) {

        foreach ($arr as $key) {

            $filePriv[$key] = $val;
        }
    }

    // 取得已建立權限
    $sql = 'SELECT action_id, action_code, action_name FROM ' . $db->table('admin_action') . ' ' .
           'WHERE parent_id <> 0 ORDER BY action_code ASC';
    $res = $db->query($sql);
    $dbPriv = array();
    while ($priv = $db->fetchAssoc($res)) {

        $dbPriv[$priv['action_code']]['action_id'] = $priv['action_id'];
        $dbPriv[$priv['action_code']]['action_name'] = $priv['action_name'];
    }

    $noCreatePriv['file'] = array_diff(array_keys($filePriv), array_keys($dbPriv));
    $noCreatePriv['db'] = array_diff(array_keys($dbPriv), array_keys($filePriv));

    $smarty->assign('priv_arr', $privArr);
    $smarty->assign('file_priv', $filePriv);
    $smarty->assign('db_priv', $dbPriv);
    $smarty->assign('no_create_priv', $noCreatePriv);

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

//-- 編輯操作代碼
case 'edit_action_code':

    $actionId   = intval($_POST['id']);
    $actionCode = trim($_POST['val']);

    $sql = 'UPDATE ' . $db->table('admin_action') . ' SET ' .
           'action_code = "' . $actionCode . '" ' .
           'WHERE action_id = ' . $actionId . ' LIMIT 1';
    if ($db->query($sql, 'SILENT')) {

        makeJsonResult(stripslashes($actionCode));

    } else {

        makeJsonError("編輯失敗。\n" . $db->error());
    }

    break;

//-- 編輯操作名稱
case 'edit_action_name':

    $actionId   = intval($_POST['id']);
    $actionName = trim($_POST['val']);

    $sql = 'UPDATE ' . $db->table('admin_action') . ' SET ' .
           'action_name = "' . $actionName . '" ' .
           'WHERE action_id = ' . $actionId . ' LIMIT 1';
    if ($db->query($sql, 'SILENT')) {

        makeJsonResult(stripslashes($actionName));

    } else {

        makeJsonError("編輯失敗。\n" . $db->error());
    }
    break;
}
exit;