<?php 

namespace api\modules\v1\controllers;

use Yii;
use common\enums\StatusEnum;
use common\helpers\ResultHelper;
use addons\Member\common\models\Auth;
use api\controllers\UserAuthController;

/**
 * 会员授权控制器
 *
 * @package addons\Member\api\modules\v1\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthController extends UserAuthController
{
    /** @var array 免登录动作 */
    protected $authOptional = ['binding-equipment'];

    /**
     * 绑定设备进行 APP 推送
     * @return Auth|array
     */
    public function actionBindingEquipment()
    {
        $oauthClient       = Yii::$app->request->post('oauth_client');
        $oauthClientUserId = Yii::$app->request->post('oauth_client_user_id');
        $token             = Yii::$app->request->post('token');

        // 平台校验
        if (!in_array($oauthClient, ['ios', 'android'], true)) {
            return ResultHelper::json(422, '未知的客户端类型');
        }

        // Token 校验
        $apiAccessToken = Yii::$app->services->apiAccessToken->findByAccessToken($token);
        if (!$token || !$apiAccessToken) {
            return ResultHelper::json(422, 'AccessToken 无效');
        }

        // 查找或新建授权记录
        $model = Yii::$app->memberService->memberAuth->findOauthClientByApp($oauthClient, $oauthClientUserId);
        if (!$model) {
            $model = new $this->modelClass();
            $model->loadDefaultValues();
            $model->attributes = Yii::$app->request->post();
        }

        $model->oauth_client         = $oauthClient;
        $model->oauth_client_user_id = $oauthClientUserId;
        $model->member_id            = $apiAccessToken->member_id;
        $model->status               = StatusEnum::DISABLED;

        return $model->save()
            ? $model
            : ResultHelper::json(422, $this->getError($model));
    }
}