<?php

/**
 *
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '創作者');

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require(dirname(__FILE__) . '/includes/init.php');

$_CFG['bgcolor'] = '#FFFFFF';
$_CFG['picture_large'] = '800 x 800';
$_CFG['picture_thumb'] = '200 x 200';

/* 初始化數據交換對像 */
$exchange = new exchange(
    $db->table('minstrel'),
    $db,
    'minstrel_id',
    'minstrel_code'
);

/* 操作項的初始化 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'list';

switch ($_REQUEST['action']) {
//-- 列表 || 查詢
case 'list':
case 'query':

    $list = getListPage();

    $smarty->assign('item_arr', $list['item']);
    $smarty->assign('filter', $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count', $list['page_count']);

    $sortFlag = sortFlag($list['filter']);
    $smarty->assign($sortFlag['tag'], $sortFlag['icon']);

    if ($_REQUEST['action'] == 'list') {

        $smarty->assign('full_page', 1);

        $actionLink[] = array(
            'text' => '匯出',
            'icon' => 'far fa-file-excel fa-fw',
            'href' => basename(__FILE__) . '?action=export_xls'
        );

        $actionLink[] = array(
            'text' => '匯入',
            'icon' => 'fas fa-file-import fa-fw',
            'href' => '#upload-excel'
        );

        $actionLink[] = array(
            'text' => '新增',
            'icon' => 'far fa-edit fa-fw',
            'sref' => buildUIRef('?action=add')
        );
        $smarty->assign('action_link', $actionLink);

        $position = assignUrHere(SCRIPT_TITLE);
        $smarty->assign('page_title', $position['title']); // 頁面標題
        $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

        assignTemplate();
        assignQueryInfo();
        $smarty->display(SCRIPT_NAME . '_list.html');

    } else {

        $smarty->assign('full_page', 0);

        assignQueryInfo();
        makeJsonResult($smarty->fetch(SCRIPT_NAME . '_list.html'), '',
            array('filter' => $list['filter'], 'page_count' => $list['page_count']));
    }
    break;

//-- 新增 || 編輯 ||　複製
case 'add':
case 'edit':
case 'copy':

    /* 檢查權限 */
    adminPriv('minstrel_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    $songLinkList = array();

    if ($isAdd) {

        /* 初始化 */
        $time = $_SERVER['REQUEST_TIME'];
        $data = array(
            'minstrel_id' => 0
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('minstrel') . ' ' .
               'WHERE minstrel_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }
    }

    $smarty->assign('data', $data);
    $smarty->assign('picture_thumb', $_CFG['picture_thumb']);
    $smarty->assign('picture_large', $_CFG['picture_large']);

    $smarty->assign('form_action', $isAdd ? 'insert' : ($_REQUEST['action'] == 'edit' ? 'update' : 'insert'));

    $actionLink[] = array(
        'text' => '返回',
        'icon' => 'fas fa-share fa-fw',
        'sref' => buildUIRef('?action=list', true),
        'style' => 'btn-pink'
    );
    $smarty->assign('action_link', $actionLink);

    $position = assignUrHere(SCRIPT_TITLE);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_info.html');
    break;

//-- 新增 || 更新
case 'insert':
case 'update':

    /* 檢查權限 */
    adminPriv('minstrel_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $minstrelId = !empty($_POST['minstrel_id']) ? (int) $_POST['minstrel_id'] : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=edit&id=' . $minstrelId)
    );

    $fields = array(
        'minstrel_code',
        'chinese_name',
        'original_name',
        'birthday_date',
        'doom_date',
        'nationality',
        'brief_info',
        'avatar_url',
        'file_url'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val]) ? trim($_POST[$val]) : '';
    }

    /* 檢查編號是否重複 */
    if ($data['minstrel_code'] != '') {

        $sql = 'SELECT COUNT(*) FROM ' . $db->table('minstrel') . ' ' .
               'WHERE minstrel_code = "' . $data['minstrel_code'] . '" ' .
               'AND minstrel_id <> ' . $minstrelId;
        if ($db->getOne($sql) > 0) {

            sysMsg('您輸入的編號已存在，請換一個', 1, array(), false);
        }
    }

    /* 如果沒有輸入編號則自動生成一個編號 */
    if ($data['minstrel_code'] == '') {

        $sql = 'SELECT IFNULL(MAX(minstrel_id), 0) + 1 FROM ' . $db->table('minstrel');
        $maxId = $isInsert ? $db->getOne($sql) : $minstrelId;
        $data['minstrel_code'] = generateCode($maxId);
    }

    require_once(BASE_PATH . 'cls_image.php');
    $image = new cls_image($_CFG['bgcolor']); // 縮圖背景色

    $imgUrl = isset($_POST['picture_large']) ? trim($_POST['picture_large']) : '';
    if ($imgUrl != '' && preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $imgUrl)) {

        $oldFileUrl = ROOT_PATH . $imgUrl;
        $oldFileUrl = cIconv($oldFileUrl, getServerOsCharset(), 'AUTO');
        if (is_file($oldFileUrl)) {

            $image->open($oldFileUrl)->moveFile();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $oldImg = $image->full_path;

            // 如果設置縮略圖大小不為0，生成縮略圖
            list($width, $height) = explode(' x ', $_CFG['picture_large']);
            $image->open(ROOT_PATH . $oldImg)->makeThumb($width, $height, 1);
            $image->autoSave();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $data['picture_large'] = $image->full_path;

            // 如果設置縮略圖大小不為0，生成縮略圖
            list($width, $height) = explode(' x ', $_CFG['picture_thumb']);
            $image->open(ROOT_PATH . $oldImg)->makeThumb($width, $height, 1);
            $image->autoSave();
            if (!empty($image->error)) {

                sysMsg($image->error[0]['error'], 1, $links, false);
            }
            $data['picture_thumb'] = $image->full_path;

            dropFile($oldImg);
        }
    }

    $tempUploadFile = array(
        'file_url',
    );

    foreach ($tempUploadFile as $val) {

        if (isset($data[$val]) &&
            preg_match('/^' . TEMP_DIR . '\/upload\/(.*)/', $data[$val])) {

            $oldFileUrl = ROOT_PATH . $data[$val];
            if (is_file(cIconv($oldFileUrl, getServerOsCharset(), 'AUTO'))) {

                preg_match(
                    '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                    $data[$val],
                    $pathinfo
                );

                $uploadPath = DATA_DIR . '/files/' . date('Ym') . '/';

                if (!makeDir(ROOT_PATH . $uploadPath)) {

                    sysMsg(sprintf('目錄 % 不存在或不可寫', $uploadPath), 1, $links, false);
                }

                $i = 0;
                do {

                    $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                    $data[$val] = $uploadPath . $newFileName;
                    $i++;

                } while (!moveFile($oldFileUrl, ROOT_PATH . $data[$val], false));

            } else {

                $data[$val] = '';
            }
        }
    }

    if ($isInsert) {

        /* 插入數據 */
        $sql = $db->buildSQL('I', $db->table('minstrel'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('minstrel') . ' ' .
               'WHERE minstrel_id = ' . $minstrelId;
        if ($minstrelId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        if (isset($data['avatar_url']) && $oldData['avatar_url'] != '') {

            dropFile(array(
                $oldData['avatar_url']
            ));
        }

        $sql = $db->buildSQL('U', $db->table('minstrel'), $data, 'minstrel_id = ' . $minstrelId);
    }

    $db->query($sql);

    /* 編號 */
    $minstrelId = $isInsert ? $db->insertId() : $minstrelId;

    /* 清空緩存 */
    clearCacheFiles();

    /* 記錄日誌 */
    adminLog(
        $data['minstrel_code'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $minstrelId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 下載範例檔
case 'import_example':

    @set_time_limit(0);

    $objPHPExcel = new Spreadsheet();

    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objPHPExcel->setActiveSheetIndex(0);

    $objActSheet = $objPHPExcel->getActiveSheet();

    $cellTitle = array();
    $cellTitle[] = '內部代碼';
    $cellTitle[] = '中文姓名';
    $cellTitle[] = '原文姓名';
    $cellTitle[] = '出生年代';
    $cellTitle[] = '逝世年代';
    $cellTitle[] = '國籍';
    $cellTitle[] = '簡要資訊';
    $cellTitle[] = '內部檔案';
    foreach ($cellTitle as $key => $value) {

        $codeKey = Coordinate::stringFromColumnIndex($key + 1);

        $objActSheet
            ->getCellByColumnAndRow($key + 1, 1)
            ->setValue($value);

        $objActSheet
            ->getColumnDimension($codeKey)
            ->setWidth(15); // 設定寬度
    }

    $lastColumn = Coordinate::stringFromColumnIndex(count($cellTitle));

    $objActSheet
        ->setAutoFilter('A1:' . $lastColumn . '1');

    $objActSheet
        ->getStyle('A1:' . $lastColumn . '1')
        ->applyFromArray(
            array(
                'fill' => array(
                    'fillType' => Fill::FILL_SOLID,
                    'color' => array('rgb' =>'B8B8B8')
                )
            )
        );

    $objActSheet
        ->getStyle('A1:' . $lastColumn . '1')
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_TOP);

    $objActSheet
        ->getStyle('A1:' . $lastColumn . '1')
        ->getBorders()
        ->getAllborders()
        ->setBorderStyle(Border::BORDER_THIN);

    // 檔案輸出存檔
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="example.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
    $objWriter->save('php://output');
    exit;
    break;

//-- 匯出 XLS
case 'export_xls':

    adminPriv('minstrel_manage');

    @set_time_limit(0);
    @ini_set('memory_limit', '2056M');

    $objPHPExcel = new Spreadsheet();

    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objPHPExcel->setActiveSheetIndex(0);

    $objActSheet = $objPHPExcel->getActiveSheet();

    // 欄位名稱陣列
    $cellTitle = array(
        'edit_mode' => array('title' => '編輯模式', 'wigth' => 15),
        'minstrel_code' => array('title' => '內部代碼', 'wigth' => 15),
        'chinese_name' => array('title' => '中文姓名', 'wigth' => 15),
        'original_name' => array('title' => '原文姓名', 'wigth' => 15),
        'birthday_date' => array('title' => '出生年代', 'wigth' => 15),
        'doom_date' => array('title' => '逝世年代', 'wigth' => 15),
        'nationality' => array('title' => '國籍', 'wigth' => 15),
        'brief_info' => array('title' => '簡要資訊', 'wigth' => 15),
        'file_url' => array('title' => '內部檔案', 'wigth' => 15)
    );

    $valKey = array_flip(array_keys($cellTitle));

    $dataRow = 2; // 資料起始列

    if (isset($_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME])) {

        $_REQUEST = $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME];
    }

    $list = getListPage(true);
    foreach ($list['item'] as $row) {

        foreach ($cellTitle as $titleKey => $titleVal) {

            $dataType = DataType::TYPE_STRING;
            if (isset($titleVal['text_type'])) {

                switch ($titleVal['text_type']) {
                    case 'NUMERIC':
                        $dataType = DataType::TYPE_NUMERIC;
                        break;
                }
            }
            $objActSheet
                ->getCellByColumnAndRow($valKey[$titleKey] + 1, $dataRow)
                ->setValueExplicit($row[$titleKey], $dataType);
        }
        $dataRow++;
    }
    unset($list);
    $dataRow--;

    $lastColumn = Coordinate::stringFromColumnIndex(count($cellTitle));

    $objActSheet
        ->setAutoFilter('A1:' . $lastColumn . '1');

    $objActSheet
        ->getStyle('A1:' . $lastColumn . '1')
        ->applyFromArray(
            array(
                'fill' => array(
                    'fillType' => Fill::FILL_SOLID,
                    'color' => array('rgb' =>'B8B8B8')
                )
            )
        );

    $objActSheet
        ->getStyle('A1:' . $lastColumn . $dataRow)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
        ->setVertical(Alignment::VERTICAL_TOP);

    $objActSheet
        ->getStyle('A1:' . $lastColumn . $dataRow)
        ->getBorders()
        ->getAllborders()
        ->setBorderStyle(Border::BORDER_THIN);

    $titleRow = 1; // 標題起始列
    foreach ($cellTitle as $key => $val) {

        $codeKey = Coordinate::stringFromColumnIndex($valKey[$key] + 1);

        $objActSheet
            ->getCellByColumnAndRow($valKey[$key] + 1, $titleRow)
            ->setValue($val['title']);

        if (!empty($val['wigth'])) {

            $objActSheet
                ->getColumnDimension($codeKey)
                ->setWidth($val['wigth']); // 設定寬度

        } else {

            $objActSheet
                ->getColumnDimension($codeKey)
                ->setAutoSize(true); // 自動寬度
        }

        if (isset($val['horizontal'])) {

            $horizontal = null;
            switch ($val['horizontal']) {
                case 'LEFT':
                    $horizontal = Alignment::HORIZONTAL_LEFT;
                    break;
                case 'RIGHT':
                    $horizontal = Alignment::HORIZONTAL_RIGHT;
                    break;
            }
            if (!is_null($horizontal)) {

                $objActSheet
                    ->getStyle($codeKey . '2:' . $codeKey . $dataRow)
                    ->getAlignment()
                    ->setHorizontal($horizontal);
            }
        }

        if (!empty($val['text_wrap'])) {

            $objActSheet
                ->getStyle($codeKey . '2:' . $codeKey . $dataRow)
                ->getAlignment()
                ->setWrapText(true);
        }
    }

    $objActSheet->freezePane('E2'); // 凍結窗格

    $fileName = '作曲_'. date('Ymd'); // 檔案名稱

    // 檔案輸出存檔
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
    $objWriter->save('php://output');
    exit;
    break;

//-- 匯入訂單
case 'upload_import':

    require_once(BASE_PATH . 'PluploadHandler.php');
    $file = PluploadHandler::handle(
        array(
            'target_dir' => TEMP_PATH . 'upload',
            'allow_extensions' => 'csv,xls,xlsx'
        )
    );

    if (!$file) {

        makeJsonError(PluploadHandler::get_error_message());

    } elseif (isset($file['path'])) {

        $tmpFileUrl = str_replace(ROOT_PATH, '', $file['path']);

        @set_time_limit(0);
        @ini_set('memory_limit', '2056M');

        $fileType = IOFactory::identify($file['path']);
        $objReader = IOFactory::createReader($fileType);

        $objPHPExcel = $objReader->load($file['path']); // 檔案名稱

        $iData = $objPHPExcel->getActiveSheet()->toArray('');

        $fields = array_shift($iData);
        $fields = array_flip($fields);

        if (empty($iData)) {

            makeJsonError('尚未建立匯入資料');
        }

        $iData = addslashesDeep($iData);

        if (!isset(
            $fields['內部代碼'],
            $fields['中文姓名'],
            $fields['原文姓名']
        )) {

            makeJsonError('匯入欄位異常');
        }

        $numShowError = array();
        foreach ($iData as $index => $row) {

            $index = $index + 2;

            $row = array_map('trim', $row);

            if (empty(array_filter($row, function ($val) {return $val != '';}))) {

                continue;
            }

            $editMode = 'I';
            if (isset($fields['編輯模式'])) {

                $editMode = strtoupper($row[$fields['編輯模式']]);
            }

            $sAutoData = array();

            $dFieldKey = array(
                'minstrel_code' => '內部代碼',
                'chinese_name' => '中文姓名',
                'original_name' => '原文姓名',
                'birthday_date' => '出生年代',
                'doom_date' => '逝世年代',
                'nationality' => '國籍',
                'brief_info' => '簡要資訊',
                'file_url' => '內部檔案'
            );
            foreach ($dFieldKey as $key => $val) {

                if (isset($fields[$val])) {

                    $sAutoData[$key] = $row[$fields[$val]];
                }
            }

            if (in_array($editMode, ['U', '2']) && $sAutoData['minstrel_code'] == '') {

                $numShowError[] = $index;
                $PHPIndexCode = Coordinate::stringFromColumnIndex($fields['內部代碼'] + 1) . $index;
                $objPHPExcel
                    ->getActiveSheet()
                    ->getStyle($PHPIndexCode)
                    ->applyFromArray(
                        array(
                            'fill' => array(
                                'fillType' => Fill::FILL_SOLID,
                                'color' => array('rgb' =>'F4AB43')
                            )
                        )
                    );
                continue;
            }

            switch ($editMode) {
                //-- 新增訂單
                case '':
                case 'I':
                case '1':

                    $isCreateCode = false;
                    // 當訂單編號空白時，系統自動產生訂單編號
                    if (!isset($sAutoData['minstrel_code']) || $sAutoData['minstrel_code'] == '') {

                        $isCreateCode = true;
                    }

                    // 插入訂單表
                    $errorNo = 0;
                    do {

                        if ($isCreateCode) {

                            $sql = 'SELECT IFNULL(MAX(minstrel_id), 0) + 1 FROM ' . $db->table('minstrel');
                            $maxId = $db->getOne($sql);
                            $sAutoData['minstrel_code'] = generateCode($maxId);
                        }

                        $db->autoExecute(
                            'I',
                            $db->table('minstrel'),
                            $sAutoData,
                            '',
                            'SILENT'
                        );

                        $errorNo = $db->errno();

                        // 建立失敗
                        if ($errorNo > 0 && $errorNo != 1062) {

                            $numShowError[] = $index;
                            $PHPIndexCode  = 'A' . $index;
                            $PHPIndexCode .= ':' . Coordinate::stringFromColumnIndex(count($fields)) . $index;
                            $objPHPExcel
                                ->getActiveSheet()
                                ->getStyle($PHPIndexCode)
                                ->applyFromArray(
                                    array(
                                        'fill' => array(
                                            'fillType' => Fill::FILL_SOLID,
                                            'color' => array('rgb' =>'F4AB43')
                                        )
                                    )
                                );
                            break;
                        }

                    } while ($errorNo == 1062); // 如果訂單號編號重複則重新送出資料

                    break;

                //-- 更新訂單
                case 'U':
                case '2':

                    $db->autoExecute(
                        'U',
                        $db->table('minstrel'),
                        $sAutoData,
                        'minstrel_code = "' . $sAutoData['minstrel_code'] . '"'
                    );
                    break;

                //-- 刪除
                case 'D':

                $sql = 'DELETE FROM ' . $db->table('minstrel') . ' ' .
                       'WHERE minstrel_code = "' . $sAutoData['minstrel_code'] . '"';
                $db->query($sql);
                break;

            }
        }

        if (!empty($numShowError)) {

            $objWriter = IOFactory::createWriter($objPHPExcel, $fileType);

            $newFileUrl = dirname(ROOT_PATH . $tmpFileUrl) . '/';
            $newFileUrl .= preg_replace('/^o\_/i', 'e_', pathinfo($tmpFileUrl, PATHINFO_FILENAME));
            $newFileUrl .= '.' . pathinfo($tmpFileUrl, PATHINFO_EXTENSION);
            $objWriter->save($newFileUrl);

            dropFile(ROOT_PATH . $tmpFileUrl);

            makeJsonResponse(
                '',
                2,
                sprintf('資料匯入有 %d 筆異常', count(array_unique($numShowError))),
                array('file_url' => BASE_URL . str_replace(ROOT_PATH, '', $newFileUrl))
            );

        } else {

            makeJsonResult('資料匯入更新成功');
        }
    }
    break;

//-- 刪除圖片
case 'cancel_picture':

    checkAuthzJson('minstrel_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $sql = 'SELECT * FROM ' . $db->table('minstrel') . ' ' .
           'WHERE minstrel_id = ' . $id;

    /* 刪除圖片文件 */
    if ($oldData = $db->getRow($sql)) {

        dropFile(array(
            $oldData['picture_large'],
            $oldData['picture_thumb']
        ));

        $exchange->edit(
            array(
                'picture_large' => '',
                'picture_thumb' => '',
                'update_time' => $_SERVER['REQUEST_TIME']
            ),
            $id
        );

        clearCacheFiles();
    }

    makeJsonResult(0, '圖案刪除成功');
    break;

//-- 刪除
case 'remove':

    checkAuthzJson('minstrel_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    handleItemDrop($id);

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'query';

    $dus->header('Location: ?' . http_build_query($queryString));
    exit;
    break;

//-- 批量刪除
case 'batch_remove':

    /* 檢查權限 */
    adminPriv('minstrel_manage');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $numDrop = handleItemDrop($_POST['checkboxes']);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    sysMsg(sprintf('您已經成功刪除 %d 筆', $numDrop), 0, $links);
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 為某生成唯一的編號
 *
 * @param   int     $minstrelId   商品編號
 *
 * @return  string  唯一的編號
 */
function generateCode($minstrelId)
{
    $GLOBALS['_CFG']['sn_prefix'] = 'C';
    $songCode = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 10 - strlen($minstrelId)) . $minstrelId;

    $sql = 'SELECT minstrel_code FROM ' . $GLOBALS['db']->table('minstrel') . ' ' .
           'WHERE minstrel_code LIKE "' . $GLOBALS['db']->likeQuote($songCode) . '%" ' .
           'AND minstrel_id <> ' . $minstrelId . ' ' .
           'ORDER BY LENGTH(minstrel_code) DESC';
    $codeList = $GLOBALS['db']->getCol($sql);
    if (in_array($songCode, $codeList)) {

        $max = pow(10, strlen($codeList[0]) - strlen($songCode) + 1) - 1;
        $newCode = $songCode . mt_rand(0, $max);
        while (in_array($newCode, $codeList)) {

            $newCode = $songCode . mt_rand(0, $max);
        }
        $songCode = $newCode;
    }

    return $songCode;
}

/**
 * 刪除
 *
 * @param     string    $idsDrop
 *
 * @return    int
 */
function handleItemDrop($idsDrop)
{
    $dropItem = array();
    $sql = 'SELECT mt.minstrel_id, mt.minstrel_code, mt.picture_large, mt.picture_thumb ' .
           'FROM ' . $GLOBALS['db']->table('minstrel') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.minstrel_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['minstrel_id']] = $row['minstrel_code'];

        dropFile(array(
            $row['picture_large'],
            $row['picture_thumb']
        ));
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('minstrel') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'minstrel_id');
        $GLOBALS['db']->query($sql);

        foreach ($dropItem as $name) {

            adminLog(addslashes($name), '刪除', SCRIPT_TITLE);
        }

        clearCacheFiles();
    }

    return count($dropItem);
}

/**
 * 獲取列表
 *
 * @return    array
 */
function getListPage($isExport = false)
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'minstrel_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';
    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND (chinese_name LIKE "%' . $queryString . '%" ';
        $operator .= 'OR original_name LIKE "%' . $queryString . '%")';
        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    if (empty($isExport)) {

        /* 總數 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('minstrel') . ' AS mt ' .
               'WHERE 1 ' . $operator;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = pageAndSize($filter);
        unset($_SESSION[SHOP_SESS]['filter']);
        $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;
    }

    $itemArr = array();

    if (!empty($isExport) || $filter['record_count'] > 0) {

        /* 獲取數據 */
        $sql = 'SELECT mt.* ' .
               'FROM ' . $GLOBALS['db']->table('minstrel') . ' AS mt ' .
               'WHERE 1 ' . $operator . ' ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = !empty($isExport)
            ? $GLOBALS['db']->query($sql)
            : $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['edit_mode'] = 'U';
            $itemArr[] = $row;
        }
    }

    if (!empty($isExport)) {

        return array('item' => $itemArr);
    }

    return array(
        'item' => $itemArr,
        'filter' => $filter,
        'page_count' => $filter['page_count'],
        'record_count' => $filter['record_count']
    );
}
