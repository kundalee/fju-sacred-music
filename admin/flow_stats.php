<?php

/**
 * 綜合流量統計
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '流量分析');

require(dirname(__FILE__) . '/includes/init.php');

/* 初始化執行動作 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'view';

switch ($_REQUEST['action']) {

case 'view':

    adminPriv('client_flow_stats');

    if (empty($_CFG['visit_stats'])) {

        sysMsg('網站流量統計已被關閉。<br>如有需要請至系統設定，開啟訪問統計服務。', 0, array(), false);
    }

    $isMulti = empty($_POST['is_multi']) ? false : true;

    /* 時間參數 */
    if (isset($_POST['start_date']) && !empty($_POST['end_date'])) {

        $startDate = strtotime($_POST['start_date']);
        $endDate = strtotime($_POST['end_date']);

    } else {

        $endDate  = $_SERVER['REQUEST_TIME'];
        $startDate = $endDate - 86400 * 7;
    }

    $startDateArr = array();
    $endDateArr = array();
    if (!empty($_POST['year_month'])) {

        foreach ($_POST['year_month'] as $tmp) {

            if (!empty($tmp)) {

                $tmpTime = strtotime($tmp . '-01');
                $startDateArr[] = $tmpTime;
                $endDateArr[]   = strtotime($tmp . '-' . date('t', $tmpTime));
            }
        }

    } else {

        $tmpTime = strtotime(date('Y-m-d'));
        $startDateArr[] = strtotime(date('Y-m') . '-01');
        $endDateArr[]   = strtotime(date('Y-m-t'));;
    }

    $stats = array();

    //-- 綜合流量
    if (!$isMulti) {

        $stats['flow']['option'] = array(
            'xAxis' => array(
                'type' => 'category',
                'data' => array()
            ),
            'yAxis' => array(
                'type' => 'value'
            ),
            'series' => array()
        );
        $values = array(
            'data' => array(),
            'type' => 'line'
        );

        $sql = 'SELECT FLOOR((access_time - ' . $startDate . ') / (24 * 3600)) AS sn, ' .
               'access_time, COUNT(*) AS access_count ' .
               'FROM ' . $db->table('stats') . ' ' .
               'WHERE access_time >= ' . $startDate . ' ' .
               'AND access_time <= ' . ($endDate + 86400) . ' ' .
               'GROUP BY sn';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $row['access_date'] = date('m-d', $row['access_time']);
            $stats['flow']['option']['xAxis']['data'][] = date('m-d', $row['access_time']);
            $values['data'][] = (int)$row['access_count'];
        }
        $stats['flow']['option']['series'][] = $values;

    } else {

        $stats['flow']['option'] = array(
            'tooltip' => array(
                'formatter' => '{a}-{b} : {c}'
            ),
            'legend' => array(
                'data' => array()
            ),
            'grid' => array(
                'left' => '3%',
                'right' => '4%',
                'bottom' => '3%',
                'containLabel' => true
            ),
            'xAxis' => array(
                'type' => 'category',
                'data' => array()
            ),
            'yAxis' => array(
                'type' => 'value'
            ),
            'series' => array()
        );

        foreach ($startDateArr as $k => $val) {

            $seriesName = date('Y-m', $startDateArr[$k]);

            $stats['flow']['option']['legend']['data'][] = $seriesName;

            $sql = 'SELECT FLOOR((access_time - ' . $startDateArr[$k] . ') / (24 * 3600)) AS sn, ' .
                   'access_time, COUNT(*) AS access_count ' .
                   'FROM ' . $db->table('stats') . ' ' .
                   'WHERE access_time >= ' . $startDateArr[$k] . ' ' .
                   'AND access_time <= ' . ($endDateArr[$k] + 86400) . ' ' .
                   'GROUP BY sn';
            $res = $db->query($sql);
            while ($row = $db->fetchAssoc($res)) {

                $day[$row['sn']][$seriesName] = (int)$row['access_count'];
            }
        }

        for ($i = 0; $i <= 30; $i++) {

            $stats['flow']['option']['xAxis']['data'][] = $i + 1;
        }

        foreach ($startDateArr as $k => $val) {

            $seriesName = date('Y-m', $startDateArr[$k]);

            $values = array(
                'name' => $seriesName,
                'type' => 'line',
                'data' => array()
            );
            for ($i = 0; $i <= 30; $i++) {

                $values['data'][] = isset($day[$i][$seriesName]) ? $day[$i][$seriesName] : 0;
            }
            $stats['flow']['option']['series'][] = $values;
        }
    }

    //-- 地域分佈
    if (!$isMulti) {

        $variable = array(
            'area' => '地域',
            'referer_domain' => '來源網站',
            'browser' => '瀏覽器',
            'system' => '作業系統'
        );
        foreach ($variable as $key => $name) {

            $itemArr = array();
            $stats[$key]['option'] = array(
                'tooltip' => array(
                    'trigger' => 'item',
                    'formatter' => '{a} <br>{b} : {c} ({d}%)'
                ),
                'legend' => array(
                    'type' => 'scroll',
                    'orient' => 'vertical',
                    'right' => 10,
                    'top' => 20,
                    'bottom' => 20,
                    'data' => array()
                ),
                'series' => array()
            );

            $values = array(
                'name' => $name,
                'type' => 'pie',
                'radius' => '70%',
                'center' => array('50%', '60%'),
                'data' => array(),
                'itemStyle' => array(
                    'emphasis' => array(
                        'shadowBlur' => 10,
                        'shadowOffsetX' => 0,
                        'shadowColor' => 'rgba(0, 0, 0, 0.5)'
                    )
                )
            );

            $sql = 'SELECT COUNT(*) AS access_count, ' . $key . ' ' .
                   'FROM ' . $db->table('stats') . ' ' .
                   'WHERE access_time >= ' . $startDate . ' ' .
                   'AND access_time < ' . ($endDate + 86400) . ' ' .
                   'GROUP BY ' . $key . ' ' .
                   'ORDER BY access_count DESC';
            $res = $db->query($sql);
            while ($row = $db->fetchAssoc($res)) {

                if ($key == 'area') {

                    $newKey = empty($row[$key]) ? 'unknow' : $row[$key];

                } elseif ($key == 'referer_domain') {

                    $newKey = empty($row[$key]) ? '直接輸入網址' : $row[$key];

                } elseif ($key == 'browser') {

                    $newKey = $row[$key] == '' || $row[$key] == 'Unknow browser' ? '未知瀏覽器' : $row[$key];
                    $newKey = preg_replace('/^Chrome(\s\d+\.\d+)(.*)$/i', 'Chrome', $newKey);
                    $newKey = preg_replace('/^FireFox(\s\d+\.\d+)(.*)$/i', 'FireFox', $newKey);
                    $newKey = preg_replace('/Safari(\s\d+\.\d+)(.*)$/i', 'Safari', $newKey);

                } elseif ($key == 'system') {

                    $newKey = $row[$key] == '' ? '未知系統' : $row[$key];
                    $newKey = preg_replace('/^i(Phone|Pod|Pad)(.*)$/i', 'i$1', $newKey);
                    $newKey = preg_replace('/^Android(\s\d+\.\d+)(.*)$/i', 'Android', $newKey);
                    $newKey = preg_replace('/^OS X(.*)$/i', 'OS X', $newKey);
                    $newKey = preg_replace('/^Windows(.*)$/i', 'Windows', $newKey);
                }

                if (!isset($itemArr[$newKey])) {

                    $itemArr[$newKey] = $row['access_count'];

                } else {

                    $itemArr[$newKey] += $row['access_count'];
                }
            }

            foreach ($itemArr as $name => $val) {

                $stats[$key]['option']['legend']['data'][] = $name;
                $values['data'][] = array(
                    'value' => $val,
                    'name' => $name
                );
            }
            $stats[$key]['option']['series'][] = $values;
        }

    } else {

        $variable = array(
            'area' => '地域',
            'referer_domain' => '來源網站',
            'browser' => '瀏覽器',
            'system' => '作業系統'
        );
        foreach ($variable as $key => $name) {

            $stats[$key]['option'] = array(
                'legend' => (object) array(),
                'tooltip' => (object) array(),
                'dataset' => array(
                    'dimensions' => array($key),
                    'source' => array()
                ),
                'xAxis' => array(
                    'type' => 'category'
                ),
                'yAxis' => (object) array(),
                'series' => array()
            );

            $category = array();
            $operator = '';
            foreach ($startDateArr as $k => $val) {

                if ($operator != '') {

                    $operator .= ' OR ';
                }
                $operator .= '(access_time >= ' . $startDateArr[$k] . ' AND access_time <= ' . ($endDateArr[$k] + 86400) . ') ';

                $date = date('Y-m', $val);
                $stats[$key]['option']['dataset']['dimensions'][] = $date;
                $stats[$key]['option']['series'][] = array('type' => 'bar');
            }

            $sql = 'SELECT ' . $key . ', COUNT(*) AS access_count, ' .
                    'FROM_UNIXTIME(access_time, "%Y-%m") AS access_date ' .
                    'FROM ' . $db->table('stats') . " " .
                    'WHERE ' . $operator .
                    'GROUP BY access_date, ' . $key;
            $res = $db->query($sql);
            while ($row = $db->fetchAssoc($res)) {

                if ($key == 'area') {

                    $newKey = $row[$key];

                } elseif ($key == 'referer_domain') {

                    $newKey = $row[$key] == '' ? '直接輸入網址' : $row[$key];

                } elseif ($key == 'browser') {

                    $newKey = $row[$key] == '' || $row[$key] == 'Unknow browser' ? '未知瀏覽器' : $row[$key];
                    $newKey = preg_replace('/^Chrome(\s\d+\.\d+)(.*)$/i', 'Chrome', $newKey);
                    $newKey = preg_replace('/^FireFox(\s\d+\.\d+)(.*)$/i', 'FireFox', $newKey);
                    $newKey = preg_replace('/Safari(\s\d+\.\d+)(.*)$/i', 'Safari', $newKey);

                } elseif ($key == 'system') {

                    $newKey = $row[$key] == '' ? '未知系統' : $row[$key];
                    $newKey = preg_replace('/^i(Phone|Pod|Pad)(.*)$/i', 'i$1', $newKey);
                    $newKey = preg_replace('/^Android(\s\d+\.\d+)(.*)$/i', 'Android', $newKey);
                    $newKey = preg_replace('/^OS X(.*)$/i', 'OS X', $newKey);
                    $newKey = preg_replace('/^Windows(.*)$/i', 'Windows', $newKey);
                }

                if (!isset($category[$newKey])) {

                    foreach ($startDateArr as $time) {

                        $date = date('Y-m', $time);
                        $category[$newKey][$date] = 0;
                    }
                }
                $category[$newKey][$row['access_date']] += $row['access_count'];
            }

            foreach ($category as $label => $row) {

                $stats[$key]['option']['dataset']['source'][] = array_merge(array($key => $label), $row);
            }
        }
    }

    $smarty->assign('stats', $stats);
    $smarty->assign('is_multi', $isMulti);

    /* 顯示日期 */
    $smarty->assign('start_date', date('Y-m-d', $startDate));
    $smarty->assign('end_date', date('Y-m-d', $endDate));
    for ($i = 0; $i < 5; $i++) {

        if (isset($startDateArr[$i])) {

            $startDateArr[$i] = date('Y-m', $startDateArr[$i]);

        } else {

            $startDateArr[$i] = null;
        }
    }
    $smarty->assign('start_date_arr', $startDateArr);

    if (!$isMulti) {

        $actionLink[] = array(
            'text' => '報表',
            'icon' => 'fas fa-print fa-fw',
            'href' => basename(__FILE__) . '?action=download&start_date=' . $startDate . '&end_date=' . $endDate,
            'style' => 'btn-primary'
        );
        $smarty->assign('action_link', $actionLink);
    }

    $extendArr[] = array('text' => '報表管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '.html');
    break;

case 'download':

    $startDate = !empty($_GET['start_date']) ? intval($_GET['start_date']) : 0;
    $endDate = !empty($_GET['end_date']) ? intval($_GET['end_date']) : 0;

    require_once(BASE_PATH . 'PHPOffice/PHPExcel/PHPExcel.php');

    // 設定 cache
    PHPExcel_Settings::setCacheStorageMethod(
        PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp,
        array('memoryCacheSize' => '8MB')
    );

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objActSheet = $objPHPExcel->getSheet(0);

    $objActSheet->setTitle('綜合到訪量統計');
    $objActSheet->setCellValueByColumnAndRow(0, 1, '地區');
    $objActSheet->setCellValueByColumnAndRow(1, 1, '到訪量');
    $objActSheet->getStyle('A1:B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $dataRow = 2; // 資料起始列
    $sql = 'SELECT FROM_UNIXTIME(access_time, "%Y-%m-%d") AS access_date, ' .
           'COUNT(*) AS access_count ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . ($endDate + 86400) . ' ' .
           'GROUP BY access_date';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $objActSheet->setCellValueByColumnAndRow(0, $dataRow, $val['access_date']);
        $objActSheet->setCellValueByColumnAndRow(1, $dataRow, $val['access_count']);
        $dataRow++;
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);
    $objActSheet->getColumnDimension('B')->setWidth(10);

    $dataRow--;
    $objActSheet->getStyle('A1:A' . $dataRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $objPHPExcel->createSheet();
    $objActSheet = $objPHPExcel->getSheet(1);

    $objActSheet->setTitle('地區分佈統計');
    $objActSheet->setCellValueByColumnAndRow(0, 1, '地區');
    $objActSheet->setCellValueByColumnAndRow(1, 1, '到訪量');
    $objActSheet->getStyle('A1:B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $dataRow = 2; // 資料起始列
    $sql = 'SELECT COUNT(*) AS access_count, area ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . ($endDate + 86400) . ' ' .
           'GROUP BY area ORDER BY access_count DESC';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $objActSheet->setCellValueByColumnAndRow(0, $dataRow, $val['area']);
        $objActSheet->setCellValueByColumnAndRow(1, $dataRow, $val['access_count']);
        $dataRow++;
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);
    $objActSheet->getColumnDimension('B')->setWidth(10);

    $objPHPExcel->createSheet();
    $objActSheet = $objPHPExcel->getSheet(2);

    $objActSheet->setTitle('來源網站統計');
    $objActSheet->setCellValueByColumnAndRow(0, 1, '來源');
    $objActSheet->setCellValueByColumnAndRow(1, 1, '到訪量');
    $objActSheet->getStyle('A1:B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $dataRow = 2; // 資料起始列
    $sql = 'SELECT COUNT(*) AS access_count, referer_domain ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . ($endDate + 86400) . ' ' .
           'GROUP BY referer_domain ORDER BY access_count DESC';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $objActSheet->setCellValueByColumnAndRow(0, $dataRow, ($val['referer_domain'] == '' ? '直接輸入網址' : $val['referer_domain']));
        $objActSheet->setCellValueByColumnAndRow(1, $dataRow, $val['access_count']);
        $dataRow++;
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);
    $objActSheet->getColumnDimension('B')->setWidth(10);

    $objPHPExcel->createSheet();
    $objActSheet = $objPHPExcel->getSheet(3);

    $objActSheet->setTitle('瀏覽器統計');
    $objActSheet->setCellValueByColumnAndRow(0, 1, '瀏覽器');
    $objActSheet->setCellValueByColumnAndRow(1, 1, '到訪量');
    $objActSheet->getStyle('A1:B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $dataRow = 2; // 資料起始列
    $sql = 'SELECT COUNT(*) AS access_count, browser ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . ($endDate + 86400) . ' ' .
           'GROUP BY browser ORDER BY access_count DESC';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $objActSheet->setCellValueByColumnAndRow(0, $dataRow, ($val['browser'] == '' ? '未知瀏覽器' : $val['browser']));
        $objActSheet->setCellValueByColumnAndRow(1, $dataRow, $val['access_count']);
        $dataRow++;
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);
    $objActSheet->getColumnDimension('B')->setWidth(10);

    $objPHPExcel->createSheet();
    $objActSheet = $objPHPExcel->getSheet(4);

    $objActSheet->setTitle('作業系統統計');
    $objActSheet->setCellValueByColumnAndRow(0, 1, '作業系統');
    $objActSheet->setCellValueByColumnAndRow(1, 1, '到訪量');
    $objActSheet->getStyle('A1:B1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    $dataRow = 2; // 資料起始列
    $sql = 'SELECT COUNT(*) AS access_count, system ' .
           'FROM ' . $db->table('stats') . ' ' .
           'WHERE access_time >= ' . $startDate . ' ' .
           'AND access_time <= ' . ($endDate + 86400) . ' ' .
           'GROUP BY system ORDER BY access_count DESC';
    $res = $db->query($sql);
    while ($val = $db->fetchAssoc($res)) {

        $objActSheet->setCellValueByColumnAndRow(0, $dataRow, ($val['system'] == '' ? '未知作業系統' : $val['system']));
        $objActSheet->setCellValueByColumnAndRow(1, $dataRow, $val['access_count']);
        $dataRow++;
    }
    $objActSheet->getColumnDimension('A')->setAutoSize(true);
    $objActSheet->getColumnDimension('B')->setWidth(10);

    // 檔案輸出存檔
    $fileName = '流量分析報表_' . date('Y-m-d', $startDate) . '_' . date('Y-m-d', $endDate);
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    break;
}
exit;