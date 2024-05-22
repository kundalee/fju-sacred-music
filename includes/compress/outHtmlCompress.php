<?php if (!defined('IN_DUS')) die('No direct script access allowed');

class outHtmlCompress
{
    public static function minify($html, $options = array())
    {
        $min = new self($html, $options);
        return $min->process();
    }

    public function __construct($html, $options = array())
    {
        $this->_html = str_replace(chr(13) . chr(10), chr(10), trim($html));
        if (isset($options['xhtml'])) {

            $this->_isXhtml = (bool)$options['xhtml'];
        }
    }

    /**
     * Minify the markeup given in the constructor
     *
     * @return string
     */
    public function process()
    {
        $this->_replacementHash = 'MINIFYHTML' . md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = array();

        $this->_html = preg_replace('/(<\/?[^>]*>)\s+$/m', '$1', $this->_html);

        // replace SCRIPTs (and minify) with placeholders
        $this->_html = preg_replace_callback(
            '/(\s*)<script(\b[^>]*?>)([\s\S]*?)<\/script>(\s*)/i'
            ,array($this, '_removeScriptCB')
            ,$this->_html);

        // replace STYLEs (and minify) with placeholders
        $this->_html = preg_replace_callback(
            '/\s*<style(\b[^>]*>)([\s\S]*?)<\/style>\s*/i',
            array($this, '_removeStyleCB'),
            $this->_html);

        // remove HTML comments (not containing IE conditional comments).
        $this->_html = preg_replace_callback(
            '/<!--([\s\S]*?)-->/',
            array($this, '_commentCB'),
            $this->_html);

        // replace PREs with placeholders
        $this->_html = preg_replace_callback(
            '/\s*<pre(\b[^>]*?>[\s\S]*?<\/pre>)\s*/i',
            array($this, '_removePreCB'),
            $this->_html);

        // replace TEXTAREAs with placeholders
        $this->_html = preg_replace_callback(
            '/\s*<textarea(\b[^>]*?>[\s\S]*?<\/textarea>)\s*/i',
            array($this, '_removeTextareaCB'),
            $this->_html);

        // trim each line.
        // @todo take into account attribute values that span multiple lines.
        $this->_html = preg_replace('/^\s+|\s+$/m', '', $this->_html);

        if (preg_match('/((?s).*<body\b[^>]*?>)([\s\S]*?)(<\/body>(?s).*)/i', $this->_html, $m)) {

            $m[2] = preg_replace('/\s+(<\/?(?:area|base(?:font)?|blockquote|body|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)|ul)\b[^>]*>)/i', '$1', $m[2]);

            $m[2] = preg_replace_callback('/>([^<]+)</',
                    array($this, '_outsideTagCB'),
                    $m[2]);

            $this->_html = $m[1] . chr(10) . trim($m[2]) . $m[3];

        } else {

            $this->_html = preg_replace('/\s+(<\/?(?:area|base(?:font)?|blockquote|body|caption|center|cite|col(?:group)?|dd|dir|div|dl|dt|fieldset|form|frame(?:set)?|h[1-6]|head|hr|html|legend|li|link|map|menu|meta|ol|opt(?:group|ion)|p|param|t(?:able|body|head|d|h||r|foot|itle)|ul)\b[^>]*>)/i', '$1', $this->_html);

            $this->_html = preg_replace_callback('/>([^<]+)</',
                array($this, '_outsideTagCB'),
                $this->_html);
        }

        $this->_html = preg_replace('/>\s<!--/', '>' . chr(10) . '<!--', $this->_html);

        // remove ws outside of all elements
        $this->_html = preg_replace(
            '/>(\s(?:\s*))?([^<]+)(\s(?:\s*))?</',
            '>$1$2$3<',
            $this->_html);

        // fill placeholders
        $this->_html = str_replace(
            array_keys($this->_placeholders),
            array_values($this->_placeholders),
            $this->_html);

        return $this->_html;
    }

    protected function _commentCB($m)
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<!['))
            ? $m[0]
            : '';
    }

    protected function _reservePlace($content)
    {
        $placeholder = '%' . $this->_replacementHash . count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $content;
        return $placeholder;
    }

    protected $_isXhtml = null;
    protected $_replacementHash = null;
    protected $_placeholders = array();

    protected function _removePreCB($m)
    {
        return $this->_reservePlace("<pre{$m[1]}");
    }

    protected function _removeTextareaCB($m)
    {
        return $this->_reservePlace("<textarea{$m[1]}");
    }

    protected function _outsideTagCB($m)
    {
        return '>' . preg_replace('/^\n+|\n+$/', ' ', $m[1]) . '<';
    }

    protected function _removeStyleCB($m)
    {
        return $this->_reservePlace($m[0]);
    }

    protected function _removeScriptCB($m)
    {
        return $this->_reservePlace($m[0]);
    }

    protected function _removeCdata($str)
    {
        return (false !== strpos($str, '<![CDATA['))
            ? str_replace(array('<![CDATA[', ']]>'), '', $str)
            : $str;
    }

    protected function _needsCdata($str)
    {
        return preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str);
    }
}
