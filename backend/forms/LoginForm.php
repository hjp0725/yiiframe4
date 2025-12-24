<?php 
namespace backend\forms;

use Yii;
use yii\base\InvalidConfigException;
use common\helpers\StringHelper;
use common\models\backend\Member;
use common\models\forms\LoginForm as BaseLoginForm;

/**
 * 后台登录表单
 *
 * @package backend\forms
 * @author  jianyan74 <751393839@qq.com>
 */
class LoginForm extends BaseLoginForm
{
    /** @var string 验证码 */
    public $verifyCode;

    /** @var int 失败多少次后出验证码 */
    public $attempts = 3;

    /** @var bool 记住我 */
    public $rememberMe = true;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['username', 'password'], 'required'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
            ['password', 'validateIp'],
            ['verifyCode', 'captcha', 'on' => 'captchaRequired'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'username'   => Yii::t('app', '用户名'),
            'rememberMe' => Yii::t('app', '记住我'),
            'password'   => Yii::t('app', '密码'),
            'verifyCode' => Yii::t('app', '验证码'),
        ];
    }

    /**
     * IP 白名单校验
     * @param string $attribute
     * @throws InvalidConfigException
     */
    public function validateIp(string $attribute): void
    {
        $ip      = Yii::$app->request->userIP;
        $allowIp = Yii::$app->debris->backendConfig('sys_allow_ip');
        if ($allowIp === null || $allowIp === '') {
            return;
        }

        $ipList = StringHelper::parseAttr($allowIp);
        if (!in_array($ip, $ipList, true)) {
            $this->addError($attribute, '登录失败，请联系管理员');
        }
    }

    /**
     * 获取用户实例
     * @return Member|null
     */
    public function getUser(): ?Member
    {
        if ($this->_user == false) {
            $this->_user = Member::findByUsername($this->username);
        }
        return $this->_user;
    }

    /**
     * 是否需要验证码
     */
    public function loginCaptchaRequired(): void
    {
        if ((int)Yii::$app->session->get('loginCaptchaRequired', 0) >= $this->attempts) {
            $this->setScenario('captchaRequired');
        }
    }

    /**
     * 登录
     * @return bool
     * @throws InvalidConfigException
     */
    public function login(): bool
    {
        if ($this->validate() && Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0)) {
            Yii::$app->session->remove('loginCaptchaRequired');
            return true;
        }

        $counter = (int)Yii::$app->session->get('loginCaptchaRequired', 0) + 1;
        Yii::$app->session->set('loginCaptchaRequired', $counter);
        return false;
    }
}