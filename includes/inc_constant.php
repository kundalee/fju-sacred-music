<?php

/**
 * 常量
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

/* 圖片處理相關常數 */
define('ERR_INVALID_IMAGE',             1); // 無法識別的圖片類型
define('ERR_NO_GD',                     2); // GD 模組未啟用
define('ERR_IMAGE_NOT_EXISTS',          3); // 圖片不存在
define('ERR_DIRECTORY_READONLY',        4); // 目錄不存在或不可寫
define('ERR_UPLOAD_FAILURE',            5); // 檔案上傳失敗
define('ERR_INVALID_PARAM',             6); // 參數為空
define('ERR_INVALID_IMAGE_TYPE',        7); // 不允許的圖片格式
define('ERR_INVALID_FILE_TYPE',         8); // 不允許的檔案格式
define('ERR_INVALID_THUMB_SIZE',        9); // 縮圖寬高未設定
define('ERR_UPLOAD_SERVER_SIZE',       10); // 超過伺服器上傳限制
define('ERR_UPLOAD_BROWSER_SIZE',      11); // 超過瀏覽器上傳限制
define('ERR_UPLOAD_PARTIAL',           12); // 檔案僅部分被上傳
define('ERR_UPLOAD_NO_FILE',           13); // 沒有上傳檔案
define('ERR_UPLOAD_NO_TMP_DIR',        14); // 寫入到暫存資料夾錯誤
define('ERR_UPLOAD_CANT_WRITE',        15); // 無法寫入硬碟
define('ERR_UPLOAD_EXTENSION',         16); // 擴充元件使檔案上傳停止
define('ERR_UPLOAD_UNKNOWN',           17); // 未知的上傳失敗

/* 驗證碼 */
define('CAPTCHA_REGISTER',              1); // 註冊時使用驗證碼
define('CAPTCHA_LOGIN',                 2); // 登錄時使用驗證碼
define('CAPTCHA_COMMENT',               4); // 留言時使用驗證碼
define('CAPTCHA_ADMIN',                 8); // 後台登錄時使用驗證碼
define('CAPTCHA_LOGIN_FAIL',           16); // 登錄失敗後顯示驗證碼

define('DB_ACTION_INSERT',              1);
define('DB_ACTION_UPDATE',              2);
define('DB_ACTION_DELETE',              3);

// 郵件訂閱狀態
define('ES_UNCONFIRMED',                0); // 未確認
define('ES_CONFIRMED',                  1); // 已確認
define('ES_UNSUBSCRIBE',                2); // 已退訂

// 分類顯示層級
define('CL_BULLETIN',                   1);
define('CL_QUESTION',                   1);
define('CL_CASE',                       1);
define('CL_COURSE',                     1);
