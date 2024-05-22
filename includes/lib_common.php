<?php if (!defined('IN_DUS')) die('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * 公用函數庫
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/**
 * 重寫 URL 地址
 *
 * @param     string     $app        執行程序
 * @param     array      $query      查詢參數陣列
 * @param     array      $params     額外參數值
 * @param     boolean    $rewrite    是否開啟 URL Rewrite
 *
 * @return    void
 */
function buildUri($app, array $query = array(), array $params = array(), $rewrite = null)
{
    if (is_null($rewrite)) {

        $rewrite = !defined('DUS_ADMIN') ? $GLOBALS['_CFG']['enable_url_rewrite'] : 0;
    }

    empty($app) && $app = basename(PHP_SELF, '.php');
    $app = trimSeparator($app);

    $query = array_merge(
        array(
            'page' => null
        ),
        $query
    );

    $params = array_merge(
        array(
            'ext' => '',
            'arg_separate' => ini_get('arg_separator.output'),
            'append' => array()
        ),
        $params
    );

    $uri = array();

    if (!defined('INIT_LANG_USE_SUBDOMAIN') && !(defined('DUS_ADMIN') && DUS_ADMIN == true)) {

        $root = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        substr($root, -1) != '/' && $root .= '/';

        if (
            isset($GLOBALS['_CFG']['lang_codes']) &&
            preg_match(
                '/^' . preg_quote($root, '/') . '(' . implode('|', $GLOBALS['_CFG']['lang_codes']) . ')?(?=(?!\/?[^\/]*\.)|(\/[^\/]*\.*))/i',
                $_SERVER['REQUEST_URI'],
                $lm
            )
        ) {

            isset($lm[1]) && $uri[1] = $lm[1];
        }
    }

    $uri = !empty($uri) ? implode('/', $uri) . '/' : '';

    if ($rewrite) {

        if ($rewrite == 2 && !empty($params['append'])) {

            $append = $params['append'];
            //$append = makeSemiangle($params['append']);
            //$append = preg_replace('/[\x20-\\x2f]|[\x3a-\x40]|[\x5b-\x60]|[\x7b-\x7e]/', '-', $append);
            $append = rtrim(preg_replace('/-+/', '-', $append) ,'-');
            $append = urlencode($append);
        }

        if ($app != 'index') {

            if (isset($query['action']) && $query['action'] == 'view') {

                switch ($app) {

                    // 預設
                    default:

                        $uri .= str_replace('_', '-', $app) . $params['ext'];
                        $uri = rtrim($uri . '?' . http_build_query($query, '', $params['arg_separate']), '?');

                        $params['ext'] = '';
                        break;
                }

            } else {

                switch ($app) {

                    // 預設
                    default:

                        $uri .= str_replace('_', '-', $app) . $params['ext'];
                        $uri = rtrim($uri . '?' . http_build_query($query, '', $params['arg_separate']), '?');

                        $params['ext'] = '';
                        break;
                }
            }

            $uri .= $params['ext'];
        }

    } else {

        if ($app != 'index') {

            $uri .= $app . '.php';
        }

        $uri .= rtrim('?' . http_build_query($query, '', $params['arg_separate']), '?');
    }

    return $uri;
}

/**
 * 檢查檔案類型
 *
 * @param     string    $filename         檔案名稱
 * @param     string    $realName         真實檔案名稱
 * @param     string    $limitExtTypes    允許的檔案類型
 *
 * @return    string
 */
function checkFileType($fileName, $realName = '', $limitExtTypes = '')
{
    if ($realName) {

        $extname = strtolower(substr($realName, strrpos($realName, '.') + 1));

    } else {

        $extname = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
    }

    $str = $format = '';

    $file = @fopen($fileName, 'rb');
    if ($file) {

        $str = @fread($file, 0x400); // 讀取前 1024 個字節
        @fclose($file);

    } else {

        if (stristr($fileName, ROOT_PATH) === false) {

            if ($extname == 'jpg'
                || $extname == 'jpeg'
                || $extname == 'gif'
                || $extname == 'png'
                || $extname == 'doc'
                || $extname == 'xls'
                || $extname == 'txt'
                || $extname == 'zip'
                || $extname == 'rar'
                || $extname == 'ppt'
                || $extname == 'pdf'
                || $extname == 'rm'
                || $extname == 'mid'
                || $extname == 'wav'
                || $extname == 'bmp'
                || $extname == 'swf'
                || $extname == 'chm'
                || $extname == 'sql'
                || $extname == 'cert'
                || $extname == 'docx'
                || $extname == 'xlsx'
                || $extname == 'pptx'
                || $extname == 'csv') {

                $format = $extname;
            }

        } else {

            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2) {

        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt') {

            $format = 'mid';

        } elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav') {

            $format = 'wav';

        } elseif (substr($str ,0, 3) == "\xFF\xD8\xFF") {

            $format = 'jpg';

        } elseif (substr($str ,0, 4) == 'GIF8' && $extname != 'txt') {

            $format = 'gif';

        } elseif (substr($str ,0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {

            $format = 'png';

        } elseif (substr($str ,0, 2) == 'BM' && $extname != 'txt') {

            $format = 'bmp';

        } elseif ((substr($str ,0, 3) == 'CWS' || substr($str ,0, 3) == 'FWS') && $extname != 'txt') {

            $format = 'swf';

        } elseif (substr($str ,0, 4) == "\xD0\xCF\x11\xE0") {

            // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str,0x200,4) == "\xEC\xA5\xC1\x00" || $extname == 'doc') {

                $format = 'doc';

            } elseif (substr($str,0x200,2) == "\x09\x08" || $extname == 'xls') {

                $format = 'xls';

            } elseif (substr($str,0x200,4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt') {

                $format = 'ppt';
            }

        } elseif (substr($str, 0, 14) == "\x50\x4B\x03\x04\x14\x00\x06\x00\x08\x00\x00\x00\x21\x00") {

            if (substr($str, 0x10, 8) == "\x87\x82\x81\x01\x00\x00\x8E\x05" || $extname == 'docx') {

                $format = 'docx';

            } elseif (substr($str, 0x10, 8) == "\x58\xA4\x7A\x01\x00\x00\x14\x06" || $extname == 'xlsx') {

                $format = 'xlsx';

            } elseif (substr($str, 0x10, 8) == "\x18\xF5\xC2\x01\x00\x00\x46\x0C" || $extname == 'pptx') {

                $format = 'pptx';
            }

        } elseif (substr($str ,0, 4) == "PK\x03\x04") {

            $format = 'zip';

        } elseif (substr($str ,0, 4) == 'Rar!' && $extname != 'txt') {

            $format = 'rar';

        } elseif (substr($str ,0, 4) == "\x25PDF") {

            $format = 'pdf';

        } elseif (substr($str ,0, 3) == "\x30\x82\x0A") {

            $format = 'cert';

        } elseif (substr($str ,0, 4) == 'ITSF' && $extname != 'txt') {

            $format = 'chm';

        } elseif (substr($str ,0, 4) == "\x2ERMF") {

            $format = 'rm';

        } elseif ($extname == 'sql') {

            $format = 'sql';

        } elseif ($extname == 'txt') {

            $format = 'txt';
        }
    }

    if ($limitExtTypes && stristr($limitExtTypes, '|' . $format . '|') === false) {

        $format = '';
    }

    return $format;
}

/**
 *  清除指定副檔名的樣版快取檔或編譯檔
 *
 * @param     boolean    $isCache    是否清除快取檔還是編譯檔
 * @param     string     $ext        需要刪除的檔案名稱，不包含副檔名
 *
 * @return    integer                回傳清除的檔案數量
 */
function clearTplFiles($isCache = true, $ext = '')
{
    $dirs = array();

    if ($isCache) {

        $dirs[] = TEMP_PATH . 'caches/*';

    } else {

        $dirs[] = TEMP_PATH . 'compiled/*';
    }

    $extLen = false;
    $count = 0;
    switch (gettype($ext)) {

        case 'array':

            if (!empty($ext)) {

                $extLen = true;
                foreach ($ext as $val) {

                    $extArr[] = preg_quote($val, '/');
                }
                $ext = implode('|', $extArr);
            }
            break;

        case 'string':
        case 'double':
        case 'integer':

            $extLen = true;
            $ext = preg_quote($ext, '/');
            break;
    }

    foreach ($dirs as $dir) {

        foreach (glob_recursive($dir) as $file) {

            if (is_dir($file) || in_array(basename($file), array('index.htm', 'index.html'))) {

                continue;
            }

            if (is_file($file)) {

                /* 如果有文件檔名則判斷是否匹配 */
                if ($extLen) {

                    if (preg_match('/\.(file\.|string\.)?' . $ext . '(\.dwt|\.lbi)?(\.cache)?\.php$/i', basename($file))
                        || preg_match('/^' . $ext . '\^[A-Za-z0-9_.]*\.php$/i', basename($file))) {

                        if (@unlink($file)) {

                            $count++;
                        }
                    }

                } else {

                    if (@unlink($file)) {

                        $count++;
                    }
                }
            }
        }
    }

    return $count;
}

/**
 * 清除樣版編譯檔
 *
 * @param     mixed    $ext    檔案名稱, 不包含副檔名
 *
 * @return    void
 */
function clearCompiledFiles($ext = null)
{
    return clearTplFiles(false, $ext);
}

/**
 * 清除快取檔案
 *
 * @param     mixed    $ext    檔案名稱, 不包含副檔名
 *
 * @return    void
 */
function clearCacheFiles($ext = null)
{
    return clearTplFiles(true, $ext);
}

/**
 * 清除樣版編譯檔和快取檔
 *
 * @param     mixed    $ext    檔案名稱, 不包含副檔名
 *
 * @return    void
 */
function clearAllFiles($ext = null)
{
    return clearTplFiles(false, $ext) + clearTplFiles(true,  $ext);
}

/**
 * 讀取靜態快取檔案
 *
 * @param     string    $cacheName    快取檔案名稱
 *
 * @return    array     $data
 */
function readStaticCache($cacheName)
{
    if ((DEBUG_MODE & 2) == 2) {

        return false;
    }
    static $result = array();
    if (!empty($result[$cacheName])) {

        return $result[$cacheName];
    }
    $cacheFilePath = TEMP_PATH . 'caches/static/static_cache_' . $cacheName . '.php';
    if (file_exists($cacheFilePath)) {

        include_once($cacheFilePath);
        $result[$cacheName] = $data;

        return $result[$cacheName];

    } else {

        return false;
    }
}

/**
 * 產生靜態快取檔案
 *
 * @param     string    $cacheName    快取檔案名稱
 * @param     string    $caches       檔案內容
 *
 * @return    void
 */
function writeStaticCache($cacheName, $caches)
{
    if ((DEBUG_MODE & 2) == 2) {

        return false;
    }
    $cacheFilePath = TEMP_PATH . 'caches/static/static_cache_' . $cacheName . '.php';
    $content = '<?php' . PHP_EOL
             . '$data = ' . var_export($caches, true) . ';' . PHP_EOL
             . '?>';
    file_put_contents($cacheFilePath, $content, LOCK_EX);
}

/**
 * 過濾和排序所有分類，返回一個帶有縮進級別的陣列
 *
 * @param     string     $app                應用名稱
 * @param     integer    $layerId            上層 ID
 * @param     array      $params             額外參數值
 *            boolean    -[is_cache]         是否快取
 *            string     -[primary_field]    主要節點名稱
 *            string     -[parent_field]     上層節點名稱
 *
 * @return    void
 */
function levelOptions($app, $layerId, $arr, array $params = array())
{
    $params = array_merge(
        array(
            'is_cache' => true,
            'primary_field' => 'cat_id',
            'parent_field' => 'parent_id'
        ),
        $params
    );

    static $staticOptions = array();

    if (isset($staticOptions[$app][$layerId])) {

        return $staticOptions[$app][$layerId];
    }

    if (!isset($staticOptions[$app][0])) {

        $level = $lastId = 0;
        $options = $idArray = $levelArray = array();

        $data = false;
        if ($params['is_cache']) {

            $data = readStaticCache('options_' . $app);
        }

        if ($data === false) {

            while (!empty($arr)) {

                foreach ($arr as $key => $value) {

                    $levelId = $value[$params['primary_field']];
                    if ($level == 0 && $lastId == 0) {

                        if ($value[$params['parent_field']] > 0) {

                            break;
                        }

                        $options[$levelId]          = $value;
                        $options[$levelId]['level'] = $level;
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {

                            continue;
                        }
                        $lastId  = $levelId;
                        $idArray = array($levelId);
                        $levelArray[$lastId] = ++$level;
                        continue;
                    }

                    if ($value[$params['parent_field']] == $lastId) {

                        $options[$levelId]          = $value;
                        $options[$levelId]['level'] = $level;
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {

                            if (end($idArray) != $lastId) {

                                $idArray[] = $lastId;
                            }
                            $lastId    = $levelId;
                            $idArray[] = $levelId;
                            $levelArray[$lastId] = ++$level;
                        }
                    }
                }

                $count = count($idArray);
                if ($count > 1) {

                    $lastId = array_pop($idArray);

                } elseif ($count == 1) {

                    if ($lastId != end($idArray)) {

                        $lastId = end($idArray);

                    } else {

                        $level = 0;
                        $lastId = 0;
                        $idArray = array();
                        continue;
                    }
                }

                if ($lastId && isset($levelArray[$lastId])) {

                    $level = $levelArray[$lastId];

                } else {

                    $level = 0;
                }
            }

            // 如果數組過大，不採用靜態緩存方式
            if (count($options) <= 2000) {

                writeStaticCache('options_' . $app, $options);
            }

        } else {

            $options = $data;
        }
        $staticOptions[$app][0] = $options;

    } else {

        $options = $staticOptions[$app][0];
    }

    if (!$layerId) {

        return $options;

    } else {

        if (empty($options[$layerId])) {

            return array();
        }

        $layerIdLevel = $options[$layerId]['level'];

        foreach ($options as $key => $value) {

            if ($key != $layerId) {

                unset($options[$key]);

            } else {

                break;
            }
        }

        $layerIdArray = array();
        foreach ($options as $key => $value) {

            if (($layerIdLevel == $value['level'] && $value[$params['primary_field']] != $layerId) ||
                ($layerIdLevel > $value['level'])) {

                break;

            } else {

                $layerIdArray[$key] = $value;
            }
        }
        $staticOptions[$app][$layerId] = $layerIdArray;

        return $layerIdArray;
    }
}

/**
 * 移動檔案
 *
 * @param     string     $oldFile    來源檔案路徑
 * @param     string     $newFile    目標檔案路徑
 * @param     boolean    $recover    是否覆寫
 *
 * @return    boolean
 */
function moveFile($oldFile, $newFile, $recover = true)
{
    $newFile = cIconv(convertSeparator($newFile), getServerOsCharset(), 'AUTO');
    if (is_file($newFile)) {

        if ($recover) {

            dropFile($newFile);

        } else {

            return false;
        }
    }
    $oldFile = cIconv(convertSeparator($oldFile), getServerOsCharset(), 'AUTO');

    return @rename($oldFile, $newFile);
}

/**
 * 刪除指定檔案
 *
 * @param     mixed     $filePath    檔案路徑或檔案路徑陣列
 * @param     string    $rootPath    檔案根目錄
 *
 * @return    mixed
 */
function dropFile($filePath, $rootPath = ROOT_PATH)
{
    $count = 0;

    if (!is_array($filePath)) {

        $filePath = explode(',', $filePath);
    }

    foreach ($filePath as $fileName) {

        if (
            strpos($fileName, '://') === false &&
            strpos(convertSeparator($fileName), convertSeparator($rootPath)) === false
        ) {
            $fileName = $rootPath . $fileName;
        }

        $fileName = cIconv($fileName, getServerOsCharset(), 'AUTO');

        if ($fileName && is_file($fileName)) {

            @unlink($fileName);
            ++$count;
        }
    }

    if ($count > 0) {

        clearstatcache();
        return $count;

    } else {

        return false;
    }
}

/**
 * 檔案或目錄權限檢查函數
 * 回傳值的取值範圍為 {0 <= x <= 15}，每個值表示的含義可由四位二進制數組合推出。
 * 回傳值在二進制計數法中，四位由高到低分別代表
 * 可執行 rename() 函數權限、可對檔案追加內容權限、可寫入檔案權限、可讀取檔案權限。
 *
 * @param     string     $filePath    檔案路徑
 *
 * @return    integer
 */
function fileModeInfo($filePath)
{
    static $pathMark = array();

    // 如果已檢查，直接回傳
    if (isset($pathMark[$filePath])) {

        return $pathMark[$filePath];
    }

    // 如果不存在，則不可讀、不可寫、不可改
    if (!file_exists($filePath)) {

        $pathMark[$filePath] = false;

        return $pathMark[$filePath];
    }

    $pathMark[$filePath] = 0;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {

        // 如果是目錄
        if (is_dir($filePath)) {

            // 檢查目錄是否可讀
            $dir = @opendir($filePath);
            if ($dir === false) {

                return $pathMark[$filePath]; // 如果目錄打開失敗，直接回傳目錄不可修改、不可寫、不可讀
            }
            if (@readdir($dir) !== false) {

                $pathMark[$filePath] ^= 1; // 目錄可讀 001，目錄不可讀 000
            }
            @closedir($dir);

            $testFile = $filePath . '/cf_test.txt'; // 測試檔案

            // 檢查目錄是否可寫
            $fp = @fopen($testFile, 'wb');
            if ($fp === false) {

                return $pathMark[$filePath]; //如果目錄中的檔案建立失敗，回傳不可寫
            }
            if (@fwrite($fp, 'directory access testing.') !== false) {

                $pathMark[$filePath] ^= 2; // 目錄可寫可讀011，目錄可寫不可讀 010
            }
            @fclose($fp);
            dropFile($testFile);

            // 檢查目錄是否可修改
            $fp = @fopen($testFile, 'ab+');
            if ($fp === false) {

                return $pathMark[$filePath];
            }
            if (@fwrite($fp, 'modify test' . PHP_EOL) !== false) {

                $pathMark[$filePath] ^= 4;
            }
            @fclose($fp);

            // 檢查目錄下是否有執行 rename() 函數的權限
            if (@rename($testFile, $testFile) !== false) {

                $pathMark[$filePath] ^= 8;
            }

            dropFile($testFile);

        } elseif (is_file($filePath)) { // 如果是檔案

            // 以讀方式打開
            $fp = @fopen($filePath, 'rb');
            if ($fp) {

                $pathMark[$filePath] ^= 1; // 可讀 001
            }
            @fclose($fp);

            // 試著修改文件
            $fp = @fopen($filePath, 'ab+');
            if ($fp && @fwrite($fp, '') !== false) {

                $pathMark[$filePath] ^= 6; // 可修改可寫可讀 111，不可修改可寫可讀 011
            }
            @fclose($fp);

            // 檢查目錄下是否有執行 rename() 函數的權限
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

/**
 * 上傳檔案
 *
 * @param     array      $uploadSrc         檔案資源陣列
 * @param     string     $uploadPath        檔案上傳路經
 * @param     boolean    $randomName        是否亂數產生檔案名稱
 * @param     boolean    $returnStatus      是否回傳狀態陣列
 * @param     string     $allowFileTypes    允許的檔案格式
 *
 * @return    mixed
 */
function uploadFile($uploadSrc, $uploadPath = '', $randomName = true, $returnStatus = false, $allowFileTypes = '')
{
    // 取得上傳狀態相關資訊
    $statusArr = getUploadInfoByError(
        $uploadSrc['error'],
        !empty($allowFileTypes) ? checkFileType($uploadSrc['tmp_name'], $uploadSrc['name'], $allowFileTypes) : null
    );
    $statusArr['path'] = '';

    if (true === $statusArr['status']) {

        if (empty($uploadPath)) {

            $uploadPath = DATA_DIR . '/files/';
        }

        $uploadPath = trim($uploadPath, '/') . '/';

        // 建立資料夾失敗
        if (!makeDir(ROOT_PATH . $uploadPath)) {

            $statusArr['status'] = false;
            $statusArr['msg'] = sprintf('目錄 % 不存在或不可寫', $uploadPath);
            $statusArr['err_no'] = ERR_DIRECTORY_READONLY;
            $statusArr['err_code'] = 'ERR_DIRECTORY_READONLY';

        } else {

            preg_match(
                '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                $uploadSrc['name'],
                $pathinfo
            );

            // 是否使用亂數檔案名稱
            if (!$randomName) {

                // 檢查檔案是否已存在
                $i = 0;
                do {

                    $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                    $newFileUrl = cIconv(ROOT_PATH . $uploadPath . $newFileName, getServerOsCharset(), 'AUTO');
                    $i++;

                } while (is_file($newFileUrl));

                $fileName = $newFileName;

            } else {

                $fileName = randomFilename() . '.' . $pathinfo['extension'];
            }

            if (
                $statusArr['status'] = @move_uploaded_file(
                    $uploadSrc['tmp_name'],
                    cIconv(ROOT_PATH . $uploadPath . $fileName, getServerOsCharset(), 'AUTO')
                )
            ) {

                $statusArr['path'] = $uploadPath . $fileName;
            }
        }
    }

    $res = $returnStatus == true
            ? $statusArr
            : (false === $statusArr['status'] ? $statusArr['status'] : $statusArr['path']);

    return $res;
}

/**
 *
 * 從上傳錯誤代碼取得相關訊息
 *
 * @param     integer     $errNum            錯誤代碼
 * @param     boolean     $allowFileTypes    檔案格式允許狀態
 *
 * @return    array
 */
function getUploadInfoByError($errNum, $allowFileTypes = null)
{
    $statusArr = array(
        'status' => true,
        'err_no' => 0,
        'err_code' => 0,
        'msg' => ''
    );

    // 判別上傳狀態
    switch ($errNum) {
        // 上傳成功
        case 0:
            // 驗證檔案格式
            if (!is_null($allowFileTypes) && $allowFileTypes == false) {

                $statusArr['status'] &= false;
                $statusArr['msg'] = '不是允許的檔案格式';
                $statusArr['err_no'] = ERR_INVALID_FILE_TYPE;
                $statusArr['err_code'] = 'ERR_INVALID_FILE_TYPE';
            }
            break;
        // 檔案大小超出了伺服器上傳限制
        case 1:
            $statusArr['status'] &= false;
            $statusArr['msg'] = sprintf(
                '圖片超出上傳檔案限制 (最大值: %s), 無法上傳。',
                ini_get('upload_max_filesize')
            );
            $statusArr['err_no'] = ERR_UPLOAD_SERVER_SIZE;
            $statusArr['err_code'] = 'ERR_UPLOAD_SERVER_SIZE';
            break;
        // 檔案大小超出瀏覽器限制
        case 2:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '圖片超出瀏覽器限制 (最大值: 2 Mbytes), 無法上傳。';
            $statusArr['err_no'] = ERR_UPLOAD_BROWSER_SIZE;
            $statusArr['err_code'] = 'ERR_UPLOAD_BROWSER_SIZE';
            break;
        // 檔案僅部分被上傳
        case 3:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '檔案僅部分被上傳。';
            $statusArr['err_no'] = ERR_UPLOAD_PARTIAL;
            $statusArr['err_code'] = 'ERR_UPLOAD_PARTIAL';
            break;
        // 沒有找到要上傳的檔案
        case 4:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '沒有找到要上傳的檔案。';
            $statusArr['err_no'] = ERR_UPLOAD_NO_FILE;
            $statusArr['err_code'] = 'ERR_UPLOAD_NO_FILE';
            break;
        // 伺服器臨時檔案遺失
        case 5:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '伺服器臨時檔案遺失。';
            $statusArr['err_no'] = ERR_UPLOAD_TEMP_MISS;
            $statusArr['err_code'] = 'ERR_UPLOAD_TEMP_MISS';
            break;
        // 檔案寫入到暫存資料夾錯誤
        case 6:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '檔案寫入到暫存資料夾錯誤。';
            $statusArr['err_no'] = ERR_UPLOAD_NO_TMP_DIR;
            $statusArr['err_code'] = 'ERR_UPLOAD_NO_TMP_DIR';
            break;
        // 無法寫入硬碟
        case 7:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '無法寫入硬碟。';
            $statusArr['err_no'] = ERR_UPLOAD_CANT_WRITE;
            $statusArr['err_code'] = 'ERR_UPLOAD_CANT_WRITE';
            break;
        // 擴充元件使檔案上傳停止
        case 8:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '擴充元件使檔案上傳停止。';
            $statusArr['err_no'] = ERR_UPLOAD_EXTENSION;
            $statusArr['err_code'] = 'ERR_UPLOAD_EXTENSION';
            break;
        // 未知狀態
        default:
            $statusArr['status'] &= false;
            $statusArr['msg'] = '未知的上傳失敗。';
            $statusArr['err_no'] = ERR_UPLOAD_UNKNOWN;
            $statusArr['err_code'] = 'ERR_UPLOAD_UNKNOWN';
            break;
    }

    return $statusArr;
}

/**
 * 載入系統設定資訊
 *
 * @param     integer    $langId       語言 ID
 *
 * @return    array
 */
function loadConfig($langId = null)
{
    $funArgs = func_get_args(); // 取得函式參數陣列

    $langId = (!is_null($langId) && in_array($langId, array_keys(getLanguage())))
            ? intval($langId)
            : getDefaultLangId();
    $funArgs[0] = $langId; // 覆寫語言 ID 參數

    // 將參數序列化後編碼產生唯一值以便讀取靜態檔使用
    $hashcode = sprintf('%s_%X', 'shop_config', crc32(serialize($funArgs)));
    $staticCache = readStaticCache($hashcode); // 讀取靜態資料

    $data = &$staticCache;

    if ($staticCache === false) {

        $data =  array();

        $sql = 'SELECT sc.code, sc.value, sc.type '
             . 'FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc '
             . 'WHERE sc.parent_id > 0 '
             . 'AND sc.lang_id ' . $GLOBALS['db']->in(array(0, $langId));
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $data[$row['code']] = $row['value'];
        }

        writeStaticCache($hashcode, $data);
    }

    return $data;
}

/**
 * 取得系統是否啟用了 gzip
 *
 * @return    boolean
 */
function gzipEnabled()
{
    static $enabledGzip = null;

    if ($enabledGzip === null) {

        $enabledGzip = $GLOBALS['_CFG']['enable_gzip']
                     && function_exists('ob_gzhandler')
                     && ini_get('zlib.output_compression') == 0;
    }

    return $enabledGzip;
}

/**
 * 格式化價格
 *
 * @param     float      $price         價格
 * @param     integer    $formatType    格式化類型
 *
 * @return    string
 */
function formatPrice($price, $formatType = null)
{
    if (is_null($formatType) && isset($GLOBALS['_CFG']['price_format'])) {

        $formatType = $GLOBALS['_CFG']['price_format'];
    }

    $price = formatNumber($price, $formatType);

    if (isset($GLOBALS['_CFG']['currency_format'])) {

        return sprintf($GLOBALS['_CFG']['currency_format'], $price);

    } else {

        return $price;
    }
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
function strMark($string, $markChar = '*', $markOffset = null, $markLength = null)
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
 * 取得真實的 URL
 *
 * @return    string
 */
function getRealUrl()
{
    if (defined('ADMIN_DIR') && defined('SELF_ADMIN')) {

        $realUrl = $GLOBALS['dus']->url(ADMIN_DIR);
        if (!SELF_ADMIN) {

            $realUrl .=  ADMIN_DIR . '/';
        }

    } else {

        $realUrl = $GLOBALS['dus']->url();
    }

    return $realUrl;
}

/**
 * 阻擋直接連結 URL
 *
 * @param     string    $checkPath    檢查路徑
 * @param     string    $targetUrl    跳轉的位置
 *
 * @return    mixed                   如果不跳轉則回傳布林值
 */
function blockDirectUrl($checkPath, $targetUrl = './')
{
    if (pathinfo(getLocationUrl(), PATHINFO_BASENAME) == pathinfo($checkPath, PATHINFO_BASENAME)) {

        if (!empty($targetUrl)) {

            $GLOBALS['dus']->header('Location: ' . $targetUrl, true, 301);

        } else {

            return true;
        }

    } else {

        return false;
    }
}

/**
 * 取得目前頁面的 URL
 *
 * @param     array     $params          查詢參數陣列
 * @param     string    $argSeparator    參數分隔字元
 *
 * @return    string
 */
function getLocationUrl(array $params = array(), $argSeparator = null)
{
    is_null($argSeparator) && $argSeparator = ini_get('arg_separator.output');

    if ($GLOBALS['_CFG']['enable_url_rewrite']) {

        $pathInfo = parse_url(getRequestUrl());

        $urlPath = str_replace(
            ltrimSeparator(str_replace(basename(PHP_SELF), '', PHP_SELF)),
            '',
            urldecode($pathInfo['path'])
        );

        isset($pathInfo['query']) && parse_str($pathInfo['query'], $urlQuery);
        if (!empty($params) && is_array($params)) {

            $urlQuery = array_merge($urlQuery, $params);
        }

        if (!empty($urlQuery)) {

            // strpos(substr($urlPath, -1), '/') === false && $urlPath .= '/';
            $urlPath .= '?' . http_build_query($urlQuery, '', $argSeparator);
        }

        return getRealUrl() . ltrimSeparator($urlPath);

    } else {

        $queryStr = $_SERVER['QUERY_STRING'] != '' ? trim($_SERVER['QUERY_STRING']) : '';

        parse_str($queryStr, $urlQuery);

        if (!empty($params) && is_array($params)) {

            $urlQuery = array_merge($urlQuery, $params);
        }

        return getRealUrl() . buildUri(
            basename(PHP_SELF, '.php'),
            $urlQuery,
            array('arg_separate' => $argSeparator)
        );
    }
}

/**
 * 取得返回 URL
 *
 * @param     boolean    $encode    是否編碼 URL
 *
 * @return    string
 */
function getBackUrl($encode = false)
{
    $backUrl = urldecode(getRefUrl(false));

    // 如果返回網址與目前網址相同
    if (count(array_diff(parse_url(getLocationUrl()), parse_url($backUrl))) < 1) {

        $backUrl = getRealUrl();
    }

    if ($encode) {

        $backUrl = base64_encode(urlencode($backUrl));
    }

    return $backUrl;
}

/**
 * 取得來源 URL
 *
 * @param     boolean    $encode    是否編碼 URL
 *
 * @return    string
 */
function getRefUrl($encode = true)
{
    $refUrl = isset($_SERVER['HTTP_REFERER']) ? urldecode(trim($_SERVER['HTTP_REFERER'])) : '';
    if (empty($refUrl)) {

        $refUrl = getLocationUrl();
    }

    $realUrl = getRealUrl();

    // 如果 ref 已經存在，則使用目前頁面作為來源
    if (false !== strpos($refUrl, 'ref')) {

        $parseUrl = parse_url(isset($_SERVER['REQUEST_URI']) ? urldecode(trim($_SERVER['REQUEST_URI'])) : '');
        $app = isset($parseUrl['path']) ? basename($parseUrl['path'], '.php') : '';

        $query = array();
        isset($parseUrl['query']) && parse_str(htmlspecialchars_decode($parseUrl['query']), $query);

        $refUrl = $realUrl . buildUri($app, $query);
        if (isset($parseUrl['fragment'])) {

            $refUrl .= '#' . $parseUrl['fragment'];
        }
    }

    // 如果來源網址非 PHP 進入則不採用
    $urlInfo = pathinfo(parse_url($refUrl, PHP_URL_PATH));
    if (
        isset($urlInfo['extension']) &&
        !in_array(strtolower($urlInfo['extension']), array('php', 'html', 'htm'))
    ) {

        $refUrl = '';
    }

    // 如果為相對路徑則使用目前環境的 URL 組合
    if (false === strpos($refUrl, '://')) {

        $refUrl = $realUrl . str_replace($realUrl, '', $refUrl);
    }

    $refUrl = urlencode($refUrl);

    if ($encode) {

        $refUrl = base64_encode($refUrl);
    }

    return $refUrl;
}

/**
 * 取得請求的 url
 *
 * @return    string
 */
function getRequestUrl()
{
    $requestUri = null;

    if (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // check this first so IIS will catch

        $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];

    } elseif (

        // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
        isset($_SERVER['IIS_WasUrlRewritten']) &&
        $_SERVER['IIS_WasUrlRewritten'] == '1' &&
        isset($_SERVER['UNENCODED_URL']) &&
        $_SERVER['UNENCODED_URL'] != ''
        ) {

        $requestUri = $_SERVER['UNENCODED_URL'];

    } elseif (isset($_SERVER['REQUEST_URI'])) {

        $requestUri = $_SERVER['REQUEST_URI'];

        // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
        $schemeAndHttpHost = $GLOBALS['dus']->url();
        if (strpos($requestUri, $schemeAndHttpHost) === 0) {

            $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
        }

    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI

        $requestUri = $_SERVER['ORIG_PATH_INFO'];

        if (!empty($_SERVER['QUERY_STRING'])) {

            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }
    }

    return $requestUri;
}

/**
 * 郵件發送
 *
 * @param     string     $eMail           接收人郵件地址
 * @param     string     $subject         郵件標題
 * @param     string     $content         郵件內容
 * @param     integer    $showReplyUser   顯示回覆人
 * @param     boolean    $notification    true: 要求回執, false: 不用回執
 * @param     array      $files           附加檔案
 *
 * @return    boolean
 */
function sendMail($eMail, $subject, $content, $showReplyUser = false, $notification = false, $files = array())
{
    $data = array(
        'mail_service' => $GLOBALS['_CFG']['mail_service'],
        'mail_charset' => $GLOBALS['_CFG']['mail_charset'],
        'smtp_mail' => $GLOBALS['_CFG']['smtp_mail'],
        'smtp_use_authentication' => $GLOBALS['_CFG']['smtp_use_authentication'],
        'smtp_user' => $GLOBALS['_CFG']['smtp_user'],
        'smtp_pass' => $GLOBALS['dus']->compilePassword($GLOBALS['_CFG']['smtp_pass']),
        'smtp_port' => $GLOBALS['_CFG']['smtp_port'],
        'smtp_host' => $GLOBALS['_CFG']['smtp_host'],
        'smtp_secure' => $GLOBALS['_CFG']['smtp_secure']
    );

    /* 郵件服務器的參數設置 */
    $mail = new PHPMailer;

    $mail->From = $data['smtp_user'];
    $mail->FromName = $GLOBALS['_CFG']['shop_name'];
    $mail->Subject = $subject;
    $mail->Body = $content;

    $mail->CharSet = $data['mail_charset'];
    //$mail->SetLanguage('zh', $pluginPath . 'language/');

    $showReplyUser && $mail->addReplyTo($data['smtp_mail'], $GLOBALS['_CFG']['shop_name']);

    $notification && $mail->ConfirmReadingTo = $data['smtp_mail'];

    $mail->Timeout = 10;
    // $mail->WordWrap = 50; // 設定一行最多為 50 個字元，即每 50 個字自動斷行
    $mail->isHTML(true); // 設定信件內容

    switch ($data['mail_service']) {
        case 'php':
            $mail->isMail();
            break;
        case 'qmail':
            $mail->isQmail();
            break;
        // 使用 SendMail 來寄送 e-mail
        case 'sendmail':
            $mail->isSendmail();
            break;
        // 使用 SMTP 寄送信件
        case 'smtp':
            $mail->isSMTP();
            // check if we should use authentication
            if ($data['smtp_use_authentication']) {

                if ($data['smtp_user'] == '' || $data['smtp_pass'] == '') {

                    // this is a severe error
                    $GLOBALS['err']->add('Please provide a username and a password if you wish to use SMTP authentication');
                    $mail->SMTPAuth = false;

                } else {

                    // if the checks went ok, set the authentication
                    $mail->SMTPAuth = true; // 設定 SMTP 需要驗證
                    $mail->Username = $data['smtp_user'];
                    $mail->Password = $data['smtp_pass'];
                }

            } else {

                $mail->SMTPAuth = false;
            }

            // set the server
            if (empty($data['smtp_port'])) {

                $data['smtp_port'] = 25;
            }
            if (empty($data['smtp_host'])) {

                $GLOBALS['err']->add('You should specify an SMTP server in order to send emails.');
                return false;

            } else {

                $mail->Host = $data['smtp_host'];
                $mail->Port = $data['smtp_port'];
                if (extension_loaded('openssl') && in_array($data['smtp_secure'], array('ssl', 'tls'))) {

                    $mail->SMTPSecure = $data['smtp_secure'];

                } else {

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
                }
            }
            break;
        default:
            $GLOBALS['err']->add('Unrecognized value of the email_service_type setting. Reverting to PHP built-in mail() functionality');
            $mail->isMail();
    }

    $multiMail = array();
    foreach (explode(',', $eMail) as $row) {

        $row = trim($row);
        if (preg_match('/\s?(.*)\s?<(.*)>/', $row, $match)) {

            if (filter_var($match[2], FILTER_VALIDATE_EMAIL) === false) {

                continue;
            }

            $multiMail[] = array(trim($match[1]), trim($match[2]));

        } elseif (filter_var($row, FILTER_VALIDATE_EMAIL) !== false) {

            $multiMail[] = $row;
        }
    }

    if (empty($multiMail)) {

        $GLOBALS['err']->add('至少一組寄送電子信箱');
        return false;

    } elseif (count($multiMail) == 1) {

        if (is_array($multiMail[0])) {

            $mail->addAddress($multiMail[0][1], $multiMail[0][0]);

        } else {

            $mail->addAddress($multiMail[0], '');
        }

    } else {

        foreach ($multiMail as $val) {

            if (is_array($val)) {

                $mail->addAddress($val[1], $val[0]);

            } else {

                $mail->addAddress($val, '');
            }
        }
    }

    // 附加檔案
    if (!empty($files) && is_array($files)) {

        foreach ($files as $val) {

            $mail->addAttachment($val);
        }
    }

    if ($mail->send()) {

        return true;

    } else {

        if (isset($GLOBALS['err'])) {

            $errMsg = $mail->ErrorInfo;
            if (empty($errMsg)) {

                $GLOBALS['err']->add('Unknown Error');

            } else {

                $GLOBALS['err']->add($errMsg);
            }
        }

        return false;
    }
}

/**
 * 寄送通知
 *
 * @param     string     $mailTplCode    郵件樣版代碼
 * @param     array      $params         sendMail 參數值
 *
 * @return    boolean
 */
function sendNotice($mailTplCode, array $params = array())
{
    $name = null;
    $email = null;
    $showReplyUser = false;
    $notification = false;
    $files = array();
    $langId = null;
    $subject = '';

    foreach ($params as $_key => $_value) {

        $_key = strToCamel(strtolower($_key));
        switch ($_key) {
            case 'langId':
                ${$_key} = is_null($_value) ? null : (int)$_value;
                break;
            case 'name':
            case 'email':
                $_value = $_value;
            case 'subject':
                ${$_key} = (string)$_value;
                break;
            case 'showReplyUser':
            case 'notification':
                ${$_key} = (boolean)$_value;
                break;
            case 'files':
                ${$_key} = (array)$_value;
                break;
            default:
        }
    }

    if (empty($mailTplCode) || empty($email)) {

        return false;
    }

    if (is_null($langId)) {

        $langId = !empty($GLOBALS['_CFG']['lang_id']) ? (int)$GLOBALS['_CFG']['lang_id'] : getDefaultLangId();
    }

    // 暫時關閉 Smarty 快取
    $curCacheStatus = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = Smarty::CACHING_OFF;

    $tpl = getMailTemplate($mailTplCode); // 取得郵件樣版

    // 變更 Smarty 錯誤回報等級
    $curErrRpt = $GLOBALS['smarty']->error_reporting;
    $GLOBALS['smarty']->error_reporting = E_ALL ^ E_NOTICE;

    $content = $GLOBALS['smarty']->fetch('string:' . $tpl['template_content'], null, $tpl['template_code']);

    // 恢復原本 Smarty 錯誤回報等級
    $GLOBALS['smarty']->error_reporting = $curErrRpt;

    // 恢復原本 Smarty 快取狀態
    $GLOBALS['smarty']->caching = $curCacheStatus;

    $sendEmail = $email;
    if (!is_null($name)) {

        if (!is_array($email)) {

            $email = explode(',', $email);
        }

        $multiMail = array();
        foreach ($email as $eKey => $eVal) {

            $row = trim($eVal);
            if (preg_match('/\s?(.*)\s?<(.*)>/', $row, $match)) {

                if (filter_var($match[2], FILTER_VALIDATE_EMAIL) === false) {

                    continue;
                }

                $multiMail[] = $row;

            } else {

                $multiMail[] = sprintf('%s<%s>', $name, $row);
            }
        }

        $sendEmail = implode(',', $multiMail);
    }

    return sendMail(
        $sendEmail,
        ($subject != '' ? $subject : $tpl['template_subject']),
        $GLOBALS['dus']->urlDecode($content),
        $showReplyUser,
        $notification,
        $files
    );
}

/**
 * 取得郵件樣版
 *
 * @param     string     $tplName    樣版代碼
 *
 * @return    array
 */
function getMailTemplate($tplName)
{
    $sql = 'SELECT mt.template_id, mt.template_code, 1 AS is_html, '
         . 'mt.template_content, mt.template_subject '
         . 'FROM ' . $GLOBALS['db']->table('mail_templates') . ' AS mt '
         . 'WHERE mt.template_code = "' . $tplName . '"';
    return $GLOBALS['db']->getRowCached($sql);
}

/**
 * 取得語系對應的語言包
 *
 * @param     array     $funcList      功能清單陣列
 *
 * @return    array
 */
function getLanguagePackge(array $langPackge)
{
    $lData = json_decode(
        json_encode(
            simplexml_load_file(
                TEMPLATE_PATH . 'language.xml',
                null,
                LIBXML_NOCDATA
            )
        ),
        true
    );

    $langData = array();
    $langPackge = array_unique($langPackge);
    foreach ($langPackge as $val) {

        isset($lData[$val]) && $langData = array_merge($langData, $lData[$val]);
    }

    return $langData;
}


/**
 * 取得語言
 *
 * @params    boolean    $enabled    啟用狀態
 *
 * @return    array
 */
function getLanguage($enabled = true)
{
    $funArgs = func_get_args(); // 將參數序列化後編碼產生唯一值以便讀取靜態檔使用
    $hashcode = sprintf('%s_%X', 'shop_language', crc32(serialize($funArgs)));

    $staticCache = readStaticCache($hashcode); // 讀取靜態資料
    $languages = &$staticCache;

    // 判斷靜態資料是否存在，存在直接讀取
    if (false === $staticCache) {

        $languages = array();

        $oper = '';
        if (!is_null($enabled)) {

            $oper .= 'AND l.status = ' . intval((boolean)$enabled);
        }
        $sql = 'SELECT l.* '
             . 'FROM ' . $GLOBALS['db']->table('language') . ' AS l '
             . 'WHERE 1 ' . $oper . ' '
             . 'ORDER BY sort_order ASC';
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $languages[$row['lang_id']] = $row;
        }

        writeStaticCache($hashcode, $languages);
    }

    return $languages;
}

/**
 * 取得預設語言 ID
 *
 * @return    integer
 */
function getDefaultLangId()
{
    $sql = 'SELECT sc.value '
         . 'FROM ' . $GLOBALS['db']->table('shop_config') . ' AS sc '
         . 'WHERE sc.code = "' . (defined('DUS_ADMIN') ? 'backend' : 'front') . '_default_lang"';
    return (int)$GLOBALS['db']->getOneCached($sql);
}

/**
 * 取得目前語言 ID
 *
 * @param     integer    $langId    語言 ID
 *
 * @return    integer
 */
function getCurLangId($langId = null)
{
    static $curLangId = null;

    if (!is_null($langId) && in_array($langId, array_column(getLanguage(), 'lang_id'))) {

        return $langId;
    }

    if (is_null($curLangId)) {

        if (isset($GLOBALS['_CFG']['lang_id'])) {

            $curLangId = $GLOBALS['_CFG']['lang_id'];

        } elseif (isset($GLOBALS['_CFG']['default_lang_id'])) {

            $curLangId = $GLOBALS['_CFG']['default_lang_id'];

        } else {

            return getDefaultLangId();
        }
    }

    return $curLangId;
}

/**
 * 藉由代碼取得對應的語言資訊
 *
 * @param     mixed    $langCodes    語言代碼或代碼陣列
 * @param     array    $languages    語言清單
 *
 * @return    array
 */
function getLanInfoByCode($langCodes, array $languages = array())
{
    $langInfo = array();

    if (!empty($langCodes)) {

        if (empty($languages)) {

            $languages = getLanguage();
        }

        if (!is_array($langCodes)) {

            $langCodes = explode(',', $langCodes);
        }

        foreach ($langCodes as $langCode) {

            foreach ($languages as $lKey => $lVal) {

                $locale = explode(',', $lVal['locale']);

                if (in_array(strtolower($langCode), array_map('strtolower', $locale))) {

                    $langInfo = $lVal;
                    break(2);
                }
            }
        }
    }

    return $langInfo;
}

/**
 * 產生表單 TOKEN
 *
 * @param     integer    $expire    有效時間(秒數)
 *
 * @return    string
 */
function genFormToken($expire = 1800)
{
    DEMO_MODE && $expire = 0;

    return $GLOBALS['dus']->compilePassword(uniqid(), 'ENCODE', SESS_ID, $expire);
}

/**
 * 檢查表單 TOKEN
 *
 * @return    boolean
 */
function chkFormToken($token)
{
    return $GLOBALS['dus']->compilePassword($token, 'DECODE', SESS_ID) != '';
}


/**
 * 取得所有區域清單
 *
 * @return    array
 */
function getAllRegion()
{
    static $regions = null;

    if ($regions === null) {

        $hashcode = sprintf('%s', 'regions');
        $data = readStaticCache($hashcode);
        if ($data === false) {

            $regions = array();

            $sql = 'SELECT r.region_id, r.region_type, r.is_ignore, r.parent_id, r.parent_ids, ' .
                   'r.region_zip, r.region_name, ' .
                   'COUNT(DISTINCT rs.region_id) AS has_children ' .
                   'FROM ' . $GLOBALS['db']->table('region') . ' AS r ' .
                   'LEFT JOIN ' . $GLOBALS['db']->table('region') . ' AS rs ' .
                   'ON rs.parent_id = r.region_id ' .
                   'GROUP BY r.region_id ' .
                   'ORDER BY r.region_id ASC';
            $res = $GLOBALS['db']->query($sql);
            while ($row = $GLOBALS['db']->fetchAssoc($res)) {

                $regions[$row['region_id']] = $row;
            }

            writeStaticCache($hashcode, $regions);

        } else {

            $regions = $data;
        }
    }

    return $regions;
}

/**
 * 取得指定區域次級列表
 *
 * @param     integer    $type             區域類型
 * @param     integer    $parentId         區域父類別編號
 * @param     integer    $referId          區域參考編號
 * @param     array      $params           額外參數值
 *                       -[lang_id]        語言 ID
 *                       -[ignore]         排除忽略區域
 *                       -[grp_by_type]    依照區域類型分組
 *
 * @return    array
 */
function getRegions($type = 0, $parentId = null, $referId = null, array $params = array())
{
    $langId = null;
    $ignore = true;
    $grpByType = false;

    foreach ($params as $_key => $_value) {

        $_key = strToCamel(strtolower($_key));

        switch ($_key) {
            case 'ignore':
            case 'grpByType':
                ${$_key} = is_null($_value) ? null : (boolean)$_value;
                break;
            default:
        }
    }

    static $regions = array();

    if (isset($regions[$type])) {

        $type == 0 && $parentId = 0;

        if (!is_null($parentId)) {

            $result = array();

            if ($type > 0 && $parentId < 1 && $referId < 1) {

                $result = array();

            } elseif ($ignore && $type > 0 && $parentId < 1 && $referId > 0) {

                if (isset($regions[$type][$referId])) {

                    if ($grpByType) {

                        $result[$type][$referId] = $regions[$type][$referId];

                    } else {

                        $result = $regions[$type][$referId];
                    }
                }

            } else {

                if (isset($regions[$type][$parentId])) {

                    if ($grpByType) {

                        $result[$type][$parentId] = $regions[$type][$parentId];

                    } else {

                        $result = $regions[$type][$parentId];
                    }
                }
            }

            return $result;

        } else {

            $regionMerge = array();

            foreach ($regions[$type] as $row) {

                $regionMerge = array_merge($regionMerge, $row);
            }

            return $regionMerge;
        }
    }

    $regionArrayCached = getAllRegion($langId);

    if ($ignore) {

        $regionIgnore = array_filter($regionArrayCached, function ($v) {

            return $v['is_ignore'] == 1 && $v['has_children'] > 0;
        });

        foreach ($regionArrayCached as $rId => &$rData) {

            if (in_array($rData['parent_id'], array_keys($regionIgnore))) {

                $parentIds = array_reverse(explode(',', $rData['parent_ids']));
                if (!empty($parentIds)) {

                    // 取得差異的父層 ID
                    $diffParentIds = array_diff($parentIds, (array)$rData['parent_id']);

                    !empty($diffParentIds) && $rData['parent_id'] = current($diffParentIds);
                }
            }
        }
    }

    foreach ($regionArrayCached as $row) {

        if ($ignore && $row['is_ignore']) {

            $regions[$row['region_type']] = array();
            continue;
        }

        $regions[$row['region_type']][$row['parent_id']][$row['region_id']] = array(
            'region_id' => $row['region_id'],
            'region_zip' => $row['region_zip'],
            'region_name' => $row['region_name']
        );
    }

    if (!isset($regions[$type]) || empty($regions)) {

        return array();
    }

    return call_user_func_array(__FUNCTION__, func_get_args());
}

/**
 * 取得指定區域的所有上級 ID
 *
 * @param     mixed      $regionIds   區域 ID 或區域 ID 陣列
 *
 * @return    array
 */
function getRegionsParents($regionIds)
{
    if (!is_array($regionIds)) {

        $regionIds = explode(',', $regionIds);
    }

    if (empty($regionIds)) {

        return array();
    }

    $regionArrayCached = getAllRegion();
    if (empty($regionArrayCached)) {

        return array();
    }

    $index = 0;
    $parentIds = array();
    foreach ($regionIds as $regionId) {

        while (true) {

            foreach ($regionArrayCached as $row) {

                if ($regionId == $row['region_id']) {

                    $regionId = $row['parent_id'];

                    $parentIds[$row['region_id']] = array(
                        'region_id' => $row['region_id'],
                        'region_name' => $row['region_name'],
                        'region_type' => $row['region_type'],
                        'parent_id' => $row['parent_id']
                    );

                    $index++;
                    break;
                }
            }

            if ($index == 0 || $regionId == 0) {

                break;
            }
        }
    }

    ksort($parentIds);

    return $parentIds;
}

/**
 * 取得指定區域所有底層的 區域 ID
 *
 * @param     integer    $regionId    指定的區域 ID
 *
 * @return    array
 */
function getRegionsChildIds($regionId = 0, $level = 0)
{
    $child = levelOptions(
        'region_child',
        $regionId,
        getAllRegion(),
        array(
            'is_cache' => false,
            'primary_field' => 'region_id',
            'parent_field' => 'parent_id'
        )
    );

    // 截取到指定的縮減級別
    if ($level > 0) {

        if ($regionId == 0) {

            $endLevel = $level;

        } else {

            $firstItem = reset($child); // 取得第一個元素
            $endLevel = $firstItem['level'] + $level;
        }

        // 保留 level 小於 end_level 的部分
        foreach ($child as $key => $val) {

            if ($val['level'] >= $endLevel) {

                unset($child[$key]);
            }
        }
    }

    return array_unique(array_merge(array($regionId), array_keys($child)));
}

/**
 * 建立完整地址
 *
 * @param     array     $regionIds    區域 ID 陣列
 * @param     string    $address      詳細地址
 * @param     mixed     $zipcode      郵遞區號
 *
 * @return    string
 */
function createRegionAddress(array $regionIds = array(), $address = '', $zipcode = false)
{
    if (!empty($regionIds)) {

        $regions = getAllRegion();

        $regionArr = array();
        foreach ($regionIds as $key => $val) {

            if (isset($regions[$val])) {

                $row = $regions[$val];

                $regionArr['zip'] = $row['region_zip'];
                $regionArr['address'][$row['region_type']] = $row['region_name'];
            }
        }

        if (!empty($regionArr)) {

            ksort($regionArr['address']);

            $address = (is_bool($zipcode) ? ($zipcode === true ? $regionArr['zip'] : '') : $zipcode)
                     . ' '
                     . implode(' ', $regionArr['address'])
                     . ' '
                     . $address;
        }
    }

    return ltrim($address);
}

/**
* 身份證字號格式檢查
*
* @param     string    $code    身分證字號
*
* @return    boolean
*/
function checkIDCode($code)
{
    $code = strtoupper($code);

    // 建立字母分數陣列
    $headPoint = array(
        'A' =>  1, 'I' => 39, 'O' => 48, 'B' => 10, 'C' => 19, 'D' => 28,
        'E' => 37, 'F' => 46, 'G' => 55, 'H' => 64, 'J' => 73, 'K' => 82,
        'L' =>  2, 'M' => 11, 'N' => 20, 'P' => 29, 'Q' => 38, 'R' => 47,
        'S' => 56, 'T' => 65, 'U' => 74, 'V' => 83, 'W' => 21, 'X' =>  3,
        'Y' => 12, 'Z' => 30
    );

    // 建立加權基數陣列
    $multiply = array(8, 7, 6, 5, 4, 3, 2, 1);

    // 檢查身份字格式是否正確
    if (preg_match('/^[a-zA-Z][1-2][0-9]+$/', $code) && strlen($code) == 10) {

        // 切開字串
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {

            $stringArray[$i] = substr($code, $i, 1);
        }
        // 取得字母分數
        $total = $headPoint[array_shift($stringArray)];
        // 取得比對碼
        $point = array_pop($stringArray);
        // 取得數字分數
        $len = count($stringArray);
        for ($j = 0; $j < $len; $j++) {

            $total += $stringArray[$j] * $multiply[$j];
        }

        // 檢查比對碼
        if (($total % 10 == 0) ? 0 : 10 - $total % 10 != $point) {

            return false;

        } else {

            return true;
        }

    } else {

       return false;
    }
}
