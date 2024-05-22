<?php if (!defined('IN_DUS')) die('No direct script access allowed');

class outCssCompress
{
    protected $min_file_list = array();
    protected $compress_log_path = 'temp/log/css-compare.log';
    protected $write_file = false;

    public static function minify($html, $outDir = '')
    {
        $min = new self($html, $outDir);
        return $min->process();
    }

    /**
     * 建構子
     *
     * @access    private
     *
     * @param     string    $html      樣版頁面內容
     * @param     string    $outDir    檔案輸出路徑
     *
     * @return    string
     */
    public function __construct($html, $outDir = '')
    {
        $this->_html = $html;
        $this->_outDir = $outDir;
        $this->_rootPath = defined('ROOT_PATH') ? ROOT_PATH : '';

        if (is_file($this->_rootPath . $this->compress_log_path)) {

            $fp = fopen($this->_rootPath . $this->compress_log_path, 'r');
            while ($line = fgets($fp)) {

                $row = explode(chr(9), trim($line));
                $this->min_file_list[$row[1]] = $row[0];
            }
            fclose($fp);
        }
    }

    /**
     * 解構子
     *
     * @access    private
     *
     * @return    void
     */
    public function __destruct()
    {
        if ($this->write_file) {

            $str = '';
            foreach ($this->min_file_list as $key => $val) {

                $str .= $val . chr(9) . $key . chr(13) . chr(10);
            }
            file_put_contents($this->_rootPath . $this->compress_log_path, trim($str));
        }
    }

    /**
     * 主要處理程序
     *
     * @access    private
     *
     * @return    string
     */
    public function process()
    {
        /* 修正產生 css 壓縮檔 */
        $this->_html = preg_replace_callback(
            '/<link\s.*(rel\s*=\s*["|\']stylesheet["|\']|type\s*=\s*["|\']text\/css["|\']).*?>/i',
            array($this, '_linkPackerCode'),
            $this->_html);

        // 判斷指定的文件是否可寫
        $mark = $this->_fileModeInfo($this->_outDir);
        if ($mark & 1 && $mark & 2 && $mark & 4) {

            // 檔案合併
            $this->_html = $this->_mergeLinkFile($this->_html);
        }

        $this->_html = preg_replace('/(<\/?[^>]*>)\\s+$/m', '$1', $this->_html);

        return $this->_html;
    }

    /**
     *
     *
     * @access    protected
     *
     * @param     string    $source
     *
     * @return    string
     */
    protected function _mergeLinkFile($source)
    {
        $outArr = array();
        preg_match_all('/<link\s.*(data\-merge\s*=\s*["|\'](.*)["|\']).*?>/i', $source, $linkSrc);

        foreach ($linkSrc[0] as $key => $val) {

            if (preg_match('/\s*href=[\'\"]*([^\'"]*)[\'"]*/is', $val, $src)) {

                $srcArr[$linkSrc[2][$key]][] = $linkSrc[0][$key];
                $outArr[$linkSrc[2][$key]][] = $src[1];
            }
        }

        foreach ($outArr as $key => $val) {

            $fileTime = $filePath = array();
            // 取得檔案合併名稱
            if (is_file($this->_outDir . $key . '.txt')) {

                $fp = fopen($this->_outDir . $key . '.txt', 'r');
                while ($line = fgets($fp)) {

                    $fileTime[] = trim($line);
                    $filePath[] = parse_url(trim($line), PHP_URL_PATH);
                }
                fclose($fp);
            }

            $val = array_unique($val);
            foreach ($val as $src) {

                if (!in_array($src, $fileTime)) {

                    $cssPath = parse_url($src, PHP_URL_PATH);
                    if (in_array($cssPath, $filePath)) {

                        @unlink($this->_outDir . $key . '.txt');
                    }
                }
            }
        }

        foreach ($outArr as $key => $val) {

            $fileTime = $filePath = array();
            // 取得檔案合併名稱
            if (is_file($this->_outDir . $key . '.txt')) {

                $fp = fopen($this->_outDir . $key . '.txt', 'r');
                while ($line = fgets($fp)) {

                    $fileTime[] = trim($line);
                    $filePath[] = parse_url(trim($line), PHP_URL_PATH);
                }
                fclose($fp);
            }

            $code = '';
            foreach ($val as $src) {

                if (!in_array($src, $fileTime)) {

                    // 寫入合併記錄
                    file_put_contents($this->_outDir . $key . '.txt', $src . chr(13) . chr(10), FILE_APPEND);
                    // 讀取 css
                    $cssPath = parse_url($src, PHP_URL_PATH);
                    $code .= file_get_contents($cssPath);
                }
            }

            $fOut = $this->_outDir . $key . '.min.css';
            if ($code != '') {

                // 先將壓縮過的 css 刪除掉
                @unlink($fOut);

                $code .= PHP_EOL . '/*';

                // 將 css 插入
                file_put_contents($fOut, $code, FILE_APPEND);
            }

            if (is_file($fOut) && isset($srcArr[$key])) {

                $source = str_replace(
                    $srcArr[$key],
                    '<link href="' . $fOut . '?t=' . filemtime($fOut) . '" rel="stylesheet">',
                    $source
                );
            }
        }

        $source = preg_replace_callback(
            '/<link\s.*(rel\s*=\s*["|\']stylesheet["|\']|type\s*=\s*["|\']text\/css["|\']).*?>/i',
            array($this, '_linkMergeFile'),
            $source);

        return $source;
    }

    /**
     *
     *
     * @access    private
     *
     * @param     array      $m    過濾陣列
     *
     * @return    string
     */
    private function _linkMergeFile($m)
    {
        static $links = array();

        if (in_array($m[0], $links) == false) {

            $links[] = $m[0];

            return $m[0];
        }

        return '';
    }

    /**
     * 壓縮原始碼
     *
     * @access    protected
     *
     * @param     array        $m    過濾陣列
     *
     * @return    string
     */
    protected function _linkPackerCode($m)
    {
        static $links = array();

        if (in_array($m[0], $links) == false) {

            $links[] = $m[0];

            return preg_replace_callback(
                '/href=[\'\"]*([^\'\"]*)[\'\"]*/is',
                array($this, '_linkPackerFile'),
                $m[0]
            );
        }

        return '';
    }

    /**
     * 產生壓縮檔案
     *
     * @access    protected
     *
     * @param     array        $m    過濾陣列
     *
     * @return    string
     */
    protected function _linkPackerFile($m)
    {
        $parse = parse_url($m[1]);
        $fSrc = $parse['path'];

        // 判斷指定原輸出的 .css 檔案是否存在
        if (!preg_match('/^((http|https):)?\/\//i', $m[1]) && is_file($fSrc)) {

            $info = pathinfo($fSrc);
            if (version_compare(PHP_VERSION, '5.2') < 0) {

                $info['filename'] = substr($info['basename'], 0, strrpos($info['basename'], '.'));
            }

            if ($info['extension'] == 'css') {

                $data = isset($parse['query']) ? parse_str($parse['query']) : array();

                if (!preg_match('/.min$/i', $info['filename'])) {

                    $fSrc = $info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'];
                    $fOut = $info['dirname'] . '/' . $info['filename'] . '.min.' . $info['extension'];

                    if (isset($this->min_file_list[$fSrc]) && $this->min_file_list[$fSrc] == filemtime($fSrc)) {

                        // 判斷指定輸出的 .css 檔案是否存在
                        if (!is_file($fOut)) {

                            file_put_contents($fOut, $this->compressCSS(file_get_contents($fSrc)));
                        }

                    } else {

                        // 判斷指定的文件是否可寫
                        $mark = $this->_fileModeInfo($info['dirname']);
                        if (!($mark & 2)) {

                            return $m[0];
                        }

                        // 壓縮 css 檔案內容後儲存
                        file_put_contents($fOut, $this->compressCSS(file_get_contents($fSrc)));

                        $this->write_file = true;
                        $this->min_file_list[$fSrc] = filemtime($fSrc);

                    }

                    $data['t'] = filemtime($fSrc);

                } else {

                    $fOut = $info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'];
                    $data['t'] = filemtime($fSrc);
                }

                $query = http_build_query($data, '', '&amp;');

                return 'href="' . $fOut . (!empty($query) ? '?' . $query : '') . '"';
            }

        }

        return $m[0];
    }

    /**
     * 壓縮 css 內容
     *
     * @access    protected
     *
     * @param     string       $css    檔案內容
     *
     * @return    string
     */
    protected function compressCSS($css)
    {
        // 強制將 calc() 前後保留空白
        if (strpos($css, 'calc(') !== false) {
            $css = preg_replace_callback(
                '/(?<=[\s:])calc\(\s*(.*?)\s*\)/',
                function ($matches)
                {
                    return 'calc(' . preg_replace('/\s+/', chr(26), $matches[1]) . ')';
                },
                $css
            );
        }

        $patterns = array();
        $replacements = array();

        // 刪除多行註解
        $patterns[] = '/\/\*.*?\*\//s';
        $replacements[] = '';

        // 刪除 跳位字元 換行
        $patterns[] = '/\r\n|\r|\n|\t/';
        $replacements[] = '';

        // 刪除空格
        $patterns[] = '/([!{}:;>+\\(\\[,])\\s+/';
        $replacements[] = '$1';

        // 刪除 {} 前後的空格
        $patterns[] = '/\\s*{\\s*/';
        $replacements[] = '{';

        $patterns[] = '/;?\\s*}\\s*/';
        $replacements[] = '}';

        // +1.2em to 1.2em, +.8px to .8px, +2% to 2%
        $patterns[] = '/((?<!\\\\)\:|\s)\+(\.?\d+)/S';
        $replacements[] = '$1$2';

        // Remove leading zeros from integer and float numbers preceded by : or a white-space
        // 000.6 to .6, -0.8 to -.8, 0050 to 50, -01.05 to -1.05
        $patterns[] = '/((?<!\\\\)\:|\s)(\-?)0+(\.?\d+)/S';
        $replacements[] = '$1$2$3';

        // Remove trailing zeros from float numbers preceded by : or a white-space
        // -6.0100em to -6.01em, .0100 to .01, 1.200px to 1.2px
        $patterns[] = '/((?<!\\\\)\:|\s)(\-?)(\d?\.\d+?)0+([^\d])/S';
        $replacements[] = '$1$2$3$4';

        // Remove trailing .0 -> -9.0 to -9
        $patterns[] = '/((?<!\\\\)\:|\s)(\-?\d+)\.0([^\d])/S';
        $replacements[] = '$1$2$3';

        // Replace 0 length numbers with 0
        $patterns[] = '/((?<!\\\\)\:|\s)\-?\.?0+([^\d])/S';
        $replacements[] = '${1}0$2';

        // Replace 0 length units 0(px,em,%) with 0.
        $patterns[] = '/(^|[^0-9])(?:0?\.)?0(?:em|ex|ch|rem|vw|vh|vm|vmin|cm|mm|in|px|pt|pc|%|deg|g?rad|k?hz)(?!\s?+\{)/iS';
        $replacements[] = '${1}0';

        // minimize hex colors
        $patterns[] = '/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i';
        $replacements[] = '$1#$2$3$4$5';

        $patterns[] = '/\/\*.*?/im';
        $replacements[] = '';

        $patterns[] = '/\x1A/';
        $replacements[] = ' ';

        return preg_replace($patterns, $replacements, $css);
    }

    /**
     * 檔案或資料夾權限檢查
     *
     * 回傳值的取值範圍為 0 <= x <= 15
     * 每個值表示的含義可由四位二進制數字組合推論出
     * 回傳值在二進制計算法中，四位由高到低分別代表
     * 執行 rename 函數權限, 追加內容權限, 寫入權限, 讀取權限
     *
     * @access    protected
     *
     * @param     string       $filePath      檔案路徑
     *
     * @return    integer
     */
    protected function _fileModeInfo($filePath)
    {
        static $pathMark = array();

        if (isset($pathMark[$filePath])) {

            return $pathMark[$filePath];
        }

        /* 如果不存在，則不可讀、不可寫、不可改 */
        if (!file_exists($filePath)) {

            $pathMark[$filePath] = false;
            return $pathMark[$filePath];
        }

        $pathMark[$filePath] = 0;

        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {

            if (is_dir($filePath)) { /* 如果是目錄 */

                /* 檢查目錄是否可讀 */
                $dir = @opendir($filePath);
                if ($dir === false) {

                    return $pathMark[$filePath]; // 如果目錄打開失敗，直接返回目錄不可修改、不可寫、不可讀
                }
                if (@readdir($dir) !== false) {

                    $pathMark[$filePath] ^= 1; // 目錄可讀 001，目錄不可讀 000
                }
                @closedir($dir);

                /* 測試文件 */
                $testFile = $filePath . '/cf_test.txt';

                /* 檢查目錄是否可寫 */
                $fp = @fopen($testFile, 'wb');
                if ($fp === false) {

                    return $pathMark[$filePath]; // 如果目錄中的文件創建失敗，返回不可寫。
                }
                if (@fwrite($fp, 'directory access testing.') !== false) {

                    $pathMark[$filePath] ^= 2; // 目錄可寫可讀011，目錄可寫不可讀 010
                }
                @fclose($fp);

                @unlink($testFile);

                /* 檢查目錄是否可修改 */
                $fp = @fopen($testFile, 'ab+');
                if ($fp === false) {

                    return $pathMark[$filePath];
                }
                if (@fwrite($fp, "modify test.\r\n") !== false) {

                    $pathMark[$filePath] ^= 4;
                }
                @fclose($fp);

                /* 檢查目錄下是否有執行 rename() 函數的權限 */
                if (@rename($testFile, $testFile) !== false) {

                    $pathMark[$filePath] ^= 8;
                }
                @unlink($testFile);

            } elseif (is_file($filePath)) { /* 如果是文件 */

                /* 以讀方式打開 */
                $fp = @fopen($filePath, 'rb');
                if ($fp) {

                    $pathMark[$filePath] ^= 1; // 可讀 001
                }
                @fclose($fp);

                /* 試著修改文件 */
                $fp = @fopen($filePath, 'ab+');
                if ($fp && @fwrite($fp, '') !== false) {

                    $pathMark[$filePath] ^= 6; // 可修改可寫可讀 111，不可修改可寫可讀011...
                }
                @fclose($fp);

                /* 檢查目錄下是否有執行 rename() 函數的權限 */
                if (@rename($filePath, $filePath) !== false) {

                    $pathMark[$filePath] ^= 8;
                }
            }

        } else {

            if (@is_readable($filePath)) {

                $pathMark[$filePath] ^= 1;
            }

            if (@is_writable($filePath)) {
                $pathMark[$filePath] ^= 14;
            }
        }

        return $pathMark[$filePath];
    }
}
