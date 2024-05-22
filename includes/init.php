<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 前台公用文件
 * ===========================================================
 *
 * ===========================================================
 * $Author: kun_wei $
 */

/* 設定錯誤回報等級 */
error_reporting(E_ALL);

if (__FILE__ == '') {

    die('Fatal error code: 0');
}

!defined('INIT_NO_URLREWRITE') && define('INIT_NO_URLREWRITE', true);

/* 載入反查數據 */
define('PHP_SAFE_LOOKUP', '');

/* 定義暫存檔存放目錄名稱 */
define('TEMP_DIR', 'temp');

/* 定義資料檔存放目錄名稱 */
define('DATA_DIR', 'data');

/* 定義圖片檔存放目錄名稱 */
define('IMAGE_DIR', 'images');

/* 定義樣版資料夾名稱 */
define('TEMPLATE_DIR', 'themes');

/* 定義當前所在的根目錄 */
define('ROOT_PATH', str_ireplace('includes/init.php', '', str_replace('\\', '/', __FILE__)));

/* 定義引用檔路徑 */
define('BASE_PATH', ROOT_PATH . 'includes' . '/');

/* 定義資料檔路徑 */
define('DATA_PATH', ROOT_PATH . DATA_DIR . '/');

/* 定義暫存檔路徑 */
define('TEMP_PATH', ROOT_PATH . TEMP_DIR . '/');

/* 定義圖片檔路徑 */
define('IMAGE_PATH', DATA_PATH . IMAGE_DIR . '/');

/* 定義專案專用 session 值 */
define('SHOP_SESS', 'SESS_' . sprintf('%X', crc32(ROOT_PATH)) . '_USER');

/* 除錯模式 0 = 關閉, 1 = 開啟，為安全起見應只有在開發AP時開啟 */
defined('DEBUG_MODE') || define('DEBUG_MODE', 7);

/* 是否 DEMO 模式 */
define('DEMO_MODE', preg_match('/WEIMINET.COM|LOCALHOST|192.168.\d{1,3}.\d{1,3}/i', $_SERVER['SERVER_NAME']));

/* 無快取模式 */
define('NOCACHE_MODE', DEMO_MODE && 'nocache' == current(explode('.', $_SERVER['HTTP_HOST'])));

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

/* 初始化設置 */

/* 紀錄錯誤資訊 */
@ini_set('log_errors', 1);
/* 設定腳本的記憶體使用最大上限 */
@ini_set('memory_limit', '256M');
/* 是否顯示錯誤訊息於螢幕 */
@ini_set('display_errors', DEMO_MODE ? 1 : 0);
/* session 檔案存放路徑 */
@ini_set('session.save_path', DEMO_MODE ? rtrim(TEMP_PATH, '/') : session_save_path());
/* 錯誤紀錄存放位置 */
@ini_set('error_log', TEMP_PATH . 'log/php-errors-web-' . date('Ymd') . '.log');
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
@ini_set('session.cookie_lifetime', $cookieLifeTime);
/* session 跨子網域使用 */
@ini_set('session.cookie_domain', $cookieDomain);
/* 「垃圾收集」(garbage collection)被處理前的存活週期 */
@ini_set('session.gc_maxlifetime', $gcMaxLifeTime);
/* session 使用 cookie 的路徑 */
@ini_set('session.cookie_path', $cookiePath);
/* session 自動啟動 */
@ini_set('session.auto_start', 0);

/* 設定時區 */
if (version_compare(PHP_VERSION, '5.1', '>=') && !empty($timezone)) {

    date_default_timezone_set($timezone);
}

$phpSelf = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($phpSelf, -1)) {

    $phpSelf .= 'index.php';
}
define('PHP_SELF', $phpSelf);

require(ROOT_PATH . 'vendor/autoload.php');

/* 載入起始設定檔 */
require(BASE_PATH . 'inc_constant.php'); // 常數
require(BASE_PATH . 'lib_base.php'); // 基本函式庫
require(BASE_PATH . 'lib_standard.php'); // PHP 標準函式庫
require(BASE_PATH . 'lib_array.php'); // 陣列處理函式庫
require(BASE_PATH . 'lib_smarty.php'); // Smarty 外掛函式庫
require(BASE_PATH . 'lib_common.php'); // 公用函式庫
require(BASE_PATH . 'lib_main.php'); // 前台公用函式庫
require(BASE_PATH . 'lib_category.php'); // 分類函式庫

if ((DEBUG_MODE & 4) == 4) {

    include(BASE_PATH . 'debug/debuglib_php5.php');
}

require(BASE_PATH . 'cls_grshop.php'); // DUS 基礎類別
require(BASE_PATH . 'cls_error.php'); // 錯誤處理類別

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

/* 建立資料庫處理物件 */
require(BASE_PATH . 'cls_mysqli.php');
$db = new cls_mysql($dbHost, $dbUser, $dbPass, array(
    'db_name' => $dbName,
    'tb_prefix' => $tbPrefix,
    'charset' => $dbCharset
));

$dbHost = $dbUser = $dbPass = $dbName = $dbCharset = $tbPrefix = $encryptKey = null; // 清除變數

/* 取得設定值 */
$_CFG = loadConfig();

if ((DEBUG_MODE & 2) == 2 || NOCACHE_MODE || $_CFG['cache_time'] < 1) {

    $db->setMaxCacheTime(0);

} else {

    $db->setMaxCacheTime($_CFG['cache_time']);
}

/* 創建錯誤處理對像 */
$err = new grs_error('message.dwt');

$languages = getLanguage(); // 取得語言

/* 載入系統參數 */
$_CFG['default_lang_id'] = getDefaultLangId();
$_CFG['lang_codes'] = array_column($languages, 'code');

$defaultLang = isset($languages[$_CFG['default_lang_id']]) ? $languages[$_CFG['default_lang_id']] : reset($languages);
$_CFG['lang_code'] = $defaultLang['code'];
$_CFG['lang_id'] = $defaultLang['lang_id'];
$_CFG['template'] = $defaultLang['directory'];

if (!defined('INIT_NO_MEMBER')) {

    @session_start();
    define('SESS_ID', session_id());
}

if (isSpider()) {

    if (!defined('INIT_NO_MEMBER')) {

        define('INIT_NO_MEMBER', true);
    }

    $_SESSION = array();
    $_SESSION[SHOP_SESS]['USER']['member_id'] = 0;
    $_SESSION[SHOP_SESS]['USER']['member_rank'] = 0;
    $_SESSION[SHOP_SESS]['USER']['is_member'] = 0;
}

if (!defined('INIT_NO_MEMBER')) {

    if (!isset($_SESSION[SHOP_SESS]['USER']['member_id'])) {

        if (!defined('INGORE_VISIT_STATS')) {

            visitStats();
        }
    }
}

if (!defined('INIT_NO_URLREWRITE')) {

    $hostArr = array();

    $domain = basename($dus->getDomain());

    if (isset($hostArr[$domain])) {

        $_CFG['lang_code'] = $hostArr[$domain];

        !defined('INIT_LANG_USE_SUBDOMAIN') && define('INIT_LANG_USE_SUBDOMAIN', true);
    }

    if (!defined('INIT_LANG_USE_SUBDOMAIN')) {

        $root = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        substr($root, -1) != '/' && $root .= '/';
        preg_match(
            sprintf(
                '/^%s(?P<lang_dir>%s)?(?=(?!\/?[^\/]*\.)|(\/[^\/]*\.*))/i',
                preg_quote($root, '/'),
                implode('|', array_column($languages, 'directory'))
            ),
            getRequestUrl(),
            $match
        );

        $langCode = '';
        if (isset($match['lang_dir'])) {

            $langCode = array_search($match['lang_dir'], array_column($languages, 'directory', 'code'));
        }

        if ($langCode == '') {

            if (!defined('INIT_SERVER_ERROR') && !isAjax()) {

                // 如果語系路徑不存在, 自動跳轉
                $redirectUrl = $dus->url() . $defaultLang['code'];

                if (isset($_COOKIE['DUS']['language'])) {

                    $langInfo = &$languages[$_COOKIE['DUS']['language']];
                    if (!empty($langInfo)) {

                        $redirectUrl = $dus->url() . $langInfo['directory'];
                    }

                } else {

                    // 依照使用者的瀏覽器語言作為預設語系
                    if ($_CFG['lang_auto_detect'] && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

                        $langInfo = getLanInfoByCode($_SERVER['HTTP_ACCEPT_LANGUAGE'], $languages);
                        if (!empty($langInfo)) {

                            $redirectUrl = $dus->url() . $langInfo['directory'];
                        }
                    }
                }

                $requestUri = str_replace($dus->url(), '', getLocationUrl());

                $requestUri != '' && $redirectUrl .= '/' . $requestUri;

                $dus->header('Location: ' . $redirectUrl);
                exit;
            }

        } else {

            $_CFG['lang_code'] = $langCode;
        }
    }

    $langInfo = getLanInfoByCode($_CFG['lang_code'], $languages);
    if (!empty($langInfo)) {

        $_CFG['lang_id'] = $langInfo['lang_id'];

        /* 載入系統參數 */
        $_CFG = array_merge($_CFG, loadConfig($_CFG['lang_id']));
        $_CFG['template'] = $langInfo['directory'];
    }
}

if ($_CFG['lang_id'] < 1 && isAjax() == false) {

    $_CFG['shop_closed'] = true;
}

/* 定義樣版資料夾路徑 */
define('TEMPLATE_PATH', ROOT_PATH . TEMPLATE_DIR . '/' . $_CFG['template'] . '/');

/* 載入語系包 */
$langPackge = array('common', basename($_SERVER['SCRIPT_FILENAME'], '.php'));
if (defined('LANG_PACKGE_EXTRA')) {

    $langPackge = array_merge(explode('|', LANG_PACKGE_EXTRA), $langPackge);
}
$_LANG = getLanguagePackge($langPackge);
unset($langPackge);

if (!defined('INIT_NO_SMARTY')) {

    header('Cache-control: private');
    header('content-type: text/html; charset=utf-8');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('X-UA-Compatible: IE=edge');

    /* 定義編譯資料夾路徑 */
    define('COMPILED_PATH', TEMP_PATH . 'compiled/');
    /* 定義快取資料夾路徑 */
    define('CACHE_PATH', TEMP_PATH . 'caches/');

    if (!file_exists(COMPILED_PATH)) {

        @mkdir(COMPILED_PATH, 0777);
        @chmod(COMPILED_PATH, 0777);
    }
    if (!file_exists(CACHE_PATH)) {

        /* 如果不存在caches目錄，則創建它 */
        @mkdir(CACHE_PATH, 0777);
        @chmod(CACHE_PATH, 0777);
    }
    clearstatcache();

    /* 創建 Smarty 對象。*/
    $smarty = new \Smarty;

    /* 定義 Smarty 物件中四個路徑的屬性。 */
    $smarty->setTemplateDir(TEMPLATE_PATH); // 樣版路徑
    $smarty->setCacheDir(CACHE_PATH); // 存放快取路徑
    $smarty->setCompileDir(COMPILED_PATH); // 存放編譯檔路徑

    $smarty->left_delimiter = '{^';
    $smarty->right_delimiter = '^}';
    $smarty->cache_lifetime = $_CFG['cache_time']; // 快取生命週期

    require(BASE_PATH . 'compress/outUrlRewrite.php');
    require(BASE_PATH . 'compress/outW3cProcess.php');
    require(BASE_PATH . 'compress/outCssCompress.php');
    require(BASE_PATH . 'compress/outJsCompress.php');
    require(BASE_PATH . 'compress/outHtmlCompress.php');

    $smarty->autoload_filters = array(
        'pre' => array('compile'), // 載入預編譯函數
        'output' => array('compress') // 輸出預編譯函數
    );

    $smarty->debugging = false; // 是否開啟 Smarty Debug 控制台

    if ((DEBUG_MODE & 1) == 1) {

        $smarty->error_reporting = E_ALL;

    } else {

        $smarty->error_reporting = E_ALL ^ E_NOTICE;
    }

    if ((DEBUG_MODE & 2) == 2 || NOCACHE_MODE) {

        $smarty->compile_check = true;
        $smarty->force_compile = true;

    } else {

        $smarty->compile_check = false;
        $smarty->force_compile = false;
    }

    $smarty->assign('lang', $_LANG);
}


// 網站已關閉，輸出關閉訊息
if (!empty($_CFG['shop_closed'])) {

    if (!defined('INIT_NO_MEMBER')) {

        define('INIT_NO_MEMBER', true);
    }

    showMessage(
        $_CFG['close_comment'],
        array(),
        array(),
        'info',
        false
    );
}

if (!defined('INIT_NO_MEMBER')) {

    if (empty($_SESSION[SHOP_SESS]['USER']['member_id'])) {

        $_SESSION[SHOP_SESS]['USER']['member_id'] = 0;
        $_SESSION[SHOP_SESS]['USER']['member_rank'] = 0;
        $_SESSION[SHOP_SESS]['USER']['is_member'] = 0;

        if (!isset($_SESSION[SHOP_SESS]['USER']['login_fail'])) {

            $_SESSION[SHOP_SESS]['USER']['login_fail'] = 0;
        }

        /* session 不存在，檢查cookie */
        if (!empty($_COOKIE['DUS'][sha1('☠ passport ☠')])) {

            $remember = json_decode($dus->strDecode($_COOKIE['DUS'][sha1('☠ passport ☠')]), true);
        }

    } else {

        $_SESSION[SHOP_SESS]['USER']['is_member'] = 1;
    }
}

if ((DEBUG_MODE & 1) == 1) {

    error_reporting(E_ALL);

} else {

    error_reporting(E_ALL ^ E_NOTICE);
}

/* 判斷是否支持 Gzip 模式 */
if (!defined('INIT_NO_SMARTY') && gzipEnabled()) {

    ob_start('ob_gzhandler');

} else {

    ob_start();
}
