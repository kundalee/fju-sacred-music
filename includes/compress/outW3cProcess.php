<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/*
 * W3C 標籤處理
 * ========================================================
 *
 * ========================================================
 * Date: 2013-03-28 11:36
 */
class outW3cProcess
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
        if (preg_match('/(?:src|href|action)/i', $this->_html)) {
            // SRC 與 HREF 路經中的 & 轉換成 &amp;
            $this->_html = preg_replace_callback('/(?:src|href|action)\s*=\s*["|\'][^"|\']*(&(?!\w+;))+[^"|\']*["|\'][^<]*>/i',
                array($this, '_charAndToHtml'),
                $this->_html);
        }

        return $this->_html;
    }

    /**
     * 取代 & 成 &amp;
     *
     * @param     string     $str    欲取代之內容
     *
     * @return    string
     */
    protected function _charAndToHtml($str)
    {
        return preg_replace('/(&(?!\w+;))+/i', '&amp;', rtrim($str[0], '&'));
    }
}
