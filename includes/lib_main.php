<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 前台公用函數庫
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/**
 * 取得分頁
 *
 * @param     string     $app            執行程序
 * @param     integer    $recordCount    記錄總數量
 * @param     integer    $size           單頁資料顯示數量
 * @param     array      $query          額外查詢參數 (index 為參數名稱, value 為參數值)
 * @param     integer    $maxLink        分頁連結顯示數量
 *
 * @return    array     $pager
 */
function getPager($app, $recordCount, $size = 10, array $query = array(), $maxLink = 5)
{
    $size = intval($size);
    if ($size < 1) {

        $size = 10;
    }

    $uriArgs = array(
        'page' => 1
    );

    switch ($app) {
        // 預設
        default:

            // nothing...
    }
    $uriArgs = array_merge($uriArgs, $query);

    $page = $uriArgs['page'] < 1 ? 1 : (int)$uriArgs['page'];

    $recordCount = intval($recordCount);

    $pageCount = $recordCount > 0 ? intval(ceil($recordCount / $size)) : 1;
    if ($page > $pageCount) {

        $page = $pageCount;
    }

    $pagePrev = ($page > 1) ? $page - 1 : 1;
    $pageNext = ($page < $pageCount) ? $page + 1 : $pageCount;

    $pager = array(
        'url' => $app,
        'start' => ($page - 1) * $size,
        'page' => $page,
        'size' => $size,
        'record_count' => $recordCount,
        'page_count' => $pageCount,
        'page_first' => '',
        'page_prev' => '',
        'page_next' => '',
        'page_last' => '',
        'navigation' => array(),
        'search' => $uriArgs
    );

    $_pagenum = (int)$maxLink; // 顯示的頁碼數量
    $_offset = 2; // 目前頁偏移值
    $_from = $_to = 0; // 開始頁, 結束頁
    if ($_pagenum > $pageCount) {

        $_from = 1;
        $_to = $pageCount;

    } else {

        $_from = $page - $_offset;
        $_to = $_from + $_pagenum - 1;
        if ($_from < 1) {

            $_to = $page + 1 - $_from;
            $_from = 1;
            if ($_to - $_from < $_pagenum) {

                $_to = $_pagenum;
            }

        } elseif ($_to > $pageCount) {

            $_from = $pageCount - $_pagenum + 1;
            $_to = $pageCount;
        }
    }

    $pager['page_first'] = ($page != 1) ? buildUri($app, array_merge($uriArgs, array('page' => 1))) : '';
    $pager['page_prev'] = ($page > 1) ? buildUri($app, array_merge($uriArgs, array('page' => $pagePrev))) : '';
    $pager['page_next'] = ($page < $pageCount) ? buildUri($app, array_merge($uriArgs, array('page' => $pageNext))) : '';
    $pager['page_last'] = ($page != $pageCount) ? buildUri($app, array_merge($uriArgs, array('page' => $pageCount))) : '';
    $pager['navigation'] = array();
    for ($i = $_from; $i <= $_to; ++$i) {

        $pager['navigation'][$i] = buildUri($app, array_merge($uriArgs, array('page' => $i)));
    }

    $pager['search'] = $uriArgs;

    return $pager;
}

/**
 * 建立一個 Javascript Alert 對話框
 *
 * @param     string    $msg          訊息內容
 * @param     string    $targetUrl    跳轉目標網址
 *
 * @return    string
 */
function createJsAlert($msg, $targetUrl = '')
{
    $js = '<script>alert("' . $msg . '");';
    if (!empty($targetUrl)) {

        $js .= 'window.location.replace("' . $targetUrl . '");';
    }
    $js .= '</script>';

    return $js;
}

/**
 * 驗證非同步的操作是否合法
 *
 * @param    string    $url    如果非法則跳轉的位置
 *
 * @return   mixed             如果不跳轉則回傳布林值
 */
function verifAsyncLegal($url = '')
{
    if (!isset($_SERVER['HTTP_REQUEST_TYPE'])) {

        $_SERVER['HTTP_REQUEST_TYPE'] = '';
    }

    $legalStatus = ($_SERVER['HTTP_REQUEST_TYPE'] == 'ajax' && !empty($_POST));

    if (!$legalStatus && !empty($url)) {

        $GLOBALS['dus']->header('Location: ' . $url);

    } else {

        return $legalStatus;
    }
}

/**
 * 顯示一個伺服器錯誤訊息
 *
 * @param     integer    $httpResponseCode    HTTP 狀態
 * @param     string     $msg                 顯示訊息
 *
 * @return    void
 */
function showServerError($httpResponseCode, $msg = '')
{
    static $statusCodes = null;

    if (null === $statusCodes) {

        $statusCodes = array (
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended'
        );
    }

    if ($statusCodes[$httpResponseCode] !== null) {

        $statusStr = $httpResponseCode . ' ' . $statusCodes[$httpResponseCode];
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $statusStr, true, $httpResponseCode);
    }

    if (true === verifAsyncLegal()) {

        exit($statusStr);

    } else {

        $displayPage = false; // 是否顯示頁面

        if (defined('INIT_NO_SMARTY')) {

            $displayPage = INIT_NO_SMARTY == false;

        } else {

            $displayPage = file_exists(TEMPLATE_PATH . 'error.dwt');
        }

        if ($displayPage) {

            $cacheStatus = $GLOBALS['smarty']->caching;

            $GLOBALS['smarty']->caching = false;

            // 樣板變數賦值
            $GLOBALS['smarty']->assign('status_msg', $statusStr);
            $GLOBALS['smarty']->assign('message', $msg);

            assignTemplate();

            $GLOBALS['smarty']->caching = $cacheStatus;

            $GLOBALS['smarty']->display('error.dwt');
            exit;

        } else {

            exit(createJsAlert($statusStr, $GLOBALS['dus']->url()));
        }
    }
}

/**
 * 顯示一個提示訊息
 *
 * @param     string     $content         提示訊息
 * @param     mixed      $links           連結名稱
 * @param     mixed      $hrefs           連結位置
 * @param     string     $type            訊息類型：warning, error, info
 * @param     boolean    $autoRedirect    是否自動跳轉
 *
 * @return    void
 */
function showMessage($content, $links = '', $hrefs = '', $type = 'info', $autoRedirect = true)
{
    $msg = array(
        'content' => $content,
        'type' => $type
    );

    if (is_array($links) && is_array($hrefs)) {

        if (!empty($links) && count($links) == count($hrefs)) {

            foreach ($links as $key => $val) {

                $msg['url_info'][$val] = $hrefs[$key];
            }
            $msg['back_url'] = $hrefs['0'];
        }

    } else {

        $siteUrl = getRealUrl();
        $refUrl = urldecode(getRefUrl(false));

        $link = empty($links)
              ? ($refUrl != $siteUrl ? $GLOBALS['_LANG']['back_up_page'] : $GLOBALS['_LANG']['back_home'])
              : $links;

        $href = empty($hrefs) ? $refUrl : $hrefs;

        // 如果為相對路徑則使用目前環境的 URL 組合
        if (false === strpos($href, '://')) {

            $href = $siteUrl . str_ireplace($siteUrl, '', $href);
        }

        // 如果返回網址與目前網址相同
        if (count(array_diff(parse_url(getLocationUrl()), parse_url($href))) < 1) {

            $msg['url_info'] = array();
            $msg['back_url'] = getBackUrl();

        } else {

            $msg['url_info'][$link] = $href;
            $msg['back_url'] = $href;
        }
    }

    $tplFilePage = false; // 是否顯示頁面
    if (defined('INIT_NO_SMARTY')) {

        $tplFilePage = INIT_NO_SMARTY == false;

    } else {

        if (defined('MESSAGE_FILE_PAGE') && is_file(TEMPLATE_PATH . MESSAGE_FILE_PAGE . '.dwt')) {

            $tplFilePage = MESSAGE_FILE_PAGE . '.dwt';

        } elseif (is_file(TEMPLATE_PATH . 'message.dwt')) {

            $tplFilePage = 'message.dwt';
        }
    }

    if ($tplFilePage) {

        $cacheStatus = $GLOBALS['smarty']->caching;

        $GLOBALS['smarty']->caching = false;

        // 樣板變數賦值
        $GLOBALS['smarty']->assign('auto_redirect', $autoRedirect);
        $GLOBALS['smarty']->assign('message', $msg);

        assignTemplate();

        $GLOBALS['smarty']->caching = $cacheStatus;

        $GLOBALS['smarty']->display($tplFilePage);
        exit;

    } else {

        exit(createJsAlert($content, !empty($msg['back_url']) ? $msg['back_url'] : ''));
    }
}

/**
 * 判斷是否為搜索引擎蜘蛛
 *
 * @return    string
 */
function isSpider($record = true)
{
    static $spider = null;

    if ($spider !== null) {

        return $spider;
    }

    if (empty($_SERVER['HTTP_USER_AGENT'])) {

        $spider = '';
        return '';
    }

    if (preg_match('/\s\(compatible;\s(.*)(?:\/.*);?\s\+?http\:\/\/(.*)\)$/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/\s\(compatible;\s(.*)(?:;)\s\+?http\:\/\/(.*)\)$/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(W3C_Validator)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(Yahoo\!\sSlurp\sChina)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(Sogou\sweb\sspider)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(Sosospider)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(Mediapartners\-Google)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(Googlebot)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(ia_archiver)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(facebookexternalhit)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } elseif (preg_match('/^(msnbot)/i', $_SERVER['HTTP_USER_AGENT'], $regs)) {

        $spider = $regs[1];

    } else {

        $spider = '';
    }

    if ($spider !== '') {

        if ($record === true) {

            $GLOBALS['db']->autoReplace(
                $GLOBALS['db']->table('searchengine'),
                array(
                    'date' => date('Y-m-d'),
                    'searchengine' => $spider,
                    'count' => 1
                ),
                array(
                    'count' => 1
                )
            );
        }
    }

    return $spider;
}

/**
 * 保存搜索引擎關鍵字
 *
 * @return    void
 */
function saveSearchengineKeyword($domain, $path)
{
    if (strpos($domain, 'google.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'GOOGLE TAIWAN';
        $keywords = urldecode($regs[1]); // google taiwan

    } elseif (strpos($domain, 'google.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'GOOGLE CHINA';
        $keywords = urldecode($regs[1]); // google china

    } elseif (strpos($domain, 'google.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'GOOGLE';
        $keywords = urldecode($regs[1]); // google

    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/wd=([^&]*)/i', $path, $regs)) {

        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu

    } elseif (strpos($domain, 'baidu.') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {

        $searchengine = 'BAIDU';
        $keywords = urldecode($regs[1]); // baidu

    } elseif (strpos($domain, '114.vnet.cn') !== false && preg_match('/kw=([^&]*)/i', $path, $regs)) {

        $searchengine = 'CT114';
        $keywords = urldecode($regs[1]); // ct114

    } elseif (strpos($domain, 'iask.com') !== false && preg_match('/k=([^&]*)/i', $path, $regs)) {

        $searchengine = 'IASK';
        $keywords = urldecode($regs[1]); // iask

    } elseif (strpos($domain, 'soso.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {

        $searchengine = 'SOSO';
        $keywords = urldecode($regs[1]); // soso

    } elseif (strpos($domain, 'sogou.com') !== false && preg_match('/query=([^&]*)/i', $path, $regs)) {

        $searchengine = 'SOGOU';
        $keywords = urldecode($regs[1]); // sogou

    } elseif (strpos($domain, 'so.163.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'NETEASE';
        $keywords = urldecode($regs[1]); // netease

    } elseif (strpos($domain, 'yodao.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'YODAO';
        $keywords = urldecode($regs[1]); // yodao

    } elseif (strpos($domain, 'zhongsou.com') !== false && preg_match('/word=([^&]*)/i', $path, $regs)) {

        $searchengine = 'ZHONGSOU';
        $keywords = urldecode($regs[1]); // zhongsou

    } elseif (strpos($domain, 'search.tom.com') !== false && preg_match('/w=([^&]*)/i', $path, $regs)) {

        $searchengine = 'TOM';
        $keywords = urldecode($regs[1]); // tom

    } elseif (strpos($domain, 'live.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'MSLIVE';
        $keywords = urldecode($regs[1]); // MSLIVE

    } elseif (strpos($domain, 'tw.search.yahoo.com') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {

        $searchengine = 'YAHOO TAIWAN';
        $keywords = urldecode($regs[1]); // yahoo taiwan

    } elseif (strpos($domain, 'cn.yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {

        $searchengine = 'YAHOO CHINA';
        $keywords = urldecode($regs[1]); // yahoo china

    } elseif (strpos($domain, 'yahoo.') !== false && preg_match('/p=([^&]*)/i', $path, $regs)) {

        $searchengine = 'YAHOO';
        $keywords = urldecode($regs[1]); // yahoo

    } elseif (strpos($domain, 'msn.com.tw') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'MSN TAIWAN';
        $keywords = urldecode($regs[1]); // msn taiwan

    } elseif (strpos($domain, 'msn.com.cn') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'MSN CHINA';
        $keywords = urldecode($regs[1]); // msn china

    } elseif (strpos($domain, 'msn.com') !== false && preg_match('/q=([^&]*)/i', $path, $regs)) {

        $searchengine = 'MSN';
        $keywords = urldecode($regs[1]); // msn
    }

    if (!empty($keywords)) {

        if (!function_exists('mb_convert_encoding')) {

            $search = array(
                'YAHOO CHINA', 'TOM', 'ZHONGSOU',
                'NETEASE', 'SOGOU', 'SOSO',
                'IASK', 'CT114', 'BAIDU'
            );
            if (in_array($searchengine, $search)) {

                $keywords = @iconv('GB2312', 'UTF-8', urldecode($keywords));
            }

        } else {

            $keywords = mb_convert_encoding(urldecode($keywords), 'UTF-8', 'ASCII,UTF-8,GB2312,GBK,CP932');
        }

        $GLOBALS['db']->autoReplace(
            $GLOBALS['db']->table('keywords'),
            array(
                'date' => date('Y-m-d'),
                'searchengine' => $searchengine,
                'keyword' => addslashes($keywords),
                'count' => 1
            ),
            array(
                'count' => 1
            )
        );
    }
}

/**
 * 統計訪問信息
 *
 * @return    void
 */
function visitStats()
{
    if (empty($GLOBALS['_CFG']['visit_stats'])) {

        return;
    }

    $time = strtotime(date('Y-m-d H:i', $_SERVER['REQUEST_TIME']));
    /* 檢查客戶端是否存在訪問統計的 cookie */
    $visitTimes = (!empty($_COOKIE['DUS']['visit_times'])) ? intval($_COOKIE['DUS']['visit_times']) + 1 : 1;
    setcookie('DUS[visit_times]', $visitTimes, $time + 86400 * 365, '/');

    include_once(BASE_PATH . 'cls_geoip.php');
    $geoip = new cls_geoip(BASE_PATH . 'codetable/GeoLiteCity.dat');

    $countryCode = array(
        'AP' => 'Asia/Pacific Region',
        'EU' => 'Europe',
        'AD' => '安道爾',
        'AE' => '阿拉伯聯合大公國',
        'AF' => '阿富汗',
        'AG' => '安地卡及巴布達',
        'AI' => '英屬安圭拉',
        'AL' => '阿爾巴尼亞',
        'AM' => '亞美尼亞',
        'CW' => '庫拉索島',
        'AO' => '安哥拉',
        'AQ' => '南極洲',
        'AR' => '阿根廷',
        'AS' => '美屬薩摩亞',
        'AT' => '奧地利',
        'AU' => '澳大利亞',
        'AW' => '阿魯巴',
        'AZ' => '亞塞拜然',
        'BA' => '波士尼亞',
        'BB' => '巴貝多',
        'BD' => '孟加拉',
        'BE' => '比利時',
        'BF' => '布吉納法索',
        'BG' => '保加利亞',
        'BH' => '巴林',
        'BI' => '浦隆地',
        'BJ' => '貝南',
        'BM' => '百慕達',
        'BN' => '汶萊',
        'BO' => '玻利維亞',
        'BR' => '巴西',
        'BS' => '巴哈馬',
        'BT' => '不丹',
        'BV' => '波維特島',
        'BW' => '波札那',
        'BY' => '白俄羅斯',
        'BZ' => '貝里斯',
        'CA' => '加拿大',
        'CC' => '可可斯群島',
        'CD' => '剛果民主共和國',
        'CF' => '中非',
        'CG' => '剛果',
        'CH' => '瑞士',
        'CI' => '象牙海岸',
        'CK' => '科克群島',
        'CL' => '智利',
        'CM' => '喀麥隆',
        'CN' => '中國大陸',
        'CO' => '哥倫比亞',
        'CR' => '哥斯大黎加',
        'CU' => '古巴',
        'CV' => '維德角島',
        'CX' => '聖誕島',
        'CY' => '賽普勒斯',
        'CZ' => '捷克',
        'DE' => '德國',
        'DJ' => '吉布地',
        'DK' => '丹麥',
        'DM' => '多米尼克',
        'DO' => '多明尼加',
        'DZ' => '阿爾及利亞',
        'EC' => '厄瓜多',
        'EE' => '愛沙尼亞',
        'EG' => '埃及',
        'EH' => '西撒哈拉',
        'ER' => '厄利垂亞',
        'ES' => '西班牙',
        'ET' => '伊索比亞',
        'FI' => '芬蘭',
        'FJ' => '斐濟群島',
        'FK' => '福克蘭群島',
        'FM' => '密克羅尼西亞',
        'FO' => '法羅群島',
        'FR' => '法國',
        'SX' => '聖馬丁島',
        'GA' => '加彭',
        'GB' => '英國',
        'GD' => '格瑞那達',
        'GE' => '喬治亞',
        'GF' => '法屬圭亞那',
        'GH' => '迦納',
        'GI' => '直布羅陀',
        'GL' => '格陵蘭',
        'GM' => '甘比亞',
        'GN' => '幾內亞',
        'GP' => '瓜德魯普島',
        'GQ' => '赤道幾內亞',
        'GR' => '希臘',
        'GS' => '南喬治亞及南桑威奇群島',
        'GT' => '瓜地馬拉',
        'GU' => '關島',
        'GW' => '幾內亞比索',
        'GY' => '蓋亞納',
        'HK' => '香港',
        'HM' => '赫德及麥當勞群島',
        'HN' => '宏都拉斯',
        'HR' => '克羅埃西亞',
        'HT' => '海地',
        'HU' => '匈牙利',
        'ID' => '印尼',
        'IE' => '愛爾蘭',
        'IL' => '以色列',
        'IN' => '印度',
        'IO' => '英屬印度洋地區',
        'IQ' => '伊拉克',
        'IR' => '伊朗',
        'IS' => '冰島',
        'IT' => '義大利',
        'JM' => '牙買加',
        'JO' => '約旦',
        'JP' => '日本',
        'KE' => '肯亞',
        'KG' => '吉爾吉斯',
        'KH' => '高棉',
        'KI' => '吉里巴斯',
        'KM' => '葛摩',
        'KN' => '聖克里斯多福',
        'KP' => '北韓',
        'KR' => '南韓',
        'KW' => '科威特',
        'KY' => '開曼群島',
        'KZ' => '哈薩克',
        'LA' => '寮國',
        'LB' => '黎巴嫩',
        'LC' => '聖露西亞',
        'LI' => '列支敦斯堡',
        'LK' => '斯里蘭卡',
        'LR' => '賴比瑞亞',
        'LS' => '賴索托',
        'LT' => '立陶宛',
        'LU' => '盧森堡',
        'LV' => '拉脫維亞',
        'LY' => '利比亞',
        'MA' => '摩洛哥',
        'MC' => '摩納哥',
        'MD' => '摩爾多瓦',
        'MG' => '馬達加斯加',
        'MH' => '馬紹爾群島',
        'MK' => '馬其頓',
        'ML' => '馬利',
        'MM' => '緬甸',
        'MN' => '蒙古',
        'MO' => '澳門',
        'MP' => '北馬里亞納群島',
        'MQ' => '法屬馬丁尼克',
        'MR' => '茅利塔尼亞',
        'MS' => '蒙瑟拉特島',
        'MT' => '馬爾他',
        'MU' => '模里西斯',
        'MV' => '馬爾地夫',
        'MW' => '馬拉威',
        'MX' => '墨西哥',
        'MY' => '馬來西亞',
        'MZ' => '莫三比克',
        'NA' => '納米比亞',
        'NC' => '新喀里多尼亞島',
        'NE' => '尼日',
        'NF' => '諾福克群島',
        'NG' => '奈及利亞',
        'NI' => '尼加拉瓜',
        'NL' => '荷蘭',
        'NO' => '挪威',
        'NP' => '尼泊爾',
        'NR' => '諾魯',
        'NU' => '紐威島',
        'NZ' => '紐西蘭',
        'OM' => '阿曼',
        'PA' => '巴拿馬',
        'PE' => '秘魯',
        'PF' => '法屬玻里尼西亞',
        'PG' => '巴布亞紐幾內亞',
        'PH' => '菲律賓',
        'PK' => '巴基斯坦',
        'PL' => '波蘭',
        'PM' => '聖匹及密啟倫群島',
        'PN' => '皮特康島',
        'PR' => '波多黎各',
        'PS' => '巴勒斯坦',
        'PT' => '葡萄牙',
        'PW' => '帛琉群島',
        'PY' => '巴拉圭',
        'QA' => '卡達',
        'RE' => '留尼旺',
        'RO' => '羅馬尼亞',
        'RU' => '俄羅斯',
        'RW' => '盧安達',
        'SA' => '沙烏地阿拉伯',
        'SB' => '索羅門群島',
        'SC' => '賽席爾',
        'SD' => '蘇丹',
        'SE' => '瑞典',
        'SG' => '新加坡',
        'SH' => '聖赫勒拿島',
        'SI' => '斯洛凡尼亞',
        'SJ' => '斯瓦巴及尖棉',
        'SK' => '斯洛伐克',
        'SL' => '獅子山',
        'SM' => '聖馬利諾',
        'SN' => '塞內加爾',
        'SO' => '索馬利亞',
        'SR' => '蘇利南',
        'ST' => '聖多美及普林西比',
        'SV' => '薩爾瓦多',
        'SY' => '敘利亞',
        'SZ' => '史瓦濟蘭',
        'TC' => '土克斯及開科斯群島',
        'TD' => '查德',
        'TF' => '法屬南部屬地',
        'TG' => '多哥',
        'TH' => '泰國',
        'TJ' => '塔吉克',
        'TK' => '拖克勞群島',
        'TM' => '土庫曼',
        'TN' => '突尼西亞',
        'TO' => '東加',
        'TL' => '東帝汶',
        'TR' => '土耳其',
        'TT' => '千里達',
        'TV' => '吐瓦魯',
        'TW' => '台灣',
        'TZ' => '坦尚尼亞',
        'UA' => '烏克蘭',
        'UG' => '烏干達',
        'UM' => '美屬邊疆群島',
        'US' => '美國',
        'UY' => '烏拉圭',
        'UZ' => '烏茲別克',
        'VA' => '梵諦岡',
        'VC' => '聖文森',
        'VE' => '委內瑞拉',
        'VG' => '英屬維爾京群島',
        'VI' => '美屬維爾京群島',
        'VN' => '越南',
        'VU' => '萬那杜',
        'WF' => '沃里斯與伏塔那島',
        'WS' => '薩摩亞群島',
        'YE' => '葉門',
        'YT' => '美亞特',
        'RS' => '塞爾維亞',
        'ZA' => '南非',
        'ZM' => '尚比亞',
        'ME' => '黑山',
        'ZW' => '辛巴威',
        'A1' => 'Anonymous Proxy',
        'A2' => 'Satellite Provider',
        'O1' => 'Other',
        'AX' => '奧蘭群島',
        'GG' => '英屬根息',
        'IM' => '英屬馬恩島',
        'JE' => '英屬澤西島',
        'BL' => '聖巴泰勒米',
        'MF' => '聖馬丁',
        'BQ' => '博內爾島,聖尤斯特歇斯島及薩巴島'
    );

    $browser  = getUserBrowser();
    $os       = getUserOS();
    $ip       = realIP();
    $ipInfo   = $geoip->geoIpRecordByAddr($ip);
    $area     = isset($countryCode[$ipInfo['country_code']]) ? $countryCode[$ipInfo['country_code']] : '';

    // 檢查 IP 是否為私有類型
    if ('unknown' == $ipInfo['region']) {

        $netType = $geoip->chkIpIsPrivate($ip);

        if (false !== $netType) {

            $area = $netType;
        }
    }

    /* 語言 */
    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

        $pos  = strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], ';');
        $lang = addslashes(($pos !== false) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, $pos) : $_SERVER['HTTP_ACCEPT_LANGUAGE']);

    } else {

        $lang = '';
    }

    /* 來源 */
    if (!empty($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 9) {

        $pos = strpos($_SERVER['HTTP_REFERER'], '/', 9);
        if ($pos !== false) {

            $domain = substr($_SERVER['HTTP_REFERER'], 0, $pos);
            $path   = substr($_SERVER['HTTP_REFERER'], $pos);

            /* 來源關鍵字 */
            if (!empty($domain) && !empty($path)) {

                saveSearchengineKeyword($domain, $path);
            }

        } else {

            $domain = $path = '';
        }

    } else {

        $domain = $path = '';
    }

    if (!(in_array($browser, array('Unknow browser', '')) && $os == 'Unknown')) {

        $data['access_time'] = $time;
        $data['ip_address'] = $ip;
        $data['browser'] = $browser;
        $data['system'] = $os;
        $data['visit_times'] = $visitTimes;
        $data['language'] = $lang;
        $data['area'] = $area;
        $data['referer_domain'] = addslashes($domain);
        $data['referer_path'] = addslashes($path);
        $data['access_url'] = addslashes(PHP_SELF);

        $GLOBALS['db']->autoReplace(
            $GLOBALS['db']->table('stats'),
            $data,
            array(
                'visit_times' => 1
            )
        );

    } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {

        $userAgent = array();
        $filePath = TEMP_PATH . 'log/HTTP_USER_AGENT.log';
        if (is_file($filePath)) {

            $userAgent = explode("\r\n", trim(file_get_contents($filePath)));
        }

        if (!in_array($_SERVER['HTTP_USER_AGENT'], $userAgent)) {

            file_put_contents($filePath, $_SERVER['HTTP_USER_AGENT'] . "\r\n", FILE_APPEND);
        }
    }
}

/**
 * 取得日期亂數編號
 *
 * @param     integer    $size    序號長度
 *
 * @return    integer
 */
function genDateRandSn($size = 12)
{
    $keyA = date('ymd');
    $keyALen = strlen($keyA);

    $searialSize = $size > $keyALen ? $size - $keyALen : $keyALen;
    $keyB = str_pad(substr(mt_rand(), 0, $searialSize), $searialSize, '0', STR_PAD_LEFT);

    return $keyA . $keyB;
}

/**
 * 取得嵌入影片的相關資訊
 *
 * @param     string    $url    影片網址 (簡短或完整皆可)
 *
 * @return    array
 */
function getEmbedVideoInfo($url)
{
    $videoInfo = array(
        'type' => 'unknown',
        'vid' => null,
        'img' => array(),
        'embed_url' => array(),
        'share_url_short' => '',
        'share_url_long' => ''
    );

    if (preg_match('/[\\?&]v=([^&#]*)/i', $url, $vid)) {

        $videoInfo['type'] = 'youtube';
        $videoInfo['vid'] = $vid[1];

    } elseif (preg_match('/youtu.be\/([a-zA-Z0-9]*)/i', $url, $vid)) {

        $videoInfo['type'] = 'youtube';
        $videoInfo['vid'] = $vid[1];

    } elseif (preg_match('/[\\?&]iid=([^&#]*)/i', $url, $vid)) {

        $videoInfo['type'] = 'tudou';
        $videoInfo['vid'] = $vid[1];

    } elseif (preg_match('/http:\/\/www.tudou.com\/programs\/view\/([^\s&#?\/]*)/i', $url, $vid)) {

        $videoInfo['type'] = 'tudou';
        $videoInfo['vid'] = $vid[1];
    }

    if (!is_null($videoInfo['vid'])) {
        // 判斷影片連結類型
        switch ($videoInfo['type']) {

            // YouTube
            case 'youtube':

                if (!empty($videoInfo['vid'])) {

                    for ($i = 0; $i < 4; $i += 1) {

                        $videoInfo['img'][] = 'http://img.youtube.com/vi/' . $videoInfo['vid'] . '/' . $i . '.jpg';
                    }
                    $videoInfo['embed_url'][0] = 'http://www.youtube.com/embed/' . $videoInfo['vid'];
                    $videoInfo['embed_url'][1] = 'http://youtube.googleapis.com/v/' . $videoInfo['vid'];
                    $videoInfo['share_url_short'] = 'http://youtu.be/' . $videoInfo['vid'];
                    $videoInfo['share_url_long'] = 'http://www.youtube.com/watch?v=' . $videoInfo['vid'];
                }
                break;

            // 土豆網
            case 'tudou':

                if (!empty($videoInfo['vid'])) {

                    $videoInfo['img'][0] = $videoInfo['img'][1] = '';
                    $videoInfo['embed_url'][0] = 'http://www.tudou.com/v/' . $videoInfo['vid'];
                    $videoInfo['embed_url'][1] = 'http://www.tudou.com/v/' . $videoInfo['vid'];
                    $videoInfo['share_url_short'] = 'http://www.tudou.com/programs/view/' . $videoInfo['vid'];
                    $videoInfo['share_url_long'] = 'http://www.tudou.com/programs/view/' . $videoInfo['vid'];
                }
                break;
        }
    }

    return $videoInfo;
}

/**
 * 取得自訂頁面內容
 *
 * @param     string     $tplCode    頁面代碼
 * @param     integer    $tplId      頁面 ID
 *
 * @return    array
 */
function getCusPageInfo($tplCode = null, $tplId = null)
{
    $oper = '1 AND 0 ';
    if (!is_null($tplCode)) {

        $oper .= ' OR ' . $GLOBALS['db']->in($tplCode, 'mt.tpl_code');

    } elseif (!is_null($tplId)) {

        $oper .= ' OR ' . $GLOBALS['db']->in($tplId, 'mt.tpl_id');
    }

    $data = array();
    $sql = 'SELECT tc.code, tc.value, tc.type '
         . 'FROM ' . $GLOBALS['db']->table('template_config') . ' AS tc '
         . 'INNER JOIN ' . $GLOBALS['db']->table('template') . ' AS mt '
         . 'ON mt.tpl_id = tc.tpl_id '
         . 'WHERE ' . $oper
         . 'AND tc.parent_id > 0 '
         . 'AND tc.lang_id ' . $GLOBALS['db']->in(array(0, $GLOBALS['_CFG']['lang_id']));
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $data[$row['code']] = $row['type'] == 'ckeditor'
            ? $GLOBALS['dus']->urlDecode($row['value'])
            : $row['value'];
    }

    return $data;
}

/**
 * 取得當前頁面標題
 *
 * @param     array     $params      參數值
 *
 * @return    array
 */
function assignPageTitle($params = array())
{
    // 主要字串 (網站標題)
    $main = $GLOBALS['_CFG']['shop_title'];
    // 次要字串 (宣傳文字)
    $extra = '';
    // 次要字串連結符號
    $separator = '_';
    // 主要與次要之間的分隔符號
    $delimiter = ' | ';
    // 是否逆排序
    $reverse = true;
    // 是否取代全形字串
    $shiftunit = false;

    isset($params['shiftunit']) && $shiftunit = (boolean)$params['shiftunit'];

    foreach ($params as $_key => $_value) {
        switch (strtolower($_key)) {
            case 'main':
            case 'extra':
                ${$_key} = array_map(
                    function ($v) use ($shiftunit) {

                        // 是否轉換全形字元為半形字元
                        $shiftunit && $v = makeSemiangle($v);

                        $v = htmlspecialchars((string)$v, ENT_COMPAT, 'UTF-8', false);

                        return $v;
                    },
                    (array)$_value
                );
                break;
            case 'separator':
            case 'delimiter':
                ${$_key} = htmlspecialchars((string)$_value, ENT_COMPAT, 'UTF-8', false);
                break;
            case 'reverse':
                ${$_key} = (boolean)$_value;
                break;
            default:
        }
    }

    $mainEle = ''; // 主要項目
    $mainFirst = ''; // 主要項目中的第一個
    $extraEle = ''; // 次要項目

    if (isset($main)) {

        if (!is_array($main)) {

            $main = explode('|', trim($main, '|'));
        }

        // 取出主要項目中的第一個
        if (count($main) > 1) {

            $mainFirst = array_shift($main);
            if ($reverse) {

                krsort($main);
            }

            $main = implode($separator, $main);

        } else {

            $mainFirst = array_shift($main);
            $main = '';
        }

        if (!empty($main)) {

            $mainEle = $main;
        }
    }

    if (isset($extra)) {

        if (!is_array($extra)) {

            $extraEleArr = explode('|', trim($extra, '|'));
            unset($extra);

            foreach ($extraEleArr as $key => $val) {

                $extra[$key]['text'] = $val;
            }
        }
        if ($reverse) {

            krsort($extra);
        }

        $index = 0;
        foreach ($extra as $key => $val) {

            $index++;
            $extraEle .= $val['text'];
            $extraEle .= ($index != count($extra)) ? $separator : '';
        }
    }

    $breadcrumb = '';
    // 是否逆排序
    if ($reverse) {

        if (!empty($extraEle)) {

            $breadcrumb .= $extraEle;

            if (!empty($mainEle)) {

                $breadcrumb .= $separator;
            }
        }
        if (!empty($mainEle) || !empty($extraEle)) {

            $breadcrumb .= $mainEle . $delimiter;
        }

        $breadcrumb .= $mainFirst;

    } else {

        $breadcrumb .= $mainFirst;

        if (!empty($mainEle) || !empty($extraEle)) {

            $breadcrumb .= $delimiter;
        }

        if (!empty($mainEle)) {

            $breadcrumb .= $mainEle;
        }

        if (!empty($extraEle)) {

            if (!empty($mainEle)) {

                $breadcrumb .= $separator;
            }

            $breadcrumb .= $extraEle;
        }
    }

    return $breadcrumb;
}

/**
 * 插入樣版共同資訊
 *
 * @return    void
 */
function assignTemplate()
{
    if (!defined('INIT_NO_SMARTY')) {

        $GLOBALS['smarty']->assign('base_url', $GLOBALS['dus']->url());
        $GLOBALS['smarty']->assign('theme_dir', 'themes/' . $GLOBALS['_CFG']['template'] . '/');
        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('shop_title', $GLOBALS['_CFG']['shop_title']);

        $GLOBALS['smarty']->assign(
            'google_api_key',
            isset($GLOBALS['_CFG']['google_api_key']) ? $GLOBALS['_CFG']['google_api_key'] : ''
        );

        $GLOBALS['smarty']->assign(
            'stats_code_head',
            isset($GLOBALS['_CFG']['stats_code_head']) ? $GLOBALS['_CFG']['stats_code_head'] : ''
        );
        $GLOBALS['smarty']->assign(
            'stats_code_body',
            isset($GLOBALS['_CFG']['stats_code_body']) ? $GLOBALS['_CFG']['stats_code_body'] : ''
        );

        if (!defined('INIT_LANG_USE_SUBDOMAIN')) {

            $root = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
            substr($root, -1) != '/' && $root .= '/';

            $requestUri = getRequestUrl();

            $langOptions = array_combine($GLOBALS['_CFG']['lang_codes'], $GLOBALS['_CFG']['lang_codes']);

            if (
                isset($GLOBALS['_CFG']['lang_codes'], $requestUri) &&
                preg_match(
                    '/^' . preg_quote($root, '/') . '(' . implode('|', $GLOBALS['_CFG']['lang_codes']) . ')?(?=(?!\/?[^\/]*\.)|(\/[^\/]*\.*))/i',
                    $requestUri,
                    $lm
                )
            ) {

                foreach ($langOptions as $key => $value) {

                    $langOptions[$key] .= isset($lm[2]) ? $lm[2] : '';
                }
            }

            $GLOBALS['smarty']->assign('lang_options', $langOptions);
        }
        $GLOBALS['smarty']->assign('lang_code', $GLOBALS['_CFG']['lang_code']);
        $GLOBALS['smarty']->assign('lang_text', json_encode($GLOBALS['_LANG'], 271));

        $navCategory = getCategory(
            'bulletin_category',
            array(
                'app' => 'blog',
                're_type' => false,
                'is_show_all' => false,
                'lang_id' => $GLOBALS['_CFG']['lang_id'],
                'app' => 'blog'
            )
        );
        $GLOBALS['smarty']->assign('nav_bulletin_category', arrayToTree($navCategory, 'cat_id'));

        $GLOBALS['smarty']->assign('common_blog_list', getIndexBulletin());
    }
}

/**
 * 最新消息
 *
 * @return    array
 */
function getIndexBulletin()
{
    $itemArr = array();

    $time = $_SERVER['REQUEST_TIME'];
    $sql = 'SELECT mt.* ' .
           'FROM ' . $GLOBALS['db']->table('bulletin') . ' AS mt ' .
           'WHERE mt.is_open = 1 ' .
           'AND ((mt.is_time = 0 AND mt.release_start_time <= ' . $time . ') ' .
           'OR (mt.is_time = 1 AND mt.release_start_time <= ' . $time . ' AND mt.release_end_time >= ' . $time . '))' .
           'ORDER BY mt.release_start_time DESC, mt.sort_order ASC, mt.bulletin_id DESC';
    $res = $GLOBALS['db']->selectLimit($sql, 6);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        if ($row['link_url'] != '') {

            $row['url'] = $row['link_url'];
            $row['open_type'] = 1;

        } elseif ($row['open_type'] == 1 && $row['file_url'] != '') {

            $row['url'] = $GLOBALS['dus']->url() . $row['file_url'];

        } else {

            $row['url'] = buildUri(
                SCRIPT_NAME,
                array(
                    'action' => 'view',
                    'id' => $row['bulletin_id']
                ),
                array(
                    'append' => $row['title']
                )
            );
        }

        $row['url'] = buildUri(
            'blog',
            array(
                'action' => 'view',
                'id' => $row['bulletin_id']
            ),
            array(
                'append' => $row['title']
            )
        );

        $itemArr[] = $row;
    }

    return $itemArr;
}
