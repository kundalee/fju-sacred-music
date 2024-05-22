<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 陣列函數庫
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/**
 * 可用一個陣列與第一個陣列進行比較
 *
 * @param     array    $arr1    與其他陣列進行比較的第一個陣列
 * @param     array    $arr2    與第一個陣列進行比較的陣列
 *
 * @return    array
 */
function arrayMultiDiff($arr1, $arr2)
{
    $ret = array();

    foreach ($arr1 as $key => $value) {

        if (isset($arr2[$key])) {

            if (gettype($value) == 'array' && gettype($arr2[$key]) == 'array') {

                $diff = arrayMultiDiff($value, $arr2[$key]);
                if (count($diff)) {

                    $ret[$key] = $diff;
                }

            } elseif($value !== $arr2[$key]) {

                $ret[$key] = $value;
            }

        } else {

            $ret[$key] = $value;
        }
    }

    return $ret;
}

/**
 * 從一個二維陣列中返回指定鍵的所有值
 *
 * @param     array     $arr
 * @param     string    $col
 *
 * @return    array
 */
function arrayColValues(&$arr, $col)
{
    $ret = array();
    foreach ($arr as $row) {

        if (isset($row[$col])) {

            $ret[] = $row[$col];
        }
    }
    return $ret;
}

/**
 * 使用自訂處理函數建立新陣列
 *
 * @param      array       $array       輸入陣列
 * @param      callable    $callback    處理函數
 *
 * @return     array
 */
function arrayBuild($array, Closure $callback)
{
    $results = array();

    foreach ($array as $key => $value){

        list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

        $results[$innerKey] = $innerValue;
    }

    return $results;
}

/**
 * 將多維陣列轉換成一維陣列，並使用 "." 去顯示原陣列資料的深度
 *
 * @param     array     $array      輸入陣列
 * @param     string    $prepend    鍵值前綴字
 *
 * @return    array
 */
function arrayDot($array, $prepend = '')
{
    $dot = function ($array, $prepend = '') use (&$dot) {

        $results = array();

        foreach ($array as $key => $value) {

            if (is_array($value)) {

                $results = array_merge($results, $dot($value, $prepend . $key . '.'));

            } else {

                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    };

    $results = array();
    foreach ($array as $key => $value) {

        if (is_array($value)) {

            $results = array_merge($results, $dot($value, $prepend . $key . '.'));

        } else {

            $results[$prepend . $key] = $value;
        }
    }

    return $results;
}

/**
 * 回傳陣列中第一個通過給定測試為真的元素
 *
 * @param     array       $array       輸入陣列
 * @param     callable    $callback    處理函數
 * @param     mixed       $default     預設值
 *
 * @return    mixed
 */
function arrayFirst($array, Closure $callback, $default = null)
{
    foreach ($array as $key => $value) {

        if (call_user_func($callback, $key, $value)) {

            return $value;
        }
    }

    return value($default);
}

/**
 * 將多維陣列扁平化成一維陣列
 *
 * @param     array    $array    輸入陣列
 *
 * @return    array
 */
function arrayFlatten($array)
{
    $return = array();

    array_walk_recursive(

        $array, function($x) use (&$return) {

            $return[] = $x;
        }
    );

    return $return;
}

/**
 * 使用 "." 符號從陣列中取得一個項目
 *
 * @param     array     $array      被搜尋的陣列
 * @param     string    $key        鍵名
 * @param     mixed     $default    預設值
 *
 * @return    mixed
 */
function arrayGet($array, $key, $default = null)
{
    if (!is_array($array)) {

        return $default;
    }
    if (is_null($key)) {

        return $array;
    }

    if (array_key_exists($key, $array)) {

        return $array[$key];
    }
    if (strpos($key, '.') === false) {

        return !isset($array[$key]) ? $default : $array[$key];
    }

    foreach (explode('.', $key) as $segment) {

        if (is_array($array) && array_key_exists($segment, $array)) {

            $array = $array[$segment];

        } else {

            return $default;
        }
    }

    return $array;
}

/**
 * 從給定陣列中取得子元素
 *
 * @param     array           $array    被搜尋的陣列
 * @param     array|string    $keys     欲取得的元素鍵名或鍵名陣列
 *
 * @return    array
 */
function arrayOnly($array, $keys)
{
    return array_intersect_key($array, array_flip((array)$keys));
}

/**
 * 取得所有除了被排除鍵名以外的陣列
 *
 * @param     array           $array    被搜尋的陣列
 * @param     array|string    $keys     欲排除的元素鍵名或鍵名陣列
 *
 * @return    array
 */
function arrayExcept($array, $keys)
{
    return array_diff_key($array, array_flip((array)$keys));
}

/**
 * 取得巢狀陣列中的指定鍵值元素
 *
 * @param     array     $array    被搜尋的陣列
 * @param     string    $key      欲取得的元素鍵名
 *
 * @return    array
 */
function arrayFetch($array, $key)
{
    $results = array();
    foreach (explode('.', $key) as $segment) {

        foreach ($array as $value) {

            if (array_key_exists($segment, $value = (array)$value)) {

                $results[] = $value[$segment];
            }
        }
        $array = array_values($results);
    }

    return array_values($results);
}

/**
 * 使用自訂處理函數過濾陣列
 *
 * @param     array       $array       輸入陣列
 * @param     callable    $callback    處理函數
 *
 * @return    array
 */
function arrayWhere($array, Closure $callback)
{
    $filtered = array();

    foreach ($array as $key => $value) {

        if (call_user_func($callback, $key, $value)) {

            $filtered[$key] = $value;
        }
    }

    return $filtered;
}

/**
 * 根據指定的鍵值對陣列排序
 *
 * @param     array      $array            要排序的陣列
 * @param     string     $keyname          鍵值名稱
 * @param     integer    $sortDirection    排序方向
 *
 * @return    array
 */
function arrayColumnSort($array, $keyname, $sortDirection = SORT_ASC)
{
    return arraySortByMultifields($array, array($keyname => $sortDirection));
}

/**
 * 計算陣列維度
 *
 * @param     array      $array    進行計算的陣列
 * @param     integer    $count    維度量
 *
 * @return    integer
 */
function arrayCountDimension($array, $count = 0)
{
    if (is_array($array)) {

        return call_user_func(__FUNCTION__, reset($array), ++$count);

    } else {

        return $count;
    }
}

/**
 * 遞迴計算陣列的差集
 *
 * @param     array    $arr1    與其他陣列進行比較的第一個陣列
 * @param     array    $arr2    與第一個陣列進行比較的陣列
 *
 * @return    array
 */
function arrayDiffRecursive(array $arr1, array $arr2)
{
    $ret = array();

    foreach ($arr1 as $key => $value) {

        if (isset($arr2[$key])) {

            if (gettype($value) == 'array' && gettype($arr2[$key]) == 'array') {

                $diff = call_user_func(__FUNCTION__, $value, $arr2[$key]);

                if (count($diff)) {

                    $ret[$key] = $diff;
                }

            } elseif($value !== $arr2[$key]) {

                $ret[$key] = $value;
            }

        } else {

            $ret[$key] = $value;
        }
    }

    return $ret;
}

/**
 * 遞迴使用鍵名計算陣列的差集
 *
 * @param     array    $arr1    從這個陣列進行比較
 * @param     array    $arr2    針對此陣列進行比較
 * @param     array    $....    更多比較陣列
 *
 * @return    array
 */
function arrayDiffKeyRecursive(array $arr1, array $arr2)
{
    $arrays = func_get_args();
    $base = array_shift($arrays);

    foreach ($arrays as $array) {

        reset($base);

        while (list($key, $value) = @each($array)) {

            if (is_array($value) && @is_array($base[$key])) {

                $base[$key] = call_user_func(__FUNCTION__, $base[$key], $value);
            } else {

                if (array_key_exists($key, $array)) {

                    unset($base[$key]);
                }
            }
        }
    }

    return $base;
}

/**
 * 將一個二維陣列按照指定字段的值分組
 *
 * @param     array    $arr         進行分組的陣列
 * @param     mixed    $keyField    用來分組的鍵名
 *
 * @return    array
 */
function arrayGroupBy($arr, $keyField)
{
    if (empty($arr) || (!is_array($arr))) {
        return $arr;
    }

    if (!is_string($keyField) && !is_int($keyField) && !is_float($keyField) && !is_callable($keyField)) {

        trigger_error(
            sprintf('%s: The keyField should be a string, an integer, or a callback', __FUNCTION__),
            E_USER_ERROR
        );

        return null;
    }

    $ret = array();
    foreach ($arr as $val) {

        if (is_callable($keyField)) {

            $key = call_user_func($keyField, $val);

        } elseif (is_object($val) && isset($val->{$keyField})) {

            $key = $val->{$keyField};

        } elseif (isset($val[$keyField])) {

            $key = $val[$keyField];

        } else {

            continue;
        }

        $ret[$key][] = $val;
    }

    if (func_num_args() > 2) {

        $args = func_get_args();

        foreach ($ret as $key => $val) {

            $parms = array_merge(array($val), array_slice($args, 2, func_num_args()));

            $ret[$key] = call_user_func_array(__FUNCTION__, $parms);
        }
    }

    return $ret;
}

/**
 * 將一個元素插入到指定位置
 *
 * @param     array      $array       操作陣列
 * @param     integer    $position    插入位置
 * @param     array      $insert      插入的元素
 *
 * @return    void
 */
function arrayInsert(&$array, $position, $insert)
{
    if (is_int($position)) {

        array_splice($array, $position, 0, $insert);

    } else {

        $pos = array_search($position, array_keys($array));

        $array = array_merge(
            array_slice($array, 0, $pos),
            $insert,
            array_slice($array, $pos)
        );
    }
}

/**
 * 遞迴計算陣列的交集
 *
 * @param     array    $arr1    與其他陣列進行比較的第一個陣列
 * @param     array    $arr2    與第一個陣列進行比較的陣列
 *
 * @return    array
 */
function arrayIntersectRecursive(array $arr1, array $arr2)
{
    foreach ($arr1 as $key => $value) {

        if (!isset($arr2[$key])) {

            unset($arr1[$key]);

        } else {

            if (is_array($arr1[$key])) {

                $arr1[$key] = call_user_func(__FUNCTION__, $arr1[$key], $arr2[$key]);

            } elseif ($arr2[$key] !== $value) {

                unset($arr1[$key]);
            }
        }
    }

    return $arr1;
}

/**
 * 把多個多維陣列合併為一個陣列(保持索引)
 *
 * @param     array    $arr1    輸入的第 1 個陣列
 * @param     array    $arr2    輸入的第 2 個陣列
 * @param     array    $....    輸入的第 N 個陣列
 *
 * @return    array
 */
function arrayMergeKeys(array $arr1, array $arr2)
{
    $arrays = func_get_args();
    $base = array_shift($arrays);

    foreach ($arrays as $array) {

        reset($base);

        while (list($key, $value) = @each($array)) {

            if (is_array($value) && @is_array($base[$key])) {

                $base[$key] = call_user_func(__FUNCTION__, $base[$key], $value);

            } else {

                $base[$key] = $value;
            }
        }
    }

    return $base;
}

/**
 * 從陣列中刪除空白的元素 (包括只有空白字符的元素)
 *
 * @param     array       $arr
 * @param     boolean     $trim
 * @param     callable    $callback
 *
 * @return    array
 */
function arrayRemoveEmpty(&$arr, $trim = true, Closure $callback = null)
{
    foreach ($arr as $key => $value) {

        if (is_array($value)) {

            $args = func_get_args();
            $args[0] = &$arr[$key];

            call_user_func_array(__FUNCTION__, $args);

        } else {

            $value = trim($value);

            if ($value == '') {

                unset($arr[$key]);

            } elseif ($trim) {

                $arr[$key] = $value;
            }
        }
    }

    if ($trim) {

        $arr = is_callable($callback) ? array_filter($arr, $callback) : array_filter($arr);
    }

    return $arr;
}

/**
 * 從陣列中刪除指定索引值的元素
 *
 * @param     array    $arr     操作陣列
 * @param     mix      $keys    要刪除的索引名稱或索引名稱陣列
 *
 * @return    array
 */
function arrayRemoveKeys(&$arr, $keys = array())
{
    if (empty($arr) || (!is_array($arr))) {

        return $arr;
    }

    // 如果是字串轉為陣列格式
    if (is_string($keys)) {

        $keys = explode(',', $keys);
    }

    if (!is_array($keys)) {

        return $arr;
    }

    $assocKeys = array();
    foreach ($keys as $key) {

        $assocKeys[$key] = true;
    }

    return array_diff_key($arr, $assocKeys);
}

/**
 * 陣列搜尋通過陣列值
 *
 * @param     array      $array     被搜尋的陣列
 * @param     string     $key       在陣列中搜尋的索引名稱
 * @param     mixed      $val       在陣列中搜尋的值
 * @param     boolean    $strict    檢查給定值的類型
 *
 * @return    array
 */
function arraySearchByValue($arr, $key, $val, $strict = false)
{
    $results = array();

    if (is_array($arr)) {

        if (isset($arr[$key])) {

            if (true == $strict && $arr[$key] === $val) {

                $results[] = $arr;
            } else {

                if ($arr[$key] == $val) {

                    $results[] = $arr;
                }
            }
        }

        foreach ($arr as $subArr) {

            $results = array_merge($results, call_user_func(__FUNCTION__, $subArr, $key, $val));
        }
    }

    return $results;
}

/**
 * 打亂陣列
 *
 * @param     array      $array       操作陣列
 * @param     array      $preserve    保留鍵名還是重置鍵名
 *
 * @return    integer
 */
function arrayShuffle(&$array, $preserve = true)
{
    if (empty($array)) {

        return false;
    }

    if ($preserve) {

        $shuffleKeys = array_keys($array);
        shuffle($shuffleKeys);

        array_replace(array_flip($shuffleKeys), $array);

    } else {

        shuffle($array);
    }
}

/**
 * 將一個二維陣列按照指定列進行排序，類似 SQL 語句中的 ORDER BY
 *
 * @param     array    $rowset
 * @param     array    $args
 *
 * @return    array
 */
function arraySortByMultifields($rowset, $args)
{
    $sortArray = array();
    $sortRule = '';
    foreach ($args as $sortField => $sortDir) {

        foreach ($rowset as $offset => $row) {

            $sortArray[$sortField][$offset] = $row[$sortField];
        }

        if (!isset($sortArray[$sortField])) {

            continue;
        }
        $sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
    }

    if (empty($sortArray) || empty($sortRule)) {

        return $rowset;
    }

    eval('array_multisort(' . $sortRule . '$rowset);');

    return $rowset;
}

/**
 * 尋找字串在另一個陣列中第一次出現的位置
 *
 * @param     string     $haystack    搜尋字串
 * @param     array      $needles     被搜尋的陣列
 * @param     integer    $offset      開始搜尋的位置
 *
 * @return    mixed
 */
function arrayStrpos($haystack, array $needles = array(), $offset = 0)
{
    if (is_array($needles)) {

        foreach ($needles as $needle) {

            if (is_array($needle)) {

                $pos = call_user_func(__FUNCTION__, $haystack, $needle);

            } else {

                $pos = strpos($haystack, $needle);
            }

            if (false !== $pos) {

                return $pos;
            }
        }

        return false;

    } else {

        return strpos($haystack, $needles);
    }
}

/**
 * 為陣列中每個鍵值增加後綴字
 *
 * @param     array      $array     格式化設定類型
 * @param     string     $suffix    後綴字
 *
 * @return    array
 */
function arraySuffixValues(array &$array, $suffix = '')
{
    if (!is_array($array)) {

        return null;
    }

    $array = preg_replace('/$/', $suffix, array_values($array));

    return $array;
}

/**
 * 將一個二維陣列轉換為 hashmap
 * 如果省略 $valueField 參數，則轉換結果每一項為包含該項所有數據的陣列
 *
 * @param     array     $arr
 * @param     string    $keyField
 * @param     string    $valueField
 *
 * @return    array
 */
function arrayToHashmap(&$arr, $keyField, $valueField = null)
{
    $ret = array();
    if ($valueField) {

        foreach ($arr as $row) {

            $ret[$row[$keyField]] = $row[$valueField];
        }

    } else {

        foreach ($arr as $row) {

            $ret[$row[$keyField]] = $row;
        }
    }

    return $ret;
}

/**
 * 將一個平面的二維陣列按照指定的字段轉換為樹狀結構
 * 當 $returnReferences 參數為 true 時，返回結果的 tree 字段為樹，refs 字段則為節點引用
 * 利用返回的節點引用，可以很方便的獲取包含以任意節點為根的子樹
 *
 * @param     array      $arr                 原始資料陣列
 * @param     string     $fid                 節點 ID 字段名
 * @param     string     $fparent             節點父 ID 字段名
 * @param     string     $fchildrens          保存子節點的字段名
 * @param     boolean    $returnReferences    是否在返回結果中包含節點引用
 *
 * @return    array
 */
function arrayToTree($arr, $fid, $fparent = 'parent_id', $fchildrens = 'childrens', $returnReferences = false)
{
    $tree = array();
    $pkvRefs = array();

    foreach ($arr as $offset => $row) {

        $pkvRefs[$row[$fid]] = &$arr[$offset];
        $tree[$row[$fid]] = &$arr[$offset];
    }

    foreach ($arr as $offset => $row) {

        $parentId = $row[$fparent];

        if ($parentId) {

            if (!isset($pkvRefs[$parentId])) {

                continue;
            }

            unset($tree[$row[$fid]]);

            $parent = &$pkvRefs[$parentId];
            $parent[$fchildrens][$row[$fid]] = &$arr[$offset];

        } else {

            $tree[$row[$fid]] = &$arr[$offset];
        }
    }

    if ($returnReferences) {

        return array(
            'tree' => $tree,
            'refs' => $pkvRefs
        );

    } else {

        return $tree;
    }
}

/**
 * 將樹狀陣列轉換為平面的陣列
 *
 * @param     array     $node
 * @param     string    $fchildrens
 *
 * @return    array
 */
function treeToArray(&$node, $fchildrens = 'childrens')
{
    $ret = array();

    if (isset($node[$fchildrens]) && is_array($node[$fchildrens])) {

        foreach ($node[$fchildrens] as $child) {

            $ret = array_merge($ret, call_user_func(__FUNCTION__, $child, $fchildrens));
        }

        unset($node[$fchildrens]);

        $ret[] = $node;

    } else {

        $ret[] = $node;
    }

    return $ret;
}

/**
 * 返回匹配鍵名的陣列
 *
 * @param     string     $pattern    要搜尋的模式
 * @param     array      $input      輸入陣列
 * @param     integer    $flags      如果設置為 PREG_GREP_INVERT 返回不匹配的陣列
 *
 * @return    array
 */
function pregGrepKeys($pattern, $input, $flags = 0)
{
    return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
}

/**
 * 遞迴對陣列依照鍵名排序
 *
 * @param     array      $array        輸入的陣列
 * @param     integer    $sortFlags    排序的行為
 *
 * @return boolean
 */
function ksortRecursive(array &$array, $sortFlags = SORT_REGULAR)
{
    if (!is_array($array)) {

        return false;
    }

    ksort($array, $sortFlags);
    foreach ($array as &$arr) {

        if (!is_array($arr)) {

            continue;
        }

        call_user_func(__FUNCTION__, $arr, $sortFlags);
    }

    return true;
}

