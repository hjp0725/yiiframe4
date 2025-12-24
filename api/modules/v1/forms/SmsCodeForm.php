<?php 

namespace api\modules\v1\forms;

use Yii;
use yii\base\Model;
use common\helpers\RegularHelper;
use yiiframe\plugs\common\AddonHelper;
use addons\Alisms\common\models\SmsLog;

/**
 * 发送短信验证码表单
 *
 * Class SmsCodeForm
 * @package api\modules\v1\forms
 */
class SmsCodeForm extends Model
{
    public $mobile;
    public $usage;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['mobile', 'usage'], 'required'],
            ['usage', 'in', 'range' => array_keys(SmsLog::$usageExplain)],
            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '请输入正确的手机号'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'mobile' => '手机号码',
            'usage'  => '用途',
        ];
    }

    /**
     * 发送验证码
     * @return bool
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function send(): bool
    {
        $code = (string)rand(1000, 9999);

        // 未安装 Alisms 直接返回 false
        if (!AddonHelper::isInstall('Alisms')) {
            return false;
        }

        return Yii::$app->alismsService->sms->send($this->mobile, $code, $this->usage);
    }
}