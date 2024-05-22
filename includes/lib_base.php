<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 基礎函式庫
 * ========================================================
 *
 * ========================================================
 * Date: 2015-09-07 17:11
 */

if (function_exists('mb_substr_replace') === false) {

    function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = null)
    {
        if (extension_loaded('mbstring') === true) {

            $stringLength = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string, $encoding);

            if ($start < 0) {

                $start = max(0, $stringLength + $start);

            } elseif ($start > $stringLength) {

                $start = $stringLength;
            }

            if ($length < 0) {

                $length = max(0, $stringLength - $start + $length);

            } elseif ((is_null($length) === true) || ($length > $stringLength)) {

                $length = $stringLength;
            }

            if (($start + $length) > $stringLength) {

                $length = $stringLength - $start;
            }

            if (is_null($encoding) === true) {

                return mb_substr($string, 0, $start) . $replacement . mb_substr($string, $start + $length, $stringLength - $start - $length);
            }

            return mb_substr($string, 0, $start, $encoding) . $replacement . mb_substr($string, $start + $length, $stringLength - $start - $length, $encoding);
        }

        return (is_null($length) === true) ? substr_replace($string, $replacement, $start) : substr_replace($string, $replacement, $start, $length);
    }
}

if (!function_exists('glob_recursive')) {
    /**
     * Computes the intersection of arrays using keys for comparison
     *
     * @param     string     pattern    The pattern. No tilde expansion or parameter substitution is done.
     * @param     integer    flags      Valid flags. Does not support flag GLOB_BRACE.
     *
     * @return    array
     */
    function glob_recursive($pattern, $flags = 0)
    {
        $matchedFiles = glob($pattern, $flags);
        $matchedDirs = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        $dirs = $matchedDirs ? $matchedDirs : array();
        $files = $matchedFiles ? $matchedFiles : array();
        foreach ($dirs as $dir) {

            $files = array_merge($files, glob_recursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}

if (!function_exists('http_parse_headers')) {
    /**
     * 解析 HTTP 標頭到一個關聯陣列
     *
     * @param     string    $header    包含字串的 HTTP 標頭
     *
     * @return    array
     */
    function http_parse_headers($header)
    {
        $headerArr = array();
        $key = '';

        foreach (explode(chr(10), $header) as $i => $h) {

            $h = explode(':', $h, 2);

            if (isset($h[1])) {

                if (!isset($headerArr[$h[0]])) {

                    $headerArr[$h[0]] = trim($h[1]);

                } elseif (is_array($headerArr[$h[0]])) {

                    $headerArr[$h[0]] = array_merge($headerArr[$h[0]], array(trim($h[1])));

                } else {

                    $headerArr[$h[0]] = array_merge(array($headerArr[$h[0]]), array(trim($h[1])));
                }

                $key = $h[0];

            } else {

                if (substr($h[0], 0, 1) == chr(9)) {

                    $headerArr[$key] .= chr(13). chr(10). chr(9) . trim($h[0]);

                } elseif (!$key) {

                    $headerArr[0] = trim($h[0]);
                    trim($h[0]);
                }
            }
        }

        return $headerArr;
    }
}

if (!function_exists('imagecreatefrombmp')) {

    /**
     * Create a new image from GD file or URL
     *
     * @param     string      $filename    Path to the GD file
     *
     * @return    resource                 success: image resource identifier
     *                                     errors: false
     */
    function imagecreatefrombmp($filename)
    {
        if (!$f1 = fopen($filename, 'rb')) {

            return false;
        }

        $file = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
        if ($file['file_type'] != 19778) {

            return false;
        }

        $bmp = unpack(
            'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
            '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
            '/Vvert_resolution/Vcolors_used/Vcolors_important',
            fread($f1, 40)
        );
        $bmp['colors'] = pow(2, $bmp['bits_per_pixel']);

        if ($bmp['size_bitmap'] == 0) {

            $bmp['size_bitmap'] = $file['file_size'] - $file['bitmap_offset'];
        }

        $bmp['bytes_per_pixel'] = $bmp['bits_per_pixel'] / 8;
        $bmp['bytes_per_pixel2'] = ceil($bmp['bytes_per_pixel']);
        $bmp['decal'] = ($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
        $bmp['decal'] -= floor($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
        $bmp['decal'] = 4 - (4 * $bmp['decal']);
        if ($bmp['decal'] == 4) {

            $bmp['decal'] = 0;
        }

        $palette = array();
        if ($bmp['colors'] < 16777216){

            $palette = unpack('V' . $bmp['colors'], fread($f1, $bmp['colors'] * 4));
        }

        $img = fread($f1, $bmp['size_bitmap']);
        $vide = chr(0);

        $res = imagecreatetruecolor($bmp['width'], $bmp['height']);
        $p = 0;
        $y = $bmp['height'] - 1;
        while ($y >= 0) {

            $x = 0;

            while($x < $bmp['width']) {

                if ($bmp['bits_per_pixel'] == 32) {

                    $color = unpack('V', substr($img, $p, 3));

                    $b = ord(substr($img, $p, 1));
                    $g = ord(substr($img, $p + 1, 1));
                    $r = ord(substr($img, $p + 2, 1));
                    $color = imagecolorexact($res, $r, $g, $b);

                    if ($color == -1) {

                        $color = imagecolorallocate($res, $r, $g, $b);
                    }

                    $color[0] = $r * 256 * 256 + $g * 256 + $b;
                    $color[1] = $color;

                } elseif ($bmp['bits_per_pixel'] == 24) {

                    $color = unpack('V', substr($img, $p, 3) . $vide);

                } elseif ($bmp['bits_per_pixel'] == 16) {

                    $color = unpack('n', substr($img, $p, 2));
                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 8) {

                    $color = unpack('n', $vide . substr($img, $p, 1));
                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 4) {

                    $color = unpack('n', $vide . substr($img, floor($p), 1));

                    if (($p * 2) % 2 == 0) {

                        $color[1] = ($color[1] >> 4);

                    } else {

                        $color[1] = ($color[1] & 0x0f);
                    }

                    $color[1] = $palette[$color[1] + 1];

                } elseif ($bmp['bits_per_pixel'] == 1) {

                    $color = unpack('n', $vide . substr($img, floor($p), 1));

                    if (($p * 8) % 8 == 0) {

                        $color[1] = $color[1] >> 7;

                    } elseif (($p * 8) % 8 == 1) {

                        $color[1] = ($color[1] & 0x40) >> 6;

                    } elseif (($p * 8) % 8 == 2) {

                        $color[1] = ($color[1] & 0x20) >> 5;

                    } elseif (($p * 8) % 8 == 3) {

                        $color[1] = ($color[1] & 0x10) >> 4;

                    } elseif (($p * 8) % 8 == 4) {

                        $color[1] = ($color[1] & 0x8) >> 3;

                    } elseif (($p * 8) % 8 == 5) {

                        $color[1] = ($color[1] & 0x4) >> 2;

                    } elseif (($p * 8) % 8 == 6) {

                        $color[1] = ($color[1] & 0x2) >> 1;

                    } elseif (($p * 8) % 8 == 7) {

                        $color[1] = ($color[1] & 0x1);
                    }

                    $color[1] = $palette[$color[1] + 1];

                } else {

                    return false;
                }

                imagesetpixel($res, $x, $y, $color[1]);

                $x++;
                $p += $bmp['bytes_per_pixel'];
            }

            $y--;
            $p += $bmp['decal'];
        }
        fclose($f1);

        return $res;
    }
}

if (!function_exists('imagebmp')) {

    /**
     * 輸出 BMP 格式圖片至瀏覽器或檔案
     *
     * @param     resource    $image          圖片資源
     * @param     string      $filename       另存新檔的檔案名稱，如果為空則直接在瀏覽器輸出
     * @param     integer     $bit            位元深度
     * @param     integer     $compression    壓縮方式
     *                                        0: 不壓縮
     *                                        1: 使用 RLE8 壓縮演算法進行壓縮
     *
     * @return    boolean
     */
    function imagebmp(&$image, $filename = '', $bit = null, $compression = 0)
    {
        if (!in_array($bit, array(1, 4, 8, 16, 24, 32))) {

            $bit = 8;

        } else if ($bit == 32) {

            $bit = 24;
        }
        $bits = pow(2, $bit);

        // 調整調色板
        imagetruecolortopalette($image, true, $bits);
        $width = imagesx($image);
        $height = imagesy($image);
        $colorsNum = imagecolorstotal($image);

        if ($bit <= 8) {

            // 顏色索引
            $rgbQuad = '';
            for ($i = 0; $i < $colorsNum; $i ++) {

                $colors = imagecolorsforindex($image, $i);
                $rgbQuad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . 0x00;
            }

            // 點陣圖資料
            $bmpData = '';
            if ($compression == 0 || $bit < 8) { // 非壓縮

                if (!in_array($bit, array(1, 4, 8))) {

                    $bit = 8;
                }
                $compression = 0;

                // 每行字元數必須為 4 的倍數，補齊
                $extra = '';
                $padding = 4 - ceil($width / (8 / $bit)) % 4;
                if ($padding % 4 != 0) {

                    $extra = str_repeat(0x00, $padding);
                }

                for ($j = $height - 1; $j >= 0; $j --) {

                    $i = 0;

                    while ($i < $width){

                        $bin = 0;
                        $limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;

                        for ($k = 8 - $bit; $k >= $limit; $k -= $bit) {

                            $index = imagecolorat($image, $i, $j);
                            $bin |= $index << $k;
                            $i ++;
                        }

                        $bmpData .= chr($bin);
                    }

                    $bmpData .= $extra;
                }

            } else if ($compression == 1 && $bit == 8) { // RLE8 壓縮

                for ($j = $height - 1; $j >= 0; $j--) {

                    $lastIndex = 0x00;
                    $sameNum = 0;

                    for ($i = 0; $i <= $width; $i ++) {

                        $index = imagecolorat($image, $i, $j);

                        if ($index !== $lastIndex || $sameNum > 255) {

                            if ($sameNum != 0) {
                                $bmpData .= chr($sameNum) . chr($lastIndex);
                            }

                            $lastIndex = $index;
                            $sameNum = 1;

                        } else {

                            $sameNum++;
                        }
                    }

                    $bmpData .= 0x00 . 0x00;
                }

                $bmpData .= 0x00 . 0x01;
            }

            $sizeQuad = strlen($rgbQuad);
            $sizeData = strlen($bmpData);

        } else {

            // 每行字元數必須為 4 的倍數，補齊
            $extra = '';
            $padding = 4 - ($width * ($bit / 8)) % 4;

            if ($padding % 4 != 0){

                $extra = str_repeat(0x00, $padding);
            }

            // 點陣圖資料
            $bmpData = '';
            for ($j = $height - 1; $j >= 0; $j--) {

                for ($i = 0; $i < $width; $i ++) {

                    $index = imagecolorat($image, $i, $j);
                    $colors = imagecolorsforindex($image, $index);

                    if ($bit == 16) {

                        $bin = 0 << $bit;
                        $bin |= ($colors['red'] >> 3) << 10;
                        $bin |= ($colors['green'] >> 3) << 5;
                        $bin |= $colors['blue'] >> 3;
                        $bmpData .= pack('v', $bin);

                    } else {

                        $bmpData .= pack('c*', $colors['blue'], $colors['green'], $colors['red']);
                    }
                }

                $bmpData .= $extra;
            }

            $sizeQuad = 0;
            $sizeData = strlen($bmpData);
            $colorsNum = 0;
            $compression = 0;
        }

        // 點陣圖檔案標頭
        $fileHeader = 'BM' . pack('V3', 54 + $sizeQuad + $sizeData, 0, 54 + $sizeQuad);
        // 點陣圖檔案標頭資訊
        $infoHeader = pack('V3v2V*', 0x28, $width, $height, 1, $bit, $compression, $sizeData, 0, 0, $colorsNum, 0);

        // 寫入檔案
        if ($filename != '') {

            $fp = fopen($filename, 'wb');

            fwrite($fp, $fileHeader);
            fwrite($fp, $infoHeader);
            fwrite($fp, $rgbQuad);
            fwrite($fp, $bmpData);
            fclose($fp);

            return 1;
        }

        // 瀏覽器輸出
        header('Content-Type: image/bmp');
        echo $fileHeader . $infoHeader;
        echo $rgbQuad;
        echo $bmpData;

        return 1;
    }
}

/**
 * UTF-8的字串依指定長度切割成陣列
 *
 * @param     string     $str    需要切割的字串
 * @param     integer    $len    每段字串的長度
 *
 * @return    array
 */
function utf8_str_split($str, $len = 1)
{
    if (!preg_match('/^[0-9]+$/', $len) || $len < 1)
        return FALSE;

    $strLen = mb_strlen($str, 'UTF-8');
    if ($strLen <= $len)
        return array($str);

    preg_match_all('/.{' . $len . '}|[^\x00]{1,' . $len . '}$/us', $str, $ar);

    return $ar[0];
}

/**
 * 功能和 PHP 原生函數 str_split 接近
 * 只是從尾部開始計數切割
 *
 * @param     string     $str    需要切割的字串
 * @param     integer    $len    每段字串的長度
 *
 * @return    array
 */
function str_rsplit($str, $len = 1)
{
    if ($str == null || $str == false || $str == '') {

        return false;
    }
    $strLen = strlen($str);
    if ($strLen <= $len) {

        return array($str);
    }
    $headLen = $strLen % $len;
    if ($headLen == 0) {

        return str_split($str, $len);

    }
    $return = array(substr($str, 0, $headLen));
    return array_merge($return, str_split(substr($str, $headLen), $len));
}

/**
 * 取得使用者真實 IP 地址
 *
 * @return    string
 */
function realIP()
{
    static $realIP = null;


    if ($realIP !== null) {
        return $realIP;
    }

    if (isset($_SERVER)) {

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一個非unknown的有效IP字符串 */
            foreach ($arr as $ip) {

                $ip = trim($ip);

                if ($ip != 'unknown') {

                    $realIP = $ip;
                    break;
                }
            }

        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {

            $realIP = $_SERVER['HTTP_CLIENT_IP'];

        } else {

            if (isset($_SERVER['REMOTE_ADDR'])) {

                $realIP = $_SERVER['REMOTE_ADDR'];

            } else {

                $realIP = '0.0.0.0';
            }
        }

    } else {

        if (getenv('HTTP_X_FORWARDED_FOR')) {

            $realIP = getenv('HTTP_X_FORWARDED_FOR');

        } elseif (getenv('HTTP_CLIENT_IP')) {

            $realIP = getenv('HTTP_CLIENT_IP');

        } else {

            $realIP = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realIP, $onlineIP);
    $realIP = !empty($onlineIP[0]) ? $onlineIP[0] : '0.0.0.0';

    return $realIP;
}

/**
 * 取得伺服器作業系統編碼
 *
 * @return    string
 */
function getServerOsCharset()
{
    static $encoding = null;

    if (null === $encoding) {

        $encoding = 'UTF-8'; // 萬國編碼

        $lcInfo = str_replace(';', '&', setlocale(LC_ALL, 0));
        parse_str($lcInfo, $lcInfo);
        if (isset($lcInfo['LC_CTYPE'])) {

            $codePage = array(
                '1252' => 'ASCII', // 拉丁編碼
                '950' => 'CP950',  // 繁中編碼
                '936' => 'CP936',  // 簡中編碼
                '932' => 'CP932',  // 日文編碼
                '949' => 'EUC-KR', // 韓文編碼
                '1258' => 'VISCII' // 越南編碼
            );

            foreach ($codePage as $cKey => $code) {

                if (false !== stripos($lcInfo['LC_CTYPE'], '.' . $cKey)) {

                    $encoding = $code;
                    break;
                }
            }
        }
    }

    return $encoding;
}

/**
 * 取得瀏覽器名稱和版本
 *
 * @return    string
 */
function getUserBrowser()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {

        return '';
    }

    $agent       = $_SERVER['HTTP_USER_AGENT'];
    $browser     = '';
    $ver         = '';

    if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {

        $browser     = 'Internet Explorer';
        $ver         = $regs[1];

    } elseif (preg_match('/Edge\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Microsoft Edge';
        $ver         = $regs[1];

    } elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'FireFox';
        $ver         = $regs[1];

    } elseif (preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Chrome';
        $ver         = $regs[1];

    } elseif (preg_match('/Android\s.*Version\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Android Browser';
        $ver         = $regs[1];

    } elseif (preg_match('/CriOS\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Chrome';
        $ver         = $regs[1];

    } elseif (preg_match('/Version\/([^\s]+)\sSafari\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Safari';
        $ver         = $regs[1];

    } elseif (preg_match('/Version\/([^\s]+)\sMobile\/([^\s]+)\sSafari\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Mobile Safari';
        $ver         = $regs[1];

    } elseif (preg_match('/Opera[\s|\/]([^\s]+).*Version\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Opera';
        $ver         = $regs[2];

    } elseif (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {

        $browser     = 'OmniWeb';
        $ver         = $regs[2];

    } elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Netscape';
        $ver         = $regs[2];

    } elseif (preg_match('/Maxthon/i', $agent, $regs)) {

        $browser     = '(Internet Explorer ' . $ver . ') Maxthon';
        $ver         = '';

    } elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {

        $browser     = '(Internet Explorer ' . $ver . ') NetCaptor';
        $ver         = $regs[1];

    } elseif(preg_match('/rv:([.0-9a-zA-Z]+)/i', $agent, $regs)) {

        $browser     = 'Internet Explorer';
        $ver         = $regs[1];

    } elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {

        $browser     = 'Lynx';
        $ver         = $regs[1];
    }

    if (!empty($browser)) {

        return addslashes($browser . ' ' . $ver);

    } else {

        return 'Unknow browser';
    }
}

/**
 * 取得客戶端的操作系統
 *
 * @return    string
 */
function getUserOS()
{
    if (empty($_SERVER['HTTP_USER_AGENT'])) {

        return 'Unknown';
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    if (preg_match('/windows|win32/i', $userAgent)) {

        $osPlatform = 'Windows';
        if (preg_match('/windows nt 10.0/i', $userAgent)) {

            $osPlatform .=  ' 10';

        } elseif (preg_match('/windows nt 6.3/i', $userAgent)) {

            $osPlatform .= ' 8.1';

        } elseif (preg_match('/windows nt 6.2/i', $userAgent)) {

            $osPlatform .= ' 8';

        } elseif (preg_match('/windows nt 6.1/i', $userAgent)) {

            $osPlatform .= ' 7';

        } elseif (preg_match('/windows nt 6.0/i', $userAgent)) {

            $osPlatform .= ' Vista';

        } elseif (preg_match('/windows nt 5.2/i', $userAgent)) {

            $osPlatform .= ' Server 2003/XP x64';

        } elseif (preg_match('/windows nt 5.1/i', $userAgent) || preg_match('/windows xp/i', $userAgent)) {

            $osPlatform .= ' XP';

        } elseif (preg_match('/windows nt 5.0/i', $userAgent)) {

            $osPlatform .= ' 2000';

        } elseif (preg_match('/windows me/i', $userAgent)) {

            $osPlatform .= ' ME';

        } elseif (preg_match('/win98/i', $userAgent)) {

            $osPlatform .= ' 98';

        } elseif (preg_match('/win95/i', $userAgent)) {

            $osPlatform .= ' 95';

        } elseif (preg_match('/win16/i', $userAgent)) {

            $osPlatform .= ' 3.11';
        }

    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {

        $osPlatform = 'Mac';
        if (preg_match('/Macintosh.*OS X (\d+[_\.]\d+)/i', $userAgent, $matches)) {

            $osPlatform = 'OS X ';
            switch (strtr($matches[1], '_', '.')) {
                case '10.11':
                    $osPlatform .= 'El Capitan';
                    break;
                case '10.10':
                    $osPlatform .= 'Yosemite';
                    break;
                case '10.9':
                    $osPlatform .= 'Mavericks';
                    break;
                case '10.8':
                    $osPlatform .= 'Mountain Lion';
                    break;
                case '10.7':
                    $osPlatform .= 'Lion';
                    break;
                case '10.6':
                    $osPlatform .= 'Snow Leopard';
                    break;
                case '10.5':
                    $osPlatform .= 'Leopard';
                    break;
                case '10.4':
                    $osPlatform .= 'Tiger';
                    break;
                case '10.3':
                    $osPlatform .= 'Panther';
                    break;
                case '10.2':
                    $osPlatform .= 'Jaguar';
                    break;
                case '10.1':
                    $osPlatform .= 'Puma';
                    break;
                case '10.0':
                    $osPlatform .= 'Cheetah';
                    break;
            }

        } elseif (preg_match('/mac_powerpc/i', $userAgent)) {

            $osPlatform .= ' OS 9';

        } elseif (preg_match('/iPad.*OS (\d+[_\.]\d+[_\.]*\d*)/i', $userAgent, $matches)) {

            $osPlatform = 'iPad OS ' . strtr($matches[1], '_', '.');

        } elseif (preg_match('/iPod.*OS (\d+[_\.]\d+[_\.]*\d*)/i', $userAgent, $matches)) {

            $osPlatform = 'iPod OS ' . strtr($matches[1], '_', '.');

        } elseif (preg_match('/iPhone.*OS (\d+[_\.]\d+[_\.]*\d*)/i', $userAgent, $matches)) {

            $osPlatform = 'iPhone OS ' . strtr($matches[1], '_', '.');
        }

    } elseif (preg_match('/Android\s+(\d+\.\d+\.*\d*)|]/i', $userAgent, $matches)) {

        $osPlatform = $matches[0];

    } elseif (preg_match('/Symbian\S*[\/|\s](\d+\.*\d*)/i', $userAgent, $matches)) {

        $osPlatform = $matches[0];

    } elseif (preg_match('/BlackBerry.*\w*\/(\d+\.\d+\.\d+\.*\d*)\s+/i', $userAgent, $matches)) {

        $osPlatform = $matches[0];

    } elseif (preg_match('/Windows Phone OS.* (\d+\.\d+)/i', $userAgent, $matches)) {

        $osPlatform = $matches[0];

    } elseif (preg_match('/webos/i', $userAgent)) {

        $osPlatform = 'Mobile';

    } elseif (preg_match('/linux/i', $userAgent)) {

        $osPlatform = 'Linux';

    } elseif (preg_match('/ubuntu/i', $userAgent)) {

        $osPlatform = 'Ubuntu';

    } elseif (preg_match('/unix/i', $userAgent)) {

        $osPlatform = 'Unix';

    } elseif (preg_match('/sun/i', $userAgent) && preg_match('/os/i', $userAgent)) {

        $osPlatform = 'SunOS';

    } elseif (preg_match('/ibm/i', $userAgent) && preg_match('/os/i', $userAgent)) {

        $osPlatform = 'IBM OS/2';

    } elseif (preg_match('/powerpc/i', $userAgent)) {

        $osPlatform = 'PowerPC';

    } elseif (preg_match('/netbsd/i', $userAgent)) {

        $osPlatform = 'NetBSD';

    } elseif (preg_match('/freebsd/i', $userAgent)) {

        $osPlatform = 'FreeBSD';

    } elseif (preg_match('/bsd/i', $userAgent)) {

        $osPlatform = 'BSD';

    } elseif (preg_match('/teleport/i', $userAgent)) {

        $osPlatform = 'teleport';

    } elseif (preg_match('/flashget/i', $userAgent)) {

        $osPlatform = 'flashget';

    } elseif (preg_match('/webzip/i', $userAgent)) {

        $osPlatform = 'webzip';

    } elseif (preg_match('/offline/i', $userAgent)) {

        $osPlatform = 'offline';

    } elseif (preg_match('/aix/i', $userAgent)) {

        $osPlatform = 'AIX';

    } elseif (preg_match('/hpux/i', $userAgent)) {

        $osPlatform = 'HPUX';

    } elseif (preg_match('/osf1/i', $userAgent)) {

        $osPlatform = 'OSF1';

    } elseif (preg_match('/irix/i', $userAgent)) {

        $osPlatform = 'IRIX';

    } else {

        $osPlatform = 'Unknown';
    }

    return $osPlatform;
}

/**
 * 取得使用者裝置類型
 *
 * @return    mixed
 */
function getUserDevice()
{
    $device = false;

    if (empty($_SERVER['HTTP_USER_AGENT'])) {

        return $device;
    }

    $ua = &$_SERVER['HTTP_USER_AGENT'];

    $iPhone = strstr(strtolower($ua), 'mobile');
    $android = strstr(strtolower($ua), 'android');
    $windowsPhone = strstr(strtolower($ua), 'phone');

    $androidTablet = null;
    if (strstr(strtolower($ua), 'android')) {

        if (!strstr(strtolower($ua), 'mobile')){

            $androidTablet = true;
        }
    }

    $iPad = strstr(strtolower($ua), 'ipad');

    if ($androidTablet || $iPad) {

        $device = 'tablet';

    } elseif ($iPhone && !$iPad || $android && !$androidTablet || $windowsPhone) {

        $device = 'mobile';

    } else {

        $device = 'desktop';
    }

    return $device;
}

/**
 * 取得使用者操作系統的換行符號
 *
 * @return    string
 */
function getCrlf()
{
    /* LF (Line Feed, 0x0A, \N) 和 CR(Carriage Return, 0x0D, \R) */
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Win')) {

        $theCrlf = '\r\n';

    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Mac')) {

        $theCrlf = '\r'; // for old MAC OS

    } else {

        $theCrlf = '\n';
    }

    return $theCrlf;
}

/**
 * 取得伺服器的 IP
 *
 * @return    string
 */
function realServerIP()
{
    static $ip = null;

    if ($ip !== null) {

        return $ip;
    }

    $ip = '0.0.0.0';

    if (isPhp('5.3')) {

        $ip = gethostbyname(gethostname());

    } else {

        $ip = gethostbyname(php_uname('n'));
    }

    return $ip;
}

/**
 * 取得伺服器上的 GD 版本
 *
 * @return    integer    可能的值為 0，1，2
 */
function gdVersion()
{
    static $version = -1;

    if ($version >= 0) {

        return $version;
    }

    if (!extension_loaded('gd')) {

        $version = 0;

    } else {

        if (function_exists('gd_info')) {

            $verInfo = gd_info();
            preg_match('/\d/', $verInfo['GD Version'], $match);
            $version = $match[0];

        } else {

            if (function_exists('imagecreatetruecolor')) {

                $version = 2;

            } elseif (function_exists('imagecreate')) {

                $version = 1;
            }
        }
    }

    return $version;
}

/**
 * 生成隨機的數字
 *
 * @return    string
 */
function randomFileName()
{
    $str = '';
    for ($i = 0; $i < 9; $i++) {

        $str .= mt_rand(0, 9);
    }
    return time() . $str;
}

/**
 * 檢查目標資料夾是否存在，如果不存在則自動建立該目錄
 *
 * @param     string     $folder    目錄路徑。不能使用相對於網站根目錄的 URL
 * @param     integer    $perm      目錄權限
 *
 * @return    boolean
 */
function makeDir($folder, $perm = 0777)
{
    $reval = false;

    if (!file_exists($folder)) {

        /* 如果目錄不存在則嘗試創建該目錄 */
        @umask(0);

        /* 將目錄路徑拆分成數組 */
        preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        /* 如果第一個字符為/則當作物理路徑處理 */
        $base = ($atmp[0][0] == '/') ? '/' : '';

        /* 遍歷包含路徑信息的數組 */
        foreach ($atmp[1] as $val) {

            if ('' != $val) {

                $base .= $val;

                if ('..' == $val || '.' == $val) {

                    /* 如果目錄為.或者..則直接補/繼續下一個循環 */
                    $base .= '/';

                    continue;
                }

            } else {

                continue;
            }

            $base .= '/';

            if (!@file_exists($base)) {

                /* 嘗試創建目錄，如果創建失敗則繼續循環 */
                if (@mkdir($base, $perm)) {

                    @chmod($base, $perm);
                    $reval = true;
                }
            }
        }

    } else {

        /* 路徑已經存在。返回該路徑是不是一個目錄 */
        $reval = is_dir($folder);
    }

    clearstatcache();

    return $reval;
}

/**
 * 遞迴方式的對變數中的特殊字元進行轉義
 *
 * @param     mixed    $value
 *
 * @return    mixed
 */
function addslashesDeep($value)
{
    if (empty($value)) {

        return $value;

    } else {

        return is_array($value) ? array_map(__FUNCTION__, $value) : addslashes($value);
    }
}

/**
 * 將物件成員變數或者陣列的特殊字元進行轉義
 *
 * @param     mixed    $obj    物件或者陣列
 *
 * @return    mixed
 */
function addslashesDeepObj($obj)
{
    if (is_object($obj) == true) {

        foreach ($obj as $key => $val) {

            $obj->$key = addslashesDeep($val);
        }

    } else {

        $obj = addslashesDeep($obj);
    }

    return $obj;
}

/**
 * 遞迴方式的對變數中的特殊字元去除轉義
 *
 * @param     mix    $value
 *
 * @return    mix
 */
function stripslashesDeep($value)
{
    if (empty($value)) {

        return $value;

    } else {

        return is_array($value) ? array_map(__FUNCTION__, $value) : stripslashes($value);
    }
}

/**
 * 將一個字元中含有全形的數字、字母、空格或'%+-()'字元轉換為對應半形字元
 *
 * @param     string    $str    待轉換字串
 *
 * @return    string    $str    處理後字串
 */
function makeSemiangle($str)
{
    $arr = array(
        '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '【' => '[', '】' => ']', '「' => '[', '」' => ']',
        '『' => '[', '』' => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
        '；' => ',', '？' => '?', '！' => '!', '…' => '-', '∥' => '|',
        '〞' => '"', '′' => '`', '『' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ', '．' => '.'
    );

    return strtr($str, $arr);
}

/**
 * 去除字串右側可能出現的亂碼
 *
 * @param     string    $str    字串
 *
 * @return    string
 */
function trimRight($str)
{
    $length = strlen(preg_replace('/[\x00-\x7F]+/', '', $str)) % 3;

    if ($length > 0) {

        $str = substr($str, 0, 0 - $length);
    }

    return $str;
}

/**
 * 將上傳檔案轉移到指定位置
 *
 * @param     string     $fileName      要複製的檔案
 * @param     string     $targetName    複製檔案的目的地
 *
 * @return    boolean
 */
function moveUploadFile($fileName, $targetName = '')
{
    if (function_exists('move_uploaded_file')) {

        if (move_uploaded_file($fileName, $targetName)) {

            @chmod($targetName, 0755);
            return true;

        } elseif (copy($fileName, $targetName)) {

            @chmod($targetName, 0755);
            return true;
        }

    } elseif (copy($fileName, $targetName)) {

        @chmod($targetName, 0755);
        return true;
    }
    return false;
}

/**
 * 取得檔案副檔名, 並判斷是否合法
 *
 * @param     string     $fileName     欲檢查的檔案名稱
 * @param     array      $allowType    允許的副檔名陣列
 *
 * @return    boolean
 */
function getFileSuffix($fileName, $allowType = array())
{
    $suffix = strtolower(array_pop(explode('.', $fileName)));
    if (empty($allowType)) {

        return $suffix;

    } else {

        if (in_array($suffix, $allowType)) {

            return true;

        } else {

            return false;
        }
    }
}

/**
 * 從現有的資源取得環境變數，並提供仿真環境的不支援或不一致的變數
 * （即 DOCUMENT_ROOT 的 IIS 上，或 SCRIPT_NAME 在 CGI 模式）
 * 也暴露出一些額外的定制環境資訊
 *
 * @param     string    $key
 *
 * @return    string
 */
function env($key)
{
    if ($key == 'HTTPS') {

        if (isset($_SERVER) && !empty($_SERVER)) {

            return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        }
        return (strpos(env('SCRIPT_URI'), 'https://') === 0);
    }

    if ($key == 'SCRIPT_NAME') {

        if (env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {

            $key = 'SCRIPT_URL';
        }
    }

    $val = null;
    if (isset($_SERVER[$key])) {

        $val = $_SERVER[$key];

    } elseif (isset($_ENV[$key])) {

        $val = $_ENV[$key];

    } elseif (getenv($key) !== false) {

        $val = getenv($key);
    }

    if ($key === 'REMOTE_ADDR' && $val === env('SERVER_ADDR')) {

        $addr = env('HTTP_PC_REMOTE_ADDR');
        if ($addr !== null) {

            $val = $addr;
        }
    }

    if ($val !== null) {

        return $val;
    }

    switch ($key) {
        case 'SCRIPT_FILENAME':
            if (defined('SERVER_IIS') && SERVER_IIS === true) {

                return str_replace('\\\\', '\\', env('PATH_TRANSLATED'));
            }
            break;
        case 'DOCUMENT_ROOT':
            $name = env('SCRIPT_NAME');
            $fileName = env('SCRIPT_FILENAME');
            $offset = 0;
            if (!strpos($name, '.php')) {

                $offset = 4;
            }
            return substr($fileName, 0, strlen($fileName) - (strlen($name) + $offset));
            break;
        case 'PHP_SELF':
            return str_replace(env('DOCUMENT_ROOT'), '', env('SCRIPT_FILENAME'));
            break;
        case 'CGI_MODE':
            return (PHP_SAPI === 'cgi');
            break;
        case 'HTTP_BASE':
            $host = env('HTTP_HOST');
            if (substr_count($host, '.') !== 1) {

                return preg_replace('/^([^.])*/i', null, env('HTTP_HOST'));
            }
            return '.' . $host;
            break;
    }
    return null;
}

/**
 * 檢查圖片類型
 *
 * @param     string    $imgType    圖片類型
 *
 * @return    boolean
 */
function checkImageType($imgType)
{
    return $imgType == 'image/gif'   ||
           $imgType == 'image/x-png' ||
           $imgType == 'image/png'   ||
           $imgType == 'image/jpg'   ||
           $imgType == 'image/pjpeg' ||
           $imgType == 'image/jpeg';
}

/**
 * 檢查圖片處理能力
 *
 * @param     string    $imgType    圖片類型
 *
 * @return    void
 */
function checkImageFunction($imgType)
{
    switch ($imgType) {
        case 'image/gif':
        case 1:

            return function_exists('imagecreatefromgif');
            break;

        case 'image/pjpeg':
        case 'image/jpeg':
        case 2:

            return function_exists('imagecreatefromjpeg');
            break;

        case 'image/x-png':
        case 'image/png':
        case 3:

            return function_exists('imagecreatefrompng');
            break;

        default:

            return false;
    }
}

/**
 * 確定目前 PHP 的版本是否大於所提供的值
 *
 * @param     string     $version    版本號
 *
 * @return    boolean                TRUE: 代表目前的版本是 $version 或更高
 */
function isPhp($version = '5.0.0')
{
    static $isPhp;

    $version = (string)$version;

    if (!isset($isPhp[$version])) {

        $isPhp[$version] = version_compare(PHP_VERSION, $version) < 0 ? false : true;
    }

    return $isPhp[$version];
}

/**
 * 測試是否有從命令行進行的請求
 *
 * @return     boolean
 */
function isCli()
{
    return (PHP_SAPI === 'cli' || defined('STDIN'));
}

/**
 * 檢查是否為加密的 (HTTPS) 連接訪問
 *
 * @return    boolean
 */
function isHttps()
{
    $isHttps = false;

    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {

        $isHttps = true;

    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {

        $isHttps = true;

    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {

        $isHttps = true;
    }

    return $isHttps;
}

/**
 * 檢測變數是否為 JSON 資料
 *
 * @param     string     $json    JSON 資料
 *
 * @return    boolean
 */
function isJson($json)
{
    json_decode($json);

    return (json_last_error() === JSON_ERROR_NONE);
}

/**
 * 檢測變數是否為 IP
 *
 * @param     string     $ip              IP address
 * @param     string     $ipFormat        IP 格式
 * @param     boolean    $chkPrivRange    檢測是否為私有的 IPv4 範圍
 * @param     boolean    $chkResRange     檢測是否為保留的 IPv4 範圍
 *
 * @return    boolean
 */
function isIp($ip, $ipFormat = null, $chkPrivRange = true, $chkResRange = true)
{
    $flag = null;

    // 檢測 IP 格式
    switch (strtolower($ipFormat)) {
        // IPv4
        case 'ipv4':
        case 'v4':

            $flag |= FILTER_FLAG_IPV4;
            break;
        // IPv6
        case 'ipv6':
        case 'v6':

            $flag |= FILTER_FLAG_IPV6;
            break;
    }

    if ($chkPrivRange) {

        $flag |= FILTER_FLAG_NO_PRIV_RANGE;
    }
    if ($chkResRange) {

        $flag |= FILTER_FLAG_NO_RES_RANGE;
    }

    return false !== filter_var($ip, FILTER_VALIDATE_IP, $flag);
}

/**
 * 檢測請求是否為非同步
 *
 * @return    boolean
 */
function isAjax()
{
    $status = false;

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {

        $status = strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    return $status;
}

/**
 * 檢測變數是否為 UNIX 格式的時間戳記
 *
 * @param     integer     $timestamp    時間戳記
 *
 * @return    boolean
 */
function isTimestamp($timestamp)
{
    return ((string) (int)$timestamp === $timestamp) && ($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX);
}

/**
 * 字元集轉換
 *
 * @param     string    $str           要轉換的字串
 * @param     string    $outCharset    輸出的字元集
 * @param     string    $inCharset     輸入的字元集
 *
 * @return    string
 */
function cIconv($str, $outCharset, $inCharset)
{
    if (strtoupper($outCharset) != strtoupper($inCharset)) {
        if (
            function_exists('mb_convert_encoding') &&
            (@$outstr = mb_convert_encoding($str, $outCharset, $inCharset))

        ) {

            return $outstr;

        } elseif (
            function_exists('iconv') &&
            (@$outstr = iconv($inCharset . '//IGNORE', $outCharset . '//IGNORE', $str))
        ) {

            return $outstr;
        }
    }
    return $str;
}

/**
 * 去除路徑前後分隔字元
 *
 * @param     string    $str    路徑
 *
 * @return    string
 */
function trimSeparator($path)
{
    if (empty($path)) {

        return $path;
    }

    return rtrimSeparator(ltrimSeparator($path));
}

/**
 * 去除路徑左方分隔字元
 *
 * @param     string    $str    路徑
 *
 * @return    string
 */
function ltrimSeparator($path)
{
    if (empty($path)) {

        return $path;
    }

    return preg_replace('/^[\\\\|\/]/', '', $path);
}

/**
 * 去除路徑右方分隔字元
 *
 * @param     string    $str    路徑
 *
 * @return    string
 */
function rtrimSeparator($path)
{
    if (empty($path)) {

        return $path;
    }

    return preg_replace('/[\\\\|\/]$/', '', $path);
}

/**
 * 轉換路徑分隔字元
 *
 * @param     string    $str          路徑
 * @param     string    $separator    路徑分隔符號
 *
 * @return    string
 */
function convertSeparator($path, $separator = '/')
{
    return preg_replace('/[\\\\|\/]/', $separator, $path);
}

/**
 * 將資料長度轉成位元組
 *
 * @param     string    $num    帶有簡寫容量單位的資料長度
 *
 * @return    float
 */
function convertToBytes($num)
{
    $units = array(
        'B' => 0,               // Byte
        'K' => 1,   'KB' => 1,  // Kilobyte
        'M' => 2,   'MB' => 2,  // Megabyte
        'G' => 3,   'GB' => 3,  // Gigabyte
        'T' => 4,   'TB' => 4,  // Terabyte
        'P' => 5,   'PB' => 5,  // Petabyte
        'E' => 6,   'EB' => 6,  // Exabyte
        'Z' => 7,   'ZB' => 7,  // Zettabyte
        'Y' => 8,   'YB' => 8   // Yottabyte
    );

    $unit = strtoupper(trim(preg_replace('/[0-9.]/', '', $num)));
    $num = trim(preg_replace('/[^0-9.]/', '', $num));

    if (!in_array($unit, array_keys($units))) {

        return false;
    }

    return (float)$num * pow(1024, $units[$unit]);
}

/**
 * 將位元組轉成可閱讀格式
 *
 * @param     float      $bytes       位元組
 * @param     integer    $decimals    分位數
 * @param     string     $unit        容量單位
 *
 * @return    string
 */
function formatByte($bytes, $decimals = 0, $unit = '')
{
    $units = array(
        'B' => 0,               // Byte
        'K' => 1,   'KB' => 1,  // Kilobyte
        'M' => 2,   'MB' => 2,  // Megabyte
        'G' => 3,   'GB' => 3,  // Gigabyte
        'T' => 4,   'TB' => 4,  // Terabyte
        'P' => 5,   'PB' => 5,  // Petabyte
        'E' => 6,   'EB' => 6,  // Exabyte
        'Z' => 7,   'ZB' => 7,  // Zettabyte
        'Y' => 8,   'YB' => 8   // Yottabyte
    );

    $value = 0;
    if ($bytes > 0) {

        if (!array_key_exists($unit, $units)) {

            $pow = floor(log($bytes) / log(1024));
            $unit = array_search($pow, $units);
        }

        $value = ($bytes / pow(1024, floor($units[$unit])));
    }

    $decimals = floor(abs($decimals));
    if ($decimals > 53) {

        $decimals = 53;
    }

    return sprintf('%.' . $decimals . 'f %s', $value, $unit);
}

/**
 * 格式化數值
 *
 * @param     float      $num           輸入的值
 * @param     integer    $formatType    格式化類型
 *
 * @return    string
 */
function formatNumber($num, $formatType = 0)
{
    switch ($formatType) {
        // 保留 2 位小數
        case 6:

            $num = number_format($num, 2, '.', ',');
            break;

        // 先四捨五入，不保留小數
        case 5:

            $num = number_format(round($num), 0, '.', ',');
            break;

        // 四捨五入，保留 1 位
        case 4:

            $num = number_format($num, 1, '.', ',');
            break;

        // 直接取整
        case 3:

            $num = number_format(intval($num), 0, '.', ',');
            break;

        // 不四捨五入，保留 1 位
        case 2:

            $num = substr(number_format($num, 2, '.', ','), 0, -1);
            break;

        // 保留不為 0 的尾數
        case 1:

            $num = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($num, 2, '.', ','));

            if (substr($num, -1) == '.') {
                $num = substr($num, 0, -1);
            }
            break;

        // 不處理
        case 0:
        default:

            // do something...
    }

    return $num;
}

/**
 * 將字串轉為駝峰顯示
 *
 * @param     string    $str    欲替换字串
 *
 * @return    string
 */
function strToCamel($str, $type = 'lower')
{
    if (!empty($str)) {

        switch (strtolower($type)) {
            /**
             * 小駝峰式
             */
            case 'lower':
            default:

                $str = preg_replace_callback(
                    '/-([\da-z])/i',
                    function($s)
                    {
                        return strtoupper($s[1]);
                    },
                    preg_replace('/_/', '-', $str)
                );
        }
    }

    return $str;
}

/**
 * 取得 Data URI scheme 資訊
 *
 * @param     string     $dataUri    data URI scheme
 *
 * @return    array
 */
function getDataUriInfo($dataUri)
{
    $result = array(
        'mime_type' => '',
        'charset' => '',
        'data' => ''
    );

    // data:[<MIME-type>][;charset=<encoding>][;base64],<data>
    if (
        preg_match(
            '/data:(?P<mime_type>image\/[^;]*)(?:;charset=(?P<charset>[^;]*)?)?;base64,(?P<data>[^;]*)/',
            $dataUri,
            $matches
        )
    ) {
        $result = array_intersect_key($matches, $result);
    }

    return $result;
}

/**
 * 檢查經過 base64 編碼後的圖片是否有效
 *
 * @param     string     $dataUri    圖片經過 base64 編碼後的字串
 *
 * @return    boolean
 */
function chkDataUriImg($dataUri)
{
    // data:[<MIME-type>][;charset=<encoding>][;base64],<data>
    $dataUriInfo = getDataUriInfo($dataUri);

    if (empty($dataUriInfo['mime_type']) && false === stripos($dataUri, $dataUriInfo['mime_type'])) {

        return false;
    }

    isset($dataUriInfo['data']) && $imageContents = base64_decode($dataUriInfo['data']);

    // 檢查是否為 base64 編碼
    if (!is_null($imageContents) && false === $imageContents) {

        return false;
    }

    // 檢查是否為圖片
    if (false === @imagecreatefromstring($imageContents)) {

        return false;
    }

    return true;
}

/**
 * 將檔案轉換為 Data URI scheme
 *
 * @param     string     $filePath    檔案路徑
 * @param     string     $mimeType    MIME TYPE
 * @param     string     $charset     字元集
 *
 * @return    mixed
 */
function convertFileToDataUri($filePath, $mineType = null, $charset = 'utf-8')
{
    $dataUri = '';

    if (!is_file($filePath)) {

        return false;
    }

    // 取得檔案的 MIME TYPE
    if (is_null($mineType)) {

        $mineType = getFileMiMeType($filePath);

        if (empty($mineType)) {

            $mineType = 'application/octet-stream';
        }
    }

    $imgStr = @file_get_contents($filePath);
    if (false == $imgStr) {

        return false;
    }

    // data:[<MIME-type>][;charset=<encoding>][;base64],<data>
    $dataUri = sprintf('data:%s;charset=%s;base64,%s', $mineType, $charset, base64_encode($imgStr));

    return $dataUri;
}

/**
 * 檢測檔案的 MIME TYPE
 *
 * @param     string     $filePath    檔案路徑
 *
 * @return    mixed
 */
function getFileMiMeType($filePath)
{
    if (function_exists('mime_content_type')) {

        return mime_content_type($filePath);

    } elseif (function_exists('finfo_open')) {

        $finfo = finfo_open(FILEINFO_MIME);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType;

    } else {

        define('APACHE_MIME_TYPES_URL', 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');

        $mimeType = '';
        $mimeList = '';
        $mimeArr = array();

        if (is_callable('curl_init')) {

            $ch = curl_init();

            $opts = array(
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
                CURLOPT_URL => APACHE_MIME_TYPES_URL
            );

            curl_setopt_array($ch, $opts);
            $mimeList = curl_exec($ch);

            if (curl_errno($ch)) {

                return $mimeType;
            }

            curl_close($ch);

        } else {

            $mimeList = @file_get_contents(APACHE_MIME_TYPES_URL);
        }

        if (!empty($mimeList)) {

            foreach (@explode(chr(10), $mimeList) as $x) {

                if (isset($x[0]) &&

                    $x[0] !== '#' &&
                    preg_match_all('#([^\s]+)#', $x, $out) &&
                    isset($out[1]) &&
                    ($c = count($out[1])) > 1) {

                    for ($i = 1; $i < $c; $i++) {

                        $mimeArr[$out[1][$i]] = $out[1][0];
                    }
                }
            }

            ksort($mimeArr);
        }

        if (!empty($mimeArr)) {

            $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);

            if (isset($mimeArr[$fileExt])) {

                $mimeType = $mimeArr[$fileExt];
            }
        }

        return $mimeType;
    }
}

/**
 * 把 XML 字串轉換為 HTML 實體
 *
 * @param     string     $string    規定要轉換的字串
 *
 * @return    string
 */
function xmlEntities($string)
{
    if (isPhp('5.4.0')) {

        return htmlentities($string, ENT_QUOTES | ENT_XML1, 'UTF-8');

    } else {

        static $patterns = null;
        static $replacements = null;
        static $translation = null;

        if ($translation === null) {

            $translation = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);

            foreach ($translation as $k => $v) {
                $patterns[] = '/' . $v . '/';
                $replacements[] = '&#' . ord($k) . ';';
            }
        }

        return preg_replace($patterns, $replacements, htmlentities($string, ENT_QUOTES, 'UTF-8'));
    }
}

/**
 * 重組檔案陣列
 *
 * @param     array      $filesArray    $_FILES[]
 * @param     boolean    $top           是否為頂層
 *
 * @return    array
 */
function restFilesArray(array $filesArray, $top = true)
{
    $files = array();

    foreach ($filesArray as $name => $file) {

        $subName = $top ? $file['name'] : $name;

        if (is_array($subName)) {

            foreach (array_keys($subName) as $key) {

                $files[$name][$key] = array(
                    'name' => $file['name'][$key],
                    'type' => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error'  => $file['error'][$key],
                    'size' => $file['size'][$key],
                );

                $files[$name] = restFilesArray($files[$name], false);
            }

        } else {

            $files[$name] = $file;
        }
    }

    return $files;
}

/**
 * 建立數值區間
 *
 * @param     integer    $min          最小值
 * @param     integer    $max          最大值
 * @param     integer    $step         步進值
 * @param     boolean    $group        區間群組化
 * @param     boolean    $numPrefix    數值前綴
 * @param     boolean    $separator    數值分隔符號
 *
 * @return    array
 */
function createRanges($min, $max, $step, $group = false, $numPrefix = '', $separator = '-')
{
    $intRange = $max - $min;
    $intIncrement = abs($intRange / $step);
    $arrRanges = array();

    if ($max == 0) {

        return false;
    }

    for ($i = 0; $i <= ($max + $step); $i += $step) {

        if ($i == 0) {

            $arrRanges[] = $min;

        } else {
            // $val = $min + ($i * $intIncrement);
            $val = $min + $i;

            if ($val >= $max) {

                $arrRanges[] = $max;
                break;

            } else {

                $arrRanges[] = $val;
            }
        }
    }

    if ($arrRanges && $group) {

        $range = array();

        foreach ($arrRanges as $intIndex => $intRange) {
            $intMin = $intIndex == 0
                    ? $intRange
                    : $arrRanges[($intIndex - 1)];
            $intMax = $intIndex == 0 && isset($arrRanges[($intIndex + 1)])
                    ? $arrRanges[($intIndex + 1)]
                    : $intRange;

            $range[$intMin . '_' . $intMax] = $numPrefix . $intMin . $separator
                                            . $numPrefix . $intMax;
        }

        return $range;
    }

    return $arrRanges;
}


/**
 * 將字串中部分文字替換為其他字元
 *
 * @param     string     $string        欲轉換的字串
 * @param     string     $markChar      轉換字元符號
 * @param     integer    $markOffset    替換開始位置
 * @param     integer    $markLength    替換長度
 *
 * @return    string
 */
function stringMark($string, $markChar = '*', $markOffset = null, $markLength = null)
{
    if (empty($string) || $markLength === 0) {

        return $string;
    }

    // 將欲轉換的字串轉為陣列
    $strLen = mb_strlen($string);
    $strArr = array();
    for ($i = 0; $i < $strLen; $i++) {

        $strArr[] = mb_substr($string, $i, 1);
    }

    // 初始化替換開始位置
    $markOffset = !is_null($markOffset) ? intval($markOffset) : floor($strLen / 2);

    // 初始化替換長度
    $markLength = !is_null($markLength) ? intval($markLength) : floor($strLen / 2);

    // 設定邊界
    if ($markOffset < 0) {

        $markOffset = max($strLen - ($markOffset + $markLength) - 1, 0);
        $markLength = $markOffset + $strLen;

    } elseif ($markOffset == $strLen) {

        $markOffset -= 1;
    }
    if ($markLength + $markOffset > $strLen) {

        $markLength = max($strLen - $markOffset, 1);
    }

    // 建立替換字元陣列
    $markArr = array_fill($markOffset, $markLength, $markChar);

    // 從指定位置替換原有字串陣列
    array_splice($strArr, $markOffset, $markLength, $markArr);

    return implode('', $strArr);
}

/**
 * 取得 URL 的參數陣列
 *
 * @param     string    $url    網址
 *
 * @return    void
 */
function getUrlParam($url)
{
    $paramArr = array();

    $query = explode('&', parse_url($url, PHP_URL_QUERY));
    if (!empty($query)) {

        foreach ($query as $val) {

            if ($val == '') {

                continue;
            }

            list($paramKey, $paramValue) = explode('=', $val);

            $paramArr[$paramKey] = $paramValue;
        }
    }

    return $paramArr;
}