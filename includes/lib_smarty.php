<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * Smarty 外掛函式庫
 * ========================================================
 *
 * ========================================================
 * Date: 2015-09-07 17:33
 */

/**
 * 編譯前預處理過濾器
 *
 * @param     string     $source       輸出內容
 * @param     object     $template     smarty 物件
 *
 * @return    string
 */
function smarty_prefilter_compile($source, Smarty_Internal_Template $template)
{
    if (!defined('DUS_ADMIN')) {

        $tplDir = 'themes/' . basename(current($template->getTemplateDir())) . '/'; // 模板所在路徑

        $fileType = '';
        if ($template->source->type == 'tpl') {

            $fileType = 'tpl';
            $fileName = $template->source->name;

        } elseif ($template->source->type == 'file') {

            $fileType = ltrim(strtolower(strrchr($template->_current_file, '.')), '.');
            $fileName = rtrim($template->source->name, '.' . $fileType);
        }

        $source = strtr($source, array(chr(13) . chr(10) => PHP_EOL, chr(13) => PHP_EOL, chr(10) => PHP_EOL));

        switch ($fileType) {

            /**
             * 處理模板文件
             */
            case 'dwt':
            case 'tpl':

                // 將樣版中所有 library 替換為 smarty 插入方式
                $source = preg_replace_callback(
                    '/<!--\s#BeginLibraryItem\s\"\/(.*?)\"(.*?)\s-->.*?<!--\s#EndLibraryItem\s-->/s',
                    function ($matches) {
                        return '{^include file="' . strtolower($matches[1]) . '"' . $matches[2] . '^}';
                    },
                    $source
                );

                if (DEMO_MODE) {

                    foreach (array('<meta.*>', '<head.*>') as $pattern) {

                        if (!preg_match('/' . $pattern . '/i', $source, $matchs)) {

                            continue;
                        }

                        $pos = stripos($source, $matchs[0]);
                        $pos += mb_strlen($matchs[0]);

                        $meta = sprintf(
                            '%1$s%2$s%1$s%3$s',
                            PHP_EOL,
                            '<meta name="robots" content="noindex,nofollow">',
                            '<meta name="wm.env" content="development">'
                        );
                        $source = substr_replace($source, $meta, $pos, 0);
                        break;
                    }
                }

                // 插入目前執行的 SCRIPT NAME
                if (
                    defined('SCRIPT_NAME') &&
                    preg_match('/<body.*>/i', substr($source, 0, round(mb_strlen($source) * 0.4)), $matchs)
                ) {

                    $pos = stripos($source, $matchs[0]);
                    $pos += strlen($matchs[0]);

                    $source = substr_replace($source, ' data-name="' . SCRIPT_NAME . '"', $pos - 1, 0);
                }

                // 修正 css 路徑
                $source = preg_replace(
                    '/(<link\s)(.*)(href=\s*["|\'])(assets\/.*?["|\'])(.*?>)/i',
                    '\1\2\3' . $tplDir . '\4\5',
                    $source
                );

                // 修正 script 路徑
                $source = preg_replace(
                    '/(<script\s)(.*)(src|data-main=\s*["|\'])(assets\/.*?>)([\s\S]*?)(<\\/script>)/i',
                    '\1\2\3' . $tplDir . '\4\5\6',
                    $source
                );

                // 修正樣版中 assets 目錄下的路徑
                $source = preg_replace(
                    '/((?:background|src|data\-(?:lazy|image|img|background))\s*=\s*["|\'])(?:\.\/|\.\.\/)?(assets\/.*?["|\'])/is',
                    '\1' . $tplDir . '\2',
                    $source
                );
                $source = preg_replace(
                    '/((?:background|background-image|bg-image):\s*?url\()(?:\.\/|\.\.\/)?(assets\/)/is',
                    '\1' . $tplDir . '\2',
                    $source
                );

                /* 替換相對鏈接 */
                $source = preg_replace(
                    '/(href=["|\'])\.\.\/(.*?)(["|\'])/i',
                    '\1\2\3',
                    $source
                );
                break;

            /**
             * 處理庫文件
             */
            case 'lbi':

                /* 替換路徑 */

                // 在 images 前加上 $tplDir
                $source  = preg_replace(
                    '/((?:background|src|data\-lazy)\s*=\s*["|\'])(?:\.\/|\.\.\/)?(assets\/.*?["|\'])/is',
                    '\1' . $tplDir . '\2',
                    $source
                );

                // 在 background 前加上 $tplDir
                $source  = preg_replace(
                    '/((?:background|background-image):\s*?url\()(?:\.\/|\.\.\/)?(assets\/)/is',
                    '\1' . $tplDir . '\2',
                    $source
                );

                // 替換相對鏈接
                $source = preg_replace(
                    '/(href=["|\'])\.\.\/(.*?)(["|\'])/i',
                    '\1\2\3',
                    $source
                );
                break;
        }
    }

    /* 替換文件編碼頭部 */
    if (strpos($source, "\xEF\xBB\xBF") !== false) {

        $source = str_replace("\xEF\xBB\xBF", '', $source);
    }

    /* 替換 smarty html 註解 */
    $source = preg_replace('/<!--(?:[^>|{\^|\n]*)?(\{\^(?:.|\n|\r\n)+?\^\})[^<|{\^|\n]*?-->/', '\1', $source);

    /* 替換 smarty javascript 註釋 */
    $source = preg_replace('/\/\*[^>|\n]*?(\{\^.+?\^\})[^<|{\^|\n]*?\*\//', '\1', $source);

    /* 替換不換行的 html 註解 */
    $source = preg_replace('/([\t| ]+)?<!--([^<|>|{\^|\n]*)?-->\r?\n?/', '', $source);

    return $source;
}

/**
 * 輸出壓縮過濾器
 *
 * @param     string     $source       輸出內容
 * @param     object     $template     smarty 物件
 *
 * @return    string
 */
function smarty_outputfilter_compress($source, Smarty_Internal_Template $template)
{
    if (class_exists('outCssCompress')
        && preg_match('/<link\s.*(rel\s*=\s*["|\']stylesheet["|\']|type\s*=\s*["|\']text\/css["|\']).*?>/i', $source)) {
        $outDir = '';
        if(!defined('DUS_ADMIN')) {

            $templateDir = str_replace('\\', '/', $template->getTemplateDir(0));
            if (strlen($templateDir) > strlen(ROOT_PATH)) {

                $outDir = str_replace(ROOT_PATH, '', $templateDir);
            }
        }
        $outDir .= 'assets/css/';
        $source = outCssCompress::minify($source, $outDir);
    }

    if (class_exists('outJsCompress')
        && preg_match('/<script\b[^>]*?>[\s\S]*?<\/script>/i', $source)) {

        // 檔案合併
        $outDir = '';
        if(!defined('DUS_ADMIN')) {

            $templateDir = str_replace('\\', '/', $template->getTemplateDir(0));
            if (strlen($templateDir) > strlen(ROOT_PATH)) {

                $outDir = str_replace(ROOT_PATH, '', $templateDir);
            }
        }
        $outDir .= 'assets/js/';
        $source = outJsCompress::minify($source, $outDir);
    }

    if (class_exists('outHtmlCompress')) {

        $source = outHtmlCompress::minify($source);
    }

    if (class_exists('outW3cProcess')) {

        $source = outW3cProcess::execute($source);
    }

    if (class_exists('outUrlRewrite')) {

        $source = outUrlRewrite::execute($source);
    }

    return $source;
}

/**
 * 區塊 - 隱藏區塊中內容
 *
 * @param     array     $params    參數值
 *
 * @return    string
 */
function smarty_block_hide($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    // 只在結束標籤時輸出
    if (!$repeat) {

        if (isset($content)) {

            $content = '<!-- hided -->';
        }

        return $content;
    }
}

/**
 * 將陣列的元素連結起來成為字串
 *
 * @param    array        $params      參數值
 *           array        -[pieces]    要結合為字串的陣列
 *           string       -[glue]      規定陣列元素之間放置的內容
 *           string       -[assign]    分配給新的變數，而不是輸出
 *           integer      -[limit]     陣列加入的最大數量
 * @param    reference    $template      smarty 物件
 *
 * return    string
 */
function smarty_function_implode($params, Smarty_Internal_Template $template)
{
    if (!isset($params['pieces'])) {

        return;
    }

    if (!isset($params['glue']) && $params['limit'] != 1) {

        return;
    }

    if (isset($params['limit'])) {

        $pieces = array_slice($params['pieces'], 0, intval($params['limit']), true);

    } else {

        $pieces = $params['pieces'];
    }

    if (isset($params['assign'])) {

        $template->assign($params['assign'], implode($params['glue'], $pieces));

    } else {

        return implode($params['glue'], $pieces);
    }
}

/**
 * 數字轉英文字母 (10 轉 26 進制)
 *
 * @param     array        $params    參數值
 * @param     reference    $template    smarty 物件
 *
 * @return    string
 */
function smarty_function_number2letter($params, Smarty_Internal_Template $template)
{
    // 欲轉換之數字
    $numberic = isset($params['num']) ? trim($params['num']) : 0;

    $baseArray = range('A', 'Z');
    $l = count($baseArray);
    $letter = '';
    for ($i = 1; $numberic >= 0 && $i < 10; $i++) {

        $letter = $baseArray[($numberic % pow($l, $i) / pow($l, $i - 1))] . $letter;
        $numberic -= pow($l, $i);
    }

    return $letter;
}

/**
 * 將阿拉伯數字轉換成中文
 *
 * @param     array        $params    參數值
 * @param     reference    $template    smarty 物件
 *
 * @return    string
 */
function smarty_function_numeric2cht($params, Smarty_Internal_Template $template)
{
    // 欲轉換之數字
    $num = isset($params['num']) ? trim($params['num']) : 0;
    // 處理模式 (1.刪除前導零 2.小數部分不刪除前導零 3.日期 4.星期)
    $mode = isset($params['mode']) ? intval($params['mode']) : 1;
    // 中文小寫顯示
    $lower = isset($params['lower']) ? (bool)$params['lower'] : true;
    // 轉換方式 (0.普通方式 1.逐位轉換沒有十百千萬)
    $conv = isset($params['conv']) ? intval($params['conv']) : 0;

    $numChar = array(
        '零', '一', '二', '三', '四', '五', '六', '七', '八', '九',
        '零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'
    );
    $numUnit = array(
        '', '十', '百', '千', '', '萬', '億', '兆',
        '', '拾', '佰', '仟', '', '萬', '億', '兆'
    );

    $dateChar = array('年', '月', '日');
    $dayOfWeek = array('日', '一', '二', '三', '四', '五', '六');
    $decimalPoint = array('點', '點');

    // 顯示中文小寫
    if ($lower) {

        $loFlag = 0;

    } else {

        $loFlag = 1;
        $result = '';
    }

    // 處理模式
    switch($mode) {
        /**
         * 刪除前導零
         */
        case 1:
            preg_match_all('/^0*(\d*)\.?(\d*)/', $num, $numbers);
            break;
        /**
         * 小數部分不刪除前導零
         */
        case 2:
            preg_match_all('/(\d*)\.?(\d*)/', $num, $numbers);
            break;
        /**
         * 日期
         */
        case 3:
             preg_match_all('/^(\d*)\-(\d*)\-(\d*)$/', $num, $numbers);
             break;
        /**
         * 星期
         */
        case 4:
             preg_match_all('/^(\d*)$/', $num, $numbers);
             break;
        default:
            return null;
    }

    if (isset($numbers[0][0]) &&
        strcasecmp(intval($numbers[0][0]), $numbers[0][0]) === 0 &&
        strlen($numbers[0][0]) === 10) {

        // 星期格式
        return $dayOfWeek[date('w', $numbers[0][0])];
    }
    if (isset($numbers[3][0]) && !empty($numbers[3][0])) {

        // 日期格式
        return smarty_function_numeric2cht(
            array(
                'num' => $numbers[1][0],
                'mode' => 1,
                'lower' => $lower,
                'conv' => 1
            ),
            $template
        )
        . $dateChar[0]
        . smarty_function_numeric2cht(
            array(
                'num' => $numbers[2][0]
            ),
            $template
        )
        . $dateChar[1]
        . smarty_function_numeric2cht(
            array(
                'num' => $numbers[3][0]
            ),
            $template
        )
        . $dateChar[2];
    }
    if (isset($numbers[2][0]) && !empty($numbers[2][0])) {

        // 小數
        $point = $decimalPoint[count($decimalPoint) / 2 * $loFlag];

        return smarty_function_numeric2cht(
            array(
                'num' => $numbers[1][0],
                'mode' => 1,
                'lower' => $lower
            ),
            $template
        )
        . $point
        . smarty_function_numeric2cht(
            array(
                'num' => $numbers[2][0],
                'mode' => 2,
                'lower' => $lower,
                'conv' => 1
            ),
            $template
        );
    }
    if (isset($numbers[1][0]) && !empty($numbers[1][0])) {
        // 整數
        $curNum = $numbers[1][0];
        $stChar = count($numChar) / 2 * $loFlag;
        $stUnit = count($numUnit) / 2 * $loFlag;
        $out = array();

        switch ($conv) {
            /**
             * 逐位轉換沒有十百千萬
             */
            case 1:

                for ($i = 0; $i < strlen($curNum); $i++) {

                    $out[$i] = $numChar[$stChar + $curNum[$i]];
                }
                break;

            /**
             * 普通方式
             */
            default:

                $curNumS = strrev($curNum);

                for ($i = 0; $i < strlen($curNumS); $i++) {

                    if ($i % 4 == 1 && $curNum >= 10 && $curNum < 20) {

                        // 去掉 一十中的 '一'
                        $out[$i] = '';

                    } else {

                        $out[$i] = $numChar[$stChar + $curNumS[$i]];
                    }

                    $out[$i] .= $curNumS[$i] != '0' ? $numUnit[$stUnit + $i % 4] : '';
                    $preNum = $i > 0 ? $curNumS[$i - 1] : 0;

                    if ($curNumS[$i] + $preNum == 0) {

                        $out[$i] = '';
                    }
                    if ($i % 4 == 0) {

                        $out[$i] .= $numUnit[$stUnit + 4 + floor($i / 4)];
                    }
                }

                $out = array_reverse($out);
        }

        return join('', $out);
   }

    return null;
}

/**
 * 將 youtube 分享網址替換成嵌入用網址
 *
 * @param     array        $params    參數值
 * @param     reference    $template    smarty 物件
 *
 * @return    string
 */
function smarty_function_youtube_embed_url($params, Smarty_Internal_Template $template)
{
    $embedUrl = 'http://www.youtube.com/embed/';

    // 結束後顯示其他推薦影片
    isset($params['rel']) && $params['rel'] = intval($params['rel']);
    // 載入後自動隱藏播放控制項
    isset($params['autohide']) && $params['autohide'] = intval($params['autohide']);
    // 載入後自動播放
    isset($params['autoplay']) && $params['autoplay'] = intval($params['autoplay']);
    // 是否顯示播放控制項
    isset($params['controls']) && $params['controls'] = intval($params['controls']);
    // 反覆播放
    isset($params['loop']) && $params['loop'] = intval($params['loop']);
    // 停用鍵盤操作
    isset($params['disablekb']) && $params['disablekb'] = intval($params['disablekb']);
    // 指定播放開始位置
    isset($params['start']) && $params['start'] = intval($params['start']);
    // 指定播放結束位置
    isset($params['end']) && $params['end'] = intval($params['end']);
    // 啟用全視窗
    isset($params['fs']) && $params['fs'] = intval($params['fs']);
    // 播放前顯示相關資訊
    isset($params['showinfo']) && $params['showinfo'] = intval($params['showinfo']);
    // 1 顯示影片註解, 3 不顯示
    isset($params['iv_load_policy']) && $params['iv_load_policy'] = intval($params['iv_load_policy']);
    // 是否隱藏字幕
    isset($params['cc_load_policy']) && $params['cc_load_policy'] = intval($params['cc_load_policy']);
    // 播放樣式(light, dark)
    isset($params['theme']) && $params['theme'] = trim($params['theme']);

    /**
     * 使用於 AS2 播放器(過時的參數)
     */
    // 顯示邊框
    isset($params['border']) && $params['border'] = intval($params['border']);
    // 邊框的基本色
    isset($params['color1']) && $params['color1'] = trim($params['color1']);
    // 邊框的輔助色
    isset($params['color2']) && $params['color2'] = trim($params['color2']);
    // 啟用 HD 高畫質
    isset($params['hd']) && $params['hd'] = intval($params['hd']);
    // 啟用增強的 Genie 選單
    isset($params['egm']) && $params['egm'] = intval($params['egm']);
    // 啟用搜尋框 (如果 rel 參數設置為 0, 那麼，不管 showsearch 設為哪一個值，都會停用搜尋框)
    isset($params['showsearch']) && $params['showsearch'] = intval($params['showsearch']);

    // 取得影片識別碼
    $videoId = null;
    $pattern = '/^(?:https?:\/\/)?'
             . '(?:www\.)?'
             . '(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))'
             . '([\w-]{11})(?:.+)?$/i';
    if (preg_match($pattern, $params['url'], $vid)) { // 完整網址

        $videoId = $vid[1];
    }
    parse_str(parse_url($params['url'], PHP_URL_QUERY), $extraParams); // 取得額外參數
    if (isset($extraParams['v'])) {

        unset($extraParams['v']); // 忽略影片 ID 參數
    }

    $params = array_merge($params, $extraParams); // 合併播放參數

    if (is_null($videoId)) {

        $embedUrl = $params['url'];

    } else {

        $embedUrl .= $videoId;

        // 補上設定參數值
        foreach ($params as $key => $value) {

            if ($key == 'url') {

                continue; // 忽略 url 參數
            }

            $embedUrl .= (strpos($embedUrl, '?') === false ? '?' : '&') . $key . '=' . $value;
        }
    }

    return $embedUrl;
}

/**
 * 將字串中部分文字替換為其他字元
 *
 * @param     string     $string        欲轉換的字串
 * @param     string     $markChar      轉換字元符號
 * @param     integer    $markOffset    替換開始位置
 * @param     integer    $markLength    替換長度
 *
 * @return    boolean
 */
function smarty_modifier_str_mark($string, $markChar = '*', $markOffset = null, $markLength = null)
{
    return strMark($string, $markChar, $markOffset, $markLength);
}

/**
 * 格式化日期
 *
 * @param     integer    $time          UNIX 時間
 * @param     string     $formatType    格式化設定類型
 * @param     string     $formatStr     格式化字串
 *
 * @return    string
 */
function smarty_modifier_format_date($time, $formatType = 'date_format', $formatStr = 'Y-m-d')
{
    // 判斷時間是否有效
    if ($time == 0) {

        return null;
    }

    if (!empty($formatType) && isset($GLOBALS['_CFG'][$formatType])) {

        $format = $GLOBALS['_CFG'][$formatType];

    } elseif (empty($formatStr)) {

        $format = $GLOBALS['_CFG']['date_format'];

    } else {

        $format = $formatStr;
    }

    return date($format, $time);
}

/**
 * 格式化數值
 *
 * @param     float      $num           輸入的值
 * @param     integer    $formatType    格式化類型
 *
 * @return    boolean
 */
function smarty_modifier_format_number($num, $formatType = null)
{
    if (!isset($num)) {

        $num = 'null';
    }
    if (is_null($formatType) && isset($GLOBALS['_CFG']['number_format'])) {

        $formatType = $GLOBALS['_CFG']['number_format'];
    }

    return formatNumber($num, $formatType);
}

/**
 * 格式化價格
 *
 * @param     float      $price         價格
 * @param     integer    $formatType    格式化類型
 *
 * @return    boolean
 */
function smarty_modifier_format_price($price, $formatType = null)
{
    if (!isset($num)) {

        $num = 'null';
    }
    if (is_null($formatType) && isset($GLOBALS['_CFG']['price_format'])) {

        $formatType = $GLOBALS['_CFG']['price_format'];
    }

    return formatPrice($price, $formatType);
}

/**
 * 將位元組轉成可閱讀格式
 *
 * @param     float      $bytes       位元組
 * @param     integer    $decimals    分位數
 * @param     string     $unit        容量單位
 *
 * @return    string
 *
 * @uses      formatByte()
 */
function smarty_modifier_format_byte($bytes, $decimals = 0, $unit = '')
{
    if (!isset($bytes)) {
        $bytes = 0;
    }

    return formatByte($bytes, $decimals, $unit);
}

/**
 * 格式化折扣數
 *
 * @param    integer    $num        折數原始數值
 * @param    boolean    $integer    折數顯示整數
 *
 * @return    mixed
 */
function smarty_modifier_format_discount($num, $integer = false)
{
    $num = isset($num) ? floatval($num) : 0;

    if ($num == 100 || $num == 0) {

        return $num;
    }

    if ($num > 1) {

        $num /= 10;
    }

    if (strpos($num, '.') !== false && $integer == false) {

        $num *= 10;
    }

    return $num;
}

/**
 * 格式化陣列成 HTML 的 JSON 結構字串
 *
 * @param     array     $array    欲格式化之陣列
 *
 * @return    string
 */
function smarty_modifier_format_array_to_htmljson(array $array)
{
    return htmlentities(json_encode($array), ENT_QUOTES, 'UTF-8');
}

/**
 * 為陣列中每個鍵值增加後綴字
 *
 * @param     array      $array     格式化設定類型
 * @param     string     $suffix    後綴字
 *
 * @return    array
 */
function smarty_modifier_suffix_array_values($array, $suffix = '')
{
    return arraySuffixValues($array, $suffix);
}

/**
 * 區塊 - 將區塊中內容的部分文字替換為其他字元
 *
 * @param     array     $params    參數值
 *
 * @return    string
 *
 * @uses      smarty_modifier_str_mark()
 */
function smarty_block_strmask($params, $content, Smarty_Internal_Template $template, &$repeat)
{
    // 只在結束標籤時輸出
    if (!$repeat) {

        $status = true;
        $markChar = '*'; // 轉換字元符號
        $markOffset = null; // 替換開始位置
        $markLength = null; // 替換長度

        foreach ($params as $_key => $_value) {
            switch (strtolower($_key)) {
                case 'mark_char':
                    ${strToCamel($_key)} = htmlspecialchars((string)$_value, ENT_COMPAT, 'UTF-8', false);
                    break;
                case 'mark_offset':
                case 'mark_length':
                    ${strToCamel($_key)} = (int)$_value;
                    break;
                case 'status':
                    ${strToCamel($_key)} = (boolean)$_value;
                    break;
                default:
            }
        }

        if (true == $status) {
            $content = smarty_modifier_str_mark(trim($content), $markChar, $markOffset, $markLength);
        }

        return $content;
    }
}

/**
 * 設定預設圖片路徑
 *
 * @param     array     $params      參數值
 * @param     object    $compiler    smarty 編譯陣列
 *
 * @return    string
 */
function smarty_modifiercompiler_default_img($params, $compiler)
{
    $output = $params[0];
    if (!isset($params[1])) {
        $params[1] = '\'\'';
    }

    array_shift($params);
    foreach ($params as $param) {
        $output = '(($tmp = @' . $output . ') === null || $tmp === \'\' ? \''
                . str_replace(ROOT_PATH, '', TEMPLATE_PATH)
                . strtr($param, array('"' => '', '\'' => '')) . '\' : $tmp)';
    }

    return $output;
}

if (defined('DUS_ADMIN')) { // 後台使用

    /**
     * 區塊 - 封鎖區塊中內容(僅後台使用)
     *
     * @param     array     $params    參數值
     *
     * @return    string
     */
    function smarty_function_syselse($params, Smarty_Internal_Template $template)
    {
        return $template->left_delimiter . 'syselse' . $template->right_delimiter;
    }

    /**
     * 區塊 - 封鎖區塊中內容(僅後台使用)
     *
     * @param     array     $params    參數值
     *
     * @return    string
     */
    function smarty_block_sys($params, $content, Smarty_Internal_Template $template, &$repeat)
    {
        // 只在結束標籤時輸出
        if (!$repeat) {

            $status = isset($content) && function_exists('adminPriv') && adminPriv('sys', false);

            foreach ($params as $_key => $_value) {

                switch (strtolower($_key)) {
                    case 'status':
                        ${strToCamel($_key)} = (boolean)$_value;
                        break;
                    default:
                }
            }

            $elseSymbol = $template->left_delimiter . 'syselse' . $template->right_delimiter;
            $trueFalse = explode($elseSymbol, $content, 2);

            $trueBlock = isset($trueFalse[0]) ? $trueFalse[0] : null;
            $falseBlock = isset($trueFalse[1]) ? $trueFalse[1] : '<!-- denied -->';

            $content = true == $status ? $trueBlock : $falseBlock;

            return $content;
        }
    }

    /**
     * 分頁選單
     *
     * @param     array        $params      參數值
     * @param     reference    $template    smarty 物件
     *
     * @return    string
     */
    function smarty_function_create_pages($params, Smarty_Internal_Template $template)
    {
        extract($params);

        if (empty($page)) {

            $page = 1;
        }

        $str = '';
        if (!empty($count)) {

            $opt = array();
            $opt[1] = '<option value="1">1</option>';

            if ($count > 1) {
                $opt[$count] = '<option value="' . $count . '"'
                             . ($page == $count ? ' selected="selected"' : '')
                             . '>' . $count . '</option>';
            }

            $i = strlen((string)$count) - 1;
            for ($i; $i >= 0; $i--) {
                $countFloor[$i] = floor($count / pow(10 , $i));
                $pageFloor[$i]  = floor($page / pow(10 , $i));
                $p = $pageFloor[$i] - 9 > 0 ? $pageFloor[$i] - 9 : 1;
                $c = $pageFloor[$i] + 9 > $countFloor[$i] ? $countFloor[$i] : $pageFloor[$i] + 9;

                for ($nav = $p; $nav <= $c; $nav++) {
                    $key = $nav * pow(10 , $i);

                    $opt[$key] = '<option value="' . $key . '"'
                               . ($page == $key ? ' selected="selected"' : '')
                               . '>' . $key . '</option>';
                }
            }

            ksort($opt);
            foreach ($opt as $v) {
                $str .= $v;
            }
        }

        return $str;
    }

    /**
     * 建立編輯器
     *
     * @param   array
     */
    function smarty_function_html_editor($params, Smarty_Internal_Template $template)
    {
        extract($params);

        if (!isset($name)) $name = 'CKeditor';
        if (!isset($value)) $value = '';

        $value = htmlspecialchars($GLOBALS['dus']->urlDecode($value), ENT_COMPAT);
        return '<textarea class="loadCKEditor" cols="80" rows="10" name="' . $name . '">' . $value . '</textarea>';;
    }

    /**
     * 插入管理員資訊
     *
     * @return  string
     */
    function smarty_insert_profile()
    {
        $data['username'] = $_SESSION[SHOP_SESS][sha1('☠ admin_name ☠')];
        $data['avatar_url'] = $_SESSION[SHOP_SESS][sha1('☠ avatar_url ☠')];

        $GLOBALS['smarty']->assign('data', $data);

        $nowTime = $_SERVER['REQUEST_TIME'];
        $readMessage = array();

        $GLOBALS['smarty']->assign('read_message', $readMessage);

        $output = $GLOBALS['smarty']->fetch('common/profile.html');

        return $output;
    }

} else { // 前台使用

    /**
     * 插入目前環境的 URL
     *
     * @return    string
     */
    function smarty_insert_base_url()
    {
        return $GLOBALS['dus']->url();
    }

    /**
     * 插入返回連結
     *
     * @return    string
     *
     * @param     array     $params      參數值
     * @param     object    $template    樣版物件
     *
     * @uses      getBackUrl()
     */
    function smarty_insert_back_url($params, Smarty_Internal_Template $template)
    {
        !isset($params['encode']) && $params['encode'] = false;

        $backUrl = getBackUrl();

        if ($backUrl == getLocationUrl()) {

            $backUrl = 'javascript:window.history.back();';
        }
        if (pathinfo($backUrl, PATHINFO_FILENAME) != pathinfo(PHP_SELF, PATHINFO_FILENAME)) {

            $backUrl = PHP_SELF;

            if (isset($params['base_url'])) {

                $base = array();
                $base['url'] = parse_url(trim($params['base_url']));
                $base['app'] = basename((isset($base['url']['path']) ? $base['url']['path'] : PHP_SELF), '.php');

                $base['query'] = array();
                isset($base['url']['query']) && parse_str(htmlspecialchars_decode($base['url']['query']), $base['query']);

                $backUrl = getRealUrl() . buildUri($base['app'], $base['query']);
                if (isset($base['url']['fragment'])) {
                    $backUrl .= '#' . $base['url']['fragment'];
                }
            }
        }

        if (true == $params['encode']) {

            $backUrl = base64_encode(urlencode($backUrl));
        }

        return $backUrl;
    }

    /**
     * 插入返回連結
     *
     * @return    string
     *
     * @uses      getRefUrl()
     */
    function smarty_insert_ref_url()
    {
        $refUrl = urldecode(getRefUrl(false));

        if ($refUrl == getRealUrl()) {

            $refUrl = PHP_SELF;
        }

        return $refUrl;
    }

    /**
     * 建立連結
     *
     * @param     array     $params      參數值
     * @param     object    $template    樣版物件
     *
     * @return    string
     */
    function smarty_function_link($params, Smarty_Internal_Template $template)
    {
        $params['url'] = isset($params['url']) ? $params['url'] : '';
        $params['param'] = isset($params['param']) ? $params['param'] : array();
        $params['separator'] = isset($params['separator']) ? $params['separator'] : '';
        $params['fragment'] = isset($params['fragment']) ? $params['fragment'] : null;

        if (!empty($params['url'])) {

            $params['param'] = array_merge(getUrlParam($params['url']), $params['param']);
            $params['fragment'] = parse_url($params['url'], PHP_URL_FRAGMENT);

            $params['param'] = array_merge($GLOBALS['dus']->urlParam($params['url']), $params['param']);
        }

        $linkUrl = getLocationUrl($params['param'], $params['separator']);
        !empty($params['fragment']) && $linkUrl .= '#' . $params['fragment'];

        return $linkUrl;
    }

    /**
     * 產生 META 麵包屑
     *
     * @param     array     $params      參數值
     * @param     object    $template    樣版物件
     *
     * @return    string
     */
    function smarty_function_breadcrumb_meta($params = array(), Smarty_Internal_Template $template)
    {
        // 載入 smarty 外掛函式檔
        require_once(SMARTY_PLUGINS_DIR . 'shared.escape_special_chars.php');

        // 主要字串 (網站標題)
        $main = '';
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

        foreach ($params as $_key => $_value) {

            switch (strtolower($_key)) {
                case 'main':
                case 'extra':
                    if (!is_array($_value)) {

                        ${strToCamel($_key)} = smarty_function_escape_special_chars((string)$_value);

                    } else {

                        ${strToCamel($_key)} = (array)$_value;
                    }
                    break;
                case 'separator':
                case 'delimiter':
                    ${strToCamel($_key)} = smarty_function_escape_special_chars((string)$_value);
                    break;
                case 'reverse':
                case 'shiftunit':
                    ${strToCamel($_key)} = (boolean)$_value;
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
                $main = array_map('trim', $main);
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
                $extraEleArr = array_map('trim', $extraEleArr);
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
            $extraEle = str_replace($delimiter, $separator, $extraEle);
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

        // 是否轉換全形字元為半形字元
        return $shiftunit ? makeSemiangle($breadcrumb) : $breadcrumb;
    }

    /**
     * 取得社群分享網址
     *
     * @param     array     $params    參數值
     *
     * @return    string
     */
    function smarty_function_share_url($params)
    {
        $params['url'] = isset($params['url']) ? trim($params['url']) : '';
        $params['type'] = isset($params['type']) ? strtolower(trim($params['type'])) : '';
        $params['text'] = isset($params['text']) ? trim($params['text']) : $GLOBALS['_CFG']['shop_name'];
        $params['popup'] = !empty($params['popup']) ? true : false;

        if ($params['url'] == '') {

            $shareUrl = getLocationUrl();

        } elseif (strpos($params['url'], '://') == false) {

            $shareUrl = $GLOBALS['dus']->url() . $params['url'];

        } else {

            $shareUrl = $params['url'];
        }
        $shareUrl != '' && $shareUrl = urlencode($shareUrl);

        switch ($params['type']) {
            /**
             * Facebook
             */
            case 'facebook':

                $shareUrl = 'http://www.facebook.com/share.php?u=' . $shareUrl;
                if (true == $params['popup']) {

                    $js = 'var windowWidth = 420;'
                        . 'var windowHeight = 450;'
                        . 'var windowLeft = 0;'
                        . 'var windowTop = 0;'
                        . 'if (typeof window.screenX != \'undefined\' && (window.screenX >= 0 && window.screenY >= 0)) {'
                        . 'windowLeft = (window.screen.availWidth - windowWidth) / 2;'
                        . 'windowTop = (window.screen.availHeight - windowHeight) / 2;'
                        . '} else if (typeof window.screenLeft != \'undefined\' && (window.screenLeft >= 0 && window.screenTop >= 0)) {'
                        . 'windowLeft = (window.screen.availWidth - windowWidth) / 2;'
                        . 'windowTop = (window.screen.availHeight - windowHeight) / 2;'
                        . '}'
                        . 'window.open(\'' . urlencode($shareUrl) . '\', null, \'width=\' + windowWidth + \',height=\' + windowHeight + \',left=\' + windowLeft + \',top=\' + windowTop + \',resizable=yes,scrollbars=no,chrome=yes,centerscreen=yes\');';
                    $shareUrl = 'javascript:void((function()%7B' . $js . '%7D)());';
                }
                break;
            /**
             * Google+
             */
            case 'google':

                $shareUrl = 'https://plus.google.com/share?url=' . $shareUrl;
                break;
            /**
             * Pinterest
             */
            case 'pinterest':

                if (isset($params['media'])) {

                    // one image
                    $shareUrl = 'http://pinterest.com/pin/create/button/'
                              . '?url=' . $shareUrl
                              . '&description=' . urlencode($params['text'])
                              . '&media=' . urlencode($GLOBALS['dus']->url() . trim($params['media']));

                } else {

                    // any images
                    $shareUrl = 'javascript:void('
                              . '(function()%7B'
                              . 'var%20e=document.createElement(\'script\');'
                              . 'e.setAttribute(\'type\',\'text/javascript\');'
                              . 'e.setAttribute(\'charset\',\'UTF-8\');'
                              . 'e.setAttribute(\'src\',\'http://assets.pinterest.com/js/pinmarklet.js?r=\'+Math.random()*99999999);'
                              . 'document.body.appendChild(e)%7D)());';
                }
                break;
            /**
             * 噗浪
             */
            case 'plurk':

                $shareUrl = 'http://www.plurk.com/?qualifier=shares&status=' . $shareUrl . urlencode(' ' . $params['text']);
                break;
            /**
             * 推特
             */
            case 'twitter':

                $shareUrl = 'http://twitter.com/home?status=' . urlencode($params['text'] . ' ') . $shareUrl;
                break;
            /**
             * 新浪微博
             */
            case 'sina':

                $shareUrl = 'http://v.t.sina.com.cn/share/share.php'
                          . '?title=' . urlencode($params['text'])
                          . '&url=' . $shareUrl;
                break;
            /**
             * 騰訊微博
             */
            case 'qq':

                $shareUrl = 'http://share.v.t.qq.com/index.php'
                          . '?c=share'
                          . '&a=index'
                          . '&title=' . urlencode($params['text'])
                          . '&url=' . $shareUrl;
                break;
            /**
             * 人人網
             */
            case 'renren':

                $shareUrl = 'http://widget.renren.com/dialog/share'
                          . '?resourceUrl=' . $shareUrl
                          . '&title=' . urlencode($params['text']);
                break;
            /**
             * 百度
             */
            case 'baidu':

                $shareUrl = 'http://www.baidu.com/home/page/show/url'
                          . '?url=' . $shareUrl
                          . '&name=' . urlencode($params['text'])
                          . '&key='
                          . '&apiType='
                          . '&buttonType='
                          . 'from=addtobaidu';
                break;
            /**
             * LINE
             */
            case 'line':

                $shareUrl = 'https://lineit.line.me/share/ui?url=' . $shareUrl . '#/';
                if (true == $params['popup']) {

                    $js = 'var windowWidth = 503;'
                        . 'var windowHeight = 510;'
                        . 'var windowLeft = 0;'
                        . 'var windowTop = 0;'
                        . 'if (typeof window.screenX != \'undefined\' && (window.screenX >= 0 && window.screenY >= 0)) {'
                        . 'windowLeft = (window.screen.availWidth - windowWidth) / 2;'
                        . 'windowTop = (window.screen.availHeight - windowHeight) / 2;'
                        . '} else if (typeof window.screenLeft != \'undefined\' && (window.screenLeft >= 0 && window.screenTop >= 0)) {'
                        . 'windowLeft = (window.screen.availWidth - windowWidth) / 2;'
                        . 'windowTop = (window.screen.availHeight - windowHeight) / 2;'
                        . '}'
                        . 'window.open(\'' . urlencode($shareUrl) . '\', null, \'width=\' + windowWidth + \',height=\' + windowHeight + \',left=\' + windowLeft + \',top=\' + windowTop + \',resizable=yes,scrollbars=no,chrome=yes,centerscreen=yes\');';
                    $shareUrl = 'javascript:void((function()%7B' . $js . '%7D)());';
                }
                break;
            /**
             * LinkedIn
             */
            case 'linkedin':

                $shareUrl = 'http://www.linkedin.com/shareArticle'
                          . '?mini=true&url=' . $shareUrl
                          . '&title=' . urlencode($params['text'])
                          . '&summary='
                          . '&source=';
                break;

            default:
        }

        return $shareUrl;
    }

    /**
     * 插入購物車商品資訊
     *
     * @return    integer
     */
    function smarty_insert_page_cart()
    {
        $curCacheStatus = $GLOBALS['smarty']->caching;
        $GLOBALS['smarty']->caching = Smarty::CACHING_OFF;

        // 載入函式檔
        require_once(BASE_PATH . 'lib_flow.php');

        $cartGoods = getCartGoods(null, false); // 取得購物商品

        $GLOBALS['smarty']->assign('global_cart_goods', $cartGoods);
        $GLOBALS['smarty']->assign('global_cart_info', calcCartGoods($cartGoods));

        $blockHtml = $GLOBALS['smarty']->fetch('library/page_cart.lbi');

        $GLOBALS['smarty']->caching = $curCacheStatus;

        return $blockHtml;
    }
}
