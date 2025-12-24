<?php 

namespace api\modules\v1\forms;

use Yii;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;
use common\models\api\AccessToken;
use common\enums\AccessTokenGroupEnum;

/**
 * 刷新令牌表单
 *
 * @package api\modules\v1\forms
 * @author  jianyan74 <751393839@qq.com>
 */
class RefreshForm extends Model
{
    /** @var string 分组 */
    public $group;

    /** @var string 刷新令牌 */
    public $refresh_token;

    /** @var \yii\web\IdentityInterface|null */
    protected $_user;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['refresh_token', 'group'], 'required'],
            ['refresh_token', 'validateTime'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'refresh_token' => '重置令牌',
            'group'         => '组别',
        ];
    }

    /**
     * 验证刷新令牌有效期
     * @param string $attribute
     * @throws UnauthorizedHttpException
     */
    public function validateTime(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        // 关闭有效期检查则直接跳过
        if (!Yii::$app->params['user.refreshTokenValidity']) {
            return;
        }

        $token     = $this->refresh_token;
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire    = (int) (Yii::$app->params['user.refreshTokenExpire'] ?? 0);

        if ($timestamp + $expire <= time()) {
            throw new UnauthorizedHttpException('您的重置令牌已经过期，请重新登录');
        }

        if (!$this->getUser()) {
            throw new UnauthorizedHttpException('找不到用户');
        }
    }

    /**
     * 根据刷新令牌获取用户身份
     * @return \yii\web\IdentityInterface|null
     */
    public function getUser()
    {
        if ($this->_user !== null) {
            return $this->_user;
        }

        $apiAccount = AccessToken::findIdentityByRefreshToken($this->refresh_token, $this->group);
        if (!$apiAccount) {
            return null;
        }

        // 根据开发模式返回对应 Member 类
        $this->_user = Yii::$app->services->devPattern->member()::findIdentity($apiAccount->member_id);

        return $this->_user;
    }
}