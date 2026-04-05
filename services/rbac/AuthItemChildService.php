<?php 

namespace services\rbac;

use Yii;
use yii\db\ActiveQuery;
use yii\web\UnprocessableEntityHttpException;
use common\components\Service;
use common\enums\AppEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;
use common\helpers\TreeHelper;
use common\models\rbac\AuthItem;
use common\models\rbac\AuthItemChild;
use common\models\rbac\AuthRole;

/**
 * 权限项子项业务层（PHP 7.3 严格兼容）
 *
 * @package services\rbac
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthItemChildService extends Service
{
    /** @var array 当前角色已缓存的权限名 */
    protected $allAuthNames = [];

    /* -------------------- 对外 API -------------------- */

    /**
     * 获取角色全部权限（含插件）
     * @param array $role
     * @param string $app_id
     * @param string $addons_name
     * @return string[]
     */
    public function getAuthByRole(array $role, string $app_id, string $addons_name = ''): array
    {
        if ($this->allAuthNames !== []) {
            return $this->allAuthNames;
        }

        $rows = AuthItemChild::find()
            ->select(['addons_name', 'name'])
            ->where(['role_id' => $role['id'], 'app_id' => $app_id])
            ->andFilterWhere(['addons_name' => $addons_name])
            ->asArray()
            ->all();

        $this->allAuthNames = array_merge(
            array_column($rows, 'addons_name'),
            array_column($rows, 'name')
        );

        return $this->allAuthNames;
    }

    /**
     * 插件安装时批量写入权限
     * @param array $allAuthItem  各应用维度权限数组
     * @param string $name        插件名
     * @param bool $delAuthItemChild 是否先清子项
     * @throws UnprocessableEntityHttpException
     */
    public function accreditByAddon(array $allAuthItem, string $name, bool $delAuthItemChild = false): void
    {
        $db = Yii::$app->db;

        /* 先清旧数据 */
        Yii::$app->services->rbacAuthItem->delByAddonsName($name, $delAuthItemChild);

        /* 重组结构 */
        foreach ($allAuthItem as &$v) {
            $v = ArrayHelper::regroupMapToArr($v, 'name');
        }

        /* 按应用维度合并 */
        $allAuth = [];
        foreach ($allAuthItem as $app => $item) {
            $allAuth = array_merge($allAuth, $this->regroupByAddonsData($item, $name, $app));
        }

        /* 批量写入 */
        $rows = $this->createByAddonsData($allAuth);
        if ($rows) {
            $db->createCommand()->batchInsert(
                AuthItem::tableName(),
                ['title', 'name', 'app_id', 'is_addon', 'addons_name', 'pid', 'level', 'sort', 'tree', 'created_at', 'updated_at'],
                $rows
            )->execute();
        }
    }

    /**
     * 给角色授权（单应用）
     * @param int $role_id
     * @param array $data        权限 ID 列表
     * @param int $is_addon
     * @param string $app_id
     */
    public function accredit(int $role_id, array $data, int $is_addon, string $app_id): void
    {
        if (!$data) {
            return;
        }

        AuthItemChild::deleteAll(['role_id' => $role_id, 'is_addon' => $is_addon]);

        $items = Yii::$app->services->rbacAuthItem->findByAppId($app_id, $data);
        $rows  = [];
        foreach ($items as $v) {
            $rows[] = [
                $role_id,
                $v['id'],
                $v['name'],
                $v['app_id'],
                $v['is_addon'],
                $v['addons_name'],
            ];
        }

        if ($rows) {
            Yii::$app->db->createCommand()->batchInsert(
                AuthItemChild::tableName(),
                ['role_id', 'item_id', 'name', 'app_id', 'is_addon', 'addons_name'],
                $rows
            )->execute();
        }
    }

    /**
     * 复制默认权限给新角色
     * @param AuthRole $role
     * @param array $itemChilds
     */
    public function accreditByDefault( $role, array $itemChilds): void
    {
        $rows = array_map(
            function (array $c) use ($role): array {
                return [
                    $role->id,
                    $c['item_id'],
                    $c['name'],
                    $c['app_id'],
                    $c['is_addon'],
                    $c['addons_name'],
                ];
            },
            $itemChilds
        );

        if ($rows) {
            Yii::$app->db->createCommand()->batchInsert(
                AuthItemChild::tableName(),
                ['role_id', 'item_id', 'name', 'app_id', 'is_addon', 'addons_name'],
                $rows
            )->execute();
        }
    }

    /**
     * 取角色已有权限明细
     * @param int $role_id
     * @return array
     */
    public function findItemByRoleId(int $role_id): array
    {
        $role = Yii::$app->services->rbacAuthRole->findById($role_id);

        return array_column(
            AuthItemChild::find()
                ->where(['role_id' => $role_id])
                ->with(['item' => function (ActiveQuery $q) use ($role): void {
                    $q->andWhere(['app_id' => $role['app_id']]);
                }])
                ->asArray()
                ->all(),
            'item'
        );
    }

    /* -------------------- 内部辅助 -------------------- */

    /**
     * 给插件数据打上统一标记
     */
    protected function regroupByAddonsData(array $item, string $name, string $app_id): array
    {
        foreach ($item as &$v) {
            $v['app_id']      = $app_id;
            $v['is_addon']    = WhetherEnum::ENABLED;
            $v['addons_name'] = $name;          // 保证每个节点都有

            if (isset($v['child']) && $v['child']) {
                $v['child'] = $this->regroupByAddonsData($v['child'], $name, $app_id);
            }
        }
        return $item;
    }

    /**
     * 批量校验并生成待插入数组
     */
    protected function createByAddonsData(array $data, $pid = 0, $level = 1, $parent = '')
    {
        $rows = [];

        foreach ($data as $datum) {
            $model = new AuthItem();
            $model = $model->loadDefaultValues();
            $model->attributes = $datum;
            // 增加父级
            !empty($parent) && $model->setParent($parent);
            $model->pid = $pid;
            $model->level = $level;
            $model->name = '/' . StringHelper::toUnderScore($model->addons_name) . '/' . $model->name;
            $model->setScenario('addonsBatchCreate');
            if (!$model->validate()) {
                throw new UnprocessableEntityHttpException($this->getError($model));
            }

            // 创建子权限
            if (isset($datum['child']) && !empty($datum['child'])) {
                // 有子权限的直接写入
                if (!$model->save()) {
                    throw new UnprocessableEntityHttpException($this->getError($model));
                }

                $rows = array_merge($rows, $this->createByAddonsData($datum['child'], $model->id, $level++, $model));
            } else {
                $model->tree = !empty($parent) ?  $parent->tree . TreeHelper::prefixTreeKey($parent->id) : TreeHelper::defaultTreeKey();

                $rows[] = [
                    $model->title,
                    $model->name,
                    $model->app_id,
                    $model->is_addon,
                    $model->addons_name,
                    $pid,
                    $level,
                    $model->sort ?? 9999,
                    $model->tree,
                    time(),
                    time(),
                ];

                unset($model);
            }
        }

        return $rows;
    }
}