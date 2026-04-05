<?php 

namespace services\backend;

use Yii;
use common\enums\StatusEnum;
use common\models\backend\Auth;
use common\components\Service;
use yii\web\UnprocessableEntityHttpException;

/**
 * 后台会员授权业务层
 *
 * @package services\backend
 * @author  jianyan74 <751393839@qq.com>
 */
class MemberAuthService extends Service
{
    /**
     * 创建授权记录
     * @param array $data
     * @return Auth
     * @throws UnprocessableEntityHttpException
     */
    public function create(array $data): Auth
    {
        $model = new Auth();
        $model->attributes = $data;

        if (!$model->save()) {
            throw new UnprocessableEntityHttpException(
                Yii::$app->debris->analyErr($model->getFirstErrors())
            );
        }

        return $model;
    }

    /**
     * 获取所有启用的授权记录
     * @return Auth[]
     */
    public function findAll(): array
    {
        return Auth::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->all();
    }

    /**
     * 解绑指定客户端
     * @param string $oauthClient
     * @param int $memberId
     * @return Auth|bool
     */
    public function unBind(string $oauthClient, int $memberId)
    {
        $model = $this->findOauthClientByMemberId($oauthClient, $memberId);
        if (!$model) {
            return true;
        }

        $model->status = StatusEnum::DISABLED;
        $model->save(false);

        return $model;
    }

    /**
     * 根据客户端 + 会员 ID 查找启用的授权
     * @param string $oauthClient
     * @param int $memberId
     * @return Auth|null
     */
    public function findOauthClientByMemberId(string $oauthClient, int $memberId): ?Auth
    {
        return Auth::find()
            ->where([
                'oauth_client' => $oauthClient,
                'member_id'    => $memberId,
                'status'       => StatusEnum::ENABLED,
            ])
            ->one();
    }

    /**
     * 根据客户端 + 第三方用户 ID 查找启用的授权
     * @param string $oauthClient
     * @param string $oauthClientUserId
     * @return Auth|null
     */
    public function findOauthClient(string $oauthClient, string $oauthClientUserId): ?Auth
    {
        return Auth::find()
            ->where([
                'oauth_client'         => $oauthClient,
                'oauth_client_user_id' => $oauthClientUserId,
                'status'               => StatusEnum::ENABLED,
            ])
            ->one();
    }
}