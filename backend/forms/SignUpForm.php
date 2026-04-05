<?php 

namespace backend\forms;

use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\web\NotFoundHttpException;
use common\enums\StatusEnum;
use addons\Member\common\models\Member;
/**
 * Class SignUpForm
 * @package backend\forms
 * @author jianyan74 <751393839@qq.com>
 */
class SignUpForm extends Model
{
    public $id;
    public $username;
    public $title;
    public $mobile;
    public $password;
    public $password_repetition;
    public $rememberMe;
    public $group;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['rememberMe'], 'isRequired'],
            [['title', 'mobile', 'password', 'password_repetition'], 'required'],
            [['mobile', 'title', 'group'], 'string', 'min' => 2, 'max' => 15],
            [
                'mobile',
                'unique',
                'targetClass' => Member::class,
                'filter'      => function (ActiveQuery $q) {
                    return $q->andWhere(['>=', 'status', StatusEnum::DISABLED]);
                },
                'message' => '该手机号码已经被占用.',
            ],
            ['mobile', 'match', 'pattern' => '/^1[3456789]\d{9}$/', 'message' => '手机号码格式不正确'],
            [
                'username',
                'unique',
                'targetClass' => Member::class,
                'filter'      => function (ActiveQuery $q) {
                    return $q->andWhere(['>=', 'status', StatusEnum::DISABLED]);
                },
                'message' => '该用户名已经被占用了.',
            ],
            [['password', 'password_repetition'], 'string', 'min' => 6, 'max' => 20],
            ['password_repetition', 'compare', 'compareAttribute' => 'password', 'message' => '两次输入的密码不一致'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'username'            => '账号',
            'title'            => '姓名',
            'mobile'              => '手机号码',
            'password'            => '账户密码',
            'password_repetition' => '确认密码',
            'rememberMe'          => '',
        ];
    }

    /**
     * 用户协议勾选校验
     */
    public function isRequired($attribute): void
    {
        if (empty($this->rememberMe)) {
            $this->addError($attribute, '请同意用户协议');
        }
    }

    /**
     * 注册入口（事务）
     * @return Member|false
     */
    public function register()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member = new Member();
            $member->mobile       = $this->mobile;
            $member->title     = $this->title;
            $member->password_hash = Yii::$app->security->generatePasswordHash($this->password);

            if (!$member->save()) {
                $this->addErrors($member->getErrors());
                throw new NotFoundHttpException('用户信息编辑错误');
            }

            $transaction->commit();
            return $member;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }
}