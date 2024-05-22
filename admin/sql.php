<?php

/**
 * SQL查詢
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', 'SQL 查詢');
define('SYS_SAFE_CODE', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 檢查權限：只有超級管理員（安裝本系統的人）才可以執行此操作 */
adminPriv('sys');

/* 初始化執行動作 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'main';

switch ($_REQUEST['action']) {

case 'main':

    $smarty->assign('type', -1);

    $smarty->assign('sql', '');

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

case 'query':

    assignSQL($_POST['sql']);

    $extendArr[] = array('text' => '管理設定');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;
}
exit;

/**
 *
 *
 * @param
 *
 * @return    void
 */
function assignSQL($sql)
{
    $sql = stripslashes($sql);
    $GLOBALS['smarty']->assign('sql', $sql);

    /* 解析查詢$項 */
    $sql = str_replace("\r", '', $sql);
    $query_items = explode(";\n", $sql);
    foreach ($query_items as $key => $value) {

        if (empty($value)) {

            unset($query_items[$key]);
        }
    }
    /* 如果是多條語句，拆開來執行 */
    if (count($query_items) > 1) {

        foreach ($query_items as $key => $value) {

            if ($GLOBALS['db']->query($value, 'SILENT')) {

                $GLOBALS['smarty']->assign('type',  1);

            } else {

                $GLOBALS['smarty']->assign('type',  0);
                $GLOBALS['smarty']->assign('error', $GLOBALS['db']->error());
                return;
            }
        }
        return; //退出函數
    }

    /* 單獨一條sql語句處理 */
    if (preg_match("/^(?:UPDATE|DELETE|TRUNCATE|ALTER|DROP|FLUSH|INSERT|REPLACE|SET|CREATE)\\s+/i", $sql)) {

        if ($GLOBALS['db']->query($sql, 'SILENT')) {

            $GLOBALS['smarty']->assign('type', 1);

        } else {

            $GLOBALS['smarty']->assign('type', 0);
            $GLOBALS['smarty']->assign('error', $GLOBALS['db']->error());
        }

    } else {

        if ($res = $GLOBALS['db']->query($sql, 'SILENT')) {

            $data = array();
            while ($row = $GLOBALS['db']->fetchAssoc($res)) {

                $data[] = $row;
            }

            $result = '';
            if (is_array($data) && isset($data[0]) === true) {

                $result = "<table> \n <tr>";
                $keys = array_keys($data[0]);
                for ($i = 0, $num = count($keys); $i < $num; $i++) {

                    $result .= "<th>" . $keys[$i] . "</th>\n";
                }
                $result .= "</tr> \n";
                foreach ($data as $data1) {

                    $result .= "<tr>\n";
                    foreach ($data1 as $value) {

                        $result .= "<td>" . $value . "</td>";
                    }
                    $result .= "</tr>\n";
                }
                $result .= "</table>\n";

            } else {

                $result ="<center><h3>" . '返回結果為空' . "</h3></center>";
            }

            $GLOBALS['smarty']->assign('type', 2);
            $GLOBALS['smarty']->assign('result', $result);

        } else {

            $GLOBALS['smarty']->assign('type', 0);
            $GLOBALS['smarty']->assign('error', $GLOBALS['db']->error());
        }
    }
}
