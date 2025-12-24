<?php 

namespace services\rbac;

use Yii;
use yii\web\UnprocessableEntityHttpException;
use common\helpers\ArrayHelper;
use common\components\Service;
use common\models\rbac\AuthAssignment;

/**
 * 授权角色
 *
 * Class AuthAssignmentService
 * @package services\rbac
 * @author jianyan74 <751393839@qq.com>
 */
class AuthAssignmentService extends Service
{
    /**
     * 分配角色
     * @throws UnprocessableEntityHttpException
     */
    public function assign(array $role_ids, int $user_id, string $app_id): void
    {
        // 移除已有授权
        AuthAssignment::deleteAll(['user_id' => $user_id, 'app_id' => $app_id]);

        foreach ($role_ids as $role_id) {
            $model = new AuthAssignment();
            $model->setAttributes([
                'user_id' => $user_id,
                'role_id' => $role_id,
                'app_id'  => $app_id,
            ], false);
            if (!$model->save()) {
                throw new UnprocessableEntityHttpException($this->getError($model));
            }
        }
    }

    /**
     * 获取当前用户权限下的所有用户 ID
     * @return int[]
     * @throws \yii\web\UnauthorizedHttpException
     */
    public function getChildIds(string $app_id): array
    {
        if (Yii::$app->services->auth->isSuperAdmin()) {
            return [];
        }

        $childRoles   = Yii::$app->services->rbacAuthRole->getChildes($app_id);
        $childRoleIds = ArrayHelper::getColumn($childRoles, 'id');
        if (!$childRoleIds) {
            return [-1];
        }

        $userIds = AuthAssignment::find()
            ->select('user_id')
            ->where(['app_id' => $app_id])
            ->andWhere(['in', 'role_id', $childRoleIds])
            ->column();

        return $userIds ?: [-1];
    }

    /**
     * 根据用户 ID 和应用 ID 查询单条记录
     */
    public function findByUserIdAndAppId(int $user_id, string $app_id): ?array
    {
        return AuthAssignment::find()
            ->where(['user_id' => $user_id, 'app_id' => $app_id])
            ->asArray()
            ->one();
    }
}