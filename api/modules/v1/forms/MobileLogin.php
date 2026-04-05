<?php 

namespace api\modules\v1\forms;

use addons\Alisms\common\models\SmsLog;
use common\enums\AccessTokenGroupEnum;
use common\enums\StatusEnum;
use common\helpers\RegularHelper;
use Yii;
use yii\base\Model;

/**
 * Class MobileLogin
 * @package api\modules\v1\forms
 */
class MobileLogin extends Model
{
    /**
     * @var string
     */
    public $mobile;

    /**
     * @var string
     */
    public $code;

    /**
     * @var string
     */
    public $group;

    /**
     * @var \yii\db\ActiveRecord|null
     */
    protected $_user;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['mobile', 'code', 'group'], 'required'],
            ['code', '\addons\Alisms\common\models\validators\SmsCodeValidator', 'usage' => SmsLog::USAGE_LOGIN],
            ['code', 'filter', 'filter' => 'trim'],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号'],
            ['mobile', 'validateMobile'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'mobile' => '手机号码',
            'code'   => '验证码',
            'group'  => '组别',
        ];
    }

    /**
     * 验证手机号是否存在
     */
    public function validateMobile($attribute): void
    {
        if (!$this->getUser()) {
            $this->addError($attribute, '找不到用户');
        }
    }

    /**
     * 获取用户（缓存）
     * @return \yii\db\ActiveRecord|null
     */
    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = Yii::$app->services->devPattern->member()::findOne([
                'mobile' => $this->mobile,
                'status' => StatusEnum::ENABLED,
            ]);
        }
        return $this->_user;
    }
}