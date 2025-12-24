<?php 

namespace api\modules\v1\forms;

use Yii;
use common\enums\StatusEnum;
use common\enums\AccessTokenGroupEnum;
use common\helpers\RegularHelper;
use addons\Alisms\common\models\validators\SmsCodeValidator;
use addons\Alisms\common\models\SmsLog;

/**
 * 修改密码表单（短信验证码版）
 *
 * @package api\modules\v1\forms
 * @author  jianyan74 <751393839@qq.com>
 */
class UpPwdForm extends \common\models\forms\LoginForm
{
    /** @var string 手机号 */
    public $mobile;

    /** @var string 新密码 */
    public $password;

    /** @var string 重复密码 */
    public $password_repetition;

    /** @var string 短信验证码 */
    public $code;

    /** @var string 分组 */
    public $group;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['mobile', 'group', 'password', 'password_repetition'], 'required'],
            ['password', 'string', 'min' => 6],
            ['code', SmsCodeValidator::class, 'usage' => SmsLog::USAGE_UP_PWD],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号码'],
            ['password_repetition', 'compare', 'compareAttribute' => 'password'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
            ['password', 'validateMobile'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'mobile'              => '手机号码',
            'password'            => '密码',
            'password_repetition' => '重复密码',
            'group'               => '类型',
            'code'                => '验证码',
        ];
    }

    /**
     * 验证手机号是否存在
     * @param string $attribute
     */
    public function validateMobile(string $attribute): void
    {
        if (!$this->getUser()) {
            $this->addError($attribute, '找不到用户');
        }
    }

    /**
     * 根据手机号获取启用的会员
     * @return \yii\db\ActiveRecord|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $class = Yii::$app->services->devPattern->member();
            $this->_user = $class::findOne([
                'mobile' => $this->mobile,
                'status' => StatusEnum::ENABLED,
            ]);
        }

        return $this->_user;
    }
}