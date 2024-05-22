<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 會員級錯誤處理類
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

class grs_error
{
    var $_message  = array();
    var $_template = '';
    var $error_no  = 0;

    /**
     * 構造函數
     *
     * @access    public
     *
     * @param     string    $tpl
     *
     * @return    void
     */
    function __construct($tpl)
    {
        $this->_template = $tpl;
    }

    /**
     * 添加一條錯誤信息
     *
     * @access  public
     * @param   string  $msg
     * @param   integer $errno
     * @return  void
     */
    function add($msg, $errno = 1)
    {
        if (is_array($msg)) {

            $this->_message = array_merge($this->_message, $msg);

        } else {

            $this->_message[] = $msg;
        }

        $this->error_no = $errno;
    }

    /**
     * 清空錯誤信息
     *
     * @access    public
     *
     * @return    void
     */
    function clean()
    {
        $this->_message = array();
        $this->error_no = 0;
    }

    /**
     * 返回所有的錯誤信息的數組
     *
     * @access    public
     *
     * @return    array
     */
    function getAll()
    {
        return $this->_message;
    }

    /**
     * 返回最後一條錯誤信息
     *
     * @access  public
     *
     * @return  void
     */
    function lastMessage()
    {
        return array_slice($this->_message, -1);
    }

    /**
     * 顯示錯誤信息
     *
     * @access    public
     *
     * @param     string    $link
     * @param     string    $href
     *
     * @return    void
     */
    function show($link = '', $href = '')
    {
        if ($this->error_no > 0) {

            $message = array();

            $link = (empty($link)) ? '返回上一頁' : $link;
            $href = (empty($href)) ? 'javascript:history.back();' : $href;
            $message['url_info'][$link] = $href;
            $message['back_url'] = $href;

            foreach ($this->_message as $msg) {

                $message['content'] = '<div>' . htmlspecialchars($msg) . '</div>';
            }

            if (isset($GLOBALS['smarty'])) {

                assignTemplate();

                $position = assignUrHere(0);
                $GLOBALS['smarty']->assign('page_title', $position['title']); // 頁面標題
                $GLOBALS['smarty']->assign('ur_here', $position['ur_here']); // 當前位置

                $GLOBALS['smarty']->assign('message', $message);
                $GLOBALS['smarty']->display($this->_template);
                $GLOBALS['smarty']->assign('auto_redirect', 0);

            } else {

                die($message['content']);
            }
            exit;
        }
    }
}
