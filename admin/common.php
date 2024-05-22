<?php
/**
 * 共用處理程序
 * ========================================================
 *
 *
 * ========================================================
 * Date: 2015-12-24 11:55
 */

define('IN_DUS', true); // 程式保護

// 載入函式檔
require(dirname(__FILE__) . '/includes/init.php');

// 初始化執行動作
$_REQUEST['action'] = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

switch ($_REQUEST['action']) {

//-- 取得地區
case 'regions':

    // 初始化參數
    $ignore = isset($_GET['ignore']) ? (boolean)$_GET['ignore'] : null;
    $type = !empty($_GET['type']) ? (int)$_GET['type'] : 0;
    $parentId = !empty($_GET['parent_id']) ? $_GET['parent_id'] : null;
    $refId = !empty($_GET['ref_id']) ? (int)$_GET['ref_id'] : 0;
    $target = !empty($_GET['target']) ? stripslashes(trim($_GET['target'])) : '';

    if (!is_array($parentId)) {

        $parentId = (int)$parentId;
    }

    $jsonRes = array(
        'type' => $type,
        'target' => $target,
        'parent_id' => $parentId,
        'regions' => getRegions(
            $type,
            $parentId,
            $refId,
            array(
                'lang_id' => $_CFG['default_lang_id'],
                'ignore' => $ignore,
                'grp_by_type' => true
            )
        )
    );

    die(json_encode($jsonRes));
    break;

case 'upload':

    require_once(BASE_PATH . 'PluploadHandler.php');
    $file = PluploadHandler::handle(
        array(
            'target_dir' => TEMP_PATH . 'upload',
            'allow_extensions' => strtolower($_CFG['upload_allow_file_ext'])
        )
    );

    if (!$file) {

        makeJsonError(PluploadHandler::get_error_message());

    } elseif (isset($file['path'])) {

        $handle = isset($_REQUEST['handle']) ? trim($_REQUEST['handle']) : '';

        $newFileUrl = $oldFileUrl = $file['path'];

        if (isset($_REQUEST['old_name'])) {

            $newFileDir = ($handle == 'batch' ? DATA_PATH . 'files' : dirname($oldFileUrl)) . '/';

            preg_match(
                '%^(?<dirname>.*?)[\\\\/]*(?<basename>(?<filename>[^/\\\\]*?)(?:\.(?<extension>[^\.\\\\/]+?)|))[\\\\/\.]*$%im',
                $_REQUEST['old_name'],
                $pathinfo
            );

            $i = 0;
            do {

                $newFileName = $pathinfo['filename'] . ($i > 0 ? '(' . $i . ')' : '') . '.' . $pathinfo['extension'];
                $newFileUrl = $newFileDir . $newFileName;
                $i++;

            } while (!moveFile($oldFileUrl, $newFileUrl, false));
        }

        $data['file_url'] = str_replace(ROOT_PATH, '', $newFileUrl);

        makeJsonResult('', '', $data);
    }
    break;

case 'save_picture':

    $fileDataUri = isset($_POST['file_data_uri']) ? trim($_POST['file_data_uri']) : '';
    if (!empty($fileDataUri) && chkDataUriImg($fileDataUri)) {

        // 取得 Data URI scheme 資訊
        $dataUriInfo = getDataUriInfo($fileDataUri);

        $imageContents = base64_decode($dataUriInfo['data']);

        switch ($dataUriInfo['mime_type']) {
            case 'image/gif':
                $ext = 'gif';
                break;
            case 'image/pjpeg':
            case 'image/jpeg':
                $ext = 'jpg';
                break;
            case 'image/x-png':
            case 'image/png':
                $ext = 'png';
                break;
        }

        $data['file_url'] = 'temp/upload/' . uniqid() . '.' . $ext;
        file_put_contents(ROOT_PATH . $data['file_url'], $imageContents);

        makeJsonResult('', '', $data);

    } else {


    }
    break;
}
exit;
