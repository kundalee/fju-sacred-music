<?php

/**
 * 找回管理員密碼
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

define('IN_DUS', true);
define('SCRIPT_NAME', basename(__FILE__, '.php'));

require(dirname(__FILE__) . '/includes/init.php');

if (empty($_SERVER['REQUEST_METHOD'])) {

    $_SERVER['REQUEST_METHOD'] = 'GET';

} else {

    $_SERVER['REQUEST_METHOD'] = trim($_SERVER['REQUEST_METHOD']);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    if (!empty($_GET['action']) && $_GET['action'] == 'reset_pwd') {

        $code = !empty($_GET['code']) ? trim($_GET['code']) : '';
        $uid = !empty($_GET['uid']) ? intval($_GET['uid']) : 0;

        if ($uid == 0 || empty($code)) {

            $dus->header("Location: privilege.php?action=login\n");
            exit;
        }

        $sql = 'SELECT admin_id, password, salt ' .
               'FROM ' . $db->table('admin_user') . ' ' .
               'WHERE admin_id = ' . $uid ;
        $row = $db->getRow($sql);
        if (md5($row['admin_id'] . $row['password'] . $row['salt']) <> $code) {

            sysMsg('您執行了一個不合法的請求，請返回！', 0,
                array(array('text' => '重新登入', 'href' => 'privilege.php?action=login')));
        }

        assignTemplate();
        $smarty->display('passport.html');
    }

} else {

    if (!empty($_POST['action']) && $_POST['action'] == 'get_pwd') {

        $jsonRes = array('error' => 0, 'message' => '');

        /* 初始化管理者帳號 */
        $eMail = isset($_POST['email']) ? trim($_POST['email']) : '';

        // 管理者帳號及郵件地址是否匹配
        $sql = 'SELECT admin_id, admin_name, email, password, salt, full_name ' .
               'FROM ' . $db->table('admin_user') . ' ' .
               'WHERE email = "' . $eMail . '"';
        if ($row = $db->getRow($sql)) {

            $code = md5($row['admin_id'] . $row['password'] . $row['salt']);

            /* 設置重置郵件模板所需要的內容信息 */
            $website = $dus->url();

            $resetPassCode = $website . ADMIN_DIR . '/' . SCRIPT_NAME . '.php?' . http_build_query(array('action' => 'reset_pwd', 'uid' => $row['admin_id'], 'code' => $code), '&amp;');

            $smarty->assign('tmp', $row);
            $smarty->assign('reset_pass_code', $resetPassCode);
            $smarty->assign('website', $website);
            $smarty->assign('shop_name', $_CFG['shop_name']);

            $tpl = getMailTemplate('send_admin_password');
            $needError = $smarty->error_reporting;
            $smarty->error_reporting = E_ALL ^ E_NOTICE;
            $templateContent = $smarty->fetch('string:' . $tpl['template_content']);
            $smarty->error_reporting = $needError;
            $templateContent = $dus->urlDecode($templateContent);

            /* 發送確認重置密碼的確認郵件 */
            if (sendMail(sprintf('%s <%s>', ($row['full_name'] != '' ? $row['full_name'] : $row['admin_name']), $eMail), $tpl['template_subject'], $templateContent, $tpl['is_html'])) {

                $jsonRes['message'] = '重置密碼的郵件已經發送到您的電子郵件信箱';

            } else {

                $jsonRes['error'] = 1;
                $jsonRes['message'] = '發送郵件出錯，請與最高管理者聯絡！';
            }

        } else {

            // 管理者帳號與郵件地址不匹配
            $jsonRes['error'] = 1;
            $jsonRes['message'] = '您填寫的管理者電子郵件地址不相符，請重新輸入！';
        }
        die(json_encode($jsonRes));

    } elseif (!empty($_POST['action']) && $_POST['action'] == 'reset_pwd') {

        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';

        if (empty($newPassword) || empty($code) || $uid == 0) {

            $dus->header("Location: privilege.php?action=login\n");
            exit;
        }

        if (strlen($newPassword) < 6 || strlen($newPassword) > 20) {

            sysMsg('登入密碼不能少於 6 個字母,最多 20 個字母');
        }

        /* 比較新密碼和確認密碼是否相同 */
        if ($newPassword <> $confirmPassword) {

            sysMsg('兩次密碼必須一致哦');
        }

        // 管理者帳號和郵件地址是否匹配
        $sql = 'SELECT admin_id, admin_name, password, salt ' .
               'FROM ' . $db->table('admin_user') . ' ' .
               'WHERE admin_id = ' . $uid;
        $row = $db->getRow($sql); // 會員記錄
        if ($row && (!empty($code) && md5($row['admin_id'] . $row['password'] . $row['salt']) == $code)) {

            $salt = substr(uniqid(rand()), -6);
            $data['password'] = md5(base64_encode(md5($newPassword) . $salt));
            $data['salt'] = $salt;

            // 更新會員密碼
            $sql = $db->buildSQL('U', $db->table('admin_user'), $data, "admin_id = '" . $uid . "'");
            $db->query($sql);
    ;
            sysMsg('新密碼已設定成功。', 1,
                array(array('text' => '重新登入', 'href' => 'privilege.php?action=login')));

        } else {

            sysMsg('系統參數錯誤，請返回重新申請！', 1,
                array(array('text' => '忘記密碼', 'href' => 'privilege.php?action=login')));
        }
    }
}
