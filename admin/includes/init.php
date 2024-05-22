<?php defined('IN_DUS') || die('No direct script access allowed');

/**
 *
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/* 設定錯誤回報等級 */
error_reporting(E_ALL);

if (__FILE__ == '') {

    die('Fatal error code: 0');
}

define('DUS_ADMIN', true);

/* 載入反查數據 */
define('PHP_SAFE_LOOKUP', '');

/* 定義暫存檔存放目錄名稱 */
define('TEMP_DIR', 'temp');

/* 定義資料檔存放目錄名稱 */
define('DATA_DIR', 'data');

/* 定義圖片檔存放目錄名稱 */
define('IMAGE_DIR', 'images');

/* 定義樣版目錄名稱 */
define('TEMPLATE_DIR', 'templates');

/* 定義管理後台目錄 */
define('ADMIN_PATH', str_ireplace('includes/init.php', '', str_replace('\\', '/', __FILE__)));

/* 定義當前所在的根目錄 */
define('ROOT_PATH', preg_replace('/(.*)\/.*\/$/i', '\1/', ADMIN_PATH));

/* 定義引用檔路徑 */
define('BASE_PATH', ROOT_PATH . 'includes' . '/');

/* 定義資料檔路徑 */
define('DATA_PATH', ROOT_PATH . DATA_DIR . '/');

/* 定義暫存檔路徑 */
define('TEMP_PATH', ROOT_PATH . TEMP_DIR . '/');

/* 定義圖片檔路徑 */
define('IMAGE_PATH', DATA_PATH . IMAGE_DIR . '/');

/* 定義後台目錄名稱 */
define('ADMIN_DIR', rtrim(str_replace(ROOT_PATH, '', ADMIN_PATH), '/'));

/* 定義專案專用 session 值 */
define('SHOP_SESS', 'SESS_' . sprintf('%X', crc32(ROOT_PATH)) . '_ADMIN');

/* 除錯模式 0 = 關閉, 1 = 開啟，為安全起見應只有在開發AP時開啟 */
defined('DEBUG_MODE') || define('DEBUG_MODE', 0);

/* 是否 DEMO 模式 */
define('DEMO_MODE', preg_match('/WEIMINET.COM|LOCALHOST|192.168.\d{1,3}.\d{1,3}/i', $_SERVER['SERVER_NAME']));

/* 伺服器使用路徑設置 */
if (DIRECTORY_SEPARATOR == '\\') {

    /* Windows 伺服器 */
    @ini_set('include_path', '.;' . ROOT_PATH);

} else {

    /* UNIX 伺服器 */
    @ini_set('include_path', '.:' . ROOT_PATH);
}

/* 載入網站基本設定檔 */
include(DATA_PATH . 'config.php');

/* 初始設定 */

/* 紀錄錯誤資訊 */
@ini_set('log_errors', 1);
/* 設定腳本的記憶體使用最大上限 */
@ini_set('memory_limit', '512M');
/* 是否顯示錯誤訊息於螢幕 */
@ini_set('display_errors', DEMO_MODE ? 1 : 0);
/* session 檔案存放路徑 */
@ini_set('session.save_path', DEMO_MODE ? rtrim(TEMP_PATH, '/') : session_save_path());
/* 錯誤紀錄存放位置 */
@ini_set('error_log', TEMP_PATH . 'log/php-errors-admin-' . date('Ymd') . '.log');
/* 正規式回溯次數 */
@ini_set('pcre.backtrack_limit', -1);
/* 正規式遞迴次數 */
@ini_set('pcre.recursion_limit', -1);

/* 網址 (url) 重新導向的標籤(tag) */
@ini_set('url_rewriter.tags', '');
/* 使用 sid 值 (session id) 傳送模式 */
@ini_set('session.use_trans_sid', 0);
/* 處理連續資料方式的模式，本功能只有 WDDX 模組或 PHP 內部使用 */
@ini_set('session.serialize_handler', 'php');
/* session 使用cookie 的功能 */
@ini_set('session.use_cookies', 1);
/* session 的名稱 */
@ini_set('session.name', 'PHPSESSID');
/* session 使用 cookie 的存活週期；以「秒」為單位 */
@ini_set('session.cookie_lifetime', 0);
/* 「垃圾收集」(garbage collection)被處理前的存活週期 */
@ini_set('session.gc_maxlifetime', 1400);
/* session 使用 cookie 的路徑 */
@ini_set('session.cookie_path', '/');
/* session 自動啟動 */
@ini_set('session.auto_start', 0);

/* 設定當地時區 */
if (PHP_VERSION >= '5.1' && !empty($timezone)) {

    date_default_timezone_set($timezone);
}

if (isset($_SERVER['PHP_SELF'])) {

    define('PHP_SELF', $_SERVER['PHP_SELF']);

} else {

    define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
}

require(ROOT_PATH . 'vendor/autoload.php');

/* 載入物件 */
require(BASE_PATH . 'inc_constant.php'); // 常數
require(BASE_PATH . 'lib_base.php'); // 基本函式庫
require(BASE_PATH . 'lib_standard.php'); // PHP 標準函式庫
require(BASE_PATH . 'lib_array.php'); // 陣列處理函式庫
require(BASE_PATH . 'lib_smarty.php'); // Smarty 外掛函式庫
require(BASE_PATH . 'lib_common.php'); // 公用函式庫

if ((DEBUG_MODE & 4) == 4) {

    include(BASE_PATH . 'debug/debuglib_php5.php'); // 除錯函式庫
}

// require(ADMIN_PATH . 'includes/lib_text.php'); // 文字處理函式庫
require(ADMIN_PATH . 'includes/lib_main.php'); // 後台公用函式庫

require(BASE_PATH . 'cls_grshop.php'); // DUS 基礎類別
require(BASE_PATH . 'cls_error.php'); // 錯誤處理類別
require(BASE_PATH . 'cls_exchange.php'); // 資料庫自動操作類別

/**
 * 對魔術方法 (Magic Methods) 傳入的變數進行轉義操作
 * 所有的 ' (單引號), " (雙引號), \ (反斜線) 和空白字元會自動轉為含有反斜線的溢出字元。
 */
if (version_compare(PHP_VERSION, '7.4', 'lt')) {

    if (get_magic_quotes_runtime()) {

        // 關閉 magic quotes
        if (!isPhp('5.3')) {

            set_magic_quotes_runtime(false);

        } else {

            ini_set('magic_quotes_runtime', 0);
        }
    }
}

if (version_compare(PHP_VERSION, '7.4', 'gt') || !get_magic_quotes_gpc()) {

    if (!empty($_GET)) {

        $_GET  = addslashesDeep($_GET);
    }
    if (!empty($_POST)) {

        $_POST = addslashesDeep($_POST);
    }
    $_COOKIE   = addslashesDeep($_COOKIE);
    $_REQUEST  = addslashesDeep($_REQUEST);
}

$dus = new DUS($encryptKey); // 建立基礎物件

$adminUrl = $dus->url(ADMIN_DIR);
define('BASE_URL', isset($baseUrl) && $baseUrl != '' ? $baseUrl : $adminUrl);
define('SELF_ADMIN', parse_url(BASE_URL, PHP_URL_HOST) != parse_url($adminUrl, PHP_URL_HOST));

/* 對路徑進行安全處理 */
if (strpos(PHP_SELF, '.php/') !== false) {

    $dus->header('Location:' . substr(PHP_SELF, 0, strpos(PHP_SELF, '.php/') + 4));
    exit;
}

/* 建立資料庫處理物件 */
require(BASE_PATH . 'cls_mysqli.php');
$db = new cls_mysql($dbHost, $dbUser, $dbPass, array(
    'db_name' => $dbName,
    'tb_prefix' => $tbPrefix,
    'charset' => $dbCharset
));

$dbHost = $dbUser = $dbPass = $dbName = $dbCharset = $tbPrefix = $encryptKey = $adminUrl = null; // 清除變數

/* 建立錯誤處理物件 */
$err = new grs_error('templates/message.html');

/* 初始化 session */
session_start();
define('SESS_ID', session_id());

/* 初始化 action */
if (!isset($_REQUEST['action'])) {

    $_REQUEST['action'] = '';

} elseif (($_REQUEST['action'] == 'login' || $_REQUEST['action'] == 'logout'
    || $_REQUEST['action'] == 'signin' || $_REQUEST['action'] == 'authorize')
    && strpos(PHP_SELF, '/privilege.php') === false) {

    $_REQUEST['action'] = '';

} elseif (($_REQUEST['action'] == 'reset_pwd' || $_REQUEST['action'] == 'get_pwd')
    && strpos(PHP_SELF, '/get_password.php') === false) {

    $_REQUEST['action'] = '';

} elseif (($_REQUEST['action'] == 'regions' || $_REQUEST['action'] == 'upload'
    || $_REQUEST['action'] == 'save_picture' || $_REQUEST['action'] == 'shipping')
    && strpos(PHP_SELF, '/common.php') === false) {

    $_REQUEST['action'] = '';
}

$languages = getLanguage(); // 取得語言

define('MULTI_LANG_MODE', count($languages) > 1);

/* 取得設定值 */
$_CFG['default_lang_id'] = getDefaultLangId();
$_CFG['lang_codes'] = array_column($languages, 'code', 'lang_id');
$_CFG['lang_code'] = $languages[$_CFG['default_lang_id']]['code'];
$_CFG['lang_id'] = $languages[$_CFG['default_lang_id']]['lang_id'];
$_CFG = array_merge($_CFG, loadConfig($_CFG['default_lang_id']));

if ((DEBUG_MODE & 2) == 2 || $_CFG['cache_time'] < 1) {

    $db->setMaxCacheTime(0);

} else {

    $db->setMaxCacheTime($_CFG['cache_time']);
}

// 登錄部分準備拿出去做，到時候把以下操作一起挪過去
if ($_REQUEST['action'] == 'captcha') {

    include(BASE_PATH . 'cls_captcha.php');
    $img = new captcha(DATA_PATH . 'captcha/', 80);
    @ob_end_clean(); // 清除之前出現的多餘輸入
    $img->generateImage();
    exit;
}

/* 如果不存在 caches 目錄，則創建它 */
if (!file_exists(TEMP_PATH . 'caches')) {

    @mkdir(TEMP_PATH . 'caches', 0777);
    @chmod(TEMP_PATH . 'caches', 0777);
}

if (!file_exists(TEMP_PATH . 'compiled/admin')) {

    @mkdir(TEMP_PATH . 'compiled/admin', 0777);
    @chmod(TEMP_PATH . 'compiled/admin', 0777);
}

clearstatcache();

/* 創建 Smarty 對象。*/
$smarty = new \Smarty;

$smarty->setTemplateDir(ADMIN_PATH . '/' . TEMPLATE_DIR);
$smarty->setCompileDir(TEMP_PATH . 'compiled/admin');

$smarty->left_delimiter  = '{^';
$smarty->right_delimiter = '^}';

require(BASE_PATH . 'compress/outCssCompress.php');
require(BASE_PATH . 'compress/outJsCompress.php');
require(BASE_PATH . 'compress/outHtmlCompress.php');
require(BASE_PATH . 'compress/outW3cProcess.php');
$smarty->autoload_filters = array(
    'pre' => array('compile'), // 載入預編譯函數
    'output' => array('compress') // 輸出預編譯函數
);

if ((DEBUG_MODE & 1) == 1) {

    $smarty->error_reporting = E_ALL;

} else {

    $smarty->error_reporting = E_ALL ^ E_NOTICE;
}

if ((DEBUG_MODE & 2) == 2) {

    $smarty->force_compile = true;
}

$excludeActions = array(
    'get_pwd', 'reset_pwd',
    'login', 'signin', 'authorize',
    'regions', 'upload', 'save_picture', 'shipping',
    'client_print'
);

/* 驗證管理員身份 */
if ((!isset($_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')])
    || intval($_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]) <= 0) &&
    !in_array($_REQUEST['action'], $excludeActions)) {

    /* session 不存在，檢查cookie */
    if (!empty($_COOKIE['DUSCP']['admin_id']) && !empty($_COOKIE['DUSCP']['admin_pass'])) {

        // 找到了 cookie, 驗證 cookie 信息
        $sql = 'SELECT au.admin_id, au.admin_name, au.avatar_url, au.email, ' .
               'au.password, au.salt, au.last_login, au.enabled, au.action_list, au.personal_cfg ' .
               'FROM ' . $db->table('admin_user') . ' AS au ' .
               'WHERE au.admin_id = ' . (int)$_COOKIE['DUSCP']['admin_id'];
        if ($row = $db->getRow($sql)) {

            // 檢查密碼是否正確
            if (md5($row['password']) == $_COOKIE['DUSCP']['admin_pass']) {

                if (empty($row['enabled'])) {

                    setcookie('DUSCP[admin_id]', '', 1);
                    setcookie('DUSCP[admin_pass]', '', 1);

                    handleLoginError('很抱歉，該帳號遭停用!');
                }

                !isset($row['last_time']) && $row['last_time'] = '';

                $sql = 'SELECT ar.action_list ' .
                       'FROM ' . $db->table('admin_relation_role') . ' AS rr ' .
                       'INNER JOIN ' . $db->table('admin_role') . ' AS ar ' .
                       'ON rr.role_id = ar.role_id ' .
                       'WHERE rr.admin_id = ' . $row['admin_id'];
                $roleAction = $db->getCol($sql);

                $operatePriv = array($row['action_list'], implode(',', $roleAction));
                $operatePriv = implode(',', $operatePriv);
                $operatePriv = explode(',', $operatePriv);
                $operatePriv = array_diff(array_unique($operatePriv), array(''));
                $operatePriv = implode(',', $operatePriv);

                setAdminSession(
                    $row['admin_id'],
                    $row['admin_name'],
                    $row['avatar_url'],
                    $operatePriv,
                    $row['last_time']
                );

                // 更新最後登錄時間和IP
                $db->query(
                    $db->buildSQL(
                        'U',
                        $db->table('admin_user'),
                        array(
                            'last_login' => $_SERVER['REQUEST_TIME'],
                            'last_ip' => realIP()
                        ),
                        'admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
                    )
                );

                /* 記錄日誌 */
                adminLog('', '管理者', '登錄');

                autoExecProcess(); // 啟動自動執行處理程序

            } else {

                setcookie('DUSCP[admin_id]', '', 1);
                setcookie('DUSCP[admin_pass]', '', 1);

                handleLoginError();
            }

        } else {

            // 沒有找到這個記錄
            setcookie('DUSCP[admin_id]', '', 1);
            setcookie('DUSCP[admin_pass]', '', 1);

            handleLoginError();
        }

    } else {

        handleLoginError();
    }
}

if (!in_array($_REQUEST['action'], $excludeActions)) {

    $adminPath = preg_replace('/:\d+/', '', $dus->url(ADMIN_DIR));
    if (!empty($_SERVER['HTTP_REFERER']) &&
        strpos(preg_replace('/:\d+/', '', urldecode($_SERVER['HTTP_REFERER'])), $adminPath) === false) {

        handleLoginError();
    }

    // 處理最要 session 值是否有更換過
    $sessionHandel = array();
    $handelHave = array('admin_id', 'admin_name', 'action_list', 'source_ip');
    foreach ($handelHave as $val) {

        if (isset($_SESSION[SHOP_SESS][sha1('☠ ' . $val . ' ☠')])) {

            $sessionHandel[] = $_SESSION[SHOP_SESS][sha1('☠ ' . $val . ' ☠')];
        }
    }

    foreach ($handelHave as $val) {

        if (isset($_SESSION[SHOP_SESS][sha1('☠ ' . $val . ' ☠')])) {

            if (!isset($_SESSION[SHOP_SESS][sha1('HANDLE')])

                || $_SESSION[SHOP_SESS][sha1('HANDLE')] !== md5(implode(' (^ω^) ', $sessionHandel))) {

                error_log('PHP Warning: Could not synchronize database state with session', 0);
                $_SESSION[SHOP_SESS] = array();
                unset($_SESSION[SHOP_SESS]);

                handleLoginError();
            }
            break;
        }
    }

    // 防止瀏覽器憑證過更換 IP
    // if (!empty($_SESSION[SHOP_SESS][sha1('☠ source_ip ☠')])
    //     && $_SESSION[SHOP_SESS][sha1('☠ source_ip ☠')] != realIP()) {

    //     error_log('PHP Warning: User source ip abnormal', 0);
    //     $_SESSION[SHOP_SESS] = array();
    //     unset($_SESSION[SHOP_SESS]);

    //     handleLoginError();
    // }
}

/* 管理員登錄後可在任何頁面使用 action=phpinfo 顯示 phpinfo() 信息 */
if ($_REQUEST['action'] == 'phpinfo' && function_exists('phpinfo')) {

    phpinfo();
    exit;
}

header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('X-UA-Compatible: IE=edge');
header('Content-Type: text/html; charset=utf-8');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache');
header('Cache-Control: must-revalidate', false);
header('Cache-Control: post-check=0,pre-check=0', false);
header('Expires: 0');
header('Pragma: no-cache');

if ((DEBUG_MODE & 1) == 1) {

    error_reporting(E_ALL);

} else {

    error_reporting(E_ALL ^ E_NOTICE);
}

/* 判斷是否支持 Gzip 模式 */
if (gzipEnabled()) {

    ob_start('ob_gzhandler');

} else {

    ob_start();
}

if (defined('SYS_SAFE_CODE') && defined('SCRIPT_NAME')) {

    if (!DEMO_MODE && !isset($_SESSION[SHOP_SESS][sha1('SYS')])) {

        if (isset($_POST['safe_code']) && isset($_POST['password'])) {

            if (SCRIPT_NAME == $dus->compilePassword($_POST['safe_code'], 'DECODE', $_POST['password'])) {

                $_SESSION[SHOP_SESS][sha1('SYS')] = 1;

            } else {

                sysMsg('您輸入的瀏覽密碼不正確。', 1);
            }

        } else {

            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('safe_code', $dus->compilePassword(SCRIPT_NAME, 'ENCODE', '85e984', 180));
            $smarty->assign('action', $_REQUEST['action']);

            assignTemplate();
            assignQueryInfo();
            $smarty->display('safe_login.html');
            exit;
        }
    }
}
