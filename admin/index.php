<?php

/**
 * 控制台首頁
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '管理中心');

require(dirname(__FILE__) . '/includes/init.php');

$_REQUEST['action'] = trim($_REQUEST['action']);
switch ($_REQUEST['action']) {
//-- 框架
case '':

    $stateRouterUrl = array_map(
        function ($v) {
            return basename($v, '.php');
        },
        glob(ADMIN_PATH . '*.php')
    );
    arsort($stateRouterUrl);

    $stateRouterUrl = array_diff($stateRouterUrl, array('index', 'dashboards', 'clear_cache'));
    $smarty->assign('state_router_url', $stateRouterUrl);

    assignTemplate();
    $smarty->display('index.html');
    break;

case 'navbar':

    assignTemplate();
    $smarty->display('common/navbar.html');
    break;

case 'footer':

    assignTemplate();
    $smarty->display('common/footer.html');
    break;

case 'menu':

    require(ADMIN_PATH . 'includes/inc_menu.php');

    foreach ($modules as $k1 => $v1) {

        if (!empty($v1['children']) && is_array($v1['children'])) {

            foreach ($v1['children'] as $k2 => $v2) {

                if (isset($v2['private']) && !adminPriv($v2['private'], false)) {

                    unset($modules[$k1]['children'][$k2]);
                }
            }

        } elseif (isset($v1['private']) && !adminPriv($v1['private'], false)) {

            unset($modules[$k1]);
        }

        // 如果 children 的子元素長度為0則刪除該組
        if (!isset($v1['code']) && empty($modules[$k1]['children'])) {

            unset($modules[$k1]);
        }
    }

    $smarty->assign('menus', $modules);

    assignTemplate();
    $smarty->display('common/nav.html');
    break;

case 'dashboards':

    $smarty->assign('shop_name', $_CFG['shop_name']);

    $gd = gdVersion();

    /* 檢查文件目錄屬性 */
    $warning = array();

    $openBaseDir = ini_get('open_basedir');
    if (!empty($openBaseDir)) {

        /* 如果 open_basedir 不為空，則檢查是否包含了 upload_tmp_dir  */
        $openBaseDir = str_replace(array("\\", "\\\\"), array("/", "/"), $openBaseDir);
        $uploadTmpDir = ini_get('upload_tmp_dir');

        if (empty($uploadTmpDir)) {

            if (stristr(PHP_OS, 'win')) {

                $uploadTmpDir = getenv('TEMP') ? getenv('TEMP') : getenv('TMP');
                $uploadTmpDir = str_replace(array("\\", "\\\\"), array("/", "/"), $uploadTmpDir);

            } else {

                $uploadTmpDir = getenv('TMPDIR') === false ? '/tmp' : getenv('TMPDIR');
            }
        }

        if (!stristr($openBaseDir, $uploadTmpDir)) {

            $warning[] = sprintf('您的服務器設置了 open_base_dir 且沒有包含 %s，您將無法上傳文件。', $uploadTmpDir);
        }
    }

    $result = fileModeInfo(DATA_PATH);
    if ($result < 2) {

        $warning[] = sprintf('%s 目錄不可寫入，%s', 'data', '您將無法上傳檔案等等文件。');
    }

    $result = fileModeInfo(IMAGE_PATH);
    if ($result < 2) {

        $warning[] = sprintf('%s 目錄不可寫入，%s', 'images', '您將無法上傳任何圖片。');
    }

    $result = fileModeInfo(TEMP_PATH);
    if ($result < 2) {

        $warning[] = sprintf('%s 目錄不可寫入，%s', 'temp', '您的網站將無法瀏覽。');
    }

    clearstatcache();

    $smarty->assign('warning_arr', $warning);

    /* 總瀏覽人數 */
    $sql = 'SELECT COUNT(access_time) ' .
           'FROM ' . $db->table('stats');
    $visit['total'] = $db->getOne($sql);
    if (isset($_CFG['visit_history_num'])) {

        $visit['total'] += (int)$_CFG['visit_history_num'];
    }

    $endDate = strtotime('today') + 86399;
    $startDate = strtotime('today') - 518400;

    $xValues = array();
    for ($i = 6; $i >= 0; $i--) {

        $xValues[] = date('m-d', strtotime('- ' . $i . ' day'));
    }

    $stats = array();
    $stats['flow']['option'] = array(
        'tooltip' => array(
            'trigger' => 'axis',
            'axisPointer' => array(
                'type' => 'cross',
                'label' => array(
                    'backgroundColor' => '#6a7985'
                )
            )
        ),
        'legend' => array(
            'data' => array()
        ),
        'grid' => array(
            'left' => '3%',
            'right' => '4%',
            'bottom' => '3%',
            'containLabel' => true
        ),
        'xAxis' => array(
            (object) array(
                'type' => 'category',
                'boundaryGap' => false,
                'data' => $xValues
            )
        ),
        'yAxis' => array(
            (object) array(
                'type' => 'value'
            )
        ),
        'series' => array()
    );

    $itemArr = array();
    $sql = 'SELECT FLOOR((access_time - ' . $startDate . ') / (24 * 3600)) AS sn, ' .
           'area, access_time, COUNT(*) AS access_count ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . $endDate . ' ' .
           'GROUP BY sn, area ' .
           'ORDER BY access_count';
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $itemArr[$row['area']][date('m-d', $row['access_time'])] = (int)$row['access_count'];
    }

    foreach ($itemArr as $area => $row) {

        $values = array(
            'name' => $area,
            'type' => 'line',
            'stack' =>  '總量',
            'areaStyle' => array(
                'normal' => (object) array()
            ),
            'data' => array()
        );

        foreach ($xValues as $date) {

            $values['data'][] = isset($row[$date]) ? $row[$date] : 0;
        }

        $stats['flow']['option']['legend']['data'][] = $area;
        $stats['flow']['option']['series'][] = $values;
    }

    $variable = array(
        'referer_domain' => '來源網站',
        'browser' => '瀏覽器',
        'system' => '作業系統'
    );
    foreach ($variable as $key => $label) {

        $itemArr = array();
        $stats[$key]['option'] = array(
            'tooltip' => array(
                'trigger' => 'item',
                'formatter' => '{a} <br>{b} : {c} ({d}%)'
            ),
            'legend' => array(
                'type' => 'scroll',
                'orient' => 'vertical',
                'right' => 10,
                'top' => 20,
                'bottom' => 20,
                'data' => array()
            ),
            'series' => array()
        );

        $values = array(
            'name' => $label,
            'type' => 'pie',
            'radius' => '70%',
            'center' => array('50%', '60%'),
            'data' => array(),
            'itemStyle' => array(
                'emphasis' => array(
                    'shadowBlur' => 10,
                    'shadowOffsetX' => 0,
                    'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                )
            )
        );

        $sql = 'SELECT COUNT(*) AS access_count, ' . $key . ' ' .
               'FROM ' . $db->table('stats') . ' ' .
               'WHERE access_time >= ' . $startDate . ' ' .
               'AND access_time < ' . $endDate . ' ' .
               'GROUP BY ' . $key . ' ' .
               'ORDER BY access_count DESC';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            if ($key == 'area') {

                $newKey = empty($row[$key]) ? 'unknow' : $row[$key];

            } elseif ($key == 'referer_domain') {

                $newKey = empty($row[$key]) ? '直接輸入網址' : $row[$key];

            } elseif ($key == 'browser') {

                $newKey = $row[$key] == '' || $row[$key] == 'Unknow browser' ? '未知瀏覽器' : $row[$key];
                $newKey = preg_replace('/^Chrome(\s\d+\.\d+)(.*)$/i', 'Chrome', $newKey);
                $newKey = preg_replace('/^FireFox(\s\d+\.\d+)(.*)$/i', 'FireFox', $newKey);
                $newKey = preg_replace('/Safari(\s\d+\.\d+)(.*)$/i', 'Safari', $newKey);

            } elseif ($key == 'system') {

                $newKey = $row[$key] == '' ? '未知系統' : $row[$key];
                $newKey = preg_replace('/^i(Phone|Pod|Pad)(.*)$/i', 'i$1', $newKey);
                $newKey = preg_replace('/^Android(\s\d+\.\d+)(.*)$/i', 'Android', $newKey);
                $newKey = preg_replace('/^OS X(.*)$/i', 'OS X', $newKey);
                $newKey = preg_replace('/^Windows(.*)$/i', 'Windows', $newKey);
            }

            if (!isset($itemArr[$newKey])) {

                $itemArr[$newKey] = $row['access_count'];

            } else {

                $itemArr[$newKey] += $row['access_count'];
            }
        }

        foreach ($itemArr as $name => $val) {

            $stats[$key]['option']['legend']['data'][] = $name;
            $values['data'][] = array(
                'value' => $val,
                'name' => $name
            );
        }
        $stats[$key]['option']['series'][] = $values;
    }

    $smarty->assign('stats', $stats);

    // 待發送郵件
    $sql = 'SELECT COUNT(*) FROM ' . $db->table('email_queue') . ' ' .
           'WHERE error < 3';
    $smarty->assign('queue_count', $db->getOne($sql));

    /* 系統信息 */
    $sysInfo['os']            = PHP_OS;
    $sysInfo['web_server']    = $_SERVER['SERVER_SOFTWARE'];
    $sysInfo['php_ver']       = PHP_VERSION;
    $sysInfo['sql_ver']       = $db->version(); // 獲得 MySQL 版本
    $sysInfo['smarty_ver']    = str_replace('Smarty-', '', Smarty::SMARTY_VERSION);
    $sysInfo['phpmailer_ver'] = \PHPMailer\PHPMailer\PHPMailer::VERSION;
    $sysInfo['zlib']          = function_exists('gzclose') ? '是' : '否';
    $sysInfo['safe_mode']     = (boolean) ini_get('safe_mode') ? '是' : '否';
    $sysInfo['safe_mode_gid'] = (boolean) ini_get('safe_mode_gid') ? '是' : '否';
    $sysInfo['socket']        = function_exists('fsockopen') ? '是' : '否';
    $sysInfo['timezone']      = date('e');
    $sysInfo['utc']           = sprintf('UTC%s', date('O'));
    $sysInfo['socket']        = function_exists('fsockopen') ? '是' : '否';
    $sysInfo['max_filesize']  = ini_get('upload_max_filesize'); /* 允許上傳的最大文件大小 */

    if ($gd == 0) {

        $sysInfo['gd'] = 'N/A';

    } else {

        if ($gd == 1) {

            $sysInfo['gd'] = 'GD1';

        } else {

            $sysInfo['gd'] = 'GD2';
        }

        $sysInfo['gd'] .= ' (';

        /* 檢查系統支持的圖片類型 */
        if ($gd && (imagetypes() & IMG_JPG) > 0) {

            $sysInfo['gd'] .= ' JPEG';
        }

        if ($gd && (imagetypes() & IMG_GIF) > 0) {

            $sysInfo['gd'] .= ' GIF';
        }

        if ($gd && (imagetypes() & IMG_PNG) > 0) {

            $sysInfo['gd'] .= ' PNG';
        }

        $sysInfo['gd'] .= ')';
    }

    $smarty->assign('sys_info', $sysInfo);

    $position = assignUrHere(SCRIPT_TITLE);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display('default.html');
    break;

//-- 清除緩存
case 'clear_cache':

    $operatePriv = explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);
    if (in_array('sys', $operatePriv) || in_array('clear_compile_tpl', $operatePriv)) {

        clearAllFiles();

    } else {

        clearCacheFiles();
    }

    sysMsg('網頁暫存檔案已經清除成功。');
    break;

//-- 郵件群發處理
case 'send_mail':

    $_SESSION[SHOP_SESS]['SENDLIST'] = !empty($_SESSION[SHOP_SESS]['SENDLIST'])
        ? $_SESSION[SHOP_SESS]['SENDLIST']
        : array();

    $sql = 'SELECT eq.* '
         . 'FROM ' . $db->table('email_queue') . ' AS eq '
         . 'WHERE eq.error < 3 '
         . 'AND eq.id NOT ' . $db->in($_SESSION[SHOP_SESS]['SENDLIST']) . ' '
         . 'ORDER BY eq.pri DESC, eq.last_send ASC LIMIT 1';
    $sendList = $db->getRow($sql);

    // 發送列表為空
    if (empty($sendList['id'])) {

        makeJsonResult('', '郵件發送列表為空!', array('count' => 0));
    }

    $sendList['email'] = $dus->dataDecode($sendList['email']);

    $_SESSION[SHOP_SESS]['SENDLIST'][$sendList['id']] = $sendList['id'];

    // 發送列表不為空，郵件地址為空
    if (!empty($sendList['id']) && empty($sendList['email'])) {

        $sql = 'DELETE FROM ' . $db->table('email_queue') . ' ' .
               'WHERE id = ' . $sendList['id'];
        $db->query($sql);

        unset($_SESSION[SHOP_SESS]['SENDLIST'][$sendList['id']]);

        $sql = 'SELECT COUNT(*) FROM ' . $db->table('email_queue') . ' ' .
               'WHERE error < 3';
        $count = $db->getOne($sql);
        makeJsonResult('', '繼續發送下一筆...', array('count' => $count, 'goon' => 1));
    }

    // 查詢相關模板
    $sql = 'SELECT * FROM ' . $db->table('mail_templates') . ' ' .
           'WHERE template_id = ' . $sendList['template_id'];
    $rt = $db->getRow($sql);

    // 如果是模板，則將已存入 email_queue 的內容作為郵件內容
    // 否則即是雜質，將 mail_templates 調出的內容作為郵件內容
    if (empty($rt['template_id']) && $sendList['email_subject']) {

        $rt['template_subject'] = $sendList['email_subject'];
        $rt['template_content'] = $sendList['email_content'];
        $rt['is_html'] = 1;

    } elseif ($rt['type'] == 'template' && $sendList['email_content'] != '') {

        $rt['template_content'] = $sendList['email_content'];
    }

    if ($rt['template_content'] != '') {

        $attachFile = @unserialize($sendList['file']);
        if (!is_array($attachFile)) {

            $attachFile = array();
        }

        $status = sendMail(
            $sendList['email'],
            $rt['template_subject'],
            $dus->urlDecode($rt['template_content']),
            $rt['is_html'],
            $attachFile
        );

        // 發送成功
        if ($status) {

            // 從列表中刪除
            $sql = 'DELETE FROM ' . $db->table('email_queue') . ' ' .
                   'WHERE id = ' . $sendList['id'];
            $db->query($sql);

            unset($_SESSION[SHOP_SESS]['SENDLIST'][$sendList['id']]);

            // 剩餘列表數
            $sql = 'SELECT COUNT(*) FROM ' . $db->table('email_queue') . ' ' .
                   'WHERE error < 3';
            $count = $db->getOne($sql);

            if ($count > 0) {

                $msg = sprintf("郵件 %s 發送成功! 還有 %s 封郵件未發送!", $sendList['email'], $count);

            } else {

                $msg = sprintf("郵件 %s 發送成功! 全部郵件已發送完成!", $sendList['email']);
            }
            makeJsonResponse('', 0, $msg, array('count' => $count));

        } else {

            // 發送出錯
            $sql = 'UPDATE ' . $db->table('email_queue') . ' SET ' .
                   'error = error + 1, ' .
                   'pri = 0, ' .
                   'last_send = ' . $_SERVER['REQUEST_TIME'] . ' ' .
                   'WHERE id = ' . $sendList['id'];
            $db->query($sql);

            unset($_SESSION[SHOP_SESS]['SENDLIST'][$sendList['id']]);

            $sql = 'SELECT COUNT(*) FROM ' . $db->table('email_queue') . ' ' .
                   'WHERE error < 3';
            $count = $db->getOne($sql);
            makeJsonResponse('', 0, sprintf("郵件 %s 發送失敗!", $sendList['email']), array('count' => $count));
        }

    } else {

        // 無效的郵件隊列
        $sql = 'DELETE FROM ' . $db->table('email_queue') . ' ' .
               'WHERE id = ' . $sendList['id'];
        $db->query($sql);

        unset($_SESSION[SHOP_SESS]['SENDLIST'][$sendList['id']]);

        $sql = 'SELECT COUNT(*) FROM ' . $db->table('email_queue') . ' ' .
               'WHERE error < 3';
        $count = $db->getOne($sql);
        makeJsonResponse('', 0, sprintf("郵件 %s 發送失敗!", $sendList['email']), array('count' => $count));
    }
    break;
}
exit;