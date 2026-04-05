<?php 

namespace services\merapi;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UnprocessableEntityHttpException;
use common\enums\CacheEnum;
use common\enums\StatusEnum;
use common\enums\MerchantStateEnum;
use common\helpers\ArrayHelper;
use common\models\merapi\AccessToken;
use common\components\Service;
use addons\Merchants\common\models\Member;
use addons\Merchants\common\models\Merchant;

/**
 * 商户 API AccessToken 业务层
 *
 * @package services\merapi
 * @author  jianyan74 <751393839@qq.com>
 */
class AccessTokenService extends Service
{
    /** @var bool 是否写入缓存 */
    public $cache = false;

    /** @var int 缓存过期时间（秒） */
    public $timeout;

    /* -------------------- 颁发 / 刷新令牌 -------------------- */

    /**
     * 颁发或刷新 access_token / refresh_token
     * @param Member $member
     * @param string $group
     * @param int $cycle 重试次数
     * @return array
     * @throws UnprocessableEntityHttpException
     */
    public function getAccessToken(Member $member, string $group, int $cycle = 1): array
    {
        $model = $this->findModel($member->id, $group);

        // 覆盖新数据
        $model->member_id   = $member->id;
        $model->merchant_id = $member->merchant_id;
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
            'refresh_token'   => $model->refresh_token,
            'access_token'    => $model->access_token,
            'expiration_time' => (int) (Yii::$app->params['user.accessTokenExpire'] ?? 7200),
        ];

        // 最后登录时间
        Yii::$app->merchantsService->member->lastLogin($member);

        // 商户状态检查
        $this->checkMerchantStatus($member);

        // 脱敏会员数据
        $memberArr = ArrayHelper::toArray($member);
        unset(
            $memberArr['password_hash'],
            $memberArr['auth_key'],
            $memberArr['password_reset_token'],
            $memberArr['access_token'],
            $memberArr['refresh_token']
        );

        $result['member'] = [
            ...$memberArr,
            'merchant' => ArrayHelper::toArray($member->merchant),
            'account'  => ArrayHelper::toArray($member->account),
        ];

        // 写入缓存
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
     * @return AccessToken|null
     */
    public function getTokenToCache(string $token, string $type = ''): ?AccessToken
    {
        if (!$this->cache) {
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
     * 禁用指定令牌
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
        return AccessToken::find()
            ->where(['access_token' => $token, 'status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
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
        return CacheEnum::getPrefix('merapiAccessToken') . $access_token;
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
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->one();

        if (!$model) {
            $model = new AccessToken();
            $model->loadDefaultValues();
        }

        return $model;
    }

    /**
     * 商户状态检查
     * @param Member $member
     * @throws UnprocessableEntityHttpException
     */
    private function checkMerchantStatus(Member $member): void
    {
        $merchant = $member->merchant;
        if (!$merchant) {
            throw new UnprocessableEntityHttpException('所属企业不存在');
        }

        if ($merchant->status == StatusEnum::DELETE) {
            throw new UnprocessableEntityHttpException('所属企业已被删除');
        }

        if ($merchant->state == MerchantStateEnum::DISABLED) {
            throw new UnprocessableEntityHttpException('所属企业未通过审核');
        }

        if ($merchant->state == MerchantStateEnum::AUDIT) {
            throw new UnprocessableEntityHttpException('所属企业正在审核');
        }
    }
}