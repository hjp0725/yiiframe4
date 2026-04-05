<?php

namespace common\traits;

use Yii;
use common\helpers\ArrayHelper;

/**
 * 通用下拉列表生成器（兼容树形层级）
 *
 * 用法不变：
 *   Category::dropDown(); // 扁平
 *   Department::dropDown(); // 自动树形
 */
trait DropDownTrait
{
    /**
     * 默认字段映射，可在模型里覆盖
     * @return array [key, value]
     */
    public static function dropDownMap()
    {
        return ['id', 'title'];
    }

    /**
     * 一键生成下拉数组（兼容树形）
     *
     * @param string|null $key        作为 option value 的字段
     * @param string|null $value      作为 option text  的字段
     * @param string      $order      排序，默认 'id asc'
     * @param array       $extraWhere 额外 where 条件
     * @return array 适合 Html::activeDropDownList() 的 map
     */
    public static function dropDown($key = null, $value = null, $order = 'id asc', $extraWhere = [])
    {
        if (!is_array($extraWhere)) {
            $extraWhere = [];
        }

        /* 1. 取字段映射 */
        if ($key === null || $value === null) {
            $map = static::dropDownMap();
            $defKey = $map[0];
            $defVal = $map[1];
            $key = $key === null ? $defKey : $key;
            $value = $value === null ? $defVal : $value;
        }

        /* 2. 基础条件：状态 + 商户隔离 */
        $where = ['status' => \common\enums\StatusEnum::ENABLED];
        $model = new static;
        if ($model->hasAttribute('merchant_id') && !Yii::$app->user->isGuest) {
            $where['merchant_id'] = Yii::$app->user->identity->merchant_id;
        }
        $where = array_merge($where, $extraWhere);

        /* 3. 判断是否有树形字段 */
        $hasTree = $model->hasAttribute('pid') && $model->hasAttribute('level');

        /* 4. 扁平下拉（老逻辑） */
        if (!$hasTree) {
            return ArrayHelper::map(
                static::find()
                    ->select([$key, $value])
                    ->where($where)
                    ->orderBy($order)
                    ->asArray()
                    ->all(),
                $key,
                $value
            );
        }

        /* 5. 树形下拉（新逻辑） */
        $list = static::find()
            ->select([$key, $value, 'pid', 'level'])
            ->where($where)
            ->orderBy($order)
            ->asArray()
            ->all();

        $tree = ArrayHelper::itemsMerge($list);          // 转成嵌套
        $flat = ArrayHelper::itemsMergeDropDown($tree); // 带前缀的一维数组
        return ArrayHelper::map($flat, 'id', 'title');
    }
}