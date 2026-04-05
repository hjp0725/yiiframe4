<?php 

namespace api\modules\v1\controllers;

use api\controllers\OnAuthController;
use api\modules\v1\forms\LoginForm;
use api\modules\v1\forms\MobileLogin;
use api\modules\v1\forms\RefreshForm;
use api\modules\v1\forms\RegisterForm;
use api\modules\v1\forms\SmsCodeForm;
use api\modules\v1\forms\UpPwdForm;
use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;
use common\enums\AppEnum;
use Yii;

/**
 * 登录接口
 * Class SiteController
 * @package api\modules\v1\controllers
 */
class SiteController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 不用进行登录验证的方法
     * @var string[]
     */
    protected $authOptional = [
        'info','login','refresh','mobile-login',
        'sms-code','register','up-pwd',
    ];

    /* -------------------- 登录/刷新 -------------------- */
    public function actionLogin()
    {
        $model = new LoginForm();
        $model->attributes = Yii::$app->request->post();
        if ($model->validate()) {
            return Yii::$app->services->apiAccessToken->getAccessToken($model->getUser(), $model->group);
        }
        return ResultHelper::json(422, $this->getError($model));
    }

    public function actionMobileLogin()
    {
        $model = new MobileLogin();
        $model->attributes = Yii::$app->request->post();
        if ($model->validate()) {
            return Yii::$app->services->apiAccessToken->getAccessToken($model->getUser(), $model->group);
        }
        return ResultHelper::json(422, $this->getError($model));
    }

    public function actionRefresh()
    {
        $model = new RefreshForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }
        return Yii::$app->services->apiAccessToken->getAccessToken($model->getUser(), $model->group);
    }

    /* -------------------- 业务 -------------------- */
    public function actionLogout()
    {
        return Yii::$app->services->apiAccessToken->disableByAccessToken(Yii::$app->user->identity->access_token)
            ? ResultHelper::json(200, '退出成功')
            : ResultHelper::json(422, '退出失败');
    }

    public function actionSmsCode()
    {
        $model = new SmsCodeForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }
        return $model->send();
    }

    public function actionRegister()
    {
        $model = new RegisterForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        $member = Yii::$app->services->devPattern->member();
        $member->attributes   = ArrayHelper::toArray($model);
        $member->merchant_id  = !empty(Yii::$app->user->identity->merchant_id)
            ? Yii::$app->user->identity->merchant_id
            : 0;
        $member->password_hash = Yii::$app->security->generatePasswordHash($model->password);

        if (!$member->save()) {
            return ResultHelper::json(422, $this->getError($member));
        }

        return Yii::$app->services->apiAccessToken->getAccessToken($member, $model->group);
    }

    public function actionUpPwd()
    {
        $model = new UpPwdForm();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        $member = $model->getUser();
        $member->password_hash = Yii::$app->security->generatePasswordHash($model->password);
        return $member->save()
            ? Yii::$app->services->apiAccessToken->getAccessToken($member, $model->group)
            : ResultHelper::json(422, $this->getError($member));
    }

    /* -------------------- 工具 -------------------- */
    public function actionRemind()
    {
        return ArrayHelper::arrayToArrays(\common\enums\RemindTypeEnum::getMap());
    }

    public function actionInfo()
    {
        if (Yii::$app->services->devPattern->isB2B2C()) {
            return Yii::$app->debris->getAllInfo(true, AppEnum::MERCHANT, Yii::$app->user->identity->merchant_id);
        }
        return Yii::$app->debris->backendConfigAll(true);
    }

    public function actionVerifyAccessToken(): array
    {
        $token = Yii::$app->request->post('token');
        if (!$token || !($apiAccessToken = Yii::$app->services->apiAccessToken->findByAccessToken($token))) {
            return ['token' => false];
        }
        return ['token' => true];
    }

    /* -------------------- 权限检查 -------------------- */
    /**
     * @param string $action
     * @param null|object $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = []): void
    {
        if (in_array($action, ['index', 'view', 'update', 'create', 'delete'], true)) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}