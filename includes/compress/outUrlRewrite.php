<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/*
 * URL 重寫處理
 * ========================================================
 * 版權所有 (C) 2015 鉅潞科技網頁設計公司，並保留所有權利。
 * 網站地址: http://www.grnet.com.tw
 * ========================================================
 * Date: 2015-08-27 15:18
 */

class outUrlRewrite
{
    public static function execute($html)
    {
        $min = new self($html);

        return $min->process();
    }

    /**
     * 建構子
     *
     * @access    private
     *
     * @param     string     $html    HTML 內容
     *
     * @return    string
     */
    private function __construct($html)
    {
        $this->_html = $html;
    }

    /**
     * 主要處理程序
     *
     * @access    private
     *
     * @return    string
     */
    private function process()
    {

        // $pattern = '/(?P<prop>(?:data-(?:\w+-)?)?(?:src|href|action))\s*=\s*(?P<quote_start>["|\'])(?P<path>(?P<lang_dir>(?:' . implode('|', array_column($GLOBALS['languages'], 'directory')) . ')\/?)?(?P<rel_full>\.?\/?' . preg_quote(trimSeparator(dirname(PHP_SELF)), '/') . '\/?|(?P<rel_path>(?:\.{1,2}\/)+)?(?P<rel_dir>(?:[^\'"\.:\n<>=])*(?<=\/)))?(?P<file_name>[^\/\'"\n\.=<>:#]+(?P<file_extension>\.php)?)?(?P<query_string>[?][^\/\'#"]*)?)(?P<fragment>#[\w\-]+)?(?P<quote_end>["|\'])/i';

        // $this->_html = preg_replace_callback(
        //     $pattern,
        //     array($this, '_urlReplace'),
        //     $this->_html
        // );
        $this->_html = preg_replace_callback(
            '/(src|href|action)(=\s*["|\'])(\/*' .
            preg_quote(trimSeparator(dirname(PHP_SELF)), '/') .
            '|\.\/*)?((?:[^\/\'"]*\.php)?(?:[#|?][^\/\'"]*)?)(["|\'])/i',
            array($this, '_urlReplace'),
            $this->_html
        );

        return $this->_html;
    }

    /**
     * 網址取代
     *
     * @access    private
     *
     * @return    string
     */
    private function _urlReplace($match)
    {
        $app = '';
        $query = array();
        if (preg_match('/^#/', $match[4])) {

            $parseUrl['fragment'] = ltrim($match[4], '#');

            $url = $match[4];

        } else {

            $parseUrl = parse_url($match[4]);

            if (false !== strpos($match[3], './')) {

                $app = 'index';

            } elseif (isset($parseUrl['path'])) {

                $app = basename($parseUrl['path'], '.php');
            }

            if (isset($parseUrl['query'])) {

                parse_str(htmlspecialchars_decode($parseUrl['query']), $query);
            }

            $url = buildUri($app, $query, array('arg_separate' => '&amp;'));
            if (isset($parseUrl['fragment'])) {

                $url .= '#' . $parseUrl['fragment'];
            }
        }

        return $match[1] . $match[2] . ($url == '' ? './' : rtrim($url, '/')) . $match[5];
    }
}
