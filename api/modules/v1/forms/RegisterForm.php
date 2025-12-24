<?php 

namespace api\modules\v1\forms;

use addons\Alisms\common\models\SmsLog;
use addons\Alisms\common\models\validators\SmsCodeValidator;
use common\enums\AccessTokenGroupEnum;
use common\helpers\RegularHelper;
use Yii;
use yii\base\Model;

/**
 * Class RegisterForm
 * @package api\modules\v1\forms
 */
class RegisterForm extends Model
{
    
    public $mobile;
    public $password;
    public $password_repetition;
    public $code;
    public $group;
    public $title;
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        $memberModel = Yii::$app->services->devPattern->member();

        return [
            [['mobile', 'group', 'code', 'password', 'password_repetition', 'title'], 'required'],
            [['title'], 'string'],
            [['password'], 'string', 'min' => 6],
            [
                ['mobile'],
                'unique',
                'targetClass'     => $memberModel,
                'targetAttribute' => 'mobile',
                'message'         => '此{attribute}已存在。',
            ],
            ['code', SmsCodeValidator::class, 'usage' => SmsLog::USAGE_REGISTER],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号码'],
            [['password_repetition'], 'compare', 'compareAttribute' => 'password'], // 验证新密码和重复密码是否相等
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'mobile'              => '手机号码',
            'title'            => '姓名',
            'password'            => '密码',
            'password_repetition' => '重复密码',
            'group'               => '类型',
            'code'                => '验证码',
        ];
    }
}