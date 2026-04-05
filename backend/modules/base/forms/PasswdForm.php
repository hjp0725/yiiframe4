<?php 

namespace backend\modules\base\forms;

use Yii;
use yii\base\Model;
use common\models\backend\Member;

/**
 * 修改密码表单
 *
 * Class PasswdForm
 * @package backend\modules\base\forms
 * @author jianyan74 <751393839@qq.com>
 */
class PasswdForm extends Model
{
    public $passwd;
    public $passwd_new;
    public $passwd_repetition;

    /**
     * @var Member|null
     */
    private $_user;

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['passwd', 'passwd_new', 'passwd_repetition'], 'filter', 'filter' => 'trim'],
            [['passwd', 'passwd_new', 'passwd_repetition'], 'required'],
            [['passwd', 'passwd_new', 'passwd_repetition'], 'string', 'min' => 6, 'max' => 15],
            ['passwd_repetition', 'compare', 'compareAttribute' => 'passwd_new'],
            ['passwd', 'validatePassword'],
            ['passwd_new', 'notCompare'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'passwd'            => Yii::t('app', '旧密码'),
            'passwd_new'        => Yii::t('app', '新密码'),
            'passwd_repetition' => Yii::t('app', '重复密码'),
        ];
    }

    /* ------------------ 验证器 ------------------ */

    /**
     * 新密码不能与旧密码相同
     */
    public function notCompare($attribute): void
    {
        if ($this->passwd === $this->passwd_new) {
            $this->addError($attribute, '新密码不能和原密码相同');
        }
    }

    /**
     * 验证原密码
     */
    public function validatePassword($attribute, $params): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->passwd)) {
                $this->addError($attribute, '原密码不正确');
            }
        }
    }

    /* ------------------ 私有方法 ------------------ */

    /**
     * 获取当前登录用户模型
     */
    protected function getUser(): ?Member
    {
        if ($this->_user === null) {
            $identity = Yii::$app->user->identity;
            $this->_user = Member::findByUsername($identity->username);
        }
        return $this->_user;
    }
}