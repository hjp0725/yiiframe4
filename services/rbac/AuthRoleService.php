<?php 

namespace services\rbac;

use common\components\Service;
use common\enums\AppEnum;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\helpers\TreeHelper;
use common\models\rbac\AuthRole;
use yii\web\UnauthorizedHttpException;
use yiiframe\plugs\services\AddonsService;
use Yii;

/**
 * 角色
 * Class AuthRoleService
 * @package services\rbac
 * @author jianyan74 <751393839@qq.com>
 */
class AuthRoleService extends Service
{
    /**
     * @var array 当前角色缓存
     */
    protected $roles = [];

    /* -------------------- 基础查询 -------------------- */
    /**
     * 获取当前登录角色（非超管）
     * @throws UnauthorizedHttpException
     */
    public function getRole(): array
    {
        if (Yii::$app->services->auth->isSuperAdmin()) {
            return [];
        }

        if (!$this->roles) {
            $assignment = Yii::$app->user->identity->assignment ?? null;
            if (!$assignment) {
                Yii::$app->user->logout();
                throw new UnauthorizedHttpException('未授权角色，请联系管理员');
            }

            $merchant_id = Yii::$app->user->identity->merchant_id;
            if (Yii::$app->id === AppEnum::BACKEND) {
                $merchant_id = '';
            }

            $this->roles = AuthRole::find()
                ->where(['id' => $assignment['role_id']])
                ->andWhere(['status' => StatusEnum::ENABLED])
                ->andFilterWhere(['merchant_id' => $merchant_id])
                ->asArray()
                ->one();

            if (empty($this->roles)) {
                throw new UnauthorizedHttpException('授权的角色已失效，请联系管理员');
            }
        }

        return $this->roles;
    }

    /**
     * 取角色名称
     * @throws UnauthorizedHttpException
     */
    public function getTitle(): string
    {
        return $this->getRole()['title'] ?? '游客';
    }

    /**
     * 根据 ID 返回单条记录
     */
    public function findById(int $id): ?array
    {
        return AuthRole::find()
            ->where(['id' => $id])
            ->asArray()
            ->one();
    }

    /**
     * 返回所有角色（可附加商户、树条件）
     */
    public function findAll(string $app_id,  $merchant_id = 0, array $condition = []): array
    {
        return AuthRole::find()
            ->where(['app_id' => $app_id])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $merchant_id])
            ->andFilterWhere($condition)
            ->orderBy('sort asc, created_at asc')
            ->asArray()
            ->all();
    }

    /**
     * 默认已启用角色（平台级）
     */
    public function findDefault(string $app_id): ?array
    {
        return AuthRole::find()
            ->where([
                'is_default' => StatusEnum::ENABLED,
                'app_id' => $app_id,
                'status' => StatusEnum::ENABLED,
            ])
            ->with('authItemChild')
            ->asArray()
            ->one();
    }

    /**
     * 商户默认角色
     */
    public function findDefaultByMerchantId( $merchant_id, string $app_id = AppEnum::MERCHANT)
    {
        return AuthRole::find()
            ->where([
                'is_default' => StatusEnum::ENABLED,
                'app_id' => $app_id,
                'merchant_id' => $merchant_id,
                'status' => StatusEnum::ENABLED,
            ])
            ->one();
    }

    /* -------------------- 业务封装 -------------------- */
    /**
     * 生成 JS-Tree 所需数据（区分默认/插件权限）
     */
    public function getJsTreeData(int $role_id=0, array $allAuth): array
    {
        $auth = Yii::$app->services->rbacAuthItemChild->findItemByRoleId($role_id);

        $addonName = [];
        $formAuth = $checkIds = $addonFormAuth = $addonsCheckIds = [];

        // 分离默认与插件权限
        foreach ($allAuth as $item) {
            if ($item['is_addon'] == WhetherEnum::DISABLED) {
                $formAuth[] = $item;
            } else {
                if ($item['pid'] == 0) {
                    $item['pid'] = $item['addons_name'];
                }
                $addonFormAuth[] = $item;
                $addonName[$item['addons_name']] = $item['addons_name'];
            }
        }

        // 补全插件顶级节点
        $addons = AddonsService::findByNames(array_keys($addonName));

        foreach ($addons as $addon) {
            $addonFormAuth[] = [
                'id'    => $addon['name'],
                'pid'   => 0,
                'title' => $addon['title'],
            ];
        }

        // 分离已授权 ID
        foreach ($auth as $val) {
            if (empty($val)) {
                continue;
            }
            if ($val['is_addon'] == WhetherEnum::DISABLED) {
                $checkIds[] = $val['id'];
            } else {
                $addonsCheckIds[] = $val['id'];
            }
        }

        return [$formAuth, $checkIds, $addonFormAuth, $addonsCheckIds];
    }

    /**
     * 编辑下拉（带顶级角色）
     */
    public function getDropDownForEdit(string $app_id, int $id ): array
    {
        $list = $this->findAll($app_id, Yii::$app->services->merchant->getId());
        $list = ArrayHelper::removeByValue($list, $id);

        $models = ArrayHelper::itemsMerge($list);
        $data   = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        if (Yii::$app->services->auth->isSuperAdmin()) {
            return ArrayHelper::merge([0 => Yii::t('app', '顶级角色')], $data);
        }

        return $data;
    }

    /**
     * 分配角色下拉（带权限继承）
     */
    public function getDropDown(string $app_id, bool $sourceAuthChild = false): array
    {
        $condition = $this->roleCondition($sourceAuthChild);
        $list      = $this->findAll($app_id, Yii::$app->services->merchant->getId(), $condition);

        $pid       = 0;
        $treeStat  = 1;
        if ($sourceAuthChild && ($role = $this->getRole())) {
            $pid      = $role['id'];
            $treeStat = $role['level'] + 1;
        }

        $models = ArrayHelper::itemsMerge($list, $pid);
        return ArrayHelper::map(
            ArrayHelper::itemsMergeDropDown($models, 'id', 'title', $treeStat),
            'id',
            'title'
        );
    }

    /**
     * 当前角色下级（树条件）
     */
    public function getChildes(string $app_id): array
    {
        return $this->findAll($app_id, Yii::$app->services->merchant->getId(), $this->roleCondition(true));
    }

    /**
     * 生成树查询条件
     */
    public function roleCondition(bool $sourceAuthChild = false): array
    {
        if (!$sourceAuthChild) {
            return [];
        }

        $role = $this->getRole();
        if (empty($role)) {
            return [];
        }

        $tree = $role['tree'] . TreeHelper::prefixTreeKey((int)$role['id']);

        return ['like', 'tree', $tree . '%', false];
    }

    /**
     * 复制默认角色到指定商户
     */
    public function cloneInDefault(string $app_id,  $merchant_id)
    {
        $default = $this->findDefault($app_id);
        if (!$default) {
            return null;
        }

        $role = new AuthRole();
        $role->attributes  = $default;
        $role->merchant_id = $merchant_id;

        if ($role->save()) {
            Yii::$app->services->rbacAuthItemChild->accreditByDefault($role, $default['authItemChild'] ?? []);
            return $role;
        }

        return null;
    }
}