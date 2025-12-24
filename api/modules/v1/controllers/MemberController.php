<?php 

namespace api\modules\v1\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\ResultHelper;
use common\models\rbac\AuthItemChild;

/**
 * 会员接口
 *
 * @package api\modules\v1\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class MemberController extends OnAuthController
{
    /** @var string 模型类名（当前无表） */
    public $modelClass = '';

    /* -------------------- 个人中心 -------------------- */

    /**
     * 个人中心
     * @return array
     */
    public function actionIndex(): array
    {
        $id    = Yii::$app->user->identity->member_id;
        $class = Yii::$app->services->devPattern->member();

        return $class::find()->alias('m')
            ->where(['m.id' => $id])
            ->with(['assignment', 'roles'])
            ->asArray()
            ->one() ?? [];
    }

    /**
     * 更新资料
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionUpdate($id): array
    {
        $model = $this->findModel($id);
        $model->attributes = Yii::$app->request->post();

        if (!$model->save()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return ResultHelper::json(200, 'OK');
    }

    /**
     * 单用户详情
     * @param string $id
     * @return array
     * @throws NotFoundHttpException
     */
    public function actionView($id): array
    {
        $class  = Yii::$app->services->devPattern->member();
        $member = $class::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->select([
                'id', 'username', 'title', 'head_portrait', 'gender',
                'qq', 'email', 'birthday', 'status', 'created_at',
            ])
            ->asArray()
            ->one();

        if (!$member) {
            throw new NotFoundHttpException('请求的数据不存在或您的权限不足.');
        }

        return $member;
    }

    /* -------------------- 个人授权菜单 -------------------- */

    /**
     * 个人信息授权入口
     * @return array
     */
    public function actionPersonal(): array
    {
        $settings = [
            ['title' => '个人资料', 'url' => '/pages/user/userinfo', 'route' => '/base/member/personal', 'content' => ''],
            ['title' => '修改密码', 'url' => '/pages/public/password?type=1', 'route' => '/base/member/up-password', 'content' => ''],
            ['title' => '清除缓存', 'url' => 'clearCache', 'route' => '/main/clear-cache', 'content' => ''],
        ];

        // 根据模式决定应用标识
        $appId = Yii::$app->services->devPattern->isB2C() ? 'backend' : 'merchant';

        // 当前角色拥有的权限项
        $authItems = AuthItemChild::find()
            ->select('name')
            ->where(['app_id' => $appId, 'role_id' => Yii::$app->user->identity->role_id])
            ->column();

        $menu = [];
        $sign = 0;
        foreach ($settings as $setting) {
            if (in_array($setting['route'], $authItems, true)) {
                $menu[] = $setting;
                if ($setting['route'] === '/base/member/personal') {
                    $sign = 1;
                }
            }
        }

        return [
            'menu' => $menu,
            'sign' => $sign,
        ];
    }

    /* -------------------- 辅助方法 -------------------- */

    /**
     * 查找模型
     * @param string $id
     * @return \yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    protected function findModel($id): \yii\db\ActiveRecord
    {
        $class = Yii::$app->services->devPattern->member();
        $model = $class::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();

        if (!$model) {
            throw new NotFoundHttpException('请求的数据不存在');
        }

        return $model;
    }

    /**
     * 权限检查
     * @param string $action
     * @param null|\yii\db\ActiveRecord $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = []): void
    {
        if (in_array($action, ['delete'], true)) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}