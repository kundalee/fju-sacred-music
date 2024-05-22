<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 後台自動操作數據庫的類文件
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/*------------------------------------------------------ */
//-- 該類用於與數據庫數據進行交換
/*------------------------------------------------------ */
class exchange
{
    var $table;
    var $db;
    var $id;
    var $name;
    var $where;
    var $error_msg;

    /**
     * 構造函數
     *
     * @access    public
     *
     * @param     string      $table    數據庫表名
     * @param     dbobject    $db       aodb的對象
     * @param     string      $id       數據表主鍵字段名
     * @param     string      $name     數據表重要段名
     *
     * @return    void
     */
    public function __construct($table, &$db, $id, $name, $where = '')
    {
        $this->table     = $table;
        $this->db        = &$db;
        $this->id        = $id;
        $this->name      = $name;
        $this->where     = 'WHERE ' . ($where != '' ? $where . ' AND' : '');
        $this->error_msg = '';
    }

    /**
     * 判斷表中某字段是否重複，若重複則中止程序，並給出錯誤信息
     *
     * @access    public
     *
     * @param     string     $col     字段名
     * @param     string     $name    字段值
     * @param     integer    $id
     *
     * @return    void
     */
    function isOnly($col, $name, $id = 0, $where = '')
    {
        $sql  = 'SELECT COUNT(*) FROM ' . $this->table . " " . $this->where . " $col = '$name'";
        $sql .= empty($id) ? '' : ' AND ' . $this->id . " <> '$id'";
        $sql .= empty($where) ? '' : ' AND ' . $where;

        return ($this->db->getOne($sql) == 0);
    }

    /**
     * 返回指定名稱記錄再數據表中記錄個數
     *
     * @access    public
     *
     * @param     string    $col    字段名
     * @param     string    $name   字段內容
     *
     * @return    int               記錄個數
     */
    function num($col, $name, $id = 0)
    {
        $sql  = 'SELECT COUNT(*) FROM ' . $this->table . " " . $this->where . " $col = '$name'";
        $sql .= empty($id) ? '' : ' AND ' . $this->id . " != '$id' ";

        return $this->db->getOne($sql);
    }

    /**
     * 編輯某個字段
     *
     * @access    public
     *
     * @param     string    $set    要更新集合如" col = '$name', value = '$value'"
     * @param     int       $id     要更新的記錄編號
     *
     * @return    bool              成功或失敗
     */
    function edit($set, $id)
    {
        if (is_array($set)) {

            $sql = $this->db->buildSQL('U', $this->table, $set, $this->id . ' = "' . $id . '"');

        } else {

            $sql = 'UPDATE ' . $this->table . ' SET ' . $set . " " . $this->where . " `" . $this->id . "` = '" . $id . "'";
        }

        if ($this->db->query($sql)) {

            return true;

        } else {

            return false;
        }
    }

    /**
     * 取得某個字段的值
     *
     * @access  public
     *
     * @param     int       $id    記錄編號
     * @param     string    $id    字段名
     *
     * @return    string           取出的數據
     */
    function getName($id, $name = '')
    {
        if (empty($name)) {

            $name = $this->name;
        }

        $sql = "SELECT `$name` FROM " . $this->table . " " . $this->where . " $this->id = '$id'";

        return $this->db->getOne($sql);
    }

    /**
     * 刪除條記錄
     *
     * @access  public
     *
     * @param     int    $id    記錄編號
     *
     * @return    bool
     */
    function drop($id)
    {
        $sql = 'DELETE FROM ' . $this->table . " " . $this->where . " $this->id = '$id'";

        return $this->db->query($sql);
    }
}
