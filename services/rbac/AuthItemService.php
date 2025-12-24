<?php 

namespace services\rbac;

use common\components\Service;
use common\enums\AppEnum;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\models\rbac\AuthItem;
use common\models\rbac\AuthItemChild;

/**
 * 权限项业务层
 *
 * @package services\rbac
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthItemService extends Service
{
    /**
     * 根据插件名删除权限项（含子项）
     * @param string $name
     * @param bool $delAuthItemChild
     * @return int 删除行数
     */
    public function delByAddonsName(string $name, bool $delAuthItemChild = true): int
    {
        $rows = AuthItem::deleteAll(['is_addon' => WhetherEnum::ENABLED, 'addons_name' => $name]);
        if ($delAuthItemChild) {
            AuthItemChild::deleteAll(['is_addon' => WhetherEnum::ENABLED, 'addons_name' => $name]);
        }
        return $rows;
    }

    /**
     * 编辑页下拉树（排除自身）
     * @param string $app_id
     * @param string $id
     * @return array
     */
    public function getDropDownForEdit(string $app_id, string $id = ''): array
    {
        $list = AuthItem::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['app_id' => $app_id, 'is_addon' => WhetherEnum::DISABLED])
            ->andFilterWhere(['<>', 'id', $id])
            ->select(['id', 'title', 'pid', 'level'])
            ->orderBy('sort asc')
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($list);
        $data   = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        return ArrayHelper::merge([0 => \Yii::t('app', '顶级权限')], $data);
    }

    /**
     * 根据应用 ID 查询启用的权限项
     * @param string $app_id
     * @param array $ids
     * @return array
     */
    public function findByAppId(string $app_id = AppEnum::BACKEND, array $ids = []): array
    {
        return AuthItem::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => $app_id])
            ->andFilterWhere(['in', 'id', $ids])
            ->select(['id', 'title', 'name', 'pid', 'level', 'app_id', 'is_addon', 'addons_name'])
            ->orderBy('sort asc, id asc')
            ->asArray()
            ->all();
    }

    /**
     * 查询当前应用全部权限项（含禁用）
     * @param string $app_id
     * @return array
     */
    public function findAll(string $app_id = AppEnum::BACKEND): array
    {
        return AuthItem::find()
            ->where(['app_id' => $app_id])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->orderBy('sort asc, created_at asc')
            ->asArray()
            ->all();
    }
}