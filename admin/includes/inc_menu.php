<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 管理中心菜單數組
 * ============================================================
 *
 * ============================================================
 * $Author: kun_wei $
 */

$modules = array(
    'book' => array(
        'label' => '歌本管理',
        'icon' => 'fa-fw far fa-clipboard',
        'code' => 'book',
        'private' => 'book_manage',
        'children' => array()
    ),
    'song' => array(
        'label' => '歌曲管理',
        'icon' => 'fa-fw far fa-clipboard',
        'code' => 'song',
        'private' => 'song_manage',
        'children' => array()
    ),

    'focus' => array(
        'label' => '重點歌曲',
        'icon' => 'fa-fw far fa-clipboard',
        'code' => 'focus',
        'private' => 'focus_manage',
        'children' => array()
    ),

    'minstrel' => array(
        'label' => '創作者管理',
        'icon' => 'fa-fw far fa-clipboard',
        'code' => 'minstrel',
        'private' => 'minstrel_manage',
        'children' => array()
    ),
    'tags' => array(
        'label' => '標籤管理',
        'icon' => 'fa-fw far fa-clipboard',
        'code' => 'tags',
        'private' => 'tag_manage',
        'children' => array()
    ),
    'info' => array(
        'label' => '日誌管理',
        'icon' => 'fa-fw far fa-clipboard',
        'children' => array(
            array(
                'label' => '聖樂日誌',
                'code' => 'bulletin',
                'private' => 'bulletin_manage'
            ),
            array(
                'label' => '日誌分類',
                'code' => 'bulletin_cat',
                'private' => 'bulletin_cat_manage'
            )
        )
    ),

    'manage' => array(
        'label' => '權限管理',
        'icon' => 'fa-fw fas fa-user-secret',
        'children' => array(
            array(
                'label' => '操作紀錄',
                'code' => 'admin_logs',
                'private' => 'logs_manage'
            ),
            array(
                'label' => '網站管理員',
                'code' => 'privilege',
                'private' => 'admin_manage'
            ),
            array(
                'label' => '角色管理',
                'code' => 'admin_role',
                'private' => 'admin_role_group'
            )
        )
    ),

    'config' => array(
        'label' => '管理設定',
        'icon' => 'fa-fw fas fa-cogs',
        'children' => array(
            array(
                'label' => '網站樣版',
                'code' => 'web_template',
                'private' => 'web_template'
            ),
            array(
                'label' => '郵件樣板',
                'code' => 'mail_template',
                'private' => 'mail_template'
            ),
            array(
                'label' => '系統設置',
                'code' => 'settings',
                'private' => 'shop_config',
                'params' => array(
                    'action' => 'web_site'
                ),
                'query' => true
            ),
            array(
                'label' => '郵件服務器',
                'code' => 'settings',
                'private' => 'shop_config',
                'params' => array(
                    'action' => 'mail_server'
                ),
                'query' => true
            )
        )
    ),

    'sys' => array(
        'label' => '系統管理',
        'icon' => 'fa-fw fas fa-database',
        'children' => array(
            array(
                'label' => '檔案校驗',
                'code' => 'file_check',
                'private' => 'sys'
            ),
            array(
                'label' => '文件權限檢測',
                'code' => 'file_priv',
                'private' => 'sys',
                'params' => array(
                    'action' => 'check'
                )
            ),
            array(
                'label' => '管理權限檢測',
                'code' => 'action_priv',
                'private' => 'sys',
                'params' => array(
                    'action' => 'check'
                )
            ),
            array(
                'label' => '資料表優化',
                'code' => 'database',
                'private' => 'sys',
                'params' => array(
                    'action' => 'optimize'
                )
            ),
            array(
                'label' => 'SQL 查詢',
                'code' => 'sql',
                'private' => 'sys',
                'params' => array(
                    'action' => 'main'
                )
            ),
            array(
                'label' => '系統紀錄',
                'code' => 'sys_log',
                'private' => 'sys'
            )
        )
    )
);

if (function_exists('phpinfo')) {

    $modules['sys']['children'][] = array(
        'label' => '顯示 PHP 資訊',
        'private' => 'sys',
        'params' => array(
            'action' => 'phpinfo'
        )
    );
}
