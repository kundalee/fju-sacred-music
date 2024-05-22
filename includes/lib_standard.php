<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * PHP 標準函式庫
 * ========================================================
 *
 * ========================================================
 * Date: 2015-07-22 18:17
 */

if (isPhp('5.5')) {

    return;
}

if (!function_exists('array_column')) {

    /**
     * array_column()
     *
     * @link      http://php.net/array_column
     *
     * @param     string    $array
     * @param     mixed     $columnKey
     * @param     mixed     $indexKey
     *
     * @return    array
     */
    function array_column(array $array, $columnKey, $indexKey = null)
    {
        if (!in_array($type = strtolower(gettype($columnKey)), array('integer', 'string', 'null'), true)) {

            if ($type === 'double') {

                $columnKey = (int)$columnKey;

            } elseif ($type === 'object' && method_exists($columnKey, '__toString')) {

                $columnKey = (string)$columnKey;

            } else {

                trigger_error(
                    'array_column(): The column key should be either a string or an integer',
                    E_USER_WARNING
                );
                return false;

            }
        }

        if (!in_array($type = strtolower(gettype($indexKey)), array('integer', 'string', 'null'), true)) {

            if ($type === 'double') {

                $indexKey = (int)$indexKey;

            } elseif ($type === 'object' && method_exists($indexKey, '__toString')) {

                $indexKey = (string)$indexKey;

            } else {

                trigger_error(
                    'array_column(): The index key should be either a string or an integer',
                    E_USER_WARNING
                );
                return false;
            }
        }

        $result = array();
        foreach ($array as &$a) {

            if ($columnKey === null) {

                $value = $a;

            } elseif (is_array($a) && array_key_exists($columnKey, $a)) {

                $value = $a[$columnKey];

            } else {

                continue;
            }

            if ($indexKey === null || !array_key_exists($indexKey, $a)) {

                $result[] = $value;

            } else {

                $result[$a[$indexKey]] = $value;
            }
        }

        return $result;
    }
}

if (isPhp('5.4')) {

    return;
}

if (!function_exists('hex2bin')) {
    /**
     * hex2bin()
     *
     * @link      http://php.net/hex2bin
     *
     * @param     string    $data
     *
     * @return    string
     */
    function hex2bin($data)
    {
        if (in_array($type = gettype($data), array('array', 'double', 'object'), true)) {

            if ($type === 'object' && method_exists($data, '__toString')) {

                $data = (string)$data;

            } else {

                trigger_error(
                    'hex2bin() expects parameter 1 to be string, ' . $type . ' given',
                    E_USER_WARNING
                );
                return null;
            }
        }

        if (strlen($data) % 2 !== 0) {

            trigger_error(
                'Hexadecimal input string must have an even length',
                E_USER_WARNING
            );
            return false;

        } elseif (!preg_match('/^[0-9a-f]*$/i', $data)) {

            trigger_error(
                'Input string must be hexadecimal string',
                E_USER_WARNING
            );
            return false;
        }

        return pack('H*', $data);
    }
}

if (isPhp('5.3')) {

    return;
}

if (!function_exists('array_replace')) {
    /**
     * array_replace()
     *
     * @link      http://php.net/array_replace
     *
     * @return    array
     */
    function array_replace()
    {
        $arrays = func_get_args();

        if (($c = count($arrays)) === 0) {

            trigger_error(
                'array_replace() expects at least 1 parameter, 0 given',
                E_USER_WARNING
            );
            return null;

        } elseif ($c === 1) {

            if (!is_array($arrays[0])) {

                trigger_error(
                    'array_replace(): Argument #1 is not an array',
                    E_USER_WARNING
                );
                return null;
            }
            return $arrays[0];
        }

        $array = array_shift($arrays);
        $c--;

        for ($i = 0; $i < $c; $i++) {

            if (!is_array($arrays[$i])) {

                trigger_error(
                    'array_replace(): Argument #' . ($i + 2) . ' is not an array',
                    E_USER_WARNING
                );
                return null;

            } elseif (empty($arrays[$i])) {

                continue;
            }

            foreach (array_keys($arrays[$i]) as $key) {

                $array[$key] = $arrays[$i][$key];
            }
        }

        return $array;
    }
}

if (!function_exists('array_replace_recursive')) {
    /**
     * array_replace_recursive()
     *
     * @link      http://php.net/array_replace_recursive
     *
     * @return    array
     */
    function array_replace_recursive()
    {
        $arrays = func_get_args();

        if (($c = count($arrays)) === 0) {

            trigger_error(
                'array_replace_recursive() expects at least 1 parameter, 0 given',
                E_USER_WARNING
            );
            return null;

        } elseif ($c === 1) {

            if (!is_array($arrays[0])) {

                trigger_error(
                    'array_replace_recursive(): Argument #1 is not an array',
                    E_USER_WARNING
                );
                return null;
            }
            return $arrays[0];
        }

        $array = array_shift($arrays);
        $c--;

        for ($i = 0; $i < $c; $i++) {

            if (!is_array($arrays[$i])) {

                trigger_error(
                    'array_replace_recursive(): Argument #' . ($i + 2) . ' is not an array',
                    E_USER_WARNING
                );
                return null;

            } elseif (empty($arrays[$i])) {

                continue;
            }

            foreach (array_keys($arrays[$i]) as $key) {

                $array[$key] = (is_array($arrays[$i][$key]) && isset($array[$key]) && is_array($array[$key]))
                             ? array_replace_recursive($array[$key], $arrays[$i][$key])
                             : $arrays[$i][$key];
            }
        }

        return $array;
    }
}

if (!function_exists('quoted_printable_encode')) {
    /**
     * quoted_printable_encode()
     *
     * @link      http://php.net/quoted_printable_encode
     *
     * @param     string    $str
     *
     * @return    string
     */
    function quoted_printable_encode($str)
    {
        if (strlen($str) === 0) {

            return '';

        } elseif (in_array($type = gettype($str), array('array', 'object'), true)) {

            if ($type === 'object' && method_exists($str, '__toString')) {

                $str = (string)$str;

            } else {

                trigger_error(
                    'quoted_printable_encode() expects parameter 1 to be string, ' . $type . ' given',
                    E_USER_WARNING
                );
                return null;
            }
        }

        if (function_exists('imap_8bit')) {

            return imap_8bit($str);
        }

        $i = $lp = 0;
        $output = '';
        $hex = '0123456789ABCDEF';
        $length = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'))
                ? mb_strlen($str, '8bit')
                : strlen($str);

        while ($length--) {

            if ((($c = $str[$i++]) === "\015") && isset($str[$i]) && ($str[$i] === "\012") && $length > 0) {

                $output .= "\015" . $str[$i++];

                $length--;

                $lp = 0;

                continue;
            }

            if (
                ctype_cntrl($c) ||
                (ord($c) === 0x7f) ||
                (ord($c) & 0x80) ||
                ($c === '=') ||
                ($c === ' ' && isset($str[$i]) && $str[$i] === "\015")
            ) {

                if (
                    (($lp += 3) > 75 && ord($c) <= 0x7f) ||
                    (ord($c) > 0x7f && ord($c) <= 0xdf && ($lp + 3) > 75) ||
                    (ord($c) > 0xdf && ord($c) <= 0xef && ($lp + 6) > 75) ||
                    (ord($c) > 0xef && ord($c) <= 0xf4 && ($lp + 9) > 75)
                ) {

                    $output .= "=\015\012";
                    $lp = 3;
                }

                $output .= '='.$hex[ord($c) >> 4].$hex[ord($c) & 0xf];
                continue;
            }

            if ((++$lp) > 75) {

                $output .= "=\015\012";
                $lp = 1;
            }

            $output .= $c;
        }

        return $output;
    }
}

if (isPhp('5.1')) {

    return;
}

if (!function_exists('array_diff_key')) {
    /**
     * Computes the difference of arrays using keys for comparison.
     *
     * @param     array    First array
     * @param     array    Second array
     *
     * @return    array    Array with different keys
     */
    function array_diff_key()
    {
        $valuesDiff = array();

        $argc = func_num_args();
        if ($argc < 2) {

            return false;
        }

        $args = func_get_args();
        foreach ($args as $param) {

            if (!is_array($param)) {

                return false;
            }
        }

        foreach ($args[0] as $valueKey => $valueData) {

            for ($i = 1; $i < $argc; $i++) {

                if (isset($args[$i][$valueKey])) {

                    continue 2;
                }
            }

            $valuesDiff[$valueKey] = $valueData;
        }

        return $valuesDiff;
    }
}

if (!function_exists('array_intersect_key')) {
    /**
     * Computes the intersection of arrays using keys for comparison
     *
     * @param     array    First array
     * @param     array    Second array
     *
     * @return    array    Array with interesected keys
     */
    function array_intersect_key($arr1, $arr2)
    {
        $res = array();
        foreach ($arr1 as $key => $value) {

            if (isset($arr2[$key])) {

                $res[$key] = $arr1[$key];
            }
        }

        return $res;
    }
}
