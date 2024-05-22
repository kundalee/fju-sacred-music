<?php

/**
 * 系統檔案校驗
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '校驗結果');
define('SYS_SAFE_CODE', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限 */
adminPriv('sys');

if (!$webFiles = @file(BASE_PATH . 'codetable/web_files.dat')) {

    sysMsg('不存在校驗檔案，無法進行此操作', 1);
}

$_REQUEST['step'] = empty($_REQUEST['step']) ? 1 : max(1, intval($_REQUEST['step']));

switch ($_REQUEST['step']) {
case 1:
case 2:

    $smarty->assign('step', $_REQUEST['step']);

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

case 3:

    @set_time_limit(0);

    $md5Data = array();

    checkFiles('./', '\.php', 0);
    checkFiles(ADMIN_PATH, '\.php', 0);
    checkFiles(ADMIN_PATH . 'includes/', '\.php', 0);
    checkFiles(ADMIN_PATH . 'templates/', '\.php|\.html', 0);
    checkFiles(ROOT_PATH . 'data/', '\.php', 1);
    checkFiles(ROOT_PATH . 'images/', '\.php', 1);
    checkFiles(ROOT_PATH . 'includes/', '\.php', 1, 'CKFinder,debug,PHPExcel,PHPMailer,smarty');
    checkFiles(ROOT_PATH . 'themes/', '\.dwt|\.lbi|\.php', 1);

    foreach ($webFiles as $line) {

        $file = trim(substr($line, 34));
        $md5DataNew[$file] = substr($line, 0, 32);
        if (isset($md5Data[$file])) {

            if ($md5DataNew[$file] != $md5Data[$file]) {

                $modifyList[$file] = $md5Data[$file];

            } else {

                $md5DataNew[$file] = $md5Data[$file];
            }
        }
    }

    $weekBefore = $_SERVER['REQUEST_TIME'] - 604800; // 一周前的時間
    $addList = @array_diff_assoc($md5Data, $md5DataNew);
    $delList = @array_diff_assoc($md5DataNew, $md5Data);
    $modifyList = @array_diff_assoc($modifyList, $delList);
    $showList = @array_merge($md5Data, $md5DataNew);
    $result = $dirLog = array();
    foreach ($showList as $file => $md5) {

        $dir = dirname($file);
        $sF = $sT = 1;
        if (@array_key_exists($file, $modifyList)) {

            $status = '<i class="fas fa-exclamation-triangle fa-fw orange"></i>';
            if (!isset($dirLog[$dir]['modify'])) {

                $dirLog[$dir]['modify'] = '';
            }
            $dirLog[$dir]['modify']++;  // 統計「被修改」的文件
            $dirLog[$dir]['marker'] = substr(md5($dir), 0, 3);

        } elseif (@array_key_exists($file, $delList)) {

            $status = '<i class="fas fa-question-circle fa-fw green"></i>';
            if (!isset($dirLog[$dir]['del'])) {

                $dirLog[$dir]['del'] = '';
            }
            $dirLog[$dir]['del']++;     // 統計「被刪除」的文件
            $dirLog[$dir]['marker'] = substr(md5($dir), 0, 3);

        } elseif (@array_key_exists($file, $addList)) {

            $status = '<i class="fas fa-trash-alt fa-fw red"></i>';
            if (!isset($dirLog[$dir]['add'])) {

                $dirLog[$dir]['add'] = '';
            }
            $dirLog[$dir]['add']++;     // 統計「未知」的文件
            $dirLog[$dir]['marker'] = substr(md5($dir), 0, 3);

        } else {

            $status = '正確';
            $sF = 0;
        }

        // 對一周之內發生修改的文件日期加粗顯示
        $fMTime = @filemtime(ROOT_PATH . $file);
        if ($fMTime > $weekBefore) {

            $fMTime = '<strong>' . date('Y-m-d H:i:s', $fMTime) . '</strong>';

        } else {

            $fMTime = $fMTime ? date('Y-m-d H:i:s', $fMTime) : '';
            $sT = 0;
        }

        if ($sF) {

            $fileList[$dir][] = array(
                'file' => basename($file),
                'size' => file_exists(ROOT_PATH . $file) ? number_format(filesize(ROOT_PATH . $file)) . ' Bytes' : '',
                'filemtime' => $fMTime, 'status' => $status
            );
        }
    }

    $result['被修改'] = count($modifyList);
    $result['被刪除'] = count($delList);
    $result['未知'] = count($addList);

    $smarty->assign('result', $result);
    $smarty->assign('dirlog', $dirLog);
    $smarty->assign('filelist', $fileList);
    $smarty->assign('step', $_REQUEST['step']);

    $actionLink[] = array(
        'text' => '重新',
        'icon' => 'fas fa-sync fa-fw',
        'sref' => buildUIRef('?step=2'),
        'style' => 'btn-success'
    );
    $smarty->assign('action_link', $actionLink);

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;
}
exit;

/**
 * 檢查文件
 *
 * @param  string  $currDir    待檢查目錄
 * @param  string  $ext        待檢查的文件類型
 * @param  int     $sub        是否檢查子目錄
 * @param  string  $skip       不檢查的目錄或文件
 *
 * @return void
 */
function checkFiles($currDir, $ext = '', $sub = 1, $skip = '')
{
    global $md5Data;

    $currDir = ROOT_PATH . str_replace(ROOT_PATH, '', $currDir);
    $dir = @opendir($currDir);

    $exts = '/('. $ext .')$/i';
    $skips = explode(',', $skip);

    while ($entry = @readdir($dir)) {

        $file = $currDir . $entry;
        if ($entry != '.' && $entry != '..' && $entry != '.svn'
            && (preg_match($exts, $entry) || ($sub && is_dir($file))) && !in_array($entry, $skips)) {

            if ($sub && is_dir($file)) {

                checkFiles($file . '/', $ext, $sub, $skip);

            } else {

                if (str_replace(ROOT_PATH, '', $file) != './md5.php') {

                    $md5Data[str_replace(ROOT_PATH, '', $file)] = md5_file($file);
                }
            }
        }
    }
}
