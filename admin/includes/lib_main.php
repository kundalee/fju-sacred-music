<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 後台函式檔
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/**
 * 取得所有模組的名稱以及連結地址
 *
 * @param     string    $directory    模組存放的目錄
 *
 * @return    array
 */
function readModules($directory = '.')
{
    $dir = @opendir($directory);

    if ($dir !== false) {

        $setModules = true;
        $modules = array();

        while (false !== ($file = @readdir($dir))) {

            if (preg_match('/^.*?\.php$/', $file)) {

                include_once($directory . '/' . $file);
            }
        }
        @closedir($dir);
        unset($setModules);

        foreach ($modules as $key => $value) {

            ksort($modules[$key]);
        }
        ksort($modules);

        return $modules;
    }
}

/**
 * URL 過濾
 *
 * @param   string  $url  參數字符串，一個urld地址,對url地址進行校正
 *
 * @return  返回校正過的 url;
 */
function sanitizeUrl($url , $check = 'http://')
{
    if (strpos($url, $check) === false) {

        $url = $check . $url;
    }
    return $url;
}

/**
 * 系統提示信息
 *
 * @param       string      msg_detail      消息內容
 * @param       int         msg_type        消息類型， 0消息，1錯誤，2詢問
 * @param       array       links           可選的鏈接
 * @param       boolen      $autoRedirect   是否需要自動跳轉
 *
 * @return      void
 */
function sysMsg($msgDetail, $msgType = 0, $links = array(), $autoRedirect = true)
{
    if (count($links) == 0) {

        $links[0]['text'] = '返回上一頁';
        $links[0]['href'] = 'javascript:history.go(-1);';
    }

    ksort($links);

    assignTemplate();
    assignQueryInfo();

    $position = assignUrHere('系統訊息');
    $GLOBALS['smarty']->assign('page_title', $position['title']); // 頁面標題
    $GLOBALS['smarty']->assign('ur_here', $position['ur_here']);  // 當前位置
    $GLOBALS['smarty']->assign('msg_detail', $msgDetail);
    $GLOBALS['smarty']->assign('msg_type', $msgType);
    $GLOBALS['smarty']->assign('links', $links);
    $GLOBALS['smarty']->assign('auto_redirect', $autoRedirect);

    $GLOBALS['smarty']->display('message.html');
    exit;
}

/**
 * 設置管理員的 session 內容
 *
 * @param     integer    $adminId       使用者編號
 * @param     string     $username      使用者帳戶
 * @param     string     $avatarUrl     使用者頭像
 * @param     string     $actionList    權限列表
 * @param     string     $lastTime      最後登錄時間
 *
 * @return    void
 */
function setAdminSession($adminId, $username, $avatarUrl, $actionList, $lastTime)
{
    $layerAdmin = adminLayerList($adminId, 0, false);
    $data = current($layerAdmin);

    $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]     = $adminId;
    $_SESSION[SHOP_SESS][sha1('☠ admin_name ☠')]   = $username;
    $_SESSION[SHOP_SESS][sha1('☠ admin_layer ☠')]  = array_keys($layerAdmin);
    $_SESSION[SHOP_SESS][sha1('☠ admin_parent ☠')] = $data['parent_id'];
    $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]  = $actionList;
    $_SESSION[SHOP_SESS][sha1('☠ last_check ☠')]   = $lastTime; // 用於保存最後一次檢查訂單的時間
    $_SESSION[SHOP_SESS][sha1('☠ source_ip ☠')]    = realIP();
    $_SESSION[SHOP_SESS][sha1('☠ avatar_url ☠')]   = $avatarUrl;

    $_SESSION[SHOP_SESS][sha1('HANDLE')] = md5(
        implode(
            ' (^ω^) ',
            array(
                $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')],
                $_SESSION[SHOP_SESS][sha1('☠ admin_name ☠')],
                $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')],
                $_SESSION[SHOP_SESS][sha1('☠ source_ip ☠')]
            )
        )
    );
}

/**
 * 檢查管理員權限，返回JSON格式數劇
 *
 * @param     string    $authz
 *
 * @return    void
 */
function checkAuthzJson($authz)
{
    $privArr = @explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);
    if (!(in_array($authz, $privArr) || in_array('sys', $privArr) || in_array('all', $privArr))) {

        makeJsonError('對不起,您沒有執行此項操作的權限!');
    }
}

/**
 * 判斷管理員對某一個操作是否有權限。
 * 根據當前對應的 action_code，然後再和會員 session 裡面的 action_list 做匹配，以此來決定是否可以繼續執行。
 *
 * @param     string     $privStr      操作對應的
 * @param     boolean    $msgOutput    返回的類型
 *
 * @return    boolean
 */
function adminPriv($privStr, $msgOutput = true)
{
    $privArr = @explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);

    if ('sys' == $privStr && in_array('sys', $privArr)) {

        return true;

    } elseif ('sys' != $privStr && (in_array('all', $privArr) || in_array($privStr, $privArr))) {

        return true;

    } else {

        if ($msgOutput) {

            $links[] = array('text' => '返回上一頁', 'href' => 'javascript:history.back(-1);');
            sysMsg('對不起,您沒有執行此項操作的權限!', 0, $links);
        }
        return false;
    }
}

/**
 * 記錄管理員的操作內容
 *
 * @param     string    $sn         數據的唯一值
 * @param     string    $action     操作的類型
 * @param     string    $content    操作的內容
 *
 * @return    void
 */
function adminLog($sn = '', $action, $content)
{
    $logInfo = $action . $content . ($sn != '' ? ': ' . addslashes($sn) : '');

    $data['ip_address'] = realIP();
    $data['log_time'] = $_SERVER['REQUEST_TIME'];
    $data['log_info'] = stripslashes($logInfo);
    $data['admin_id'] = $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];

    $sql = $GLOBALS['db']->buildSQL('I', $GLOBALS['db']->table('admin_log'), $data);
    $GLOBALS['db']->query($sql);
}

/**
 * 管理員憑證授權操作
 *
 * @param   integer  $adminId
 * @param   string   $adminName
 * @param   string   $eMail
 *
 * @return  void
 */
function adminAuthOperate($adminId, $adminUser, $eMail)
{
    if (!DEMO_MODE) {

        $realIP = realIP();
        $ip = pack('N', intval(ip2long(gethostbyname($realIP))));
        if (!($realIP == '0.0.0.0' || ord($ip[0]) == 10 || ord($ip[0]) == 127 || (ord($ip[0]) == 192 && ord($ip[1]) == 168) || ($one == 172 && (ord($ip[1]) >= 16 && ord($ip[1]) <= 31)))) {

            $sendAuth = true;

            $adminId   = md5($adminId);
            $sourceIP  = md5($realIP);
            $userAgent = md5($_SERVER['HTTP_USER_AGENT'] . '-' . $adminUser . '-'  . $eMail);

            $data = array();
            $data['auth_code'] = $userAgent;
            $data['auth_time'] = $_SERVER['REQUEST_TIME'];

            $sql = 'SELECT auth_time, ' .
                   'SUBSTR(auth_code, 1, 32) AS user_agent, ' .
                   'SUBSTR(auth_code, 33, 32) AS is_validated, ' .
                   'SUBSTR(auth_code, 65, 32) AS admin_id, ' .
                   'SUBSTR(auth_code, 97, 32) AS source_ip ' .
                   'FROM ' . $GLOBALS['db']->table('admin_authorize') . ' ' .
                   'WHERE SUBSTR(auth_code, 65, 32) = "' . $adminId . '" ' .
                   'HAVING (source_ip = "' . $sourceIP . '" OR user_agent = "' . $userAgent . '")';
            $res = $GLOBALS['db']->query($sql);
            while ($tmp = $GLOBALS['db']->fetchAssoc($res)) {

                $sendAuth = false;
                if ($tmp['source_ip'] == $sourceIP) {

                   $verify[$tmp['is_validated']]['user_agent'][] = $tmp['user_agent'];
                   $verify[$tmp['is_validated']]['source_ip'][]  = $tmp['source_ip'];
                   $verify[$tmp['is_validated']]['auth_time'][]  = $tmp['auth_time'];
                }
            }

            $links[] = array('text' => '管理者登錄', 'href' => '?action=login');

            // 新增 動態IP 憑證確認
            if ($sendAuth || !isset($verify)) {

                $data['auth_code'] .= 'cfcd208495d565ef66e7dff9f98764da' . $adminId . $sourceIP;
                $sql = $GLOBALS['db']->buildSQL('I', $GLOBALS['db']->table('admin_authorize'), $data);
                $GLOBALS['db']->query($sql);
                $sendAuth = true;

            } elseif (isset($verify['c4ca4238a0b923820dcc509a6f75849b'])) { // 是否已開通的IP

                // 更新憑證
                $sql = $GLOBALS['db']->buildSQL('U', $GLOBALS['db']->table('admin_authorize'), array('auth_time' => $data['auth_time']), "SUBSTR(auth_code, 65, 64) = '" . $adminId . $sourceIP . "'");
                $GLOBALS['db']->query($sql);

                // 新增 瀏覽器 憑證
                if (!in_array($userAgent, $verify['c4ca4238a0b923820dcc509a6f75849b']['user_agent'])) {

                    $data['auth_code'] .= 'c4ca4238a0b923820dcc509a6f75849b' . $adminId . $sourceIP;
                    $sql = $GLOBALS['db']->buildSQL('I', $GLOBALS['db']->table('admin_authorize'), $data);
                    $GLOBALS['db']->query($sql);
                }

            } else {

                // 憑證確認郵件在1小時內確認
                if (max($verify['cfcd208495d565ef66e7dff9f98764da']['auth_time']) >= $data['auth_time'] - 180) {

                    sysMsg('請至管理者信箱收取驗證信', 0, $links, 0);

                } else {

                    // 刪除使用者未驗證記錄
                    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('admin_authorize') . ' ' .
                           'WHERE SUBSTR(auth_code, 33, 96) = "cfcd208495d565ef66e7dff9f98764da' . $adminId . $sourceIP . '"';
                    $GLOBALS['db']->query($sql);

                    // 新增憑證
                    $data['auth_code'] .= 'cfcd208495d565ef66e7dff9f98764da' . $adminId . $sourceIP;
                    $sql = $GLOBALS['db']->buildSQL('I', $GLOBALS['db']->table('admin_authorize'), $data);
                    $GLOBALS['db']->query($sql);
                    $sendAuth = true;
                }
            }

            // 刪除過期憑證
            $sql = 'DELETE FROM ' . $GLOBALS['db']->table('admin_authorize') . ' ' .
                   'WHERE auth_time <= ' . strtotime('-2 week');
            $GLOBALS['db']->query($sql);

            // 發送憑證確認郵件
            if ($sendAuth) {

                $verifyUrl = $GLOBALS['dus']->url() . ADMIN_DIR . '/privilege.php?action=authorize&hash=' . str_replace('+', '%2b', $GLOBALS['dus']->compilePassword($adminId . '-' . $userAgent . '-' . $sourceIP, 'ENCODE', '', 3600));

                $GLOBALS['smarty']->assign('username', $adminUser);
                $GLOBALS['smarty']->assign('source_ip', $realIP);
                $GLOBALS['smarty']->assign('operate_browser', getUserBrowser());
                $GLOBALS['smarty']->assign('operate_os', getUserOS());
                $GLOBALS['smarty']->assign('verify_url', $verifyUrl);
                $GLOBALS['smarty']->assign('shop_url', $GLOBALS['dus']->url());
                $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);

                $tpl = getMailTemplate('send_operate_authorize');
                $needError = $GLOBALS['smarty']->error_reporting;
                $GLOBALS['smarty']->error_reporting = E_ALL ^ E_NOTICE;
                $templateContent = $GLOBALS['smarty']->fetch('string:' . $tpl['template_content'], null, $tpl['template_code']);
                $GLOBALS['smarty']->error_reporting = $needError;
                $templateContent = $GLOBALS['dus']->urlDecode($templateContent);

                $status = sendMail(
                    sprintf('%s <%s>', $adminUser, $eMail),
                    $tpl['template_subject'],
                    $templateContent
                );
                if ($status) {

                    sysMsg('請至 ' . $adminUser . ' 管理者信箱收取驗證信。', 2, $links, 0);

                } else {

                    sysMsg('管理者操作驗證信，發送郵件失敗');
                }
            }
        }
    }
}

/**
 * 獲得指定管理者下的子管理者的數組
 *
 * @param     int        $layerId     管理者的ID
 * @param     int        $selected    當前選中管理者的ID
 * @param     boolean    $reType      返回的類型: 值為真時返回下拉列表,否則返回數組
 * @param     int        $level       限定返回的級數。為0時返回所有級數
 *
 * @return    mix
 */
function adminLayerList($layerId = 0, $selected = 0, $reType = true, $level = 0)
{
    static $res = null;

    if ($res === null) {

        $sql = 'SELECT m.admin_id, m.parent_id, m.admin_name, m.enabled, ' .
               'm.email, m.full_name, m.avatar_url, ' .
               'COUNT(s.admin_id) AS has_children ' .
               'FROM ' . $GLOBALS['db']->table('admin_user') . ' AS m ' .
               'LEFT JOIN ' . $GLOBALS['db']->table('admin_user') . ' AS s ' .
               'ON s.parent_id = m.admin_id ' .
               'GROUP BY m.admin_id ' .
               'ORDER BY m.parent_id, m.admin_id ASC';
        $res = $GLOBALS['db']->getAll($sql);
    }

    if (empty($res) == true) {

        return array();
    }

    $params = array('is_cache' => false, 'primary_field' => 'admin_id');
    $options = levelOptions('admin_user', $layerId, $res, $params); // 獲得指定分類下的子分類的數組

    /* 截取到指定的縮減級別 */
    if ($level > 0) {

        if ($layerId == 0) {

            $endLevel = $level;

        } else {

            $firstItem = reset($options); // 獲取第一個元素
            $endLevel = $firstItem['level'] + $level;
        }

        /* 保留 level 小於 endLevel的部分 */
        foreach ($options as $key => $val) {

            if ($val['level'] >= $endLevel) {

                unset($options[$key]);
            }
        }
    }

    return $options;
}

/**
 * 取得指定管理者下所屬的管理者 ID
 *
 * @param     integer    $adminId    指定的管理者 ID
 *
 * @return    array
 */
function getAdminChildId($adminId)
{
    $childIds = array_merge(array($adminId), array_keys(adminLayerList($adminId, 0, false)));

    return array_unique(array_filter($childIds));
}

/**
 * 取得指定管理者所隸屬的角色群組 ID
 *
 * @param     integer    $adminId    管理者 ID
 *
 * @return    array
 */
function getAdminRelRoleIds($adminId)
{
    $sql = 'SELECT role_id ' .
           'FROM ' . $GLOBALS['db']->table('admin_relation_role') . ' ' .
           'WHERE admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    return $GLOBALS['db']->getCol($sql);
}

/**
 * 取得當前位置和頁面標題
 *
 * @param     string    $str   作品名、文章標題或其他附加的內容（無鏈接）
 *
 * @return    array
 */
function assignUrHere($str = '', $extendArr = array())
{
    /* 取得文件名 */
    $fileName = basename($_SERVER['SCRIPT_NAME'], '.php');

    /* 初始化「頁面標題」和「當前位置」 */
    $pageTitle = $GLOBALS['_CFG']['shop_name'];
    $urHere = '<li><a href="./">首頁</a></li>';
    if (!empty($extendArr)) {

        foreach ($extendArr as $key => $val) {

            $pageTitle .= ' - ' . htmlspecialchars($val['text']);
            $urHere .= '<li>';
            if (isset($val['href']) && $val['href'] != '') {

                $urHere .= '<a href="' . $val['href'] . '">';
            }
            $urHere .= htmlspecialchars($val['text']);
            if (isset($val['href']) && $val['href'] != '') {

                $urHere .= '</a>';
            }
            $urHere .= '</li>';
        }
    }

    /* 處理最後一部分 */
    if (!empty($str)) {

        $pageTitle .= ' - ' . $str;
        $urHere    .= '<li class="active">' . $str . '</li>';
    }

    /* 返回值 */
    return array('title' => $pageTitle, 'ur_here' => $urHere);
}

function assignTemplate()
{
    $isAuthorize = !empty($_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]);

    $GLOBALS['smarty']->assign('is_authorize', $isAuthorize);
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('default_lang_id', $GLOBALS['_CFG']['default_lang_id']);
    $GLOBALS['smarty']->assign('upload_allow_file_ext', $GLOBALS['_CFG']['upload_allow_file_ext']);
    $GLOBALS['smarty']->assign('upload_allow_img_ext', $GLOBALS['_CFG']['upload_allow_img_ext']);
    $GLOBALS['smarty']->assign('upload_max_filesize', convertToBytes(ini_get('upload_max_filesize')));

    $uiLayoutCFG = !empty($_COOKIE['UI']) ? $_COOKIE['UI'] : array();
    $GLOBALS['smarty']->assign('ui_layout_cfg', $uiLayoutCFG);
}

/**
 * 獲得查詢時間和次數，並賦值給 smarty
 *
 * @return    void
 */
function assignQueryInfo()
{
    if ($GLOBALS['db']->query_time == '') {

        $queryTime = 0;

    } else {

        $queryTime = number_format(microtime(true) - $GLOBALS['db']->query_time, 6);
    }
    $GLOBALS['smarty']->assign('query_info', sprintf('共執行 %d 次資料庫查詢，花費 %f 秒', $GLOBALS['db']->query_count, $queryTime));

    /* 內存佔用情況 */
    if (function_exists('memory_get_usage')) {

        $GLOBALS['smarty']->assign('memory_info', sprintf('，記憶體佔用 %0.3f MB', memory_get_usage() / 1048576));
    }

    /* 是否啟用了 gzip */
    $enabled = gzipEnabled() ? '，Gzip 已啟用' : '，Gzip 已禁用';
    $GLOBALS['smarty']->assign('gzip_enabled', $enabled);
}

/**
 * 清除 SQL 緩存文件
 *
 * @param     mix     $clearAll    是否一次性清理所有緩存文件
 *
 * @return    void
 */
function clearCacheSQL($clearAll = false)
{
    $dir = TEMP_PATH . 'caches/query/';
    $folder = @opendir($dir);
    if ($folder === false) {

        return false;
    }

    $count = 0;
    $time = $_SERVER['REQUEST_TIME'] - (int)$GLOBALS['_CFG']['cache_time'];
    while ($file = readdir($folder)) {

        if (substr($file, 0, 9) != 'sql_cache_') {

            continue;
        }
        if (filemtime($dir . $file) < $time) {

            @unlink($dir . $file);
            if ($clearAll == false && $count++ > 3000) {

                break;
            }
        }
    }
    closedir($folder);

    return true;
}

/**
 * 清除過期檔案
 *
 * @param     mix     $clearAll    是否一次性清理所有緩存文件
 *
 * @return    void
 */
function clearExpiredFiles($clearAll = false)
{
    $count = 0;
    $time = $_SERVER['REQUEST_TIME'] - 86400;

    foreach (glob_recursive(TEMP_PATH . 'upload/*') as $file) {

        if (is_dir($file) || in_array(basename($file), array('index.htm', 'index.html'))) {

            continue;
        }

        if (filemtime($file) < $time) {

            @unlink($file);
            if ($clearAll == false && $count++ > 100) {

                break;
            }
        }
    }
}

/**
 * 清除過期資料表
 *
 * @return    void
 */
function clearExpiredDataTable()
{
    $repairTable = false;

    $currTime = $_SERVER['REQUEST_TIME'];

    // 檢查訪問統計資料表狀態
    $row = $GLOBALS['db']->getRow('OPTIMIZE TABLE ' . $GLOBALS['db']->table('stats'));
    if (false !== stripos($row['Msg_type'], 'Error')) {

        // 優化出錯，嘗試修復
        $repairTable = true;
        $GLOBALS['db']->query('REPAIR TABLE ' . $GLOBALS['db']->table('stats') . ' QUICK');
    }

    // 更新系統設置
    if (isset($GLOBALS['_CFG']['visit_history_num'])) {

        // 統計目前訪客人數
        $accessTime = strtotime('now -3 month', $currTime);
        $sql = 'SELECT COUNT(*) FROM ' .  $GLOBALS['db']->table('stats') . ' ' .
               'WHERE access_time <= ' . $accessTime;
        $currentVisit = $GLOBALS['db']->getOne($sql);

        $sql = 'UPDATE ' . $GLOBALS['db']->table('shop_config') . ' SET ' .
               'value = ' . (intval($GLOBALS['_CFG']['visit_history_num']) + $currentVisit) . ' ' .
               'WHERE code = "visit_history_num"';
        $GLOBALS['db']->query($sql);

        // 資料筆數過大時先修復
        if ($currentVisit > 50000 && $repairTable == false) {

            $repairTable = true;
            $GLOBALS['db']->query('REPAIR TABLE ' . $GLOBALS['db']->table('stats') . ' QUICK');
        }

        // 清除過期訪客資訊
        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('stats') . ' ' .
               'WHERE access_time <= ' . $accessTime;
        $GLOBALS['db']->query($sql);

        // 再判斷是否大量清除過期訪客
        if ($repairTable) {

            $GLOBALS['db']->getRow('OPTIMIZE TABLE ' . $GLOBALS['db']->table('stats'));
        }
    }

    // 刪除過期管理記錄
    if (!empty($GLOBALS['_CFG']['clear_log_admin_action'])) {

        $sql = 'DELETE FROM' . $GLOBALS['db']->table('admin_log') . ' ' .
               'WHERE log_time <= ' . ($currTime - (86400 * 60));
        $GLOBALS['db']->query($sql);
    }

    // 清除過期關鍵字資訊
    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('keywords') . ' ' .
           'WHERE date <= "' . date('Y-m-d', strtotime('now -6 month', $currTime)) . '"';
    $GLOBALS['db']->query($sql);

    // 清除過期搜索引擎資訊
    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('searchengine') . ' ' .
           'WHERE date <= "' . date('Y-m-d', strtotime('now -6 month', $currTime)) . '"';
    $GLOBALS['db']->query($sql);
}

/**
 * 自動執行處理程序
 *
 * @return    void
 */
function autoExecProcess()
{
    clearCacheSQL(false);

    clearExpiredFiles(false);

    clearExpiredDataTable(); // 清除過期資料表資訊

    // do something
}

/**
 * 根據過濾條件獲得排序的標記
 *
 * @param     array    $filter
 *
 * @return    array
 */
function sortFlag($filter)
{
    $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['icon'] = sprintf('-%s', ($filter['sort_order'] == 'DESC' ? 'down' : 'up'));

    return $flag;
}

/**
 * 根據 SESSION 中的過濾條件建立查詢 URL
 *
 * @param     string    $fileUrl
 * @param     string    $fileQuery
 *
 * @return    string
 */
function buildQueryUrl($fileUrl = '', $fileQuery = null)
{
    if ($fileQuery === null) {

        $fileQuery = basename($_SERVER['SCRIPT_FILENAME'], '.php');
    }

    parse_str(parse_url($fileUrl, PHP_URL_QUERY), $urlQuery);
    if (isset($_SESSION[SHOP_SESS]['filter'][$fileQuery]) && is_array($_SESSION[SHOP_SESS]['filter'][$fileQuery])) {

        $urlQuery = array_merge($_SESSION[SHOP_SESS]['filter'][$fileQuery], $urlQuery);
    }

    $urlPath = $fileUrl == '' ? basename($_SERVER['SCRIPT_FILENAME']) : parse_url($fileUrl,  PHP_URL_PATH);

    if (!empty($urlQuery)) {

        $urlPath .= '?' . http_build_query($urlQuery);
    }

    return $urlPath;
}

function buildUIRef($viewQuery = '?action=list', $sessionUrl = false, $viewRef = null, $fileQuery = null)
{
    if ($viewRef === null) {

        $viewRef = SCRIPT_NAME;
    }

    if ($sessionUrl) {

        $viewQuery =  buildQueryUrl($viewQuery, $fileQuery);
    }

    parse_str(parse_url($viewQuery, PHP_URL_QUERY), $viewQuery);

    $mainQuery = array('action', 'id');
    foreach ($mainQuery as $key) {

        if (isset($viewQuery[$key])) {

            $handleQuery[$key] = $viewQuery[$key];
            unset($viewQuery[$key]);
        }
    }

    if (!empty($viewQuery)) {

        $handleQuery['query'] = base64_encode(json_encode($viewQuery));
    }

    return $viewRef . (!empty($handleQuery) ? '(' . json_encode($handleQuery) . ')' : '');
}

/**
 * 建立一個 JSON 格式的資料
 *
 * @param     string     $content
 * @param     integer    $error
 * @param     string     $message
 * @param     array      $append
 *
 * @return    void
 */
function makeJsonResponse($content = '', $error = '0', $message = '', $append = array())
{
    $res = array('error' => $error, 'message' => $message, 'content' => $content);

    if (!empty($append)) {

        foreach ($append as $key => $val) {

            $res[$key] = $val;
        }
    }

    $val = json_encode($res);

    exit($val);
}

/**
 * 建立一個 JSON 格式的資訊
 *
 * @param     string    $content
 * @param     string    $message
 * @param     array     $append
 *
 * @return    void
 */
function makeJsonResult($content, $message = '', $append = array())
{
    makeJsonResponse($content, 0, $message, $append);
}

/**
 * 創建一個 JSON 格式的錯誤信息
 *
 * @param     string    $message
 *
 * @return    void
 */
function makeJsonError($message)
{
    makeJsonResponse('', 1, $message);
}

/**
 * 處理登出錯誤回傳信息格式
 *
 * @param     string    $message
 *
 * @return    void
 */
function handleLoginError($message = '對不起,您沒有執行此項操作的權限!')
{
    if (!empty($_REQUEST['is_ajax'])) {

        makeJsonError($message);
    }

    $loginUrl = 'privilege.php?action=login&ref=' . getRefUrl();
    if (isset($_SERVER['HTTP_REQUEST_TYPE'])) {

        die('<script>window.location.replace("' . $loginUrl . '");</script>');
    }

    $GLOBALS['dus']->header('Location: ' . $loginUrl);
}

/**
 * 分頁的信息加入條件的數組
 *
 * @return    array
 */
function pageAndSize($filter)
{
    if (isset($_REQUEST['page_size']) && (int)$_REQUEST['page_size'] > 0) {

        $filter['page_size'] = intval($_REQUEST['page_size']);

    } elseif (isset($_COOKIE['DUSCP']['page_size']) && (int)$_COOKIE['DUSCP']['page_size'] > 0) {

        $filter['page_size'] = (int)$_COOKIE['DUSCP']['page_size'];

    } elseif (isset($_SESSION[SHOP_SESS]['personal']['page_size'])) {

        $filter['page_size'] = (int)$_SESSION[SHOP_SESS]['personal']['page_size'];

    } else {

        $filter['page_size'] = 15;
    }

    if ($filter['page_size'] > 1000) {

        $filter['page_size'] = 1000;
    }

    /* 每頁顯示 */
    $filter['page'] = 1;
    if (!empty($_REQUEST['page']) && (int)$_REQUEST['page'] > 0) {

        $filter['page'] = (int)$_REQUEST['page'];
    }

    /* page 總數 */
    $filter['page_count'] = 1;
    if (!empty($filter['record_count']) && $filter['record_count'] > 0) {

        $filter['page_count'] = ceil($filter['record_count'] / $filter['page_size']);
    }

    /* 邊界處理 */
    if ($filter['page'] > $filter['page_count']) {

        $filter['page'] = $filter['page_count'];
    }

    $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];

    return $filter;
}

/**
 * 保存資料表關聯紀錄
 *
 * @param     integer    $dataId       主要欄位值
 * @param     string     $tbName       資料表名稱
 * @param     string     $keyField     主要欄位名稱
 * @param     boolean    $chckAdmin    是否檢查管理員 ID
 *
 * @return    void
 */
function handleRealationRecord($dataId, $tbName, $keyField, $chckAdmin = true)
{
    $oper = $keyField . ' = 0';
    if ($chckAdmin) {

        $oper .= ' AND admin_id = "' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . '"';
    }

    $sql = $GLOBALS['db']->buildSQL(
        'U',
        $GLOBALS['db']->table($tbName),
        array(
            $keyField => $dataId
        ),
        $oper
    );
    $GLOBALS['db']->query($sql);
}

/**
 * 刪除資料表無效紀錄
 *
 * @param     string     $tbName       資料表名稱
 * @param     string     $keyField     主要欄位名稱
 * @param     boolean    $chckAdmin    檢查管理員 ID
 * @param     mixed      $dropFile     一併刪除的檔案欄位
 *
 * @return    mixed                    刪除成功回傳已刪除的 PRIMARY KEY
 *                                     刪除失敗回傳狀態 FALSE
 */
function clearInvalidRecord($tbName, $keyField, $chckAdmin = true, $dropFile = null)
{
    $oper = $keyField . ' = 0';
    if ($chckAdmin) {

        $oper .= ' AND admin_id = "' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . '"';
    }

    return removeRealation($tbName, $oper, $dropFile);
}

/**
 * 刪除資料表關聯紀錄
 *
 * @param     string    $tbName      資料表名稱
 * @param     string    $oper        刪除條件
 * @param     mixed     $dropFile    一併刪除的檔案欄位名稱或陣列
 *
 * @return    mixed                  刪除成功回傳已刪除的 AUTO PRIMARY KEY
 *                                   刪除失敗回傳狀態 FALSE
 */
function removeRealation($tbName, $oper, $dropFile = null)
{
    $tbName = $GLOBALS['db']->table($tbName);

    // 取得資料表主鍵欄位名稱
    $fieldDescs = $GLOBALS['db']->getAll('DESC ' . $tbName);
    $dropPriKey = null;
    foreach ($fieldDescs as $value) {

        if ($value['Key'] == 'PRI' && $value['Extra'] == 'auto_increment') {

            $dropPriKey = $value['Field'];
            break;
        }
    }

    if (!is_null($dropFile)) {

        $field = is_array($dropFile)
               ? sprintf('tb.%s', implode(', tb.', $dropFile))
               : sprintf('tb.%s', $dropFile);

        $sql = 'SELECT ' . $field . ' '
             . 'FROM ' . $tbName . ' AS tb '
             . 'WHERE ' . $oper;
        $res = $GLOBALS['db']->query($sql, 'SILENT');
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            if (!empty($row)) {

                dropFile($row);
            }
        }
    }

    $sql = $GLOBALS['db']->buildSQL('D', $tbName, array(), $oper);
    if ($GLOBALS['db']->query($sql, 'SILENT')) {

        return $dropPriKey;
    }

    return false;
}