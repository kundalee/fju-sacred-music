<?php if (!defined('IN_DUS')) die('No direct script access allowed');

/**
 * 分類函式庫
 */

/**
 * 取得指定分類下所有子分類陣列
 *
 * @param     string    $table                分類資料表名稱
 * @param     array     $params               額外參數值
 *            string     -[app]               應用名稱
 *            integer    -[cat_id]            分類 ID
 *            string     -[cid_qs_name]       分類查詢參數名稱
 *            boolean    -[cid_use_hash]      分類查詢參數是否使用錨點的方式表示
 *            mixed      -[excl_cat_id]       欲排除的分類 ID 或 ID 陣列
 *            boolean    -[hide_empty_cat]    是否隱藏項目為空的分類
 *            boolean    -[is_show_all]       值為 true 顯示所有分類，值為 false 隱藏不可見分類
 *            integer    -[lang_id]           語系 ID
 *            integer    -[level]             返回的級數: 值為 0 時返回所有級數
 *            string     -[parent_field]      分類上層鍵值名稱
 *            string     -[primary_field]     分類節點鍵值名稱
 *            string     -[query_item_sql]    查詢分類項目的 SQL 語句
 *                                            當 [hide_empty_cat] 為真時不可為空
 *            mixed      -[re_type]           返回的類型:
 *                                            0: 完整陣列
 *                                            1: HTML SELECT 選項
 *                                            2: 值 -> 名稱陣列 (cat_id -> cat_name)
 *            string     -[select_expr]       分類查詢表達式
 *            integer    -[selected]          目前選中分類的 ID
 *            array      -[uri_args]          額外查詢參數 (index 為參數名稱, value 為參數值)
 *            boolean    -[use_tree_name]     分類名稱附加上層分類名稱
 *
 * @return    array
 */
function getCategory($table, $params = array())
{
    $params = array_merge(
        array(
            'lang_id' => null,
            'cat_id' => 0,
            'excl_cat_id' => array(),
            'selected' => '',
            're_type' => 'array',
            'level' => 0,
            'is_show_all' => true,
            'primary_field' => 'cat_id',
            'parent_field' => 'parent_id',
            'select_expr' => 'c.*',
            'app' => null,
            'uri_args' => array(),
            'cid_qs_name' => 'cid',
            'cid_use_hash' => false,
            'use_tree_name' => false,
            'query_item_sql' => null,
            'hide_empty_cat' => false
        ),
        $params
    );

    if ($params['hide_empty_cat'] == true && is_null($params['query_item_sql'])) {

        throw new SmartyException('$params[\'query_item_sql\'] is empty!');
    }

    if (is_array($params['select_expr'])) {

        $params['select_expr'] = implode(', ', $params['select_expr']);
    }

    $params['selected'] = (array)$params['selected'];

    static $res = array();

    $sql = 'SELECT ' . $params['select_expr'] . ', '
         . 'COUNT(DISTINCT s.' . $params['primary_field'] . ') AS has_children '
         . 'FROM ' . $GLOBALS['db']->table($table) . ' AS c '
         . 'LEFT JOIN ' . $GLOBALS['db']->table($table) . ' AS s '
         . 'ON s.' . $params['parent_field'] . ' = c.' . $params['primary_field'] . ' '
         . 'GROUP BY c.' . $params['primary_field'] . ' '
         . 'ORDER BY c.' . $params['parent_field'] . ' ASC, '
         . 'c.sort_order ASC, c.' . $params['primary_field'] . ' ASC';
    $staticName = implode('_', array($table, sprintf('%X', crc32($sql))));

    if (!isset($res[$staticName])) {

        $data = readStaticCache($staticName . '_pid_releate');
        if ($data === false) {

            $res[$staticName] = $GLOBALS['db']->getAll($sql);

            if (count($res[$staticName]) <= 1000) {

                writeStaticCache($staticName . '_pid_releate', $res[$staticName]);
            }

        } else {

            $res[$staticName] = $data;
        }
    }

    if (empty($res[$staticName]) == true) {

        return in_array(strtolower($params['re_type']), array('1', 'html_option')) ? '' : array();
    }

    // 獲得指定分類下的子分類的數組
    $options = levelOptions($staticName, $params['cat_id'], $res[$staticName], $params);

    // 如果有額外的分類項目查詢語句
    if (!is_null($params['query_item_sql'])) {

        $itemRes = $GLOBALS['db']->query($params['query_item_sql']);
        while ($row = $GLOBALS['db']->fetchAssoc($itemRes)) {

            $catItemNum[$row[$params['primary_field']]] = $row['item_num'];
        }

        foreach ($options as $key => $val) {

            $options[$key]['item_num'] = !empty($catItemNum[$key]) ? $catItemNum[$key] : 0;
        }

        foreach ($options as $key => $val) {

            // 取得子元素
            $children = array_diff_key(
                levelOptions(
                    $staticName . '_children',
                    $val[$params['primary_field']],
                    $options,
                    array_merge(
                        array(
                            'is_cache' => false
                        ),
                        $params
                    )
                ),
                array($key => $key)
            );

            // 重新累積計算數量
            foreach ($children as $cKey => $cVal) {

                if ($cVal['item_num'] > 0) {

                    $options[$key]['item_num'] += $cVal['item_num'];
                }
            }
        }
    }

    $childrenLevel = 99999; // 大於這個分類的將被刪除
    if ($params['is_show_all'] == false) {

        foreach ($options as $key => $val) {

            if ($val['level'] > $childrenLevel) {

                unset($options[$key]);

            } else {

                if ($val['is_show'] == 0) {

                    unset($options[$key]);
                    if ($childrenLevel > $val['level']) {

                        $childrenLevel = $val['level']; // 標記一下，這樣子分類也能刪除
                    }

                } else {

                    $childrenLevel = 99999; // 恢復初始值
                }
            }
        }
    }

    /* 截取到指定的縮減級別 */
    if ($params['level'] > 0) {

        if ($params['cat_id'] == 0) {

            $endLevel = $params['level'];

        } else {

            $firstItem = reset($options); // 獲取第一個元素
            $endLevel  = $firstItem['level'] + $params['level'];
        }

        /* 保留level小於end_level的部分 */
        foreach ($options as $key => $val) {

            if ($val['level'] >= $endLevel) {

                unset($options[$key]);
            }
        }
    }

    // 排除不需要的分類
    if (!empty($params['excl_cat_id'])) {

        if (!is_array($params['excl_cat_id'])) {

            $params['excl_cat_id'] = explode(',', $params['excl_cat_id']);
        }

        // 進行排除
        foreach ($options as $key => $val) {

            !isset($val['parent_ids']) && $val['parent_ids'] = '';

            $isUset = in_array($val[$params['primary_field']], $params['excl_cat_id']);

            $exclParentIds = array_intersect_key(
                array_flip($params['excl_cat_id']),
                array_flip(explode(',', $val['parent_ids'])
            ));
            if (count($exclParentIds) > 0) {

                $isUset = true;
            }

            if ($isUset) {

                unset($options[$key]);
            }
        }
    }

    if ($params['hide_empty_cat'] == true) {

        foreach ($options as $key => $val) {

            if ($val['item_num'] < 1) {

                unset($options[$key]);
            }
        }
    }

    if ($params['use_tree_name'] == true) {

        $pathData = getCatPathData(
            $table,
            array_merge(
                $params,
                array(
                    'category' => $options,
                    'self' => true
                )
            )
        );

        foreach ($options as $key => $val) {

            $options[$key]['tree_name'] = isset($pathData[$key]) ? $pathData[$key] : $val['cat_name'];
        }
    }

    // 返回類型
    switch (strtolower($params['re_type'])) {
        case '2':
        case 'option':

            return array_column($options, $params['use_tree_name'] ? 'tree_name' : 'cat_name', $params['primary_field']);
            break;

        case '1':
        case 'html_sel_opt':

            $optStr = '';
            foreach ($options as $val) {

                $optStr .= '<option value="' . $val[$params['primary_field']] . '"';
                $optStr .= in_array($val[$params['primary_field']], $params['selected']) ? ' selected' : '';
                $optStr .= '>';

                if ($val['level'] > 0) {

                    $optStr .= str_repeat('&nbsp;', $val['level'] * 4);
                }
                $optStr .= htmlspecialchars($val['cat_name']) . '</option>';
            }

            return $optStr;
            break;

        case '0':
        case 'array':
        default:

            if (!is_null($params['app'])) {

                foreach ($options as $key => $val) {

                    if ($params['cid_use_hash'] == true) {

                        $uriArgs = (array)$params['uri_args'];

                    } else {

                        $uriArgs = array_merge(
                            array($params['cid_qs_name'] => $val[$params['primary_field']]),
                            (array)$params['uri_args']
                        );
                    }
                    ksort($uriArgs);

                    $options[$key]['url'] = buildUri($params['app'], $uriArgs);
                    if ($params['cid_use_hash'] == true) {

                        $options[$key]['url'] .= sprintf(
                            '#%s=%s',
                            $params['cid_qs_name'],
                            $val[$params['primary_field']]
                        );
                    }

                    $options[$key]['selected'] = in_array(
                        (int)$val[$params['primary_field']],
                        $params['selected']
                    );
                }
            }

            return $options;
    }
}

/**
 * 計算分類下的項目數量
 *
 * @param     string     $table     資料表名稱
 * @param     array      $params    額外參數值
 *
 * @return    string
 */
function calcCatItemNum($table, array $params = array())
{
    $params = array_merge(
        array(
            'primary_field' => 'cat_id',
            'parent_field' => 'parent_id'
        ),
        $params
    );

    $options = array();

    $sql = 'SELECT ' . $params['primary_field'] . ', COUNT(*) AS item_num '
         . 'FROM ' . $GLOBALS['db']->table($table) . ' '
         . 'GROUP BY ' . $params['primary_field'] . ' '
         . 'ORDER BY NULL';
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchAssoc($res)) {

        $options[$row[$params['primary_field']]] = $row['item_num'];
    }

    return $options;
}

/**
 * 取得指定分類下所有底層的 分類 ID
 *
 * @param     string     $table     分類資料表名稱
 * @param     integer    $catId     指定的分類ID
 * @param     array      $params    額外參數值
 *
 * @return    string
 */
function getCatChildIds($table, $catId = 0, array $params = array())
{
    $child = getCategory(
        $table,
        array_merge(
            array(
                'cat_id' => $catId,
                're_type' => 0,
                'is_show_all' => false
            ),
            $params
        )
    );

    return array_unique(array_merge(array($catId), array_keys($child)));
}

/**
 * 獲得指定分類下所有底層分類的ID
 *
 * @param     integer    $cat    指定的分類ID
 *
 * @return    string
 */
function getChildrenCats($table, $catId = 0, $label = 'cat_id')
{
    return $GLOBALS['db']->in(getCatChildIds($table, $catId), $label);
}

/**
 * 取得指定分類的所有上級分類 ID
 *
 * @param     string     $table              分類資料表名稱
 * @param     integer    $catId              指定的分類 ID
 * @param     array      $params             額外參數值
 *            string     -[primary_field]    分類節點鍵值名稱
 *            string     -[parent_field]     分類上層鍵值名稱
 *
 * @return    array
 */
function getCatParentIds($table, $catId, array $params = array())
{
    $params = array_merge(
        array(
            'primary_field' => 'cat_id',
            'parent_field' => 'parent_id'
        ),
        $params
    );

    if ($catId == 0) {

        return array();
    }

    $sql = 'SELECT ' . $params['primary_field'] . ', ' . $params['parent_field'] . ' '
         . 'FROM ' . $GLOBALS['db']->table($table);
    $arr = $GLOBALS['db']->getAll($sql);
    if (empty($arr)) {

        return array();
    }

    $index = 0;
    $cats  = array();

    while (1) {

        foreach ($arr as $row) {

            if ($catId == $row[$params['primary_field']]) {

                $catId = $row[$params['parent_field']];

                $cats[$row[$params['primary_field']]] = $row[$params['primary_field']];

                $index++;
                break;
            }
        }

        if ($index == 0 || $catId == 0) {

            break;
        }
    }

    return $cats;
}

/**
 * 取得指定分類資訊
 *
 * @param     string     $table     分類資料表名稱
 * @param     integer    $catId     分類 ID
 *
 * @return    array
 */
function getCatInfo($table, $catId)
{
    $sql = 'SELECT c1.*, COUNT(c2.cat_id) AS has_children ' .
           'FROM ' . $GLOBALS['db']->table($table) . ' AS c1 ' .
           'LEFT JOIN ' . $GLOBALS['db']->table($table) . ' AS c2 ' .
           'ON c2.parent_id = c1.cat_id ' .
           'WHERE c1.cat_id = ' . $catId;
    return $GLOBALS['db']->getRowCached($sql);
}

/**
 * 取得分類的麵包屑
 *
 * @param     string     $table      分類資料表名稱
 * @param     integer    $catId      目前選中分類的 ID
 * @param     array      $extends    其他附加的內容
 *
 * @return    array
 */
function getCatBreadcrumb($table, $catId, array $params = array(), array $extends = array())
{
    $params = array_merge(
        array(
            'app' => basename(PHP_SELF, '.php')
        ),
        $params
    );

    $data = getCatInfo($table, $catId);

    $parentIds = array();
    if (!empty($data['parent_ids'])) {

        $parentIds = explode(',', $data['parent_ids']);
    }
    $parentIds = array_merge($parentIds, array($catId));

    $category = getCategory(
        $table,
        array(
            're_type' => 0
        )
    );

    $breadcrumb = array();
    foreach ($parentIds as $id) {

        if (!isset($category[$id])) {

            continue;
        }

        $breadcrumb[] = array(
            'text' => $category[$id]['cat_name'],
            'href' => buildUri(
                $params['app'],
                array('cid' => $id)
            )
        );
    }

    if (!empty($extends)) {

        $breadcrumb = array_merge($breadcrumb, $extends); // 合併附加內容
    }

    return $breadcrumb;
}

/**
 * 取得分類的路徑資料
 *
 * @param     string     $table         分類資料表名稱
 * @param     array      $params        額外參數值
 *            boolean    -[cat_id]      分類 ID
 *            array      -[category]    分類陣列
 *            string     -[glue]        連接符號
 *            integer    -[lang_id]     語系 ID
 *            boolean    -[self]        是否包含本身
 *
 * @return    array
 */
function getCatPathData($table, array $params = array())
{
    $catId = 0;
    $category = null;
    $glue = '／';
    $self = false;

    foreach ($params as $_key => $_value) {
        $_key = strToCamel(strtolower($_key));

        switch ($_key) {
            case 'category':
                ${$_key} = (array)$_value;
                break;
            case 'self':
                ${$_key} = (boolean)$_value;
                break;
            case 'catId':
                ${$_key} = (int)$_value;
                break;
            case 'glue':
                ${$_key} = (string)$_value;
                break;
            default:
        }
    }

    if (is_null($category)) {

        $category = getCategory(
            $table,
            array(
                'cat_id' => $catId,
                're_type' => 0
            )
        );
    }

    $pathData = array();
    if (!empty($category)) {

        foreach ($category as $cId => $cData) {

            if ($cData['parent_id'] == 0) {

                $pathData[$cId] = '';

            } else {

                $pArr = explode(',', $cData['parent_ids']);
                foreach ($pArr as $key => $val) {

                    $pArr[$key] = isset($category[$val]['cat_name']) ? $category[$val]['cat_name'] : '*';
                }
                $pathData[$cId] = implode($glue, $pArr) . $glue;
            }

            if ($self) {

                $pathData[$cId] .= $cData['cat_name'];
            }
        }
    }

    return $pathData;
}
