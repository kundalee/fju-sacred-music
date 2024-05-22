<?php if (!defined('IN_DUS')) die('No direct script access allowed');

if (false && (!defined('PHP_SAFE_LOOKUP') || PHP_SAFE_LOOKUP == '' || strlen(PHP_SAFE_LOOKUP) % 32
    || !in_array(md5_file($_SERVER['SCRIPT_FILENAME']), str_split(PHP_SAFE_LOOKUP, 32)))) {

    error_log('PHP Warning: No direct script access allowed ' . $_SERVER['SCRIPT_FILENAME'], 0);
    exit;
}

/**
 * 基礎類
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

class DUS
{
    private $data_key = array(
        'ٸ', 'ݧ', 'ڃ', '٭', '؆', 'گ', 'ڋ', 'ق', 'ع', 'ݸ', 'ۍ', 'ه', 'ۺ', '٘', 'ڴ', 'ؽ',
        'ۮ', 'ڏ', 'ڷ', 'ڶ', 'ۛ', 'ل', 'ـ', 'ڠ', 'ڪ', 'ݹ', '۫', 'ۏ', 'ۇ', 'ڛ', 'ځ', 'ۀ',
        '۰', 'ݚ', 'ە', '؛', 'ݙ', '؟', 'ۼ', 'ؑ', 'ڽ', '؃', 'ݜ', 'ێ', 'ٝ', 'ݶ', 'ݻ', 'ڼ',
        'ڢ', 'ڀ', 'ٿ', 'ث', 'ۯ', 'ۂ', 'ٔ', 'ى', 'ن', 'ۅ', 'ݨ', '۬', '۸', 'ڰ', 'ٳ', 'ٱ',
        'ݘ', 'ڕ', 'ڣ', 'ݮ', 'ً', 'ݺ', 'ݾ', 'ڡ', 'ڵ', '٧', 'ڦ', 'ݗ', 'ڸ', 'ݷ', 'ۜ', 'ڻ',
        'ت', 'ݯ', 'ڂ', '۷', 'ۓ', 'ݵ', 'آ', 'ݔ', 'ٕ', '۵', 'ػ', '۟', 'ݿ', 'ݫ', 'ۈ', 'ۃ',
        '؏', 'ڎ', '۱', '؎', 'ۿ', 'ې', '۽', 'ݪ', 'ي', 'ڞ', 'ݢ', 'ݤ', 'ݞ', 'د', 'ْ', 'ڊ',
        'ؐ', 'ح', 'ڧ', 'چ', '٫', 'ړ', 'ج', 'ؚ', 'ۢ', 'ݒ', 'ۭ', '۝', 'ظ', '۾', 'ݲ', 'ض',
        'ز', 'ڍ', '،', 'ۧ', 'ݭ', '٩', 'ڇ', 'ٷ', '۶', 'س', 'ٰ', 'ۨ', 'ؔ', '۠', 'ڔ', 'ډ',
        'پ', 'ڮ', '٦', 'ݐ', 'ݳ', 'ۤ', 'ݦ', 'ذ', 'ُ', 'ک', 'و', 'ؖ', 'ګ', 'ۉ', 'ڬ', 'ؘ',
        '؈', 'ۄ', 'ش', '٬', 'ݑ', 'ڗ', 'ڹ', 'ی', 'ۊ', '۞', 'ڭ', 'ؿ', 'ݴ', 'ص', 'څ', 'ݝ',
        '۴', 'ۑ', 'ڳ', 'ٛ', 'ڟ', 'ؓ', 'ٽ', 'ݛ', '۲', 'ر', 'ڒ', '٠', 'ے', 'ٹ', 'ݠ', 'ۗ',
        'خ', 'ب', 'ۥ', '٣', 'ء', 'ݩ', '؊', 'ڿ', '؇', 'ڙ', 'ٓ', 'ٮ', 'ۖ', 'ڱ', '۩', '؁',
        'ھ', 'ّ', 'ږ', 'ؼ', 'ف', 'ݽ', 'ا', '؍', 'ؗ', 'ٙ', 'ؤ', 'ٲ', 'ݡ', 'ۣ', 'ٯ', 'ط',
        '٪', 'ة', 'ݕ', '؋', 'ہ', 'ݬ', 'ۘ', 'ك', 'ڄ', 'ۦ', 'ٍ', 'ٺ', 'ۚ', 'ۆ', 'ݖ', '۳',
        'ٜ', 'ټ', 'ؒ', 'ٴ', 'ٻ', 'ڝ', 'ۻ', 'ٖ', 'ۡ', '؀', 'ݱ', 'ٵ', 'ڌ', 'ڈ', '۪', 'ݼ'
    );

    public $prefix = 'grs_';

    private $encrypt_key = '';

    /**
     * 構造函數
     *
     * @access    public
     *
     * @param     string    $prefix        資料表前綴字
     * @param     string    $encryptKey    加密密鑰
     *
     * @return    void
     */
    public function __construct($prefix, $encryptKey = '')
    {
        $this->prefix = $prefix;
        $this->encrypt_key = $encryptKey;
    }

    /**
     * 將指定的資料表名稱加上前綴字
     *
     * @access    public
     *
     * @param     string    $str    資料表名稱
     *
     * @return    string
     */
    public function table($str, $extraPrefix = null)
    {
        if ($extraPrefix === null) {

            return '`' . $this->prefix . $str . '`';

        } else {

            return '`' . $this->prefix . $extraPrefix . '_' . $str . '`';
        }
    }

    /**
     * 密碼編譯方法
     *
     * @access    public
     *
     * @param     string     $string           原文或者密文
     * @param     string     $operation        操作選項(ENCODE:編碼, DECODE:解碼)
     * @param     string     $key              密鑰
     * @param     integer    $expiry           密文有效期, 加密時候有效, 單位秒, 0 為永久有效
     * @param     integer    $randKeyLength
     *
     * @return    string                       處理後的原文或者經過處理後的密文
     */
    public function compilePassword($string, $operation = 'DECODE', $key = '', $expiry = 0, $randKeyLength = 4)
    {
        $key = md5($key ? $key : $this->encrypt_key);

        $key1 = md5(substr($key, 0, 16));
        $key2 = md5(substr($key, 16, 16));
        $key3 = $randKeyLength ? ($operation == 'DECODE' ? substr($string, 0, $randKeyLength) : substr(md5(microtime()), -$randKeyLength)) : '';

        $cryptkey = $key1 . md5($key1 . $key3);
        $keyLength = strlen($cryptkey);

        $string = $operation == 'DECODE'
                ? base64_decode(substr($string, $randKeyLength))
                : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string. $key2), 0, 16) . $string;
        $stringLength = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {

            $rndkey[$i] = ord($cryptkey[$i % $keyLength]);
        }

        for ($j = $i = 0; $i < 256; $i++) {

            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $stringLength; $i++) {

            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {

            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr ($result, 26).$key2), 0, 16)) {

                return substr($result, 26);

            } else {

                return '';
            }

        } else {

            return $key3 . str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 取得目前的網域名稱
     *
     * @access    public
     *
     * @return    string
     */
    public function getDomain()
    {
        /* 協議 */
        $protocol = self::http();

        $host = '';

        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {

            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];

        } elseif (isset($_SERVER['HTTP_HOST'])) {

            $host = $_SERVER['HTTP_HOST'];

        } else {

            /* 端口 */
            if (isset($_SERVER['SERVER_PORT'])) {

                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {

                    $port = '';
                }

            } else {

                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME'])) {

                $host = $_SERVER['SERVER_NAME'] . $port;

            } elseif (isset($_SERVER['SERVER_ADDR'])) {

                $host = $_SERVER['SERVER_ADDR'] . $port;
            }
        }

        return $protocol . $host;
    }

    /**
     * 取得目前環境的 URL
     *
     * @access    public
     *
     * @return    void
     */
    public function url($dir = 'admin')
    {
        $curr = stripos($_SERVER['SCRIPT_NAME'], $dir . '/') !== false ?
                preg_replace('/(.*)(' . $dir . ')(\/?)(.)*/i', '\1', dirname($_SERVER['SCRIPT_NAME'])) :
                dirname($_SERVER['SCRIPT_NAME']);
        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {

            $root .= '/';
        }

        return self::getDomain() . $root;
    }

    /**
     * 取得目前環境的 HTTP 協議方式
     *
     * @access    public
     *
     * @return    void
     */
    public function http()
    {
        return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    }

    /**
     * 截取 UTF-8 編碼下字符串的函數
     *
     * @param     string    $str       被截取的字符串
     * @param     int       $length    截取的長度
     * @param     bool      $append    是否附加省略號
     *
     * @return    string
     */
    public function substr($str, $length = 0, $append = true)
    {
        $str = trim($str);
        $strLength = strlen($str);

        if ($length == 0 || $length >= $strLength) {

            return $str;

        } elseif ($length < 0) {

            $length = $strLength + $length;
            if ($length < 0) {

                $length = $strLength;
            }
        }

        if (function_exists('mb_substr')) {

            $newStr = mb_substr($str, 0, $length, 'UTF-8');

        } elseif (function_exists('iconv_substr')) {

            $newStr = iconv_substr($str, 0, $length, 'UTF-8');

        } else {

            $newStr = substr($str, 0, $length);
            $newLength = strlen(preg_replace('/[\x00-\x7F]+/', '', $newStr)) % 3;
            if ($newLength > 0) {

                $newStr = substr($str, 0, 0 - $newLength);
            }
        }

        if ($append && $str != $newStr) {

            $newStr .= '...';
        }

        return $newStr;
    }

    private function utf8StrSplit($str, $splitLen = 1)
    {
        if (!preg_match('/^[0-9]+$/', $splitLen) || $splitLen < 1) {

            return false;
        }

        $len = mb_strlen($str, 'UTF-8');
        if ($len <= $splitLen) {

            return array($str);
        }

        preg_match_all('/.{' . $splitLen . '}|[^\x00]{1,' . $splitLen . '}$/us', $str, $ar);

        return $ar[0];
    }

    /**
     * 自定義 header 函式，用於過濾可能出現的安全隱患
     *
     * @param     string     $string              內容
     * @param     boolean    $replace             是否取代表頭
     * @param     integer    $httpResponseCode    HTTP 狀態碼
     *
     * @return    void
     */
    public function header($string, $replace = true, $httpResponseCode = 0)
    {
        $string = str_replace(array("\r", "\n"), array('', ''), $string);

        if (empty($httpResponseCode)) {

            @header($string . "\n", $replace);

        } else {

            @header($string . "\n", $replace, $httpResponseCode);
        }
        exit();
    }

    /**
     * 將 16 進制的數字字串轉為 64 進制的數字字串
     *
     * @access    private
     *
     * @param     string     $m      16 進制的數字字串
     * @param     integer    $len    返回字串長度，如果長度不夠用 0 填充，0 為不填充
     *
     * @return    string
     */
    private function hex16to64($m, $len = 0)
    {
        $hex2 = array();
        for ($i = 0, $j = strlen($m); $i < $j; ++$i) {

            $hex2[] = str_pad(base_convert($m[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }
        $hex2 = implode('', $hex2);
        $hex2 = str_rsplit($hex2, 8); // 2 ^ 8 = 256
        foreach ($hex2 as $one) {

            $hex64[] = $this->data_key[bindec($one)];
        }

        $return = preg_replace('/^0*/', '', implode('', $hex64));
        if ($len) {

            $clen = strlen($return);

            if ($clen >= $len) {

                return $return;

            } else {

                return str_pad($return, $len, '0', STR_PAD_LEFT);
            }
        }
        return $return;
    }

    /**
     * 將 64 進制的數字字串轉為 10 進制的數字字串
     *
     * @access    private
     *
     * @param     string     $m      64 進制的數字字串
     * @param     integer    $len    回傳字串長度，如果長度不夠用 0 填充，0 為不填充
     *
     * @return    string
     */
    private function hex64to10($m, $len = 0)
    {
        $m = self::utf8StrSplit($m);
        $hex2 = '';
        $keyCode = array_flip($this->data_key);
        for ($i = 0, $l = count($m); $i < $l; $i++) {

            if (!isset($keyCode[$m[$i]])) {

                // error_log($this->db_name . ' database encryption key is be changed!!!', 0);
                // exit;
                break;
            }

            $hex2 .= str_pad(decbin($keyCode[$m[$i]]), 8, '0', STR_PAD_LEFT);
        }
        $return = bindec($hex2);

        if ($len) {

            $clen = strlen($return);

            if ($clen >= $len) {

                return $return;

            } else {

                return str_pad($return, $len, '0', STR_PAD_LEFT);
            }
        }

        return $return;
    }

    /**
     * 資料編碼
     *
     * @access    public
     *
     * @param     string    $value    欲編碼之內容
     *
     * @return    string
     */
    public function dataEncode($value)
    {
        if (empty($value)) {

            return $value;

        } elseif (is_array($value)) {

            return array_map(array($this, __METHOD__), $value);

        } else {

            $output = '';
            foreach (str_split(bin2hex(mb_convert_encoding($value, 'UTF-16', 'UTF-8')), 4) as $val) {

                $output .= self::hex16to64($val, 2);
            }

            return $output;
        }
    }

    /**
     * 資料解碼
     *
     * @access    public
     *
     * @param     string    $value    欲編碼之內容
     *
     * @return    string
     */
    public function dataDecode($value)
    {
        if (empty($value)) {

            return $value;

        } elseif (is_array($value)) {

            return array_map(array($this, __METHOD__), $value);

        } else {

            $output = '';
            foreach(self::utf8StrSplit($value, 2) as $val) {

                $output .= str_pad(base_convert(self::hex64to10($val), 10, 16), 4, '0', STR_PAD_LEFT);
            }

            return mb_convert_encoding(pack('H*', $output), 'UTF-8', 'UTF-16');
        }
    }

    /**
     * 將內容中含有目前環境的 URL 地址轉為 WEB_SITE_URL
     *
     * @access    public
     *
     * @param     string    $value    欲轉換之內容
     *
     * @return    void
     */
    public function urlEncode($value)
    {
        if (empty($value)) {

            return $value;

        } elseif (is_array($value)) {

            return array_map(array($this, __METHOD__), $value);

        } else {

            if (isCli() && defined('BASE_URL')) {

                $siteUrl = BASE_URL;

            } else {

                $siteUrl = self::url();
            }

            $value = str_replace('[[WEB_SITE_URL]]', md5('[[WEB_SITE_URL]]'), $value);

            $value = str_replace($siteUrl, '[[WEB_SITE_URL]]', $value);

            return $value;
        }
    }

    /**
     * 將內容中含有 WEB_SITE_URL 轉為目前環境的 URL 地址
     *
     * @access    public
     *
     * @param     string    $value    欲轉換之內容
     *
     * @return    void
     */
    public function urlDecode($value)
    {
        if (empty($value)) {

            return $value;

        } elseif (is_array($value)) {

            return array_map(array($this, __METHOD__), $value);

        } else {

            if (isCli() && defined('BASE_URL')) {

                $siteUrl = BASE_URL;

            } else {

                $siteUrl = self::url();
            }

            $value = str_replace('[[WEB_SITE_URL]]', $siteUrl, $value);

            $value = str_replace(md5('[[WEB_SITE_URL]]'), '[[WEB_SITE_URL]]', $value);

            return $value;
        }
    }

    /**
     * 字串編碼
     *
     * @access    public
     *
     * @param     string    $string    欲編碼字串
     * @param     string    $salt      私鑰
     *
     * @return    string
     */
    public function strEncode($string, $salt = '')
    {
        return $this->compilePassword($string, 'ENCODE', $salt, 0, 0);
    }

    /**
     * 字串解碼
     *
     * @access    public
     *
     * @param     string    $string    欲解碼字串
     * @param     string    $salt      私鑰
     *
     * @return    string
     */
    public function strDecode($string, $salt = '')
    {
        return $this->compilePassword($string, 'DECODE', $salt, 0, 0);
    }

    /**
     * 自定義 cookie 函式，用於立即存取 cookie 內容
     *
     * @access    public
     *
     * @param     string     name      cookie 的名稱
     * @param     string     value     cookie 的值
     * @param     integer    expire    cookie 的有效期
     * @param     string     path      cookie 的伺服器路徑
     * @param     string     domain    cookie 的域名。
     * @param     boolean    secure    是否通過安全的 HTTPS 連接來傳輸 cookie
     *
     * @return    boolean
     */
    public function cookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false)
    {
        $status = true;
        $_COOKIE['DUS'][$name] = $value;

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $status &= setcookie(sprintf('%s[%s][%s]', 'DUS', $name, $k), $v, $expire, $path, $domain, $secure);
            }
        } else {
            $status &= setcookie(sprintf('%s[%s]', 'DUS', $name), $value, $expire, $path, $domain, $secure);
        }

        return $status;
    }
}