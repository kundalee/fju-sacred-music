<?php

/**
 * 搜索引擎關鍵字統計
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '搜尋引擎');

require(dirname(__FILE__) . '/includes/init.php');

/* 操作項的初始化 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'view';

switch ($_REQUEST['action']) {

case 'view':

    adminPriv('client_flow_stats');

    /* 時間參數 */
    /* TODO: 時間需要改 */
    if (isset($_POST) && !empty($_POST)) {

        $startDate = $_POST['start_date'];
        $endDate   = $_POST['end_date'];

    } else {

        $startDate = date('Y-m-d', strtotime('-4 week'));
        $endDate   = date('Y-m-d');
    }

    $data = (object) array('cols' => array(), 'rows' => array());
    $data->cols[] = array('label' => '來源', 'type' => 'string');

    $sql = 'SELECT keyword, count, searchengine ' .
           'FROM ' .$db->table('keywords') . ' ' .
           'WHERE date >= "' . $startDate . '" AND date <= "' . $endDate . '" ';
    if (!empty($_POST['filter'])) {

        foreach ($_POST['filter'] as $searchengine) {

            $search[] = 'searchengine LIKE "' . $searchengine . '%" ';
        }
        $sql .= 'AND (' . implode(" OR ", $search) . ') ';
    }
    $sql .= 'GROUP BY searchengine, keyword ORDER BY count LIMIT 300';
    $res = $db->query($sql);

    $searchengine = array();
    $keywords = array();
    while ($val = $db->fetchAssoc($res)) {

        $keywords[] = $val['keyword'];
        $searchengine[$val['searchengine']][$val['keyword']] = $val['count'];
    }

    foreach ($searchengine as $search => $row) {

        $data->cols[] = array('label' => $search, 'type' => 'number');
    }

    foreach ($keywords as $key => $val) {

        $data->rows[$key]['c'][] = array('v' => $val);
        foreach ($searchengine as $row) {

            $count = 0;
            if (!empty($row[$val])) {

                $count = $row[$val];
            }
            $data->rows[$key]['c'][] = array('v' => $count);
        }
    }

    $smarty->assign('general_json', json_encode($data));

    $searchengines = array();
    $sql = 'SELECT DISTINCT searchengine FROM ' . $db->table('keywords');
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $searchengines[$row['searchengine']] = isset($_POST['filter']) && in_array($row['searchengine'], $_POST['filter'])
            ? true
            : false;
    }
    ksort($searchengines);

    $smarty->assign('searchengines', $searchengines);

    /* 顯示日期 */
    $smarty->assign('start_date', $startDate);
    $smarty->assign('end_date', $endDate);

    $actionLink[] = array(
        'text' => '報表',
        'icon' => 'fas fa-print fa-fw',
        'href' => basename(__FILE__) . '?action=download&start_date=' . $startDate . '&end_date=' . $endDate,
        'style' => 'btn-primary'
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '報表管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    /* 顯示頁面 */
    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

case 'download':

    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

    $searchengine = array();
    $keyword = array();

    $sql = 'SELECT keyword, count, searchengine ' .
           'FROM ' .$db->table('keywords') . ' ' .
           'WHERE date >= "' . $startDate . '" AND date <= "' . $endDate . '" ' .
           'ORDER BY count DESC, searchengine, keyword';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $keyword[$val['keyword']] = 1;
        $searchengine[$val['searchengine']][$val['keyword']] = $val['count'];
    }

    require_once(BASE_PATH . 'PHPOffice/PHPExcel/PHPExcel.php');

    // 設定 cache
    PHPExcel_Settings::setCacheStorageMethod(
        PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
        array('memoryCacheSize' => '8MB')
    );

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objActSheet = $objPHPExcel->getSheet(0);

    $objActSheet->setTitle('搜尋引擎報表');

    $i = 0;
    foreach ($searchengine as $k => $v) {

        $i++;
        $objActSheet->setCellValueByColumnAndRow($i, 1, $k);
        $objActSheet->getStyleByColumnAndRow($i, 1)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    }

    $n = 1;
    foreach ($keyword as $kw => $val) {

        $i = 0;
        $n++;
        $objActSheet->setCellValueByColumnAndRow($i, $n, $kw);
        foreach ($searchengine as $k => $v) {

            $i++;
            $objActSheet->setCellValueByColumnAndRow($i, $n, isset($searchengine[$k][$kw]) ? $searchengine[$k][$kw] : 0);
        }
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);

    // 檔案輸出存檔
    $fileName = '搜尋引擎報表_' . $startDate . '_' . $endDate;
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    break;
}
exit;