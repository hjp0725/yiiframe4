<?php 

namespace backend\modules\base\forms;

use common\models\backend\Member;
use common\models\rbac\AuthRole;
use Yii;
use yii\base\Model;
use yii\web\NotFoundHttpException;

/**
 * Class MemberForm
 * @package backend\modules\base\forms
 * @author jianyan74 <751393839@qq.com>
 */
class MemberForm extends Model
{
    public $id;
    public $password;
    public $username;
    public $mobile;
    public $title;
    public $role_id;
    public $department_id;

    /**
     * @var Member
     */
    protected $member;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['password', 'username', 'title', 'mobile'], 'required'],
            ['password', 'string', 'min' => 6],
            [
                ['role_id'],
                'exist',
                'skipOnError'     => true,
                'targetClass'     => AuthRole::class,
                'targetAttribute' => ['role_id' => 'id'],
            ],
            [['username'], 'isUnique'],
            [['role_id'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'password'      => Yii::t('app', '密码'),
            'username'      => Yii::t('app', '用户名'),
            'title'      => Yii::t('app', '姓名'),
            'mobile'        => Yii::t('app', '手机'),
            'role_id'       => Yii::t('app', '角色'),
            'department_id' => Yii::t('app', '部门'),
        ];
    }

    /**
     * 加载默认数据
     */
    public function loadData(): void
    {
        $this->member = Yii::$app->services->backendMember->findByIdWithAssignment((int)$this->id)
            ?: new Member();

        $this->title     = $this->member->title;
        $this->mobile       = $this->member->mobile;
        $this->password     = $this->member->password_hash;
        $this->department_id = $this->member->department_id;
        $this->role_id       = $this->member->assignment->role_id ?? '';
    }

    /**
     * 场景
     */
    public function scenarios(): array
    {
        return [
            'default'    => ['username', 'password'],
            'generalAdmin' => array_keys($this->attributeLabels()),
        ];
    }

    /**
     * 验证用户名称
     */
    public function isUnique(): void
    {
        $exist = Member::findOne(['username' => $this->username]);
        if ($exist && (int)$exist->id !== (int)$this->id) {
            $this->addError('username', '用户名称已经被占用');
        }
    }

    /**
     * 保存入口（事务）
     * @return bool
     */
    public function save(): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $member = $this->member;

            if ($member->isNewRecord) {
                $member->last_ip   = '0.0.0.0';
                $member->last_time = time();
            }

            $member->title     = $this->title;
            $member->mobile       = $this->mobile;
            $member->department_id = $this->department_id;
            $member->role_id       = $this->role_id;

            // 密码被修改时重新哈希
            if ($member->password_hash !== $this->password) {
                $member->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            }

            if (!$member->save()) {
                $this->addErrors($member->getErrors());
                throw new NotFoundHttpException('用户编辑错误');
            }

            // 超级管理员不重新分配角色
            if ((int)$this->id === (int)(Yii::$app->params['adminAccount'] ?? 0)) {
                $transaction->commit();
                return true;
            }

            // 分配角色
            Yii::$app->services->rbacAuthAssignment->assign([$this->role_id], $member->id, Yii::$app->id);

            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }
}