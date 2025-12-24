<?php
namespace common\helpers;

use yii\helpers\BaseArrayHelper;
use yii\helpers\Json;

/**
 * 数组助手（PHP 7.3 + MySQL 5.7）
 * 在 Yii2 原生 BaseArrayHelper 之上扩展常用业务函数
 */
class ArrayHelper extends BaseArrayHelper
{
    /* ---------- 通用常量 ---------- */
    const CHILD_KEY = '-';        // 递归子节点默认键名
    const DF_ID     = 'id';       // 默认主键字段
    const DF_PID    = 'pid';      // 默认父键字段
    const MAX_DEPTH = 50;         // 递归深度上限（防死循环）

    /* ============================================================
     * 一、树形结构相关（无限级分类）
     * ============================================================ */

    /**
     * 将平铺数组转为树形结构（最常用）
     *
     * @param array  $items    原始二维数组
     * @param int    $pid      顶级父 ID
     * @param string $idField  主键字段名
     * @param string $pidField 父键字段名
     * @param string $child    子节点存放键名
     *
     * @return array 树形嵌套（每个节点以 $child 为 key 存放子集）
     *
     * @example
     * $tree = ArrayHelper::itemsMerge($list, 0, 'id', 'pid', 'children');
     */
    public static function itemsMerge(array $items, $pid = 0, $idField = self::DF_ID, $pidField = self::DF_PID, $child = self::CHILD_KEY)
    {
        if (empty($items)) {
            return [];
        }
        $map  = [];
        $tree = [];
        // 第一遍：建立 id => 引用
        foreach ($items as &$item) {
            $item[$child] = [];
            $map[$item[$idField]] = &$item;
        }
        unset($item);
        // 第二遍：挂到父级
        foreach ($items as &$item) {
            $parent = &$map[$item[$pidField]] ?? null;
            if ($parent) {
                $parent[$child][] = &$item;
            } elseif ($item[$pidField] == $pid) {
                $tree[] = &$item;
            }
        }
        unset($item, $map);
        return $tree;
    }

    /**
     * 向上追溯：根据子 ID 返回所有父级节点（含自身）
     *
     * @param array  $items    平铺数组
     * @param int    $id       起始子 ID
     * @param string $idField  主键字段
     * @param string $pidField 父键字段
     * @param int    &$depth   递归深度（引用，外部无需传）
     *
     * @return array 按层级升序排列的父节点集合
     *
     * @example
     * $bread = ArrayHelper::getParents($list, 15);
     */
    public static function getParents(array $items, $id, $idField = self::DF_ID, $pidField = self::DF_PID, &$depth = 0)
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }
        $depth++;
        $out = [];
        foreach ($items as $v) {
            if ($v[$idField] == $id) {
                $out[] = $v;
                $out   = array_merge(self::getParents($items, $v[$pidField], $idField, $pidField, $depth), $out);
                break;
            }
        }
        return $out;
    }

    /**
     * 向下追溯：根据父 ID 返回所有子节点（不含父自身）
     *
     * @param array  $items    平铺数组
     * @param int    $pid      起始父 ID
     * @param string $idField  主键字段
     * @param string $pidField 父键字段
     *
     * @return array 子节点集合
     *
     * @example
     * $children = ArrayHelper::getChilds($list, 10);
     */
    public static function getChilds(array $items, $pid, $idField = self::DF_ID, $pidField = self::DF_PID)
    {
        $out = [];
        foreach ($items as $v) {
            if ($v[$pidField] == $pid) {
                $out[] = $v;
                $out   = array_merge($out, self::getChilds($items, $v[$idField], $idField, $pidField));
            }
        }
        return $out;
    }

    /**
     * 仅返回子 ID 集合（常用于批量删除/更新）
     *
     * @param array  $items    平铺数组
     * @param int    $pid      起始父 ID
     * @param string $idField  主键字段
     * @param string $pidField 父键字段
     *
     * @return int[] 一维 ID 数组
     *
     * @example
     * $ids = ArrayHelper::getChildIds($list, 10);
     */
    public static function getChildIds(array $items, $pid, $idField = self::DF_ID, $pidField = self::DF_PID)
    {
        $ids = [];
        foreach ($items as $v) {
            if ($v[$pidField] == $pid) {
                $ids[] = $v[$idField];
                $ids   = array_merge($ids, self::getChildIds($items, $v[$idField], $idField, $pidField));
            }
        }
        return $ids;
    }

    /* ============================================================
     * 二、排序与格式化
     * ============================================================ */

    /**
     * 根据某个字段对二维数组排序（支持 asc/desc）
     *
     * @param array  &$arr  待排序数组（引用，内部不会破坏键名）
     * @param string $keys  排序字段
     * @param string $type  asc｜desc
     *
     * @return array 排序后新数组
     *
     * @example
     * $hot = ArrayHelper::arraySort($goods, 'sale_num', 'desc');
     */
    public static function arraySort(array &$arr, $keys, $type = 'asc')
    {
        $cnt = count($arr);
        if ($cnt <= 1) {
            return $arr;
        }
        $keysValue = [];
        foreach ($arr as $k => $v) {
            $keysValue[$k] = $v[$keys] ?? null;
        }
        $type === 'asc' ? asort($keysValue) : arsort($keysValue);
        $sorted = [];
        foreach ($keysValue as $k => $v) {
            $sorted[$k] = $arr[$k];
        }
        return $sorted;
    }

     /**
     * 把二维数组某个字段值当 key，快速转哈希
     *
     * @param array  $arr   原始数组
     * @param string $field 作为 key 的字段名
     *
     * @return array 哈希数组 [fieldValue => 原元素]
     *
     * @example
     * $map = ArrayHelper::arrayKey($rows, 'sku');
     */
    public static function arrayKey(array $arr, $field)
    {
        $out = [];
        foreach ($arr as $v) {
            if (isset($v[$field])) {
                $out[$v[$field]] = $v;
            }
        }
        return $out;
    }

    /**
     * 根据值移除元素（返回新数组）
     *
     * @param array  $array 原数组
     * @param mixed  $value 要删除的值
     * @param string $key   对比字段
     *
     * @return array 删除后的新数组
     *
     * @example
     * $rows = ArrayHelper::removeByValue($rows, 0, 'status');
     */
    public static function removeByValue(array $array, $value, $key = 'id')
    {
        foreach ($array as $k => $v) {
            if ($v[$key] == $value) {
                unset($array[$k]);
            }
        }
        return $array;
    }

    /* ============================================================
     * 三、区间与树形下拉
     * ============================================================ */

    /**
     * 生成数字区间数组
     *
     * @param int  $start 起始值
     * @param int  $end   结束值（包含）
     * @param bool $key   是否用值当 key
     * @param int  $step  步长
     *
     * @return array 区间数组
     *
     * @example
     * $hours = ArrayHelper::numBetween(0, 23, true, 1);
     */
    public static function numBetween($start = 0, $end = 1, $key = true, $step = 1)
    {
        $arr = [];
        for ($i = $start; $i <= $end; $i += $step) {
            $key ? $arr[$i] = $i : $arr[] = $i;
        }
        return $arr;
    }

    /**
     * 为树形结构生成带「├──」前缀的下拉描述符
     *
     * @param array  $models     已由 itemsMerge 生成的树
     * @param string $idField    作为 option value 的字段
     * @param string $titleField 作为 option text 的字段
     * @param int    $treeStat   层级修正值（一般 1）
     *
     * @return array 可直接喂给 yii\helpers\Html::dropDownList()
     *
     * @example
     * $options = ArrayHelper::itemsMergeDropDown($tree, 'id', 'title');
     */
    public static function itemsMergeDropDown($models, $idField = self::DF_ID, $titleField = 'title', $treeStat = 1)
    {
        $arr = [];
        foreach ($models as $k => $model) {
            $arr[] = [
                $idField   => $model[$idField],
                $titleField => self::itemsLevel($model['level'], $models, $k, $treeStat) . " " . \Yii::t('app', $model[$titleField]),
            ];
            if (!empty($model[self::CHILD_KEY])) {
                $arr = array_merge($arr, self::itemsMergeDropDown($model[self::CHILD_KEY], $idField, $titleField, $treeStat));
            }
        }
        return $arr;
    }

    /**
     * 生成层级前缀字符串（├──）
     *
     * @param int   $level    当前层级
     * @param array $models   同级模型集合（用于判断最后一项）
     * @param int   $k        当前索引
     * @param int   $treeStat 层级修正值
     *
     * @return string|false 前缀或 false
     *
     * @example
     * echo ArrayHelper::itemsLevel(3, $models, $k, 1);
     */
    public static function itemsLevel($level, array $models, $k, $treeStat = 1)
    {
        $str = '';
        for ($i = 1; $i < $level; $i++) {
            $str .= '　　';
            if ($i == $level - $treeStat) {
                return $str . (isset($models[$k + 1]) ? '├──' : '└──');
            }
        }
        return false;
    }

    /* ============================================================
     * 四、IP / 安全 / 回调
     * ============================================================ */

    /**
     * IP 白名单检测（支持 CIDR 与通配符）
     *
     * @param string $ip         待检测 IP
     * @param array  $allowedIPs 白名单列表 ['192.168.0.0/24', '10.0.*']
     *
     * @return bool 是否命中白名单
     *
     * @example
     * if (!ArrayHelper::ipInArray($userIP, ['192.168.0.0/24', '10.0.*'])) { ... }
     */
    public static function ipInArray($ip, array $allowedIPs)
    {
        foreach ($allowedIPs as $filter) {
            if ($filter === '*' || $filter === $ip) {
                return true;
            }
            // CIDR 192.168.0.0/24
            if (strpos($filter, '/') !== false) {
                list($subnet, $mask) = explode('/', $filter);
                if ((ip2long($ip) >> (32 - $mask)) === (ip2long($subnet) >> (32 - $mask))) {
                    return true;
                }
                continue;
            }
            // 通配符 192.168.*
            if (($pos = strpos($filter, '*')) !== false && strncmp($ip, $filter, $pos) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * 模板变量递归替换（支持无限级路径）
     *
     * @param string $content 模板字符串，例：Hello {user.name}
     * @param array  $data    数据源
     * @param string $start   变量左边界
     * @param string $end     变量右边界
     *
     * @return string 替换后字符串
     *
     * @example
     * $html = ArrayHelper::recursionGetVal('Hello {user.name}!', $data);
     */
    public static function recursionGetVal($content, array $data = [], $start = '{', $end = '}')
    {
        $depth = 0;
        while (($keywords = StringHelper::matchStr($content, $start, $end)) && $depth < self::MAX_DEPTH) {
            $depth++;
            foreach ($keywords as $keyword) {
                $val = self::getFieldData(explode('.', $keyword), $data);
                // 正则转义关键字，防止冲突
                $content = preg_replace('/' . preg_quote($start . $keyword . $end, '/') . '/', $val, $content, 1);
            }
        }
        return $content;
    }

    /**
     * 按点号路径取值（支持 a.b.c）
     *
     * @param array $fields 路径 ['a','b','c']
     * @param mixed $data   数据源
     *
     * @return string 找不到返回空字符串
     *
     * @example
     * $val = ArrayHelper::getFieldData(['user','name'], $data);
     */
    public static function getFieldData(array $fields, $data)
    {
        if (empty($data) || empty($fields)) {
            return '';
        }
        foreach ($fields as $f) {
            if (!isset($data[$f])) {
                return '';
            }
            $data = $data[$f];
        }
        return is_array($data) ? '' : $data;
    }

    /* ============================================================
     * 五、差异对比 & XML
     * ============================================================ */

    /**
     * 对比两组 ID，返回「保留」和「被删除」列表
     *
     * @param array $oldIds 旧 ID 集合
     * @param array $newIds 新 ID 集合
     *
     * @return array [updatedIds, deletedIds]
     *
     * @example
     * [$keep, $del] = ArrayHelper::comparisonIds($old, $new);
     */
    public static function comparisonIds(array $oldIds, array $newIds)
    {
        $updated = array_values(array_intersect($oldIds, $newIds));
        $deleted = array_values(array_diff($oldIds, $newIds));
        return [$updated, $deleted];
    }

    /**
     * 数组转微信/支付宝风格 XML（自动 CDATA）
     *
     * @param array $arr 源数组
     *
     * @return string|false XML 字符串；空数组返回 false
     *
     * @example
     * echo ArrayHelper::toXml(['return_code' => 'SUCCESS']);
     */
    public static function toXml($arr)
    {
        if (!is_array($arr) || !$arr) {
            return false;
        }
        $xml = '<xml>';
        foreach ($arr as $k => $v) {
            $xml .= is_numeric($v) ? "<$k>$v</$k>" : "<$k><![CDATA[$v]]></$k>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    /* ============================================================
     * 六、向后兼容（PHP 7.3 无 each）
     * ============================================================ */

    /**
     * 取树中第一个无子级的节点（深度优先）
     *
     * @param array $array 已由 itemsMerge 生成的树
     *
     * @return array|false 叶子节点或 false
     *
     * @example
     * $leaf = ArrayHelper::getFirstRowByItemsMerge($tree);
     */
    public static function getFirstRowByItemsMerge(array $array)
    {
        foreach ($array as $item) {
            if (!empty($item['-'])) {
                return self::getFirstRowByItemsMerge($item['-']);
            } else {
                return $item;
            }
        }

        return false;
    }
    /**
     * 取树中全部叶子节点
     *
     * @param array $array 树
     *
     * @return array 叶子集合
     *
     * @example
     * $leaves = ArrayHelper::getNotChildRowsByItemsMerge($tree);
     */
    public static function getNotChildRowsByItemsMerge(array $array)
    {
        $arr = [];

        foreach ($array as $item) {
            if (!empty($item['-'])) {
                $arr = array_merge($arr, self::getNotChildRowsByItemsMerge($item['-']));
            } else {
                $arr[] = $item;
            }
        }

        return $arr;
    }
    /**
     * 树转平铺二维数组（自动删除子键）
     *
     * @param array $array     树
     * @param string $childField 子键名
     *
     * @return array 平铺数组
     *
     * @example
     * $flat = ArrayHelper::getRowsByItemsMerge($tree, 'children');
     */
    public static function getRowsByItemsMerge(array $array, $childField = '-')
    {
        $arr = [];

        foreach ($array as $item) {
            if (!empty($item[$childField])) {
                $arr = array_merge($arr, self::getRowsByItemsMerge($item[$childField]));
            }

            unset($item[$childField]);
            $arr[] = $item;
        }

        return $arr;
    }
    /**
     * 带分组的 yii\helpers\ArrayHelper::map 增强版（自动翻译）
     *
     * @param array  $array 源数组
     * @param string $from  作为 key 的字段
     * @param string $to    作为 value 的字段
     * @param string|null $group 分组字段
     *
     * @return array
     *
     * @example
     * $map = ArrayHelper::map($list, 'id', 'name', 'category');
     */
    public static function map($array, $from, $to, $group = null)
    {
        $result = [];
        foreach ($array as $element) {
            $key = static::getValue($element, $from);
            $value = \Yii::t('addon',static::getValue($element, $to));
            if ($group !== null) {
                $result[static::getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
    /**
     * 把 map 型数组转正常二维数组（常用于路由菜单）
     *
     * @param array  $array        源 map
     * @param string $keyForField   新 key 字段
     * @param string $valueForField 新 value 字段
     *
     * @return array
     *
     * @example
     * $routes = ArrayHelper::regroupMapToArr($map, 'route', 'title');
     */
    public static function regroupMapToArr($array = [], $keyForField = 'route', $valueForField = 'title')
    {
        $arr = [];
        foreach ($array as $key => $item) {
            if (!is_array($array[$key])) {
                $arr[] = [
                    $keyForField => $key,
                    $valueForField => $item,
                ];
            } else {
                $arr[] = $item;
            }
        }

        return $arr;
    }
    /**
     * 把指定字段 JSON 字符串批量解码为数组
     *
     * @param array  $data  源数组
     * @param string $field 字段名
     *
     * @return array
     *
     * @example
     * $rows = ArrayHelper::fieldToArray($rows, 'covers');
     */
    public static function fieldToArray(array $data, $field = 'covers')
    {
        foreach ($data as &$datum) {
            if (empty($datum[$field])) {
                $datum[$field] = [];
            }

            if (!is_array($datum[$field])) {
                $datum[$field] = Json::decode($datum[$field]);
            }
        }

        return $data;
    }
    /**
     * 一维哈希转二维数组（id/key, title/value）
     *
     * @param array $data 一维数组 ['a'=>1,'b'=>2]
     *
     * @return array 二维数组 [['id'=>'a','title'=>1], ...]
     *
     * @example
     * $drop = ArrayHelper::arrayToArrays(['a'=>1,'b'=>2]);
     */
    public static  function arrayToArrays(array $data)
    {
        $tmp = array();
        reset($data);
        while (list($key, $val) = ArrayHelper::fun_adm_each($data)) {
            $tmp[] = array('id'=>$key,'title'=>$val);
        }
        return $tmp;
    }
    /**
     * 仅抽取值并返回索引数组
     *
     * @param array $data 一维数组
     *
     * @return array 值集合 [1,2,3]
     *
     * @example
     * $vals = ArrayHelper::arraysToArray($map);
     */
    public static  function arraysToArray(array $data)
    {
        $tmp = [];
        reset($data);
        while (list($key, $val) = ArrayHelper::fun_adm_each($data)) {
            array_push($tmp,$val);
        }

        return $tmp;
    }
    /**
     * 废弃 each() 的安全替代（返回格式与原生一致）
     *
     * @param array &$array 输入数组（内部会移动指针）
     *
     * @return array|false 当前元素 [1=>value,'value'=>value,0=>key,'key'=>key] 或 false
     *
     * @example
     * while (list($k, $v) = ArrayHelper::fun_adm_each($arr)) { ... }
     */
    public static function fun_adm_each(&$array)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }
        $key   = key($array);
        $value = current($array);
        if ($key === null) {
            return false;
        }
        next($array);
        return [1 => $value, 'value' => $value, 0 => $key, 'key' => $key];
    }

    /* -------------------------------------------------- */
    /* 其余函数 */
    /* -------------------------------------------------- */
    /**
     * 递归对象转数组
     *
     * @param mixed $array 对象或数组
     *
     * @return array
     *
     * @example
     * $pure = ArrayHelper::object_array($model);
     */
    public static function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as &$v) {
                $v = self::object_array($v);
            }
        }
        return $array;
    }
}