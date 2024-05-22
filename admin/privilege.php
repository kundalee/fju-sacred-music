<?php

/**
 * 管理員信息以及權限管理程序
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));
define('SCRIPT_TITLE', '網站管理員');

require(dirname(__FILE__) . '/includes/init.php');

/* 初始化數據交換對像 */
$exchange = new exchange($db->table('admin_user'), $db, 'admin_id', 'admin_name');

/* 初始化執行動作 */
$_REQUEST['action'] = $_REQUEST['action'] != '' ? trim($_REQUEST['action']) : 'login';

//-- 退出登錄
if ($_REQUEST['action'] == 'logout') {

    /* 清除cookie */
    setcookie('DUSCP[admin_id]', '', 1);
    setcookie('DUSCP[admin_pass]', '', 1);
    $_SESSION[SHOP_SESS] = array();
    unset($_SESSION[SHOP_SESS]);

    $_REQUEST['action'] = 'login';
}

switch ($_REQUEST['action']) {

//-- 登錄界面
case 'login':

    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    $smarty->assign('random', rand());

    /* 模板賦值 */
    $position = assignUrHere();
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 當前位置

    assignTemplate();
    $smarty->display('passport.html');
    break;

case 'authorize':

    $links[] = array('text' => '管理者登錄', 'href' => '?action=login');
    if (!empty($_GET['hash']) && $hash = $dus->compilePassword(trim($_GET['hash']))) {

        list($adminId, $userAgentt, $sourceIP) = preg_split('/-/', $hash);

        $authorizeCode = array(
            $userAgentt . 'cfcd208495d565ef66e7dff9f98764da' . $adminId . $sourceIP,
            $userAgentt . 'c4ca4238a0b923820dcc509a6f75849b' . $adminId . $sourceIP
        );

        // 註冊開通
        $sql = 'SELECT * FROM ' . $db->table('admin_authorize') . ' ' .
               'WHERE ' . $db->in($authorizeCode, 'auth_code');
        if ($row = $db->getRow($sql)) {

            // 更新驗證狀態
            $db->query(
                $db->buildSQL(
                    'U',
                    $db->table('admin_authorize'),
                    array(
                        'auth_code' => $authorizeCode[1]
                    ),
                    'auth_code = "' . $authorizeCode[0] . '"'
                )
            );

            sysMsg('您的電腦操作環境資訊驗證成功', 0, $links);
        }
    }

    sysMsg('驗證失敗，請確認你的驗證連結是否正確', 1, $links);
    break;

//-- 驗證登錄處理
case 'signin':

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $refUrl = isset($_POST['ref']) ? urldecode(base64_decode(trim($_POST['ref']))) : '';

    include_once(BASE_PATH . 'cls_captcha.php');
    /* 檢查驗證碼是否正確 */
    $validator = new captcha();
    if (!empty($_POST['captcha']) && !$validator->checkWord($_POST['captcha'])) {

        $links[] = array(
            'text' => '管理者登錄',
            'href' => '?action=login' . ($refUrl ? '&ref=' . base64_encode(urlencode($refUrl)) : '')
        );
        sysMsg('您輸入的驗證碼不正確。', 1, $links);
    }

    $sql = 'SELECT au.admin_id, au.admin_name, au.avatar_url, au.email, au.password, au.salt, ' .
           'au.last_login, au.enabled, au.action_list, au.personal_cfg ' .
           'FROM ' . $db->table('admin_user') . ' AS au ' .
           'WHERE au.admin_name = "' . $username . '"';
    $row = $db->getRow($sql);

    /* 檢查密碼是否正確 */
    if ($row['password'] == md5(base64_encode(md5($password) . $row['salt']))) {

        $links[] = array('text' => '管理者登錄', 'href' => '?action=login');

        if (empty($row['enabled'])) {

            sysMsg('很抱歉，該帳號遭停用!', 0, $links, 0);
        }

        // adminAuthOperate($row['admin_id'], $row['admin_name'], $row['email']);

        $sql = 'SELECT ar.action_list ' .
               'FROM ' . $db->table('admin_relation_role') . ' AS rr ' .
               'INNER JOIN ' . $db->table('admin_role') . ' AS ar ' .
               'ON rr.role_id = ar.role_id ' .
               'WHERE rr.admin_id = ' . $row['admin_id'];
        $roleAction = $db->getCol($sql);

        $operatePriv = array($row['action_list'], implode(',', $roleAction));
        $operatePriv = implode(',', $operatePriv);
        $operatePriv = explode(',', $operatePriv);
        $operatePriv = array_diff(array_unique($operatePriv), array(''));
        $operatePriv = implode(',', $operatePriv);

        // 登錄成功
        setAdminSession(
            $row['admin_id'],
            $row['admin_name'],
            $row['avatar_url'],
            $operatePriv,
            $row['last_login']
        );

        // 更新最後登錄時間和IP
        $db->query(
            $db->buildSQL(
                'U',
                $db->table('admin_user'),
                array(
                    'last_login' => $_SERVER['REQUEST_TIME'],
                    'last_ip' => realIP()
                ),
                'admin_id = ' . $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')]
            )
        );

        if (isset($_POST['rememberme'])) {

            $time = strtotime('+1 year', $_SERVER['REQUEST_TIME']);
            setcookie('DUSCP[admin_id]', $row['admin_id'], $time);
            setcookie('DUSCP[admin_pass]', md5($row['password']), $time);
        }

        /* 記錄日誌 */
        adminLog('', '管理者', '登錄');

        autoExecProcess(); // 啟動自動執行處理程序

        //$dus->header('Location: ' . ($refUrl ? $refUrl : './'));
        $dus->header('Location: ./');
        exit;

    } else {

        $links[] = array(
            'text' => '返回上一頁',
            'href' => '?action=login' . ($refUrl ? '&ref=' . base64_encode(urlencode($refUrl)) : '')
        );
        sysMsg('您輸入的帳號資料不正確。', 1, $links);
    }
    break;

//-- 管理員列表頁面
case 'list':
case 'query':

    /* 檢查權限 */
    adminPriv('admin_manage');

    $siblings = $childrens = array();
    $adminParent = $_SESSION[SHOP_SESS][sha1('☠ admin_parent ☠')];
    $layerAdmin = adminLayerList($adminParent, 0, false);

    $adminTree = arrayToTree($layerAdmin, 'admin_id');
    if (empty($adminParent)) {

        $childrens = $adminTree;

    } else {

        $adminTree = array_shift($adminTree);
        if (isset($adminTree['childrens'])) {

            $childrens = $adminTree['childrens'];
        }
    }

    foreach ($childrens as $key => $row) {

        // 非管理設定者只能瀏覽本身建立的管理者
        if (
            false == adminPriv('sys', false) &&
            !in_array($row['admin_id'], $_SESSION[SHOP_SESS][sha1('☠ admin_layer ☠')])
        ) {

            continue;
        }

        if (!isset($row['childrens'])) {

            $row['childrens'] = array();
        }
        $siblings[$row['admin_id']] = array(
            'admin_id' => $row['admin_id'],
            'admin_name' => $row['admin_name'],
            'email' => $row['email'],
            'full_name' => $row['full_name'],
            'html_tree' => buildHtmlTree($row['childrens']),
            'remove' => $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] != $row['admin_id']
        );
    }

    $smarty->assign('html_siblings_tree', $siblings);

    $actionLink[] = array(
        'text' => '新增',
        'icon' => 'fas fa-user-plus fa-fw',
        'sref' => buildUIRef('?action=add')
    );
    $smarty->assign('action_link', $actionLink);

    $extendArr[] = array('text' => '權限管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    /* 顯示頁面 */
    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_list.html');
    break;

case 'edit_node':

    /* 檢查權限 */
    checkAuthzJson('admin_manage');

    $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

    if (isset($_POST['serialize'])) {

        $readble = parseTreeLayer($_POST['serialize'], $parentId);
        foreach ($readble as $row) {

            $db->query('UPDATE ' . $db->table('admin_user') . ' ' .
                       'SET parent_id = ' . $row['parent_id'] . ' ' .
                       'WHERE admin_id = ' . $row['id']);
        }
    }
    break;

//-- 編輯管理員信息
case 'add':
case 'edit':
case 'modif':

    if ($_REQUEST['action'] == 'add') {

        $data = array(
            'admin_id' => 0,
            'admin_name' => '',
            'email' => '',
            'enabled' => 1,
            'full_name' => '',
            'avatar_url' => '',
            'action_list' => ''
        );

        $smarty->assign('form_action', 'insert');
        $smarty->assign('action', 'add');

        $selfOldPriv = array();

    } else {

        $isEdit = $_REQUEST['action'] == 'edit';
        $adminId = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($isEdit && $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')] != $adminId) {

            /* 查看是否有權限編輯其他管理員的信息 */
            adminPriv('admin_manage');
            $smarty->assign('form_action', 'update');

        } else {

            $adminId = $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
            $smarty->assign('form_action', 'update_self');

            $_REQUEST['action'] = 'modif';
        }

        /* 獲取管理員權限數據 */
        $sql = 'SELECT * FROM ' . $db->table('admin_user') . ' ' .
               'WHERE admin_id = ' . $adminId;
        if (!$data = $db->getRow($sql)) {

            sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。', 1,
                array(array('text' => '返回列表', 'href' => basename(__FILE__) . '?action=list')));
        }

        $data['personal_cfg'] = unserialize($data['personal_cfg']);

        if ($isEdit) {

            $sql = 'SELECT role_id FROM ' . $db->table('admin_relation_role') . ' ' .
                   'WHERE admin_id = ' . $adminId;
            $data['play_role'] = $db->getCol($sql);
        }

        $data['add_time'] = date($_CFG['time_format'], $data['add_time']);
        $data['last_login'] = $data['last_login'] ? date($_CFG['time_format'], $data['last_login']) : 'N/A';

        $selfOldPriv = explode(',', $data['action_list']);
    }

    /* 獲取權限的分組數據 */
    $privArr = array();
    $selectRole = array();

    if ($_REQUEST['action'] != 'modif' && adminPriv('allot_priv', false) &&
        !(in_array('all', $selfOldPriv) || in_array('sys', $selfOldPriv))) {

        /* 獲得該操作管理者的權限 */
        $operatePriv = explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);

        $sql = 'SELECT * FROM ' . $db->table('admin_action') . ' ' .
               'WHERE parent_id = 0';
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $privArr[$row['action_id']] = $row;
        }

        /* 按權限組查詢底級的權限名稱 */
        $sql = 'SELECT * FROM ' . $db->table('admin_action') . ' ' .
               'WHERE parent_id ' . $db->in(array_keys($privArr)) . ' ';
        if (!(in_array('all', $operatePriv) || in_array('sys', $operatePriv))) {

            /* 按權限組查詢底級的權限名稱 */
            $sql .= 'AND action_code ' . $db->in($operatePriv);
        }
        $res = $db->query($sql);
        while ($row = $db->fetchAssoc($res)) {

            $privArr[$row['parent_id']]['priv'][$row['action_code']] = $row;
        }

        // 將同一組的權限使用 "," 連接起來，供JS全選
        foreach ($privArr as $id => $group) {

            if (!isset($group['priv'])) {

                unset($privArr[$id]);

            } else {

                $actionList = explode(',', $data['action_list']);
                $privArr[$id]['priv_list'] = implode(',', @array_keys($group['priv']));
                foreach ($group['priv'] as $key => $val) {

                    $privArr[$id]['priv'][$key]['cando'] = in_array($val['action_code'], $actionList);
                }
            }
        }

        $selectRole = array();
        if (adminPriv('admin_role_group', false)) {

            $selectRole = getRoleList();
        }

        $actionLink[] = array(
            'text' => '返回',
            'icon' => 'fas fa-share fa-fw',
            'sref' => buildUIRef('?action=list'),
            'style' => 'btn-pink'
        );
        $smarty->assign('action_link', $actionLink);
    }

    $smarty->assign('data', $data);
    $smarty->assign('priv_arr', $privArr);
    $smarty->assign('select_role', $selectRole);

    $extendArr[] = array('text' => '權限管理');
    $position = assignUrHere(SCRIPT_TITLE, $extendArr);
    $smarty->assign('page_title', $position['title']); // 頁面標題
    $smarty->assign('ur_here', $position['ur_here']);  // 目前位置

    assignTemplate();
    assignQueryInfo();
    $smarty->display(SCRIPT_NAME . '_info.html');
    break;

//-- 添加管理員的處理
case 'insert':

    /* 檢查權限 */
    adminPriv('admin_manage');

    /* 判斷管理員是否已經存在 */
    if (!empty($_POST['username'])) {

        $isOnly = $exchange->isOnly('admin_name', stripslashes($_POST['username']));
        if (!$isOnly) {

            sysMsg(sprintf('該 %s 管理者已經存在!', stripslashes($_POST['username'])), 1);
        }
    }

    /* Email地址是否有重複 */
    if (!empty($_POST['email'])) {

        $isOnly = $exchange->isOnly('email', stripslashes($_POST['email']));
        if (!$isOnly) {

            sysMsg(sprintf('該 %s 電子郵件已經有人使用。', stripslashes($_POST['email'])), 1);
        }
    }

    $data['admin_name'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
    $data['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';
    $data['enabled'] = !empty($_POST['enabled']) ? 1 : 0;

    $data['full_name'] = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';

    $pictureDataUri = isset($_POST['avatar_base64']) ? trim($_POST['avatar_base64']) : '';
    if (!empty($pictureDataUri) && chkDataUriImg($pictureDataUri)) {

        // 取得 Data URI scheme 資訊
        $dataUriInfo = getDataUriInfo($pictureDataUri);

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

        $data['avatar_url'] = 'data/avatars/' . uniqid() . '.' . $ext;
        file_put_contents(ROOT_PATH . $data['avatar_url'], $imageContents);
    }

    $allotPriv = adminPriv('allot_priv', false);

    if ($allotPriv) {

        $data['action_list'] = !empty($_POST['action_code']) && is_array($_POST['action_code'])
            ? implode(',', $_POST['action_code'])
            : '';
    }

    /* 獲取添加日期及密碼 */
    $salt = substr(uniqid(rand()), -6);
    $data['password'] = md5(base64_encode(md5($data['password']) . $salt));
    $data['salt'] = $salt;

    $data['parent_id'] = $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    $data['add_time'] = $_SERVER['REQUEST_TIME'];

    $db->query(
        $db->buildSQL(
            'I',
            $db->table('admin_user'),
            $data
        )
    );

    /* 轉入權限分配列表 */
    $adminId = $db->insertId();

    /* 處理管理者的角色 */
    if ($allotPriv && !empty($_POST['play_role']) && is_array($_POST['play_role'])) {

        handlePlayRole($adminId, array_unique($_POST['play_role']));
    }

    /* 記錄日誌 */
    adminLog($data['admin_name'], '新增', SCRIPT_TITLE);

    $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
    $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $adminId));
    $links[0] = array('text' => '繼續新增', 'sref' => buildUIRef('?action=add'));
    sysMsg(sprintf('新增 %s 管理員操作成功', stripslashes($data['admin_name'])), 0, $links);
    break;

//-- 更新管理員信息
case 'update':
case 'update_self':

    /* 變量初始化 */
    $data['admin_name'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $data['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';

    $data['full_name'] = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';

    if ($_REQUEST['action'] == 'update') {

        /* 檢查權限 */
        adminPriv('admin_manage');
        $adminId = !empty($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;

    } else {

        $adminId = $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')];
    }

    $sql = 'SELECT * FROM ' . $db->table('admin_user') . ' ' .
           'WHERE admin_id = ' . $adminId;
    $oldData = $db->getRow($sql);

    /* 判斷管理員帳號是否已經存在 */
    if ($oldData['admin_name'] != $data['admin_name']) {

        $isOnly = $exchange->num('admin_name', stripslashes($data['admin_name']), $adminId);
        if ($isOnly == 1) {

            sysMsg(sprintf('該 %s 管理者已經存在!', stripslashes($data['admin_name'])), 1);
        }

        $oldData['admin_name'] = $data['admin_name'];
    }

    /* 判斷電子信箱是否有重複 */
    if ($oldData['email'] != $data['email'] && $data['email'] != '') {

        $isOnly = $exchange->num('email', stripslashes($data['email']), $adminId);
        if ($isOnly == 1) {

            sysMsg(sprintf('該 %s 電子郵件已經有人使用。', stripslashes($data['email'])), 1);
        }
    }

    $pictureDataUri = isset($_POST['avatar_base64']) ? trim($_POST['avatar_base64']) : '';
    if (!empty($pictureDataUri) && chkDataUriImg($pictureDataUri)) {

        // 取得 Data URI scheme 資訊
        $dataUriInfo = getDataUriInfo($pictureDataUri);

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

        $data['avatar_url'] = 'data/avatars/' . uniqid() . '.' . $ext;
        file_put_contents(ROOT_PATH . $data['avatar_url'], $imageContents);
        dropFile($oldData['avatar_url']);
        $oldData['avatar_url'] = $data['avatar_url'];
    }

    // 如果要修改密碼
    $pwdModified = false;
    $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $pwdConfirm = isset($_POST['pwd_confirm']) ? trim($_POST['pwd_confirm']) : '';

    if ($newPassword != '') {

        if ($_REQUEST['action'] == 'update_self') {

            $oldPassword = isset($_POST['old_password'])
                ? md5(base64_encode(md5($_POST['old_password']) . $oldData['salt']))
                : '';
            if ($oldData['password'] <> $oldPassword) {

                $links[] = array(
                    'text' => '返回上一頁',
                    'href' => 'javascript:history.back(-1)'
                );
                sysMsg('輸入的舊密碼錯誤!', 0, $links);
            }
        }

        /* 比較新密碼和確認密碼是否相同 */
        if ($newPassword <> $pwdConfirm) {

            $links[] = array(
                'text' => '返回上一頁',
                'href' => 'javascript:history.back(-1)'
            );
            sysMsg('兩次輸入的密碼不一致!', 0, $links);
        }

        $salt = substr(uniqid(rand()), -6);
        $data['password'] = md5(base64_encode(md5($newPassword) . $salt));
        $data['salt'] = $salt;
        $pwdModified = true;
    }

    if ($_REQUEST['action'] == 'update') {

        $data['enabled'] = !empty($_POST['enabled']) ? 1 : 0;

        $selfOldPriv = @explode(',', $oldData['action_list']);

        /* 處理管理者的角色 */
        if (adminPriv('allot_priv', false) &&
            !(in_array('all', $selfOldPriv) || in_array('sys', $selfOldPriv))) {

            $data['action_list'] = !empty($_POST['action_code']) && is_array($_POST['action_code'])
                ? implode(',', $_POST['action_code'])
                : '';

            $playRole = !empty($_POST['play_role']) && is_array($_POST['play_role'])
                ? array_unique($_POST['play_role'])
                : array(0);
            handlePlayRole($adminId, $playRole);
        }
    }

    // 更新管理員信息
    $db->query(
        $db->buildSQL(
            'U',
            $db->table('admin_user'),
            $data,
            'admin_id = ' . $adminId
        )
    );

    /* 記錄日誌 */
    adminLog($data['admin_name'], '編輯', SCRIPT_TITLE);

    /* 提示信息 */
    $links = array();
    if ($_REQUEST['action'] == 'update_self') {

        /* 如果修改了密碼，則需要將 session 中該管理員的數據清空 */
        if ($pwdModified) {

            unset($_SESSION[SHOP_SESS]);
            $msg = '您已經成功的修改了密碼，因此您必須重新登入!';

        } else {

            $msg = '您已經成功的修改了個人帳號資料!';

            $_SESSION[SHOP_SESS][sha1('☠ admin_name ☠')] = $oldData['admin_name'];
            $_SESSION[SHOP_SESS][sha1('☠ avatar_url ☠')] = $oldData['avatar_url'];
            $_SESSION[SHOP_SESS][sha1('HANDLE')] = md5(
                implode(
                    ' (^ω^) ',
                    array(
                        $_SESSION[SHOP_SESS][sha1('☠ admin_id ☠')],
                        $_SESSION[SHOP_SESS][sha1('☠ admin_name ☠')],
                        $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')],
                        $_SESSION[SHOP_SESS][sha1('☠ source_ip ☠')]
                    )
                )
            );
        }

        $links[0] = array('text' => '編輯個人資料', 'sref' => buildUIRef('?action=modif'));

        sysMsg($msg, 0, $links);

    } else {

        $links[2] = array('text' => '返回列表', 'sref' => buildUIRef('?action=list', true));
        $links[1] = array('text' => '繼續編輯', 'sref' => buildUIRef('?action=edit&id=' . $adminId));

        sysMsg(sprintf('管理員 %s 成功編輯', stripslashes($data['admin_name'])), 0, $links);
    }
    break;

//-- 刪除一個管理員
case 'remove':

    adminPriv('admin_drop');

    if (!empty($_REQUEST['id'])) {

        $id = intval($_REQUEST['id']);

    } else {

        sysMsg('很抱歉，頁面目前無法正常顯示，可能是因為網頁不存在或網址錯誤。');
    }

    /* 當前分類下是否有子分類 */
    $sql = 'SELECT COUNT(*) FROM ' . $db->table('admin_user') . ' WHERE parent_id = ' . $id;
    $uCount = $db->getOne($sql);

    /* 獲得管理員帳號 */
    $username = $exchange->getName($id);

    /* 如果不存在下級子管理者，則刪除之 */
    if ($uCount == 0) {

        /* 刪除管理員 */
        $exchange->drop($id);

        /* 記錄日誌 */
        adminLog(addslashes($username), '刪除', SCRIPT_TITLE);

    } else {

        /* 同級別下不能有重複的分類名稱 */
        $links[] = array('text' => '返回上一頁', 'href' => 'javascript:history.back(-1);');
        sysMsg('很抱歉『 ' . $username . ' 』不是最底層管理者，不能刪除!', 0, $links);
    }

    parse_str($_SERVER['QUERY_STRING'], $queryString);
    $queryString['action'] = 'query';

    $dus->header('Location: ?' . http_build_query($queryString));
    exit;
    break;

//-- 上傳頭像
case 'avatar_upload':

    $jsonRes = array();

    require_once(BASE_PATH . 'PluploadHandler.php');
    $file = PluploadHandler::handle(
        array(
            'target_dir' => TEMP_PATH . 'upload',
            'allow_extensions' => 'jpg,jpeg,png,gif'
        )
    );

    if (!$file) {

        makeJsonError(PluploadHandler::get_error_message());

    } elseif (isset($file['path'])) {

        $dataUri = convertFileToDataUri($file['path']);
        dropFile($file['path']);

        makeJsonResult('', '', array('data_uri' => $dataUri));
    }
    break;
}
exit;

/*------------------------------------------------------ */
//-- PRIVATE FUNCTIONS
/*------------------------------------------------------ */

/**
 * 取得角色列表
 *
 * @return    array
 */
function getRoleList()
{
    $itemArr = array();

    $operatePriv = explode(',', $_SESSION[SHOP_SESS][sha1('☠ action_list ☠')]);
    $sql = 'SELECT role_id, role_name ' .
           'FROM ' . $GLOBALS['db']->table('admin_role') . ' ';
    if (!(in_array('all', $operatePriv) || in_array('sys', $operatePriv))) {

        $sql .= 'WHERE role_id <> 0';
    }

    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $itemArr[$row['role_id']] = $row['role_name'];
    }

    return $itemArr;
}

/**
 * 保存管理者的角色
 *
 * @param     integer    $adminId     管理者編號
 * @param     array      $roleList    角色編號陣列
 *
 * @return    void
 */
function handlePlayRole($adminId, $roleList)
{
    /* 查詢現有的角色 */
    $sql = 'SELECT role_id FROM ' . $GLOBALS['db']->table('admin_relation_role') . ' ' .
           'WHERE admin_id = ' . $adminId;
    $existList = $GLOBALS['db']->getCol($sql);

    /* 刪除不再的角色 */
    $deleteList = array_diff($existList, $roleList);
    if ($deleteList) {

        $sql = 'DELETE FROM ' . $GLOBALS['db']->table('admin_relation_role') . ' ' .
               'WHERE admin_id = ' . $adminId . ' ' .
               'AND role_id ' . $GLOBALS['db']->in($deleteList);
        $GLOBALS['db']->query($sql);
    }

    /* 新加的角色 */
    $addList = array_diff($roleList, $existList, array(0));
    foreach ($addList as $roleId) {

        // 插入記錄
        $GLOBALS['db']->query(
            $GLOBALS['db']->buildSQL(
                'I',
                $GLOBALS['db']->table('admin_relation_role'),
                array(
                    'admin_id' => $adminId,
                    'role_id' => $roleId
                )
            )
        );
    }
}

/**
 * 產生 JQuery 套件 (nestable) 的樹狀結構 HTML
 *
 * @param     array     $item           階層的樹狀陣列
 * @param     string    $handleClass    拖曳 class 名稱
 *
 * @return    string
 */
function buildHtmlTree(array $item, $handleClass = 'dd-handle')
{
    $html = '';

    if (!empty($item)) {

        $html .= '<ol class="dd-list">';

        foreach ($item as $row) {

            $html .= '<li class="dd-item" data-id="' . $row['admin_id'] . '">'
                   . '<div class="dd-handle">Drag</div>'
                   . '<div class="widget-tree-comments-item">'
                   . '  <img src="' . ($row['avatar_url'] ? (BASE_URL . $row['avatar_url']) : 'assets/images/profile-pic.png') . '" alt="' . $row['admin_name'] . '" class="widget-tree-comments-avatar">'
                   . '  <div class="widget-tree-comments-header">'
                   . '    <a href="#">' . $row['admin_name'] . '</a><span>&nbsp;&nbsp;' . $row['full_name'] . '</span>'
                   . '  </div>'
                   . '  <div class="widget-tree-comments-footer">'
                   . '    <a class="blue" href="#" ui-sref="' . SCRIPT_NAME . '({\'action\':\'edit\',\'id\':' . $row['admin_id'] . '})' . '">'
                   . '      <i class="fas fa-pencil-alt fa-fw"></i> 編輯'
                   . '    </a>'
                   . '    <a class="red" href="#" data-username="' . $row['admin_name'] . '" data-remove-href="' . basename(__FILE__) . '?action=remove&id=' . $row['admin_id'] . '" data-item-handler="remove">'
                   . '      <i class="fas fa-user-times fa-fw"></i> 刪除'
                   . '    </a>'
                   . '  </div>'
                   . '</div>';
            if (isset($row['childrens'])) {

                $html .= buildHtmlTree($row['childrens'], $handleClass);
            }
            $html .= '</li>';
        }

        $html .= '</ol>';
    }

    return $html;
}

/**
 * 解析管理者樹狀層級
 *
 * @param     array      $layer       層級陣列
 * @param     integer    $parentId    上層 ID
 *
 * @return    array
 */
function parseTreeLayer(array $layer = array(), $parentId = 0)
{
    $res = array();
    if (!empty($layer)) {

        foreach ($layer as $row) {

            $subLayer = array();
            if (isset($row['children'])) {

                $subLayer = parseTreeLayer($row['children'], $row['id']);
            }
            $res[] = array('id' => $row['id'], 'parent_id' => $parentId);
            $res = array_merge($res, $subLayer);
        }
    }
    return $res;
}
