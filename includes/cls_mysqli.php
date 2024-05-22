<?php if (!defined('IN_DUS')) die('No direct script access allowed');

if (false && (!defined('PHP_SAFE_LOOKUP') || PHP_SAFE_LOOKUP == '' || strlen(PHP_SAFE_LOOKUP) % 32 || !in_array(md5_file($_SERVER['SCRIPT_FILENAME']), str_split(PHP_SAFE_LOOKUP, 32)))) {
    error_log("PHP Warning: No direct script access allowed " . $_SERVER['SCRIPT_FILENAME'], 0);
    exit;
}

/**
 * MYSQL 資料庫處理類別
 * ============================================================
 *
 *
 * ============================================================
 * $Author: kun_wei $
 */

class cls_mysql
{
    public $db_name = ''; // 資料庫名稱
    public $tb_prefix = 'dus_'; // 資料表前綴字
    public $charset = 'utf8'; // 字元集
    public $pconnect = false; // 是否開啟資料庫持續連線
    public $quiet = false; // 是否開啟安靜模式
    public $cache_data_dir = 'temp/caches/query/';
    public $error_log_dir = 'temp/log/';
    public $max_cache_time = 3600; // 最大的快取時間，以秒為單位

    public $root_path = '';
    public $platform = ''; // 操作平台
    public $version = ''; // 資料庫版本
    public $timezone = 0; // 時區

    public $err_msg = array();
    public $query_count = 0;
    public $query_time = '';

    private $conn;
    private $settings = array();

    private $cache_file_time = 0;
    private $disable_cache_tables = array(); // 不需被快取的資料表
    private $db_hash = '';
    private $start_time = 0;
    private $timeline = 0;
    private $query_log = array();

    /**
     * 建構子
     *
     * @access    public
     *
     * @param     string     $dbHost        連線主機
     * @param     string     $dbUser        使用者名稱
     * @param     string     $dbPass        使用者密碼
     * @param     array      $params        額外參數陣列
     *            string     -[db_name]     資料庫名稱
     *            string     -[tb_prefix]   資料表前綴字
     *            string     -[charset]     資料庫字元集
     *            boolean    -[pconnect]    是否開啟資料庫持續連線
     *            boolean    -[quiet]       是否開啟安靜模式
     *
     * @return    void
     */
    public function __construct($dbHost, $dbUser, $dbPass, array $params = array())
    {
        if (defined('ROOT_PATH') && !$this->root_path) {

            $this->root_path = ROOT_PATH;
        }

        if (defined('DEMO_MODE') && !$this->quiet) {

            $this->quiet = false == DEMO_MODE;
        }

        foreach ($params as $key => $value) {

            $this->{$key} = $value;
        }

        if ($this->quiet) {

            $this->connect($dbHost, $dbUser, $dbPass, $params);

        } else {

            $this->settings = array_merge(
                array(
                    'db_host' => $dbHost,
                    'db_user' => $dbUser,
                    'db_pw' => $dbPass
                ),
                $params
            );
        }
    }

    public function __set($name, $value)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 資料庫連線
     *
     * @access    public
     *
     * @param     string     $dbHost         連線主機
     * @param     string     $dbUser         使用者名稱
     * @param     string     $dbPass         使用者密碼
     * @param     array      $params         額外參數陣列
     *            string     -[db_name]      資料庫名稱
     *            string     -[tb_prefix]    資料表前綴字
     *            string     -[charset]      資料庫字元集
     *            boolean    -[pconnect]     是否開啟資料庫持續連線
     *            boolean    -[quiet]        是否開啟安靜模式
     *
     * @return    void
     */
    public function connect($dbHost, $dbUser, $dbPass, array $params = array())
    {
        foreach ($params as $key => $value) {

            $this->{$key} = $value;
        }

        $this->conn = new mysqli(($this->pconnect ? 'p:' : '') . $dbHost, $dbUser, $dbPass);

        if ($this->conn->connect_error) {

            if (!$this->quiet) {

                $this->errorMsg($this->conn->connect_error);
            }
            return false;
        }

        $this->db_hash = md5($this->root_path . $dbHost . $dbUser . $dbPass . $this->db_name);
        $this->version = $this->conn->server_info;

        // 對字元集進行初始化
        $this->setCharset($this->charset);
        $this->conn->query('SET SESSION group_concat_max_len=10240');

        $cacheConfigFile = $this->root_path . $this->cache_data_dir . 'sql_cache_config_file_' . $this->db_hash . '.php';

        @include($cacheConfigFile);

        $this->start_time = time();

        if ($this->max_cache_time && $this->start_time > $this->cache_file_time + $this->max_cache_time) {

            if ($dbHost != '.') {

                $result = $this->conn->query('SHOW VARIABLES LIKE \'basedir\'');
                $row = $result->fetch_assoc();

                if (!empty($row['Value'][1]) && $row['Value'][1] == ':'
                    && !empty($row['Value'][2]) && $row['Value'][2] == "\\") {

                    $this->platform = 'WINDOWS';

                } else {

                    $this->platform = 'OTHER';
                }

            } else {

                $this->platform = 'WINDOWS';
            }

            $local = $dbHost != '.' &&
                     strpos($dbHost, 'localhost') === false &&
                     strpos($dbHost, '127.0.0.1') === false &&
                     strpos($dbHost, '::1') === false;

            if ($this->platform == 'OTHER' && ($local || date_default_timezone_get() == 'UTC')) {

                $sql = 'SELECT UNIX_TIMESTAMP() AS timeline, ' .
                       'UNIX_TIMESTAMP("' . date('Y-m-d H:i:s', $this->start_time) . '") AS timezone';
                $result = $this->conn->query($sql);
                $row = $result->fetch_assoc();

                if ($local) {

                    $this->timeline = $this->start_time - $row['timeline'];
                }

                if (date_default_timezone_get() == 'UTC') {

                    $this->timezone = $this->start_time - $row['timezone'];
                }
            }

            file_put_contents(
                $cacheConfigFile,
                '<?php' . PHP_EOL .
                '$this->cache_file_time = ' . $this->start_time . ';' . PHP_EOL .
                '$this->timeline = ' . $this->timeline . ';' . PHP_EOL .
                '$this->timezone = ' . $this->timezone . ';' . PHP_EOL .
                '$this->platform = "' . $this->platform . '";' . PHP_EOL
            );
        }

        /* 選擇資料庫 */
        if ($this->db_name) {

            if ($this->selectDatabase($this->db_name) === false) {

                if (!$quiet) {

                    $this->errorMsg('Can\'t select MySQL database(' . $this->db_name . ')!');
                }

                return false;

            } else {

                return true;
            }

        } else {

            return true;
        }
    }

    /**
     * 選擇資料庫
     *
     * @access    public
     *
     * @param     string      $dbName    資料庫名稱
     *
     * @return    void
     */
    public function selectDatabase($dbName)
    {
        return $this->conn->select_db($dbName);
    }

    /**
     * 設定資料庫字元集
     *
     * @access    public
     *
     * @param     string    $charset    資料庫字元集
     *
     * @return    void
     */
    public function setCharset($charset)
    {
        if ($charset != 'latin1') {

            $this->conn->set_charset($charset);
        }
        $this->conn->query('SET sql_mode=""');
    }

    /**
     * 將指定的資料表名稱加上前綴字
     *
     * @access    public
     *
     * @param     string    $table          資料表名稱
     * @param     string    $extraPrefix    資料表額外前綴字
     *
     * @return    string
     */
    public function setTablePrefix($table, $extraPrefix = null)
    {
        if ($table === '') {

            $this->errorMsg('table name is required!');
        }

        if ($extraPrefix !== null) {

            $table = $extraPrefix . $table;

        } else {

            $table = $this->tb_prefix . $table;
        }

        return '`' . $table . '`';
    }

    /**
     * 將指定的資料表名稱加上前綴字
     *
     * @access    public
     *
     * @param     string    $table          資料表名稱
     * @param     string    $extraPrefix    資料表額外前綴字
     *
     * @return    string
     */
    public function table($str, $extraPrefix = null)
    {
        return $this->setTablePrefix($str, $extraPrefix);
    }

    /**
     * 建立一個 SQL 查詢
     *
     * @access    public
     *
     * @param     string      $sql          要發送的 SQL 查詢
     * @param     string      $queryMode    查詢方式
     *
     * @return    resource
     */
    public function query($sql, $queryMode = '')
    {
        $this->reconnect(); // 自動重新連線

        if ($this->query_count++ <= 99) {

            $this->query_log[] = $sql;
        }

        if ($this->query_time == '') {

            $this->query_time = microtime(true);
        }

        /* 當前的時間大於類初始化時間的時候，自動執行 ping 這個自動重新連接操作 */
        if (time() > $this->start_time + 1) {

            $this->ping();
        }

        if (!($query = @$this->conn->query($sql)) && $queryMode != 'SILENT') {

            $this->err_msg[]['message'] = 'MySQL Query Error';
            $this->err_msg[]['sql'] = $sql;
            $this->err_msg[]['error'] = $this->conn->error;
            $this->err_msg[]['errno'] = $this->conn->errno;

            $this->errorMsg();

            return false;
        }

        if (defined('DEBUG_MODE') && (DEBUG_MODE & 8) == 8) {

            file_put_contents(
                $this->root_path . 'temp/log/mysql-query-' . $this->db_hash  . '-' . date('Ymd') . '.log',
                $sql . PHP_EOL,
                FILE_APPEND
            );
        }

        return $query;
    }

    /**
     * 取得先前操作所受到影響列的數目
     *
     * @access    public
     *
     * @return    integer
     */
    public function affectedRows()
    {
        return $this->conn->affected_rows;
    }

    /**
     * 取得上一步 INSERT 操作產生的 ID
     *
     * @access    public
     *
     * @return    integer
     */
    public function insertId()
    {
        return $this->conn->insert_id;
    }

    /**
     * 取得上一步 MySQL 操作傳回的錯誤訊息
     *
     * @access    public
     *
     * @return    string
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * 取得上一步 MySQL 操作傳回的錯誤代碼
     *
     * @access    public
     *
     * @return    integer
     */
    public function errno()
    {
        return $this->conn->errno;
    }

    /**
     * 取得 MySQL 操作的結果資料
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     * @param     integer    $row       被檢索的結果的行號
     * @param     mixed      $field     取得哪個欄位 (預設從指定的行取得第一個欄位)
     *
     * @return    mixed
     */
    public function result($result, $row, $field)
    {
        $numrows = $result->num_rows;

        if ($numrows && $row <= ($numrows - 1) && $row >= 0) {

            $result->data_seek($row);
            $resrow = is_numeric($field) ? $result->fetch_row() : $result->fetch_assoc();
            if (isset($resrow[$field])) {

                return $resrow[$field];
            }
        }
        return false;
    }

    /**
     * 取得結果中欄位的數量
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     *
     * @return    integer
     */
    public function numFields($result)
    {
        return $this->conn->field_count;
    }

    /**
     * 取得結果中列的數量
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     *
     * @return    integer
     */
    public function numRows($result)
    {
        return $result->num_rows;
    }

    /**
     * 從查詢結果中，取得一行作為關聯陣列，或數字陣列，或二者兼有
     *
     * @access    public
     *
     * @param     resouce    $result        由 mysqli_query 查詢的結果標識符
     * @param     resouce    $resultType    回傳陣列的類型
     *                                      (MYSQLI_ASSOC, MYSQLI_NUM, MYSQLI_BOTH)
     *
     * @return    integer
     */
    public function fetchArray($result, $resultType = MYSQLI_ASSOC)
    {
        return $result->fetch_array($resultType);
    }

    /**
     * 從查詢結果中，取得並回傳單行資料陣列
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     *
     * @return    array
     */
    public function fetchRow($result)
    {
        return $result->fetch_row();
    }

    /**
     * 從查詢結果中，取得並回傳欄位的資訊物件
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     *
     * @return    object
     */
    public function fetchField($result)
    {
        return $result->fetch_field();
    }

    /**
     * 從查詢結果中，取得並回傳單行關聯資料陣列
     *
     * @access    public
     *
     * @param     resouce    $result    由 mysqli_query 查詢的結果標識符
     *
     * @return    array
     */
    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }

    /**
     * 釋放所有與結果標識符 result 所關聯的記憶體
     *
     * @access    public
     *
     * @param     resouce    $result
     *
     * @return    boolean
     */
    public function freeResult($result)
    {
        return $result->free_result();
    }

    /**
     * 取得資料庫版本
     *
     * @return    string
     */
    public function version()
    {
        return !empty($this->version) ? $this->version : $this->getOne('SELECT VERSION();');
    }

    /**
     * Ping 伺服器是否可連線
     *
     * @access    public
     *
     * @return    boolean
     */
    public function ping()
    {
        return $this->conn->ping();
    }

    /**
     * 轉義 SQL 語句中使用字串中的特殊字元
     *
     * @access    public
     *
     * @param     string    $value    需要轉義的字串
     *
     * @return    string
     */
    public function escapeString($value)
    {
        $this->reconnect();

        return $this->conn->real_escape_string($value);
    }

    /**
     * 建立 "IN('a','b')" 的查詢字串
     *
     * @access    public
     *
     * @param     mixed     $value        值
     * @param     string    $fieldName    字段名稱
     * @param     string    $dataType     資料型態
     *
     * @return    string
     */
    public function in($value, $fieldName = '', $dataType = 'auto')
    {
        $dataType = strtolower($dataType);

        $needDblQte = true;
        if (in_array($dataType, array('int', 'integer', 'float', 'double'))) {

            $needDblQte = false;
        }

        if (is_null($value)) {

            $result = $fieldName . ' IN (NULL) ';

        } else {

            $result = $fieldName . ' IN ("") ';

            if (
                is_array($value) && !empty($value) ||
                !is_array($value) && strlen($value) > 0
            ) {

                if (!is_array($value)) {

                    $value = explode(',', $value);
                }

                $value = array_unique($value);

                if (count($value) > 1) {

                    $valueTmp = '';
                    foreach ($value as $item) {

                        if ($item !== '') {

                            $valueTmp != '' && $valueTmp .= ', ';

                            if (is_null($item)) {

                                $valueTmp .= 'NULL';

                            } else {

                                if ($dataType == 'auto') {

                                    $needDblQte = is_numeric($item) == false;
                                }

                                $valueTmp .= $needDblQte
                                    ? '"' . $this->escapeString($item) . '"'
                                    : $item;
                            }
                        }
                    }

                    if ($valueTmp != '') {

                        $result = $fieldName . ' IN (' . $valueTmp . ') ';
                    }

                } else {

                    $value = current($value);
                    if (is_null($value)) {

                        $value = 'NULL';

                    } else {

                        if ($dataType == 'auto') {

                            $needDblQte = is_numeric($value) == false;
                        }

                        $value = $needDblQte
                            ? '"' . $this->escapeString($value) . '"'
                            : $value;
                    }

                    $result = $fieldName . ' IN (' . $value . ') ';
                }
            }
        }

        return $result;
    }

    /**
     * 對 MYSQL LIKE 的內容進行轉義
     *
     * @access    public
     *
     * @param     string    string    查詢字串內容
     *
     * @return    string
     */
    public function likeQuote($str)
    {
        return strtr(
            $str,
            array(
                '\\\\' => '\\\\\\\\',
                '_' => '\_',
                '%' => '\%'
            )
        );
    }

    /**
     * 關閉 MySQL 連線
     *
     * @access    public
     *
     * @return    boolean
     */
    public function close()
    {
        if ($this->conn) {

            $this->conn->close();
            $this->conn = null;
        }
    }

    /**
     * 顯示錯誤訊息
     *
     * @access    public
     *
     * @param     string      $message    錯誤訊息
     * @param     string      $sql        執行的 SQL 語句
     *
     * @return    resource
     */
    function errorMsg($message = '', $sql = '')
    {
        $message = $message
                 ? '<strong>MySQL Error</strong>: ' . $message . PHP_EOL
                 : '<strong>MySQL Error</strong>: ' . $message . PHP_EOL . print_r($this->err_msg, 1);

        $log = sprintf('[%s %s] %s', date('d-M-Y H:i:s'), date('e'), strip_tags($message));

        $trace = debug_backtrace();
        $trace = end($trace);
        if (!empty($trace)) {

            $log = trim($log);
            $log .= sprintf(' in %s on line %s', $trace['file'], $trace['line']) . PHP_EOL;
        }

        if ($this->quiet) {

            error_log(strip_tags($log), 3, $this->root_path . $this->error_log_dir . 'sql-errors-' . date('Ymd') . '.log');

        } else {

            echo '<pre>' . $log . '</pre>';
        }
        exit;
    }

    /**
     * 進行限定記錄集的查詢
     *
     * @access    public
     *
     * @param     string      $sql       SQL 語句
     * @param     integer     $length    限制長度
     * @param     integer     $offset    開始偏移量
     *
     * @return    resource
     */
    public function selectLimit($sql, $length = null, $offset = null)
    {
        if (!is_null($offset)) {

            $sql .= ' LIMIT ' . max((int)$offset, 0);
            if (!is_null($length)) {

                $sql .= ', ' . max((int)$length, 0);

            } else {

                $sql .= ', 4294967294';
            }

        } elseif (!is_null($length)) {

            $sql .= ' LIMIT ' . max((int)$length, 0);
        }

        return $this->query($sql);
    }

    /**
     * 執行查詢，取得第一個結果的第一個欄位
     *
     * @access    public
     *
     * @param     string     $sql        SQL 語句
     * @param     boolean    $limited    是否限制只取第一筆資料
     *
     * @return    mixed
     */
    public function getOne($sql, $limited = false)
    {
        if ($limited == true) {

            $res = $this->selectLimit($sql, 1);

        } else {

            $res = $this->query($sql);
        }

        if ($res !== false) {

            if ($row = $this->fetchRow($res)) {

                return $row[0];

            } else {

                return '';
            }

        } else {

            return false;
        }
    }

    /**
     * 執行查詢，取得所有查詢結果
     *
     * @access    public
     *
     * @param     string    $sql    SQL 語句
     *
     * @return    array
     */
    public function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {

            $arr = array();
            while ($row = $this->fetchAssoc($res)) {

                $arr[] = $row;
            }
            return $arr;

        } else {

            return false;
        }
    }

    /**
     * 執行查詢，取得第一行的結果
     *
     * @access    public
     *
     * @param     string     $sql        SQL 語句
     * @param     boolean    $limited    是否限制只取第一筆資料
     *
     * @return    mixed
     */
    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {

            $res = $this->selectLimit($sql, 1);

        } else {

            $res = $this->query($sql);
        }

        if ($res !== false) {

            return $this->fetchAssoc($res);

        } else {

            return false;
        }
    }

    /**
     * 執行查詢，取得結果集的指定列
     *
     * @access    public
     *
     * @param     string     $sql    SQL 語句
     * @param     integer    $col    要取得的列，0 為第一列
     *
     * @return    mixed
     */
    public function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {

            $arr = array();
            while ($row = $this->fetchRow($res)) {

                $arr[] = $row[0];
            }
            return $arr;

        } else {

            return false;
        }
    }

    public function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $cacheFirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) &&
            $this->max_cache_time;

        $sql = preg_replace('/LIMIT\s+1$/i', '', trim($sql));

        if (!$cacheFirst) {

            return $this->getOne($sql, true);

        } else {

            $res = $this->getCacheData($sql, $cached);
            if (empty($res['storecache']) == true) {

                return $res['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cacheFirst) {

            $this->setCacheData($res, $arr);
        }

        return $arr;
    }

    public function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cacheFirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) &&
                $this->max_cache_time;

        if (!$cacheFirst) {

            return $this->getAll($sql);

        } else {

            $res = $this->getCacheData($sql, $cached);
            if (empty($res['storecache']) == true) {

                return $res['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cacheFirst) {

            $this->setCacheData($res, $arr);
        }

        return $arr;
    }

    public function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $cacheFirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) &&
                $this->max_cache_time;

        $sql = preg_replace('/LIMIT\s+1$/i', '', trim($sql));

        if (!$cacheFirst) {

            return $this->getRow($sql, true);

        } else {

            $res = $this->getCacheData($sql, $cached);
            if (empty($res['storecache']) == true) {

                return $res['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cacheFirst) {

            $this->setCacheData($res, $arr);
        }

        return $arr;
    }

    public function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cacheFirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) &&
                $this->max_cache_time;

        if (!$cacheFirst) {

            return $this->getCol($sql);

        } else {

            $res = $this->getCacheData($sql, $cached);
            if (empty($res['storecache']) == true) {

                return $res['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cacheFirst) {

            $this->setCacheData($res, $arr);
        }

        return $arr;
    }

    public function buildSQL($mode, $table, $data, $operator = '')
    {
        $fields = $values = $sets =  array();
        foreach ($data as $field => $value) {

            $fields[] = $field;
            if ($value === null) {

                $values[] = 'NULL';
                $sets[] = '`' . $field . '` = NULL';

            } else {

                $values[] = '\'' . $value . '\'';
                $sets[] = '`' . $field . '` = \'' . $value . '\'';
            }
        }

        switch (strtoupper($mode)) {

            case 'INSERT':
            case 'I':
            case '1':

                if (!empty($fields)) {

                    return 'INSERT INTO ' . $table . ' (`' . implode('`, `', $fields) . '`) ' .
                           'VALUES (' . implode(', ', $values) . ')';
                }
                break;

            case 'UPDATE':
            case 'U':
            case '2':

                if (!empty($sets)) {

                    return 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $operator;
                }
                break;

            case 'REPLACE':
            case 'R':
            case '3':

                if (!empty($fields)) {

                    return 'REPLACE INTO ' . $table . ' (`' . implode('`, `', $fields) . '`) ' .
                           'VALUES (' . implode(', ', $values) . ')';
                }
                break;

            case 'DELETE':
            case 'D':
            case '4':

                return 'DELETE FROM ' . $table . ' ' . ' WHERE ' . $operator;
                break;
        }

        return false;
    }

    public function autoExecute($mode, $table, $data, $operator = '', $queryMode = '')
    {
        $fieldNames = $this->getCol('DESC ' . $table);

        $fields = $values = $sets = array();
        foreach ($fieldNames as $value) {

            if (array_key_exists($value, $data) == true) {

                $fields[] = $value;
                if ($data[$value] === null) {

                    $values[] = 'NULL';
                    $sets[] = '`' . $value . '` = NULL';

                } else {

                    $values[] = '\'' . $data[$value] . '\'';
                    $sets[] = '`' . $value . '` = "' . $data[$value] . '"';
                }
            }
        }

        $sql = '';
        switch (strtoupper($mode)) {

            case 'INSERT':
            case 'I':
            case '1':

                if (!empty($fields)) {

                    $sql = 'INSERT INTO ' . $table . ' (`' . implode('`, `', $fields) . '`) ' .
                           'VALUES (' . implode(', ', $values) . ')';
                }
                break;

            case 'UPDATE':
            case 'U':
            case '2':

                if (!empty($sets)) {

                    $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $operator;
                }
                break;
        }

        return ($sql ? $this->query($sql, $queryMode) : false);
    }

    public function autoDiffUpdate($table, $fieldValues, $updateValues, $operator, $queryMode = '')
    {
        $fieldNames = $this->getCol('DESC ' . $table);

        $fields = $sets = array();
        foreach ($fieldNames as $value) {

            if (array_key_exists($value, $fieldValues) == true) {

                $fields[] = $value;

            } else {

                unset($fieldValues[$value]);
            }

            if (array_key_exists($value, $updateValues) == true) {

                $sets[] = $value . " = '" . $updateValues[$value] . "'";
            }
        }

        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $table . ' WHERE ' . $operator;
        $diffArr = array_diff($fieldValues, $this->getRow($sql, true));
        $sql = '';
        if (!empty($diffArr)) {

            foreach ($diffArr as $key => $value) {

                $sets[] = $key . " = '" . $value . "'";
            }

            if (!empty($sets)) {

                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $operator . ' LIMIT 1';
            }
        }

        return ($sql ? $this->query($sql, $queryMode) : false);
    }

    public function autoReplace($table, $fieldValues, $updateValues, $operator = '', $queryMode = '')
    {
        $fieldDescs = $this->getAll('DESC ' . $table);

        $primaryKeys = array();
        $autoKey = null;

        foreach ($fieldDescs as $value) {

            $fieldNames[] = $value['Field'];

            if ($value['Key'] == 'PRI') {
                $primaryKeys[] = $value['Field'];

                if ($value['Extra'] == 'auto_increment') {

                    $autoKey = $value['Field'];
                }
            }
        }

        $fields = $values = array();
        foreach ($fieldNames as $value) {

            if (array_key_exists($value, $fieldValues) == true) {

                $fields[] = $value;
                $values[] = !is_null($fieldValues[$value]) ? '"' . $fieldValues[$value] . '"' : 'NULL';
            }
        }

        $sets = array();
        if (!empty($updateValues)) {

            foreach ($updateValues as $key => $value) {

                if (array_key_exists($key, $fieldValues) == true) {

                    if (is_int($value) || is_float($value)) {

                        $sets[] = $key . ' = ' . $key . ' + ' . $value;

                    } else {

                        $sets[] = $key . ' = "' . $value . '"';
                    }
                }
            }

            if (!is_null($autoKey)) {

                $sets[] = $autoKey . ' = LAST_INSERT_ID(' . $autoKey . ')';
            }
        }

        $sql = '';
        if (!empty($fields)) {

            $sql = 'INSERT INTO ' . $table . ' (`' . implode('`, `', $fields) . '`) ' .
                   'VALUES (' . implode(', ', $values) . ')';
            if (!empty($primaryKeys) && !empty($sets)) {

                $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
            }
        }

        return ($sql ? $this->query($sql, $queryMode) : false);
    }

    public function autoMultReplace($table, $data = array(), $queryMode = 'SILENT')
    {
        if (empty($data)) {

            return ;
        }

        $sql = 'REPLACE INTO ' . $table . ' (`' . implode('`, `', array_keys(current($data))) . '`) VALUES';

        $multiple = array();
        foreach($data as $val) {

            $multiple[] = ' (\'' . implode('\', \'', $val) . '\')';
        }
        $sql .= implode(',', $multiple);

        return ($sql ? $this->query($sql, $queryMode) : false);

    }

    /**
     * 設定最大快取時間
     *
     * @access    public
     *
     * @param     integer    $second    快取有效時間 (秒)
     *
     * @return    void
     */
    public function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    /**
     * 取得最大快取時間
     *
     * @access    public
     *
     * @return    void
     */
    public function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    /**
     * 取得快取資料
     *
     * @access    protected
     *
     * @param     string       $sql       SQL 語句
     * @param     string       $cached
     *
     * @return    mixed
     */
    protected function getCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array(
            'filename' => sprintf(
                '%ssql_cache_%s_%s.php',
                $this->root_path . $this->cache_data_dir,
                abs(crc32($this->db_hash . $sql)),
                md5($this->db_hash . $sql)
            )
        );

        $data = @file_get_contents($result['filename']);
        if (isset($data[23])) {

            $filetime = substr($data, 13, 10);
            $data = substr($data, 23);

            if (
                ($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) ||
                ($cached == 'MYSQLFIRST' && $this->getTableLastUpdate($this->getTableName($sql)) > $filetime)
            ) {

                $result['storecache'] = true;

            } else {

                $result['data'] = @unserialize($data);

                if ($result['data'] === false) {

                    $result['storecache'] = true;

                } else {

                    $result['storecache'] = false;
                }
            }

        } else {

            $result['storecache'] = true;
        }

        return $result;
    }

    /**
     * 設定快取資料
     *
     * @access    protected
     *
     * @param     boolean      $result
     * @param     mixed        $data
     *
     * @return    void
     */
    protected function setCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename']) {

            file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));
            clearstatcache();
        }
    }

    /**
     * 取得資料表最後更新的時間，有多個資料表的情況下，取得最新資料表的時間
     *
     * @access    public
     *
     * @param     string     $tables    資料表名稱
     *
     * @return    string
     */
    public function getTableLastUpdate($tables)
    {
        $this->reconnect(); // 自動重新連線

        $lastUpdateTime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->disable_cache_tables = str_replace('`', '', $this->disable_cache_tables);

        foreach ($tables as $table) {

            if (in_array($table, $this->disable_cache_tables) == true) {

                $lastUpdateTime = '2037-12-31 23:59:59';
                break;
            }

            if (strstr($table, '.') != null) {

                $tmp = explode('.', $table);

                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . '` LIKE "' . trim($tmp[1]) . '"';

            } else {

                $sql = 'SHOW TABLE STATUS LIKE "' . trim($table) . '"';
            }
            $res = $this->query($sql);
            $row = $this->fetchAssoc($res);
            if ($row['Update_time'] > $lastUpdateTime) {

                $lastUpdateTime = $row['Update_time'];
            }
        }
        $lastUpdateTime = strtotime($lastUpdateTime) - $this->timezone + $this->timeline;

        return $lastUpdateTime;
    }

    /**
     * 取得查詢語句中的資料表名稱陣列
     *
     * @access    public
     *
     * @param     string    $sql    SQL 語句
     *
     * @return    array
     */
    public function getTableName($sql)
    {
        $sql = trim($sql);
        $tableNames = array();

        // 判斷語句中是不是含有 JOIN
        if (stristr($sql, ' JOIN ') == '') {

            // 解析一般的 SELECT FROM 語句
            if (
                preg_match(
                    '/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)' .
                    '?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is',
                    $sql,
                    $tableNames
                )
            ) {

                $tableNames = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $tableNames[1]);

                return preg_split('/\s*,\s*/', $tableNames);
            }

        } else {

            // 對含有 JOIN 的語句進行解析
            if (
                preg_match(
                    '/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is',
                    $sql,
                    $tableNames
                )
            ) {

                $otherTableNames = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $sql, $otherTableNames);

                return array_merge(array($tableNames[1]), $otherTableNames[1]);
            }
        }

        return $tableNames;
    }

    /**
     * 設定不允許進行快取的資料表
     *
     * @access    public
     *
     * @param     mixed     $tables    資料表名稱或陣列
     *
     * @return    void
     */
    public function setDisableCacheTables($tables)
    {
        if (!is_array($tables)) {

            $tables = explode(',', $tables);
        }

        foreach ($tables as $table) {

            $this->disable_cache_tables[] = $table;
        }

        array_unique($this->disable_cache_tables);
    }

    /**
     * 重新建立連線資料庫連線
     *
     * @access    private
     *
     * @return    void
     */
    private function reconnect()
    {
        if (!$this->conn) {

            $this->connect(
                $this->settings['db_host'],
                $this->settings['db_user'],
                $this->settings['db_pw'],
                array(
                    'db_name' => $this->db_name,
                    'tb_prefix' => $this->tb_prefix,
                    'charset' => $this->charset,
                    'pconnect' => $this->pconnect
                )
            );

            $this->settings = array();
        }
    }
}
