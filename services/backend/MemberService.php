<?php 

namespace services\backend;

use common\components\Service;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\models\backend\Member;
use common\models\rbac\AuthRole;
use Yii;

/**
 * Class MemberService
 * @package services\backend
 * @author jianyan74 <751393839@qq.com>
 */
class MemberService extends Service
{
    /**
     * @var Member|null
     */
    protected $member;

    /**
     * 注入当前操作会员
     */
    public function set(Member $member): self
    {
        $this->member = $member;
        return $this;
    }

    /**
     * 按 id 获取会员（带缓存）
     */
    public function get($id): ?Member
    {
        if (!$this->member || (int)$this->member['id'] !== (int)$id) {
            $this->member = $this->findById($id);
        }
        return $this->member;
    }

    /* ---------- 只读查询 ---------- */
    public function getName($id): string
    {
        $user = Member::findOne($id);
        return $user ? $user->title : '';
    }

    public function getNames(array $ids = []): string
    {
        $str = '';
        foreach ($ids as $id) {
            $user = Member::findOne($id);
            if ($user) {
                $str .= $user->title . ' ';
            }
        }
        return trim($str);
    }

    public function getMember($id): ?Member
    {
        return Member::findOne($id);
    }

    public function getRoleTitle($id): string
    {
        $role = AuthRole::findOne($id);
        return $role ? $role->title : '';
    }

    public function getMemberIDByRoleId(int $member_id, int $role_id): ?string
    {
        $user = Member::findOne($member_id);
        if (!$user || !$user->department_id) {
            return null;
        }
        return Member::find()
            ->select(['id'])
            ->where(['department_id' => $user->department_id, 'role_id' => $role_id])
            ->scalar();
    }

    public function getLeaderIDByMemberId(int $member_id): ?int
    {
        $user = Member::findOne($member_id);
        if (!$user || !$user->department) {
            return null;
        }
        return (int)$user->department['department_leader'];
    }

    public function getMap(): array
    {
        return ArrayHelper::map($this->findAll(), 'id', 'title');
    }

    /* ---------- 列表/单条 ---------- */
    public function findAll(): array
    {
        return Member::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->asArray()
            ->all();
    }

    public function findById(int $id): ?Member
    {
        return Member::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->one();
    }

    public function findByIdWithAssignment(int $id): ?Member
    {
        return Member::find()
            ->where(['id' => $id])
            ->with('assignment')
            ->one();
    }

    /* ---------- 写操作 ---------- */
    public function lastLogin(Member $member): void
    {
        $member->visit_count += 1;
        $member->last_time    = time();
        $member->last_ip      = Yii::$app->request->getUserIP();
        $member->save();
    }
}