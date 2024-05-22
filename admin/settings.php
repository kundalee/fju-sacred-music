<?php

/**
 * 管理中心系統設置
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));

if (
    (isset($_GET['action']) && 'mail_server' == $_GET['action']) ||
    (isset($_POST['type']) && 'mail_server' == $_POST['type'])
) {

    define('SCRIPT_TITLE', '郵件伺服器設定'); // 腳本標題
    define('ACTION_CODE', 'mail_server');

} else {

    define('SCRIPT_TITLE', '系統設定'); // 腳本標題
    define('ACTION_CODE', 'web_site');
}

/* 載入函式檔 */
require(dirname(__FILE__) . '/includes/init.php');

switch ($_REQUEST['action']) {

//-- 網站設定 || 郵件服務器設定
case 'web_site':
case 'mail_server':

    /* 檢查權限 */
    adminPriv('shop_config');

    if ($_REQUEST['action'] == 'web_site') {

        $smarty->assign('group_list', getSettings(null, array('smtp')));

    } else {

        $cfg = true == extension_loaded('openssl')
             ? getSettings(array('smtp'))
             : getSettings(array('smtp'), array('smtp_secure'));
        $cfg = array_shift($cfg);

        $smarty->assign('cfg', $cfg['vars']);
    }

    $smarty->assign('languages', getLanguage());

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_' . $_REQUEST['action'] . '.html');
    break;

//-- 提交
case 'post':

    /* 檢查權限 */
    adminPriv('shop_config');

    handlePostConfigure();

    /* 記錄日誌 */
    adminLog('', '編輯', SCRIPT_TITLE);

    /* 清除緩存 */
    clearCacheFiles();

    $_CFG = array_merge($_CFG, loadConfig());

    $links[0] = array('text' => '返回上一頁', 'sref' => buildUIRef('?action=' . ACTION_CODE));

    sysMsg('儲存設定成功。', 0, $links);
    break;

//-- 刪除上傳文件
case 'del':

    checkAuthzJson('shop_config');

    /* 取得參數 */
    if (isset($_POST['code'])) {

        $code = trim($_POST['code']);

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $langId = !empty($_POST['lang_id']) ? intval($_POST['lang_id']) : 0;

    $sql = 'SELECT sc.name, sc.value ' .
           'FROM ' . $db->table('shop_config') . ' AS sc ' .
           'WHERE sc.type IN("file", "image") ' .
           'AND sc.code = "' . $code . '" ' .
           'AND sc.lang_id = "' . $langId . '"';
    if ($oldFile = $db->getRow($sql)) {

        if (true == updateConfigure($langId, $code, '')) {

            clearCacheFiles(); // 清空暫存檔

            adminLog($oldFile['name'], '編輯', SCRIPT_TITLE); // 記錄日誌

            // 刪除檔案
            dropFile($oldFile['value']);

            makeJsonResult('', '檔案刪除成功');
        }
    }

    makeJsonError('檔案刪除失敗');
    break;

//-- 發送測試郵件
case 'send_test_email':

    /* 取得參數 */
    $eMail = isset($_POST['email']) ? trim($_POST['email']) : '';

    /* 更新配置 */
    isset($_POST['mail_service']) && $_CFG['mail_service'] = trim($_POST['mail_service']);
    isset($_POST['smtp_host']) && $_CFG['smtp_host'] = trim($_POST['smtp_host']);
    isset($_POST['smtp_port']) && $_CFG['smtp_port'] = trim($_POST['smtp_port']);
    if (isset($_POST['smtp_use_authentication'])) {

        $_CFG['smtp_use_authentication'] = intval($_POST['smtp_use_authentication']);
    }
    isset($_POST['smtp_user']) && $_CFG['smtp_user'] = trim($_POST['smtp_user']);
    isset($_POST['smtp_pass']) && $_CFG['smtp_pass'] = $dus->compilePassword(trim($_POST['smtp_pass']), 'ENCODE');
    isset($_POST['smtp_secure']) && $_CFG['smtp_secure'] = trim($_POST['smtp_secure']);
    isset($_POST['smtp_mail']) && $_CFG['smtp_mail'] = trim($_POST['reply_email']);
    isset($_POST['mail_charset']) && $_CFG['mail_charset'] = trim($_POST['mail_charset']);

    $mailTpl = '您好！這是一封檢測郵件伺服器設定的測試郵件。<br>'
             . '收到此郵件，意味著您的郵件伺服器設定正確！'
             . '您可以進行其它郵件發送的操作了！';
    if (sendMail($eMail, '測試郵件', $mailTpl)) {

        makeJsonResult('', '恭喜！測試郵件已成功發送到 ' . $eMail);

    } else {

        makeJsonError(implode(PHP_EOL, $err->_message));
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 *
 *
 * @return    void
 */
function handlePostConfigure()
{
    $sql = 'SELECT sc.code FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc ' .
           'WHERE sc.type = "password"';
    $passwordFields = $GLOBALS['db']->getCol($sql);
    $passwordFields = array_unique($passwordFields);

    if (!empty($_POST['value'])) {

        // 儲存設定值
        $count = count($_POST['value']);
        foreach ($_POST['value'] as $key => $val) {

            $val = trim($val);
            $key = trim($key);

            if (!empty($val) && in_array($key, $passwordFields)) {

                $val = $GLOBALS['dus']->compilePassword($val, 'ENCODE');
            }

            $GLOBALS['db']->query(
                $GLOBALS['db']->buildSQL(
                    'U',
                    $GLOBALS['db']->table('shop_config'),
                    array(
                        'value' => $val,
                        'is_display' => !empty($_POST['is_display'][$key]) ? 1 : 0
                    ),
                    'lang_id = 0 AND ' .
                    'code = "' . $key . '"'
                )
            );
        }
    }

    // 儲存語系設定值
    if (!empty($_POST['description']['value'])) {

        foreach ($_POST['description']['value'] as $lKey => $lData) {

            foreach ($lData as $code => $val) {

                $val = trim($val);
                $code = trim($code);

                if (in_array($code, $passwordFields)) {

                    $val = $dus->compilePassword($val, 'ENCODE');
                }

                $GLOBALS['db']->query(
                    $GLOBALS['db']->buildSQL(
                        'U',
                        $GLOBALS['db']->table('shop_config'),
                        array(
                            'value' => $val,
                            'is_display' => !empty($_POST['description']['is_display'][$lKey][$code]) ? 1 : 0
                        ),
                        'lang_id = ' . (int) $lKey . ' AND ' .
                        'code = "' . $code . '"'
                    )
                );
            }
        }
    }

    /* 處理上傳檔案 */
    $fileVarList = array();
    $sql = 'SELECT sc.* ' .
           'FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc ' .
           'WHERE sc.type IN("file", "image")';
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $fileVarList[$row['code']] = $row;
    }
    if (!empty($fileVarList)) {

        $links[0] = array('text' => '返回上一頁', 'sref' => buildUIRef('?action=' . ACTION_CODE));

        // 載入圖片處理類別
        require_once(BASE_PATH . 'cls_image.php');

        $_files = restFilesArray($_FILES);

        foreach ($_files as $files) {

            $langFiles = restFilesArray($files);

            foreach ($langFiles as $langId => $field) {

                $fieldFiles = restFilesArray($field);

                foreach ($fieldFiles as $code => $file) {

                    // 是否選擇了檔案
                    if (
                        (isset($file['error']) && $file['error'] == 0) ||
                        (!isset($file['error']) && $file['tmp_name'] != 'none')
                    ) {

                        // 允許上傳的檔案類型]
                        $allowFileTypes = '';
                        if ($fileVarList[$code]['value_range'] != '') {

                            $allowFileTypes = $fileVarList[$code]['value_range'];

                        } elseif ($fileVarList[$code]['type'] == 'file') {

                            $allowFileTypes = '|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|CERT|';

                        } elseif ($fileVarList[$code]['type'] == 'image') {

                            $allowFileTypes = '|GIF|JPG|PNG|BMP|';
                        }

                        $uploadStstus = uploadFile(
                            $file,
                            $fileVarList[$code]['upload_dir'],
                            true,
                            true,
                            $allowFileTypes
                        );

                        // 是否上傳成功
                        if (true === $uploadStstus['status']) {

                            if (
                                $fileVarList[$code]['type'] == 'image' &&
                                $fileVarList[$code]['field_size'] != '' &&
                                $tmpVar = explode('|', $fileVarList[$code]['field_size'])
                            ) {

                                $bgColor = '';
                                $thumbWidth = $thumbHeight = $thumbType = 0;
                                isset($tmpVar[0]) && $thumbWidth = (int) $tmpVar[0];
                                isset($tmpVar[1]) && $thumbHeight = (int) $tmpVar[1];
                                isset($tmpVar[2]) && $thumbType = (int) $tmpVar[2];
                                isset($tmpVar[3]) && $bgColor = (int) $tmpVar[3];

                                $image = new cls_image($bgColor); // 建立圖片物件
                                $image->open(ROOT_PATH . $uploadStstus['path']);
                                if ($thumbWidth > 0 && $thumbHeight > 0) {

                                    $image->makeThumb($thumbWidth, $thumbHeight, $thumbType);

                                } elseif ($thumbWidth > 0) {

                                   $image->makeThumbWidth($thumbWidth);

                                } elseif ($thumbHeight > 0) {

                                    $image->makeThumbHeight($thumbHeight);
                                }

                                $image->autoPathSave(ROOT_PATH . $fileVarList[$code]['upload_dir']);
                                if (!empty($image->error)) {

                                    sysMsg($image->error[0]['error'], 0, $links);
                                }

                                dropFile($uploadStstus['path']);

                                $uploadStstus['path'] = $image->full_path;
                            }

                            // 更新資料
                            if (true == updateConfigure($langId, $code, $uploadStstus['path'])) {

                                // 刪除舊檔案
                                dropFile($fileVarList[$code]['value']);
                            }

                        } else {

                            sysMsg($uploadStstus['msg'], 0, $links);
                        }
                    }
                }
            }
        }
    }
}

/**
 * 設置系統設置
 *
 * @param     integer   $langId
 * @param     string    $code
 * @param     string    $val
 *
 * @return    boolean
 */
function updateConfigure($langId, $code, $val = '')
{
    if (!empty($code)) {

        return $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'U',
                $GLOBALS['db']->table('shop_config'),
                array(
                    'value' => $val
                ),
                'lang_id = ' . (int) $langId . ' AND ' .
                'code = "' . $code . '"'
            )
        );
    }

    return true;
}

/**
 * 取得設定資訊
 *
 * @param     array    $groups      需要取得的設定群組
 * @param     array    $excludes    不需要取得的設定群組
 *
 * @return    array
 */
function getSettings($groups = null, $excludes = null)
{
    $item = array();

    if (!empty($groups)) {

        foreach ($groups as $key => $val) {

            !is_numeric($val) && $item[] = $val;
        }
    }
    if (!empty($excludes)) {

        foreach ($excludes as $key => $val) {

            !is_numeric($val) && $item[] = $val;
        }
    }

    if (!empty($item)) {

        $sql = 'SELECT sc.id, sc.code FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc ' .
               'WHERE ' . $GLOBALS['db']->in($item, 'sc.code');
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $code[$row['code']] = $row['id'];
        }
    }

    /**
     * 取出全部設定陣列
     */
    $oper = 'type <> "hidden" ';

    // 如果非系統管理者
    if (false == adminPriv('sys', false)) {

        $oper .= 'AND is_display = 1 ';
    }
    // 取出需要設定群組
    if (!empty($groups)) {

        foreach ($groups as $key => $val) {

            if (is_numeric($val)) {

                $oper .= 'AND (sc.id = ' . $val . ' OR sc.parent_id = ' . $val . ') ';

            } elseif (isset($code[$val])) {

                $oper .= 'AND (sc.code = "' . $val . '" OR sc.parent_id = ' . $code[$val] . ') ';
            }
        }
    }
    // 排除不需要設定群組
    if (!empty($excludes)) {

        foreach ($excludes as $key => $val) {

            if (is_numeric($val)) {

                $oper .= 'AND (sc.id <> ' . $val . ' AND sc.parent_id <> ' . $val . ') ';

            } elseif (isset($code[$val])) {

                $oper .= 'AND (sc.code <> "' . $val . '" AND sc.parent_id <> ' . $code[$val] . ') ';
            }
        }
    }

    // 重構設定陣列
    $groupList = array();

    $sql = 'SELECT * FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc '
         . 'WHERE ' . $oper . ' '
         . 'ORDER BY sc.parent_id ASC, sc.sort_order ASC, sc.id ASC';
    $res = $GLOBALS['db']->query($sql);
    while ($item = $GLOBALS['db']->fetchAssoc($res)) {

        $item['name'] = !empty($item['name']) ? $item['name'] : $item['code'];

        if ($item['parent_id'] == 0) {

            // 設定群組
            if ($item['type'] == 'group') {

                $groupList[$item['id']] = $item;
            }

        } else {

            // 設定值
            if (!isset($groupList[$item['parent_id']])) {

                continue;
            }

            switch ($item['type']) {
                // 文字區域
                case 'textarea':

                    if ($item['field_size'] != '') {

                        list($sizeCols, $sizeRows) = array_map('intval', explode('|', $item['field_size']));
                        unset($item['field_size']);

                        $item['field_size'] = array('cols' => $sizeCols, 'rows' => $sizeRows);
                    }
                    break;

                // 選擇項
                case 'select':
                case 'options':

                    if (strlen($item['value_range']) > 0) {

                        foreach (explode(',', $item['value_range']) as $k => $v) {

                            list(
                                $item['store_options'][$k]['value'],
                                $item['store_options'][$k]['name']
                            ) = explode('|', $v, 2);
                        }
                    }
                    break;

                // 滑塊
                case 'slider':

                    $item['config'] = sprintf(
                        '{%s}',
                        htmlentities(str_replace('"', '\'', $item['value_range']), ENT_QUOTES, 'UTF-8')
                    );
                    break;

                // 密碼欄位
                case 'password':

                    $item['value'] = $GLOBALS['dus']->compilePassword($item['value']);
                    break;
            }

            if (isset($item['lang_id']) && $item['lang_id'] > 0) {

                $groupList[$item['parent_id']]['langs'][$item['lang_id']][] = $item;

            } else {

                $groupList[$item['parent_id']]['vars'][] = $item;
            }
        }
    }

    // 刪除空的設定群組
    foreach ($groupList as $key => $val) {

        if (!(isset($val['vars']) || isset($val['langs']))) {

            unset($groupList[$key]);
        }
    }

    return $groupList;
}
