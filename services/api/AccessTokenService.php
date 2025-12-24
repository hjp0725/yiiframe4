<?php 

namespace services\api;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UnprocessableEntityHttpException;
use common\enums\CacheEnum;
use common\enums\StatusEnum;
use common\enums\DevPatternEnum;
use common\helpers\ArrayHelper;
use common\models\api\AccessToken;
use common\components\Service;

/**
 * AccessToken 业务层
 *
 * @package services\api
 * @author  jianyan74 <751393839@qq.com>
 */
class AccessTokenService extends Service
{
    /** @var bool 是否写入缓存 */
    public $cache = false;

    /** @var int 缓存过期时间（秒） */
    public $timeout;

    /* -------------------- 主流程 -------------------- */

    /**
     * 颁发/刷新令牌
     * @param \yii\db\ActiveRecord $member
     * @param string $group
     * @param int $cycle 重试次数
     * @return array
     * @throws \yii\base\Exception
     */
    public function getAccessToken(ActiveRecord $member, string $group, int $cycle = 1): array
    {
        $model = $this->findModel($member->id, $group);

        // 覆盖旧数据
        $model->member_id   = $member->id;
        $model->merchant_id = $member->merchant_id ?? 0;
        $model->role_id     = $member->role_id ?? 0;
        $model->group       = $group;
        $model->status      = StatusEnum::ENABLED;

        // 删除旧缓存
        if ($model->access_token) {
            Yii::$app->cache->delete($this->getCacheKey($model->access_token));
        }

        $model->refresh_token = Yii::$app->security->generateRandomString() . '_' . time();
        $model->access_token  = Yii::$app->security->generateRandomString() . '_' . time();

        if (!$model->save()) {
            if ($cycle < 3) {
                return $this->getAccessToken($member, $group, $cycle + 1);
            }
            throw new UnprocessableEntityHttpException($this->getError($model));
        }

        // 基础返回
        $result = [
            'refresh_token'    => $model->refresh_token,
            'access_token'     => $model->access_token,
            'expiration_time'  => (int) (Yii::$app->params['user.accessTokenExpire'] ?? 7200),
        ];

        // 最后登录时间
        $this->recordLastLogin($member);

        // 会员数据（脱敏）
        $memberArr = ArrayHelper::toArray($member);
        unset(
            $memberArr['password_hash'],
            $memberArr['auth_key'],
            $memberArr['password_reset_token'],
            $memberArr['access_token'],
            $memberArr['refresh_token']
        );

        // 账户 & 等级信息（B2C/B2B2C 场景）
        if (Yii::$app->services->devPattern->isB2C()) {
            $result['member'] = array_merge(
                $memberArr,
                [
                    'account'     => ArrayHelper::toArray($member->account),
                    'memberLevel' => ArrayHelper::toArray($member->memberLevel),
                ]
            );
        } elseif(Yii::$app->services->devPattern->isB2B2C()){
            $result['member'] = array_merge(
                $memberArr,
                [
                    'account'     => ArrayHelper::toArray($member->account),
                ]
            );
        }else {
            $result['member'] = $memberArr;
        }

        // 缓存令牌
        if ($this->cache) {
            Yii::$app->cache->set($this->getCacheKey($model->access_token), $model, $this->timeout);
        }

        return $result;
    }

    /* -------------------- 查询 / 禁用 -------------------- */

    /**
     * 缓存读取令牌
     * @param string $token
     * @param string $type 兼容旧参数，可忽略
     * @param bool $forceCache 强制读缓存
     * @return AccessToken|null
     */
    public function getTokenToCache(string $token, string $type = '', bool $forceCache = false): ?AccessToken
    {
        if (!$forceCache && !$this->cache) {
            return $this->findByAccessToken($token);
        }

        $key = $this->getCacheKey($token);
        if (!($model = Yii::$app->cache->get($key))) {
            $model = $this->findByAccessToken($token);
            Yii::$app->cache->set($key, $model, $this->timeout);
        }

        return $model;
    }

    /**
     * 禁用令牌
     * @param string $access_token
     * @return bool
     */
    public function disableByAccessToken(string $access_token): bool
    {
        if ($this->cache) {
            Yii::$app->cache->delete($this->getCacheKey($access_token));
        }

        if (!($model = $this->findByAccessToken($access_token))) {
            return false;
        }

        $model->status = StatusEnum::DISABLED;
        return (bool) $model->save();
    }

    /**
     * 根据 access_token 查找记录
     * @param string $token
     * @return AccessToken|null
     */
    public function findByAccessToken(string $token): ?AccessToken
    {
        // B2B2C 模式下按商户隔离，其余按全局
        $merchantId = Yii::$app->params['devPattern'] === DevPatternEnum::B2B2C
            ? (Yii::$app->user->identity->merchant_id ?? '')
            : '';

        return AccessToken::find()
            ->where(['access_token' => $token, 'status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => $merchantId])
            ->one();
    }

    /* -------------------- 内部辅助 -------------------- */

    /**
     * 缓存键前缀
     * @param string $access_token
     * @return string
     */
    protected function getCacheKey(string $access_token): string
    {
        return CacheEnum::getPrefix('apiAccessToken') . $access_token;
    }

    /**
     * 查找或实例化令牌模型
     * @param int $member_id
     * @param string $group
     * @return AccessToken
     */
    protected function findModel(int $member_id, string $group): AccessToken
    {
        $model = AccessToken::find()
            ->where(['member_id' => $member_id, 'group' => $group])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->one();

        if (!$model) {
            $model = new AccessToken();
            $model->loadDefaultValues();
        }

        return $model;
    }

    /**
     * 记录最后登录时间（按模式分发）
     * @param \yii\db\ActiveRecord $member
     */
    private function recordLastLogin(ActiveRecord $member): void
    {
        $dev = Yii::$app->services->devPattern;

        if ($dev->isB2B2C()) {
            Yii::$app->merchantsService->member->lastLogin($member);
        } elseif ($dev->isSAAS()) {
            Yii::$app->services->backendMember->lastLogin($member);
        } elseif ($dev->isB2C()) {
            Yii::$app->memberService->member->lastLogin($member);
        }
    }
}