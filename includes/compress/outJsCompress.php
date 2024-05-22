<?php if (!defined('IN_DUS')) die('No direct script access allowed');

require_once('class.JavaScriptPacker.php');

class outJsCompress
{
    protected $jsMinFile = array();
    protected $jsFileLog = 'temp/log/js-compress.log';
    protected $writeFile = false;

    public static function minify($html, $outDir = '')
    {
        $min = new self($html, $outDir);
        return $min->process();
    }

    public function __construct($html, $outDir = '')
    {
        $this->_html = $html;
        $this->_outDir = $outDir;
        $this->_rootPath = defined('ROOT_PATH') ? ROOT_PATH : '';

        if (is_file($this->_rootPath . $this->jsFileLog)) {

            $fp = fopen($this->_rootPath . $this->jsFileLog, 'r');
            while ($line = fgets($fp)) {

                $row = explode(chr(9), trim($line));
                $this->jsMinFile[$row[1]] = $row[0];
            }
            fclose($fp);
        }
    }

    public function __destruct()
    {
        if ($this->writeFile) {

            $str = '';
            foreach ($this->jsMinFile as $key => $val) {

                $str .= $val . chr(9). $key . chr(13) . chr(10);
            }
            file_put_contents($this->_rootPath . $this->jsFileLog, trim($str));
        }
    }

    /**
     * 構造子
     *
     * @return string
     */
    public function process()
    {
        /* 修正產生 js 壓縮檔 */
        $this->_html = preg_replace_callback(
            '/(<script\b[^>]*?>)([\s\S]*?)<\/script>/is',
            array($this, '_scriptPackerCode'),
            $this->_html);

        // 判斷指定的文件是否可寫
        $mark = $this->_fileModeInfo($this->_outDir);
        if ($mark & 1 && $mark & 2 && $mark & 4) {

            // 檔案合併
            $this->_html = $this->_mergeScriptFile($this->_html);
        }

        return $this->_html;
    }

    protected function _mergeScriptFile($source)
    {
        $outArr = $codeArr = array();
        preg_match_all('/(<script\b[^>]*?\\s*data\-merge=["|\'].*?["|\']*?>)([\s\S]*?)<\/script>/is', $source, $scriptSrc);
        foreach ($scriptSrc[1] as $key => $val) {

            if (preg_match("/\\s*data\-merge=['\"]*([^'\"]*)['\"]*/is", $val, $out)) {

                $srcArr[$out[1]][] = $scriptSrc[0][$key];
                if (preg_match("/\\s*src=['\"]*([^'\"]*)['\"]*/is", $val, $src)) {

                    $outArr[$out[1]][] = $src[1];

                } else {

                    $codeArr[$out[1]][] = $scriptSrc[2][$key];
                }
            }
        }

        foreach ($codeArr as $key => $val) {

            // 判斷指定原輸出的 .js 檔案是否存在
            $fOut = $this->_outDir . $key . '.min.js';
            if (!is_file($this->_outDir . $fOut)) {

                $jCode = '';
                foreach ($val as $code) {

                    $jCode .= $code != '' ? $code . chr(13) . chr(10) . ';' . chr(13) . chr(10) : '';
                }

                // 先將壓縮過的 javascript 刪除掉
                @unlink($fOut);
                // 寫入 javascript
                file_put_contents($fOut, $jCode);
            }

            if (isset($srcArr[$key])) {

                $source = str_replace($srcArr[$key], '<script src="' . $fOut . '?t=' . filemtime($fOut) . '"></script>', $source);
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

                    $jsPath = parse_url($src, PHP_URL_PATH);
                    if (in_array($jsPath, $filePath)) {

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

            $jCode = '';
            foreach ($val as $src) {

                if (!in_array($src, $fileTime)) {

                    // 寫入合併記錄
                    file_put_contents($this->_outDir . $key . '.txt', $src . chr(13) . chr(10), FILE_APPEND);
                    // 讀取 javascript
                    $jsPath = parse_url($src, PHP_URL_PATH);
                    $jCode .= file_get_contents($jsPath) . chr(13) . chr(10) . ';' . chr(13) . chr(10);
                }
            }

            $fOut = $this->_outDir . $key . '.min.js';
            if ($jCode != '') {

                // 先將壓縮過的 javascript 刪除掉
                @unlink($fOut);
                // 將 javascript 插入
                file_put_contents($fOut, $jCode, FILE_APPEND);
            }

            if (is_file($fOut) && isset($srcArr[$key])) {

                $source = str_replace($srcArr[$key], '<script src="' . $fOut . '?t=' . filemtime($fOut) . '"></script>', $source);
            }
        }

        $source = preg_replace_callback(
            '/(<script\b[^>]*?>)([\s\S]*?)<\/script>/is',
            array($this, '_scriptMergeFile'),
            $source);

        return $source;
    }

    protected function _scriptMergeFile($m)
    {
        static $scripts = array();

        if (in_array($m[0], $scripts) == false) {

            $scripts[] = $m[0];

            return $m[0];
        }

        return '';
    }

    protected function _scriptPackerCode($m)
    {
        static $scripts = array();

        if (in_array($m[0], $scripts) == false) {

            $scripts[] = $m[0];

            $openScript = $m[1];

            $js = $m[2];

            if (!preg_match('/type="application\/ld\+json"/is', $openScript)) {

                $openScript = preg_replace_callback('/(src)=[\'\"]*([^\'\"]*)[\'\"]*/is', array($this, '_scriptPackerFile'), $openScript);
                $openScript = preg_replace_callback('/(data-main)=[\'\"]*([^\'\"]*)[\'\"]*/is', array($this, '_handleRequireConfig'), $openScript);

                $packer = new JavaScriptPacker($js, 62, true, false);
                $js = $this->_escapeEncode(trim($packer->pack()));
                $js = $js != '' ? PHP_EOL . $js . PHP_EOL : '';

            } else {

                $packer = new JavaScriptPacker($js, 0, true, false);
                $js = trim($packer->pack());
                $js = $js != '' ? $js : '';
            }

            return $openScript . $js . '</script>';
        }

        return '';
    }

    protected function _scriptPackerFile($m)
    {
        $parse = parse_url($m[2]);
        $fSrc = $parse['path'];
        $info = pathinfo($fSrc);

        // 判斷指定原輸出的 .js 檔案是否存在
        if (!preg_match('/^((http|https):)?\/\//i', $fSrc) && is_file($fSrc) && $info['extension'] == 'js') {

            $query = array();
            if (isset($parse['query'])) {

                parse_str($parse['query'], $query);
            }

            $query['t'] = filemtime($fSrc);

            $fSrc = $info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'];
            $fOut = $info['dirname'] . '/' . $info['filename'] . '.min.' . $info['extension'];

            if (!preg_match('/.min$/i', $info['filename'])) {

                // 判斷指定的文件是否可寫
                $mark = $this->_fileModeInfo($info['dirname']);
                if (!($mark & 2)) {

                    $query = http_build_query($query, '', '&amp;');
                    return $m[1] . '="' . $fSrc . '?' . $query . '"';
                }

                $isBuildFile = true;

                if (isset($this->jsMinFile[$fSrc]) && $this->jsMinFile[$fSrc] == $query['t']) {

                    // 判斷指定輸出的 .js 檔案是否存在
                    if (is_file($fOut)) {

                        $isBuildFile = false;
                    }

                } else {

                    $this->writeFile = true;
                    $this->jsMinFile[$fSrc] = $query['t'];
                }

                if ($isBuildFile) {

                    $packer = new JavaScriptPacker(file_get_contents($fSrc), 62, true, false);
                    file_put_contents($fOut, $this->_escapeEncode(trim($packer->pack())));
                }

            } else {

                $fOut = $fSrc;
            }

            $query = http_build_query($query, '', '&amp;');
            return $m[1] . '="' . $fOut . '?' . $query . '"';
        }

        return $m[0];
    }

    protected function _handleRequireConfig($m)
    {
        $parse = parse_url($m[2]);
        $configFilePath = $parse['path'];
        $info = pathinfo($configFilePath);

        // 判斷指定原輸出的 .js 檔案是否存在
        if (
            !preg_match('/^((http|https):)?\/\//i', $configFilePath) &&
            is_file($configFilePath) &&
            $info['extension'] == 'js' &&
            !preg_match('/.min$/i', $info['filename'])
        ) {

            $isBuildMainFile = true;
            $sourceFilePath = $info['dirname'] . '/' . $info['filename'] . '.' . $info['extension'];
            $outputFilePath = $info['dirname'] . '/' . $info['filename'] . '.min.' . $info['extension'];

            $mainFileQuery = array();
            if (isset($parse['query'])) {

                parse_str($parse['query'], $mainFileQuery);
            }

            $lastUpdateTime[] = filemtime($sourceFilePath);

            // 判斷指定的文件是否可寫
            $mark = $this->_fileModeInfo($info['dirname']);
            if (!($mark & 2)) {

                $mainFileQuery['t'] = max($lastUpdateTime);
                $mainFileQuery = http_build_query($mainFileQuery, '', '&amp;');
                return $m[1] . '="' . $sourceFilePath . '?' . $mainFileQuery . '"';
            }

            $requireConfig = trim(file_get_contents($sourceFilePath));
            $requireConfig = preg_replace('/\\/\\/[^\\n\\r]*[\\n\\r]/', '', $requireConfig);
            $requireConfig = preg_replace('/\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\//', '', $requireConfig);
            $requireConfig = preg_replace('/require\.config\((.*)\);/is', '$1', $requireConfig);
            $requireConfig = json_decode($requireConfig, true);

            if ($requireConfig) {

                $isBuildConfig = false;

                $extension = '.js';
                if (!isset($requireConfig['baseUrl'])) {

                    $mainFileQuery['t'] = max($lastUpdateTime);
                    $mainFileQuery = http_build_query($mainFileQuery, '', '&amp;');
                    return $m[1] . '="' . $sourceFilePath . '?' . $mainFileQuery . '"';
                }

                $baseUrl = $requireConfig['baseUrl'];

                if (isset($requireConfig['autoLoadExtra']['urlPath'], $requireConfig['autoLoadExtra']['prefix'])) {

                    $prefix = $requireConfig['autoLoadExtra']['prefix'];
                    $urlPath = $requireConfig['autoLoadExtra']['urlPath'];

                    $autoLoadPath = $baseUrl . '/' . $urlPath;
                    $jsLoadFile = array_filter(
                            glob_recursive($autoLoadPath . '/*'),
                            'is_file'
                        );
                    $jsLoadFile = array_filter(
                            array_map('basename', $jsLoadFile),
                            function ($v) {
                                return strtolower(pathinfo($v, PATHINFO_EXTENSION)) == 'js';
                            }
                        );

                    foreach ($jsLoadFile as $val) {

                        $val = pathinfo($val, PATHINFO_FILENAME);
                        $requireConfig['paths'][$prefix . $val] = $urlPath . '/' . $val;
                    }
                }

                if (isset($requireConfig['paths'])) {

                    foreach ($requireConfig['paths'] as $key => $val) {

                        if (!is_string($val) || !is_file(($jsFilePath = $baseUrl . '/' . $val) . $extension)) {

                            continue;
                        }

                        if ($query = parse_url($val, PHP_URL_QUERY)) {

                            parse_str($query, $query);

                        } else {

                            $query = array();
                        }

                        $fSrc = $jsFilePath . $extension;
                        $fOut = $jsFilePath . '.min' . $extension;

                        $query['t'] = filemtime($fSrc);

                        $lastUpdateTime[] = $query['t'];

                        if (preg_match('/.min$/i', $val)) {

                            $query = http_build_query($query, '', '&amp;');
                            $requireConfig['paths'][$key] = $val . $extension . '?' . $query;
                            continue;
                        }

                        $info = pathinfo($fSrc);

                        // 判斷指定的文件是否可寫
                        $mark = $this->_fileModeInfo($info['dirname']);
                        if (!($mark & 2)) {

                            $query = http_build_query($query, '', '&amp;');
                            $requireConfig['paths'][$key] = $val . $extension . '?' . $query;
                            continue;
                        }

                        $isBuildFile = true;

                        if (isset($this->jsMinFile[$fSrc]) && $this->jsMinFile[$fSrc] == $query['t']) {

                            // 判斷指定輸出的 .js 檔案是否存在
                            if (is_file($fOut)) {

                                $isBuildFile = false;
                            }

                        } else {

                            $this->writeFile = true;
                            $this->jsMinFile[$fSrc] = $query['t'];

                            $isBuildConfig = true;
                        }

                        if ($isBuildFile) {

                            $packer = new JavaScriptPacker(file_get_contents($fSrc), 62, true, false);
                            file_put_contents($fOut, $this->_escapeEncode(trim($packer->pack())));
                        }

                        $query = http_build_query($query, '', '&amp;');
                        $requireConfig['paths'][$key] = $val . '.min' . $extension . '?' . $query;;
                    }
                }

                if ($isBuildConfig) {

                    file_put_contents($outputFilePath, 'require.config(' . json_encode($requireConfig) . ');');
                }

            } else {

                $outputFilePath = $sourceFilePath;
            }

            $mainFileQuery['t'] = max($lastUpdateTime);
            $mainFileQuery = http_build_query($mainFileQuery, '', '&amp;');
            return $m[1] . '="' . $outputFilePath . '?' . $mainFileQuery . '"';
        }

        return $m[0];
    }

    /**
     * 將 javascript 中文字串部分進行 escape 編碼
     *
     * @access    private
     * @param     string     $js    javascript
     *
     * @return    string
     */
    private function _escapeEncode($js)
    {
        preg_match_all('/[\xc2-\xdf][\x80-\xbf]+|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}|[\x01-\x7f]+/', $js, $r);

        $str = $r[0]; // 匹配 UTF-8
        $length = count($str);
        for ($i = 0; $i < $length; $i++) {

            $value = ord($str[$i][0]);
            if ($value < 223) {

                $str[$i] = utf8_decode($str[$i]);

            } else {

                $str[$i] = "\u" . strtolower(bin2hex(mb_convert_encoding($str[$i], 'UCS-2', 'UTF-8')));
            }
        }

        return implode('', $str);
    }

    /**
     * 文件或目錄權限檢查函數
     *
     * @access          public
     * @param           string  $filePath   文件路徑
     *
     * @return          int     返回值的取值範圍為{0 <= x <= 15}，每個值表示的含義可由四位二進制數組合推出。
     *                          返回值在二進制計數法中，四位由高到低分別代表
     *                          可執行rename()函數權限、可對文件追加內容權限、可寫入文件權限、可讀取文件權限。
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
