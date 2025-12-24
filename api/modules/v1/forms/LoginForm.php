<?php 

namespace api\modules\v1\forms;

use Yii;
use common\enums\StatusEnum;
use common\enums\AccessTokenGroupEnum;

/**
 * API 登录表单
 *
 * @package api\modules\v1\forms
 * @author  jianyan74 <751393839@qq.com>
 */
class LoginForm extends \common\models\forms\LoginForm
{
    /** @var string 登录分组 */
    public $group;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['mobile', 'password', 'group'], 'required'],
            ['password', 'validatePassword'],
            ['group', 'in', 'range' => AccessTokenGroupEnum::getKeys()],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'mobile'   => '登录帐号',
            'password' => '登录密码',
            'group'    => '组别',
        ];
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