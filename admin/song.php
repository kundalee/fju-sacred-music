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
define('SCRIPT_TITLE', '歌曲');

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
    $db->table('song_info'),
    $db,
    'song_id',
    'song_code'
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
    adminPriv('song_manage');

    $isAdd = $_REQUEST['action'] == 'add'; // 新增還是編輯的標識

    $songLinkList = array();

    if ($isAdd) {

        /* 初始化 */
        $time = $_SERVER['REQUEST_TIME'];
        $data = array(
            'song_id' => 0,
            'show_img' => 1
        );

    } else {

        $_REQUEST['id'] = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        /* 取得數據 */
        $sql = 'SELECT * FROM ' . $db->table('song_info') . ' ' .
               'WHERE song_id = ' . $_REQUEST['id'];
        if ($_REQUEST['id'] <= 0 || !$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        $tags = array();
        $sql = 'SELECT td.tag_name FROM ' . $db->table('song_relation_tag') . ' AS rt ' .
               'INNER JOIN ' . $db->table('tags') . ' AS td ' .
               'ON td.tag_id = rt.tag_id ' .
               'WHERE rt.song_id = ' . $_REQUEST['id'] . ' ' .
               'ORDER BY rt.sort_order ASC';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $tags[] = $row['tag_name'];
        }

        $data['tags'] = implode(',', $tags);
    }

    $tagOptions = array();
    $sql = 'SELECT * FROM ' . $db->table('tags');
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        $tagOptions[] = array('text' => $row['tag_name'], 'value' => $row['tag_name']);
    }
    $smarty->assign('tag_options', $tagOptions);

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
    adminPriv('song_manage');

    /* 插入還是更新的標識 */
    $isInsert = $_REQUEST['action'] == 'insert';

    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $links[0] = array(
        'text' => '返回上一頁',
        'sref' => buildUIRef($isInsert ? '?action=add' : '?action=edit&id=' . $songId)
    );

    $fields = array(
        'song_code',
        'zh_song_title',
        'en_song_title_1',
        'en_song_title_2',
        'tune_name',
        'first_melody',
        'first_lyric',
        'tonality',
        'tempo',
        'rhythm',
        'reference',
        'copyright',
        'chinese_lyrics',
        'sheet_music_num',
        'hymn_music_num',
        'composer_name',
        'lyricist_name',
        'file_url_1',
        'file_url_2',
        'file_url_3',
        'file_url_4',
        'song_theme',
        'song_prepared',
        'song_remark',
        'source_book',
        'page_title',
        'meta_keywords',
        'meta_description',
        'page_code_head',
        'page_code_body'
    );
    foreach ($fields as $val) {

        $data[$val] = isset($_POST[$val]) ? trim($_POST[$val]) : '';
    }

    $data['is_open'] = isset($_POST['is_open']) ? 1 : 0;
    $data['show_img'] = isset($_POST['show_img']) ? 1 : 0;

    /* 檢查編號是否重複 */
    if ($data['song_code'] != '') {

        $sql = 'SELECT COUNT(*) FROM ' . $db->table('song_info') . ' ' .
               'WHERE song_code = "' . $data['song_code'] . '" ' .
               'AND song_id <> ' . $songId;
        if ($db->getOne($sql) > 0) {

            sysMsg('您輸入的編號已存在，請換一個', 1, array(), false);
        }
    }

    /* 如果沒有輸入編號則自動生成一個編號 */
    if ($data['song_code'] == '') {

        $sql = 'SELECT IFNULL(MAX(song_id), 0) + 1 FROM ' . $db->table('song_info');
        $maxId = $isInsert ? $db->getOne($sql) : $songId;

        $data['song_code'] = generateCode($maxId, 'song');
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
        'file_url_1',
        'file_url_2',
        'file_url_3',
        'file_url_4'
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
        $sql = $db->buildSQL('I', $db->table('song_info'), $data);

    } else {

        $sql = 'SELECT * FROM ' . $db->table('song_info') . ' ' .
               'WHERE song_id = ' . $songId;
        if ($songId <= 0 || !$oldData = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'sref' => buildUIRef())));
        }

        if (isset($data['picture_thumb']) && $oldData['picture_thumb'] != '') {

            dropFile(array(
                $oldData['picture_large'],
                $oldData['picture_thumb']
            ));
        }

        $sql = $db->buildSQL('U', $db->table('song_info'), $data, 'song_id = ' . $songId);
    }

    $db->query($sql);

    /* 編號 */
    $songId = $isInsert ? $db->insertId() : $songId;

    if ($isInsert) {

        $db->query(
            $db->buildSQL(
                'U',
                $db->table('book_relation_song'),
                array(
                    'song_id' => $songId
                ),
                'song_id = 0 AND ' .
                'admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
            )
        );
    }

    handleRelationTag(
        $songId,
        isset($_POST['tags']) ? $_POST['tags'] : ''
    );

    /* 清空緩存 */
    clearCacheFiles();

    /* 記錄日誌 */
    adminLog(
        $data['song_code'],
        $isInsert ? '新增' : '編輯',
        SCRIPT_TITLE
    );

    /* 提示頁面 */
    $links = array();
    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $songId));

    if ($isInsert) {

        $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add', true));
    }

    sysMsg('操作完成', 0, $links);
    break;

//-- 下載範例檔
case 'import_example':

    header('Location: ' . BASE_URL . 'data/匯入範例(歌曲內碼歌本內碼不用填).xlsx');
    exit;

    @set_time_limit(0);

    $objPHPExcel = new Spreadsheet();

    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objPHPExcel->setActiveSheetIndex(0);

    $objActSheet = $objPHPExcel->getActiveSheet()->setTitle('歌曲');

    $cellTitle = array();
    $cellTitle[] = '歌曲內碼';
    $cellTitle[] = '歌曲名稱';
    $cellTitle[] = '外文歌名１';
    $cellTitle[] = '外文歌名２';
    $cellTitle[] = '調性';
    $cellTitle[] = '節奏';
    $cellTitle[] = '首句旋律';
    $cellTitle[] = '首句歌詞';
    $cellTitle[] = '調名';
    $cellTitle[] = '韻律';
    $cellTitle[] = '參考經文';
    $cellTitle[] = '版權';
    $cellTitle[] = '原曲音檔';
    $cellTitle[] = '原文譜';
    $cellTitle[] = '中文譜';
    $cellTitle[] = '中文歌詞';
    $cellTitle[] = '投影片';
    $cellTitle[] = '輕歌讚主榮頁碼';
    $cellTitle[] = '遣使會聖歌選集頁碼';
    $cellTitle[] = '作曲人';
    $cellTitle[] = '作詞人';
    $cellTitle[] = '歌曲主題';
    $cellTitle[] = '編制';
    $cellTitle[] = '歌曲編號';
    $cellTitle[] = '歌本頁碼';
    $cellTitle[] = '歌本內碼';

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

    adminPriv('song_manage');

    @set_time_limit(0);
    @ini_set('memory_limit', '2056M');

    $objPHPExcel = new Spreadsheet();

    $objPHPExcel->getDefaultStyle()->getFont()->setSize(12); // 設定字體大小

    $objPHPExcel->setActiveSheetIndex(0);

    $objActSheet = $objPHPExcel->getActiveSheet()->setTitle('歌曲');

    // 欄位名稱陣列
    $cellTitle = array(
        'edit_mode' => array('title' => '編輯模式', 'wigth' => 15),
        'song_code' => array('title' => '歌曲內碼', 'wigth' => 15),
        'zh_song_title' => array('title' => '歌曲名稱', 'wigth' => 30, 'horizontal' => 'LEFT'),
        'en_song_title_1' => array('title' => '外文歌名１', 'wigth' => 30, 'horizontal' => 'LEFT'),
        'en_song_title_2' => array('title' => '外文歌名２', 'wigth' => 30, 'horizontal' => 'LEFT'),
        'tonality' => array('title' => '調性', 'wigth' => 15),
        'tempo' => array('title' => '節奏', 'wigth' => 15),
        'first_melody' => array('title' => '首句旋律', 'wigth' => 20, 'horizontal' => 'LEFT'),
        'first_lyric' => array('title' => '首句歌詞', 'wigth' => 30, 'horizontal' => 'LEFT'),
        'tune_name' => array('title' => '調名', 'wigth' => 15),
        'rhythm' => array('title' => '韻律', 'wigth' => 15),
        'reference' => array('title' => '參考經文', 'wigth' => 15, 'text_wrap' => true, 'horizontal' => 'LEFT'),
        'copyright' => array('title' => '版權', 'wigth' => 15),
        'file_url_1' => array('title' => '原曲音檔', 'wigth' => 15),
        'file_url_2' => array('title' => '原文譜', 'wigth' => 15),
        'file_url_3' => array('title' => '中文譜', 'wigth' => 15),
        'chinese_lyrics' => array('title' => '中文歌詞', 'wigth' => 15, 'text_wrap' => true, 'horizontal' => 'LEFT'),
        'file_url_4' => array('title' => '投影片', 'wigth' => 15),
        'sheet_music_num' => array('title' => '輕歌讚主榮頁碼', 'wigth' => 15),
        'hymn_music_num' => array('title' => '遣使會聖歌選集頁碼', 'wigth' => 15),
        'composer_name' => array('title' => '作曲人', 'wigth' => 15),
        'lyricist_name' => array('title' => '作詞人', 'wigth' => 15),
        'song_theme' => array('title' => '歌曲主題', 'wigth' => 15),
        'song_prepared' => array('title' => '編制', 'wigth' => 15),
        'song_remark' => array('title' => '備註', 'wigth' => 60, 'text_wrap' => true, 'horizontal' => 'LEFT'),
        'tags' => array('title' => '標籤', 'wigth' => 30),
        'relation_book_code' => array('title' => '歌本內碼', 'wigth' => 15),
        'relation_page_code' => array('title' => '歌本頁碼', 'wigth' => 15)
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

    $fileName = '歌曲清單_'. date('Ymd'); // 檔案名稱

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

        list($usec) = explode(' ', microtime());
        $writeFileNo = 'U' . (date('Y') - 1911) . date('md') . strtoupper(str_pad(base_convert(date('s') . preg_replace('/\./', '', $usec), 10, 36), 7, '0', STR_PAD_LEFT));

        $fileType = IOFactory::identify($file['path']);
        $objReader = IOFactory::createReader($fileType);

        $objPHPExcel = $objReader->load($file['path']); // 檔案名稱

        $allSheet = $objPHPExcel->getSheetNames();

        $dFieldKey['歌本'] = array(
            'book_code' => '歌本內碼',
            'catalog_no' => '編目索引號',
            'book_title' => '書名',
            'author' => '編者',
            'publisher' => '出版者',
            'quasi_print' => '准印者',
            'first_edit_time' => '初版時間',
            'ver_info' => '版本資訊',
            'print_method' => '印刷方式',
            'page_num' => '頁數',
            'song_num' => '歌曲數',
            'song_lang' => '歌曲使用語言',
            'book_brief' => '內容簡述',
            'admin_note' => '備註'
        );

        $dFieldKey['歌曲'] = array(
            'song_code' => '歌曲內碼',
            'zh_song_title' => '歌曲名稱',
            'en_song_title_1' => '外文歌名１',
            'en_song_title_2' => '外文歌名２',
            'tonality' => '調性',
            'tempo' => '節奏',
            'first_melody' => '首句旋律',
            'first_lyric' => '首句歌詞',
            'tune_name' => '調名',
            'rhythm' => '韻律',
            'reference' => '參考經文',
            'copyright' => '版權',
            'file_url_1' => '原曲音檔',
            'file_url_2' => '原文譜',
            'file_url_3' => '中文譜',
            'chinese_lyrics' => '中文歌詞',
            'file_url_4' => '投影片',
            'sheet_music_num' => '輕歌讚主榮頁碼',
            'hymn_music_num' => '遣使會聖歌選集頁碼',
            'composer_name' => '作曲人',
            'lyricist_name' => '作詞人',
            'song_theme' => '歌曲主題',
            'song_prepared' => '編制',
            'song_remark' => '備註'
        );

        $dataTableKey = array(
            '歌本' => 'book',
            '歌曲' => 'song'
        );
        foreach (array_keys($dataTableKey) as $sheetName) {

            if (!in_array($sheetName, $allSheet)) {

                continue;
            }

            $excelData[$sheetName] = $objPHPExcel->getSheetByName($sheetName)->toArray('');

            $excelFields[$sheetName] = array_shift($excelData[$sheetName]);
            $excelFields[$sheetName] = array_flip($excelFields[$sheetName]);

            if (empty($excelData[$sheetName])) {

                makeJsonError($sheetName . '尚未建立匯入資料');
            }

            if ($sheetName == '歌本') {

                if (!isset(
                    $excelFields[$sheetName]['歌本內碼'],
                    $excelFields[$sheetName]['書名']
                )) {

                    makeJsonError($sheetName . '匯入欄位異常');
                }

            } else {

                if (!isset(
                    $excelFields[$sheetName]['歌曲內碼'],
                    $excelFields[$sheetName]['歌曲名稱'],
                    $excelFields[$sheetName]['外文歌名１'],
                    $excelFields[$sheetName]['外文歌名２']
                )) {

                    makeJsonError($sheetName . '匯入欄位異常');
                }
            }

            $excelData[$sheetName] = addslashesDeep($excelData[$sheetName]);
        }

        if (empty($excelData)) {

            makeJsonError('資料異常匯入失敗');
        }

        $isImportSingleBook = false;
        if (!empty($excelData['歌本']) && count($excelData['歌本']) == 1) {

            $isImportSingleBook = true;
        }

        $numShowError = array();
        foreach ($excelData as $sheetName => $iData) {

            $fields = $excelFields[$sheetName];
            foreach ($iData as $index => $row) {

                $index = $index + 2;

                $row = array_map('trim', $row);

                if (empty(array_filter($row, function ($val) {return $val != '';}))) {

                    continue;
                }

                $editMode = 'I';
                if (isset($fields['編輯模式']) && $row[$fields['編輯模式']] != '') {

                    $editMode = strtoupper($row[$fields['編輯模式']]);
                }

                $sAutoData = array();

                foreach ($dFieldKey[$sheetName] as $key => $val) {

                    if (isset($fields[$val])) {

                        $sAutoData[$key] = $row[$fields[$val]];
                    }
                }

                if (in_array($editMode, ['U', '2', 'D']) && $sAutoData[$dataTableKey[$sheetName] . '_code'] == '') {

                    $numShowError[] = $index;
                    $PHPIndexCode = Coordinate::stringFromColumnIndex($fields[$sheetName . '內碼'] + 1) . $index;
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

                if (
                    in_array($editMode, ['I', '1']) &&
                    $sheetName == '歌曲'
                ) {

                    if (
                        isset($singleBookCode) &&
                        $isImportSingleBook
                    ) {

                        $sAutoData['source_book'] = $singleBookCode;
                    }
                }

                switch ($editMode) {
                    //-- 新增
                    case '':
                    case 'I':
                    case '1':
                    default:

                        $isCreateCode = false;
                        // 當訂單編號空白時，系統自動產生訂單編號
                        if (
                            !isset($sAutoData[$dataTableKey[$sheetName] . '_code']) ||
                            $sAutoData[$dataTableKey[$sheetName] . '_code'] == ''
                        ) {

                            $isCreateCode = true;
                        }

                        // 插入訂單表
                        $errorNo = 0;
                        do {

                            if ($isCreateCode) {

                                $sql = 'SELECT IFNULL(MAX(' . $dataTableKey[$sheetName] . '_id), 0) + 1 ' .
                                       'FROM ' . $db->table($dataTableKey[$sheetName] . '_info');
                                $maxId = $db->getOne($sql);
                                $sAutoData[$dataTableKey[$sheetName] . '_code'] = generateCode($maxId, $dataTableKey[$sheetName]);

                            } else {

                                $sql = 'SELECT COUNT(*) FROM ' . $db->table($dataTableKey[$sheetName] . '_info') . ' ' .
                                       'WHERE ' . $dataTableKey[$sheetName] . '_code = "' . $sAutoData[$dataTableKey[$sheetName] . '_code'] . '"';
                                if ($db->getOne($sql)) {

                                    $numShowError[] = $index;
                                    $PHPIndexCode = Coordinate::stringFromColumnIndex($fields[$sheetName . '內碼'] + 1) . $index;
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
                            }

                            $sAutoData['write_file_no'] = $writeFileNo;

                            $db->autoExecute(
                                'I',
                                $db->table($dataTableKey[$sheetName] . '_info'),
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

                        if ($sheetName == '歌本') {

                            $singleBookCode = $sAutoData[$dataTableKey[$sheetName] . '_code'];
                        }
                        break;

                    //-- 更新
                    case 'U':
                    case '2':

                        $db->autoExecute(
                            'U',
                            $db->table($dataTableKey[$sheetName] . '_info'),
                            $sAutoData,
                            $dataTableKey[$sheetName] . '_code = "' . $sAutoData[$dataTableKey[$sheetName] . '_code'] . '"'
                        );
                        break;

                    //-- 刪除
                    case 'D':

                        $sql = 'DELETE FROM ' . $db->table($dataTableKey[$sheetName] . '_info') . ' ' .
                               'WHERE ' . $dataTableKey[$sheetName] . '_code = "' . $sAutoData[$dataTableKey[$sheetName] . '_code'] . '"';
                        $db->query($sql);
                        break;
                }

                if (
                    in_array($editMode, ['I', '1']) &&
                    $sheetName == '歌曲' &&
                    isset($fields['歌本內碼'], $fields['歌本頁碼'])
                ) {

                    if (
                        isset($singleBookCode) &&
                        $isImportSingleBook &&
                        $row[$fields['歌本內碼']] == ''
                    ) {

                        $row[$fields['歌本內碼']] = $singleBookCode;
                    }

                    $codeList = str_replace(array("\r", "\n", "\r\n", "\n\r"), ',', $row[$fields['歌本內碼']]);
                    $codeList = str_replace(' ', '', $codeList);
                    $codeList = explode(',', $codeList);
                    $codeList = array_map('trim', $codeList);
                    $codeList = array_filter($codeList, function ($val) {return $val != '';});

                    $pageList = str_replace(array("\r", "\n", "\r\n", "\n\r"), ',', $row[$fields['歌本頁碼']]);
                    $pageList = str_replace(' ', '', $pageList);
                    $pageList = explode(',', $pageList);
                    $pageList = array_map('trim', $pageList);
                    $pageList = array_filter($pageList, function ($val) {return $val != '';});

                    if (isset($fields['歌曲編號'])) {

                        $sortCode = str_replace(array("\r", "\n", "\r\n", "\n\r"), ',', $row[$fields['歌曲編號']]);
                        $sortCode = str_replace(' ', '', $sortCode);
                        $sortCode = explode(',', $sortCode);
                        $sortCode = array_map('trim', $sortCode);
                        $sortCode = array_filter($sortCode, function ($val) {return $val != '';});
                    }

                    // if (count($codeList) != count($pageList)) {

                    //     continue;
                    // }

                    $sql = 'SELECT song_id FROM ' . $db->table('song_info') . ' ' .
                           'WHERE song_code = "' . $sAutoData[$dataTableKey[$sheetName] . '_code'] . '"';
                    if ($relSongId = $db->getOne($sql)) {

                        $sql = 'DELETE FROM ' . $db->table('book_relation_song') . ' ' .
                               'WHERE song_id = ' . $relSongId;
                        $db->query($sql);

                        foreach ($codeList as $i => $v) {

                            $sql = 'SELECT book_id FROM ' . $db->table('book_info') . ' ' .
                                   'WHERE book_code = "' . $v . '"';
                            $relBookId = $db->getOne($sql);
                            if ($relBookId = $db->getOne($sql)) {

                                $db->query(
                                    $db->buildSQL(
                                        'R',
                                        $db->table('book_relation_song'),
                                        array(
                                            'book_id' => $relBookId,
                                            'song_id' => $relSongId,
                                            'page_code' => isset($pageList[$i]) ? trim($pageList[$i]) : '',
                                            'sort_order' => isset($sortCode[$i]) ? trim($sortCode[$i]) : ''
                                        )
                                    )
                                );
                            }
                        }
                    }
                }

                if (
                    $editMode == 'D' &&
                    $sheetName == '歌曲'
                ) {

                    $sql = 'SELECT song_id FROM ' . $db->table('song_info') . ' ' .
                           'WHERE song_code = "' . $sAutoData[$dataTableKey[$sheetName] . '_code'] . '"';
                    if ($relSongId = $db->getCol($sql)) {

                        $sql = 'DELETE FROM ' . $db->table('book_relation_song') . ' ' .
                               'WHERE song_id = ' . $relSongId;
                        $db->query($sql);
                    }
                }
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

//-- 搜尋歌曲
case 'search_book':

    $bookId = !empty($_POST['book_id']) ? (int) $_POST['book_id'] : 0;

    /* 搜尋欄位 */
    $operator = '1 ';

    // 關鍵字
    if (isset($_POST['search_keyword'])
        && ($_POST['search_keyword'] = trim($_POST['search_keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['search_keyword']);

        $operator .= 'AND (mt.book_code LIKE "%' . $queryString . '%" ' .
                     'OR mt.book_title LIKE "%' . $queryString . '%" ';
    }

    /* 取得歌曲 */
    $itemArr = array();
    $sql = 'SELECT * ' .
           'FROM ' . $GLOBALS['db']->table('book_info') . ' AS mt ' .
           'WHERE ' . $operator;
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        /* 資料陣列 */
        $itemArr[$row['book_id']] = $row;
    }

    $smarty->assign('tpl', 'search');
    $smarty->assign('item_arr', $itemArr);

    makeJsonResult(
        $smarty->fetch(SCRIPT_NAME . '_relation_book.html'),
        '',
        array(
            'find_define_book' => true
        )
    );
    break;

//-- 取得商品
case 'load_define_book':

    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $operator = 'bs.song_id = ' . $songId . ' ';
    if ($songId <= 0) {

        $operator .= 'AND bs.admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }

    /* 取得商品 */
    $itemArr = array();
    $sql = 'SELECT bi.book_id, bi.book_code, ' .
           'bi.book_title, bi.picture_large, bi.picture_thumb, ' .
           'bs.page_code, bs.sort_order ' .
           'FROM ' . $db->table('book_relation_song') . ' AS bs ' .
           'INNER JOIN ' . $db->table('book_info') . ' AS bi ' .
           'ON bi.book_id = bs.book_id ' .
           'WHERE ' . $operator . ' ' .
           'ORDER BY bs.page_code, bs.sort_order';
    $res = $db->query($sql);
    while ($row = $db->fetchAssoc($res)) {

        /* 資料陣列 */
        $itemArr[$row['book_id']] = $row;
    }

    $smarty->assign('tpl', 'relation');
    $smarty->assign('item_arr', $itemArr);

    makeJsonResult(
        $smarty->fetch(SCRIPT_NAME . '_relation_book.html'),
        '',
        array(
            'load_define_book' => true
        )
    );
    break;

//-- 新增歌曲
case 'add_define_book':

    /* 字串處理 */
    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    if (!empty($_POST['join_book']) && is_array($_POST['join_book'])) {

        foreach ($_POST['join_book'] as $val) {

            $db->query(
                $db->buildSQL(
                    'I',
                    $db->table('book_relation_song'),
                    array(
                        'song_id' => $songId,
                        'book_id' => (int) $val,
                        'admin_id' => $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
                    )
                ),
                'SILENT'
            );
        }
    }

    makeJsonResult(
        '',
        '',
        array(
            'reload_define_book' => true
        )
    );
    break;

//-- 移除歌曲
case 'drop_define_book':

    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    if (!empty($_POST['update_book']) && is_array($_POST['update_book'])) {

        /* 移除歌曲 */
        $sql = 'DELETE FROM ' . $db->table('book_relation_song') . ' ' .
               'WHERE song_id = ' . $songId . ' ' .
               'AND ' . $db->in($_POST['update_book'], 'book_id');
        if ($bookId <= 0) {

            $sql .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
        }
        $db->query($sql);
    }

    makeJsonResult(
        '',
        '',
        array(
            'reload_define_book' => true
        )
    );
    break;

case 'relation_edit_sort_code':

    checkAuthzJson('book_manage');

    $sortCode = !empty($_POST['val']) ? (int) $_POST['val'] : 0;
    $bookId = !empty($_POST['book_id']) ? (int) $_POST['book_id'] : 0;
    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $operator = 'book_id = ' . $bookId . ' ';
    if ($songId > 0) {
        $operator .= 'AND song_id = ' . $songId . ' ';
    } else {
        $operator .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . ' ';
    }

    $db->query(
        $db->buildSQL(
            'U',
            $db->table('book_relation_song'),
            array(
                'sort_order' => $sortCode
            ),
            $operator
        )
    );

    clearCacheFiles();
    makeJsonResult($sortCode);
    break;

case 'relation_edit_page_code':

    checkAuthzJson('book_manage');

    $pageCode = !empty($_POST['val']) ? (int) $_POST['val'] : 0;
    $bookId = !empty($_POST['book_id']) ? (int) $_POST['book_id'] : 0;
    $songId = !empty($_POST['song_id']) ? (int) $_POST['song_id'] : 0;

    $operator = 'book_id = ' . $bookId . ' ';
    if ($songId > 0) {
        $operator .= 'AND song_id = ' . $songId . ' ';
    } else {
        $operator .= 'AND admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] . ' ';
    }

    $db->query(
        $db->buildSQL(
            'U',
            $db->table('book_relation_song'),
            array(
                'page_code' => $pageCode
            ),
            $operator
        )
    );

    clearCacheFiles();
    makeJsonResult($pageCode);
    break;

//-- 刪除圖片
case 'cancel_picture':

    checkAuthzJson('song_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $sql = 'SELECT * FROM ' . $db->table('song_info') . ' ' .
           'WHERE song_id = ' . $id;

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

    checkAuthzJson('song_manage');

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
    adminPriv('song_manage');

    if (!isset($_POST['checkboxes']) || !is_array($_POST['checkboxes'])) {

        sysMsg('您沒有選擇任何項目', 1,
            array(array('text' => '返回列表', 'sref' => buildUIRef())));
    }

    $numDrop = handleItemDrop($_POST['checkboxes']);

    $links[] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    sysMsg(sprintf('您已經成功刪除 %d 筆', $numDrop), 0, $links);
    break;

//-- 切換是否顯示
case 'toggle_show':

    /* 檢查權限 */
    checkAuthzJson('song_manage');

    if (!empty($_REQUEST['id'])) {

        $id = (int) $_REQUEST['id'];

    } else {

        makeJsonError('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    $val = !empty($_REQUEST['val']) ? 1 : 0;

    $name = $exchange->getName($id);
    if ($exchange->edit(array('is_open' => $val), $id)) {

        adminLog(addslashes($name), '編輯', SCRIPT_TITLE);
        clearCacheFiles();
        makeJsonResult($val);

    } else {

        makeJsonError($db->error());
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 為某生成唯一的編號
 *
 * @param   int     $tableId   商品編號
 *
 * @return  string  唯一的編號
 */
function generateCode($tableId, $tableKey)
{
    $GLOBALS['_CFG']['sn_prefix'] = $tableKey == 'book' ? 'B' : 'S';
    $tableCode = $GLOBALS['_CFG']['sn_prefix'] . str_repeat('0', 10 - strlen($tableId)) . $tableId;

    $sql = 'SELECT ' . $tableKey . '_code FROM ' . $GLOBALS['db']->table($tableKey . '_info') . ' ' .
           'WHERE ' . $tableKey . '_code LIKE "' . $GLOBALS['db']->likeQuote($tableCode) . '%" ' .
           'AND ' . $tableKey . '_id <> ' . $tableId . ' ' .
           'ORDER BY LENGTH(' . $tableKey . '_code) DESC';
    $codeList = $GLOBALS['db']->getCol($sql);
    if (in_array($tableCode, $codeList)) {

        $max = pow(10, strlen($codeList[0]) - strlen($tableCode) + 1) - 1;
        $newCode = $tableCode . mt_rand(0, $max);
        while (in_array($newCode, $codeList)) {

            $newCode = $tableCode . mt_rand(0, $max);
        }
        $tableCode = $newCode;
    }

    return $tableCode;
}

/**
 * 保存擴展標籤
 *
 * @param     int      $songId     項目編號
 * @param     array    $tagList    標籤數組
 *
 * @return    void
 */
function handleRelationTag($songId, $tagList)
{
    $relationList = array();

    if ($tagList != '') {

        $oldTags = array();

        /* 查詢現有的擴展分類 */
        $sql = 'SELECT tag_id, tag_name FROM ' . $GLOBALS['db']->table('tags');
        $res = $GLOBALS['db']->query($sql);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $oldTags[$row['tag_id']] = $row['tag_name'];
        }

        $tagList = explode(',', $tagList);
        $tagList = array_map('trim', $tagList);
        foreach ($tagList as $key => $val) {

            if (in_array($val, $oldTags)) {

                $tagId = array_search($val, $oldTags);

            } else {

                $GLOBALS['db']->query(
                    $GLOBALS['db']->buildSQL(
                        'I',
                        $GLOBALS['db']->table('tags'),
                        array(
                            'tag_name' => $val,
                            'add_time' => $_SERVER['REQUEST_TIME'],
                            'update_time' => $_SERVER['REQUEST_TIME']
                        )
                    )
                );

                $tagId = $GLOBALS['db']->insertId();
            }

            $relationList[] = $tagId;
        }
    }

    $relationList = array_unique($relationList);

    $sql = 'DELETE FROM ' . $GLOBALS['db']->table('song_relation_tag') . ' ' .
           'WHERE song_id = ' . $songId;
    $GLOBALS['db']->query($sql);

    foreach ($relationList as $key => $tagId) {

        $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'R',
                $GLOBALS['db']->table('song_relation_tag'),
                array(
                    'song_id' => $songId,
                    'tag_id' => $tagId,
                    'sort_order' => $key
                )
            )
        );
    }
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
    $sql = 'SELECT mt.song_id, mt.song_code, mt.picture_large, mt.picture_thumb ' .
           'FROM ' . $GLOBALS['db']->table('song_info') . ' AS mt ' .
           'WHERE ' . $GLOBALS['db']->in($idsDrop, 'mt.song_id');
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $dropItem[$row['song_id']] = $row['song_code'];

        dropFile(array(
            $row['picture_large'],
            $row['picture_thumb']
        ));
    }

    /* 清空緩存 & 記錄日誌 */
    if (!empty($dropItem)) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('book_relation_song') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'song_id');
        $GLOBALS['db']->query($sql);

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('song_relation_tag') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'song_id');
        $GLOBALS['db']->query($sql);

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('song_info') . ' ' .
               'WHERE ' . $GLOBALS['db']->in(array_keys($dropItem), 'song_id');
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
 * @param     boolean    $isExport    是否匯出使用
 *
 * @return    array
 */
function getListPage($isExport = false)
{
    $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : 'song_id';
    $filter['sort_order'] = isset($_REQUEST['sort_order']) ? trim($_REQUEST['sort_order']) : 'DESC';

    $operator = '';
    if (isset($_REQUEST['keyword'])
        && ($_REQUEST['keyword'] = trim($_REQUEST['keyword'])) != '') {

        $queryString = $GLOBALS['db']->likeQuote($_REQUEST['keyword']);
        $operator .= 'AND (si.zh_song_title LIKE "%' . $queryString . '%" ';
        $operator .= 'OR si.en_song_title_1 LIKE "%' . $queryString . '%" ';
        $operator .= 'OR si.en_song_title_2 LIKE "%' . $queryString . '%") ';
        $filter['keyword'] = stripslashes($_REQUEST['keyword']);
    }

    $fields = array(
        'write_file_no',
    );
    foreach ($fields as $key) {

        if (isset($_REQUEST[$key])
            && ($_REQUEST[$key] = trim($_REQUEST[$key])) != '') {

            $filter[$key] = $_REQUEST[$key];
            $operator .= ' AND ' . $GLOBALS['db']->in($filter[$key], 'si.' . $key, 'string');
            $filter[$key] = stripslashes($filter[$key]);
        }
    }

    if (isset($_REQUEST['recommend'])
        && ($_REQUEST['recommend'] = trim($_REQUEST['recommend'])) != '') {

        switch ($_REQUEST['recommend']) {
            case 'best':
                $operator .= ' AND si.is_best = 1 ';
                break;
        }
        $filter['recommend'] = stripslashes($_REQUEST['recommend']);
    }

    if (empty($isExport)) {

        /* 總數 */
        $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['db']->table('song_info') . ' AS si ' .
               'WHERE 1 ' . $operator;
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        $filter = pageAndSize($filter);
        unset($_SESSION[SHOP_SESS]['filter']);
        $_SESSION[SHOP_SESS]['filter'][SCRIPT_NAME] = $filter;
    }

    $itemArr = array();

    if (!empty($isExport) || $filter['record_count'] > 0) {

        /* 獲取數據 */
        $sql = 'SELECT si.*, (' .

               'SELECT  ' .
               'GROUP_CONCAT(CONCAT(bi.book_code, "#", brs.page_code) SEPARATOR "\n") ' .
               'FROM ' . $GLOBALS['db']->table('book_relation_song') . ' AS brs ' .
               'INNER JOIN ' . $GLOBALS['db']->table('book_info') . ' AS bi ' .
               'ON bi.book_id = brs.book_id ' .
               'WHERE brs.song_id = si.song_id ' .

               ') AS relation_data_code, (' .

               'SELECT GROUP_CONCAT(mt.tag_name) FROM ' . $GLOBALS['db']->table('tags') . ' AS mt ' .
               'INNER JOIN ' . $GLOBALS['db']->table('song_relation_tag') . ' AS rt ' .
               'ON rt.tag_id = mt.tag_id ' .
               'WHERE rt.song_id = si.song_id ' .

               ') AS tags ' .


               'FROM ' . $GLOBALS['db']->table('song_info') . ' AS si ' .
               'WHERE 1 ' . $operator . ' ' .
               'ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'];
        $res = !empty($isExport)
            ? $GLOBALS['db']->query($sql)
            : $GLOBALS['db']->selectLimit($sql, $filter['page_size'], $filter['start']);
        while ($row = $GLOBALS['db']->fetchAssoc($res)) {

            $row['edit_mode'] = 'U';

            $row['relation_book_code'] = $row['relation_page_code'] = '';
            if ($row['relation_data_code'] != '') {

                $relationDataCode = explode(PHP_EOL, $row['relation_data_code']);
                foreach ($relationDataCode as $val) {

                    if (preg_match('/(.*)#(\d+)$/', $val, $m)) {

                        $row['relation_book_code'] .= $m[1] . PHP_EOL;
                        $row['relation_page_code'] .= $m[2] . PHP_EOL;
                    }
                }

                $row['relation_book_code'] = trim($row['relation_book_code']);
                $row['relation_page_code'] = trim($row['relation_page_code']);
            }

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
