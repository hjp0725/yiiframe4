<?php 

namespace services\common;

use Yii;
use common\components\Service;
use common\enums\DevPatternEnum;
use common\enums\AppEnum;
use common\enums\StatusEnum;
use common\models\rbac\AuthRole;

/**
 * 开发模式
 *
 * @package services\common
 */
class DevPatternService extends Service
{
    /* -------------------- 模式判断 -------------------- */

    /**
     * 是否多企业(B2B2C)
     */
    public function isB2B2C(): bool
    {
        return Yii::$app->params['devPattern'] === DevPatternEnum::B2B2C;
    }

    /**
     * 是否单商户(B2C)
     */
    public function isB2C(): bool
    {
        return Yii::$app->params['devPattern'] === DevPatternEnum::B2C;
    }

    /**
     * 是否 SAAS
     */
    public function isSAAS(): bool
    {
        return Yii::$app->params['devPattern'] === DevPatternEnum::SAAS;
    }

    /**
     * 平台不可见（B2C 或商户端）
     */
    public function isNotPlatformFunction(): bool
    {
        return $this->isB2C() || ($this->isB2B2C() && Yii::$app->id === AppEnum::MERCHANT);
    }

    /**
     * 仅企业端功能
     */
    public function isMerchantFunction(): bool
    {
        return $this->isB2B2C() && Yii::$app->id === AppEnum::MERCHANT;
    }

    /* -------------------- 会员类映射 -------------------- */

    /**
     * 获取当前模式对应的 Member AR 类名
     */
    public static function member(): string
    {
        $pattern = Yii::$app->params['devPattern'] ?? DevPatternEnum::B2C;
        return Yii::$app->params['memberMap'][$pattern] ?? \common\models\backend\Member::class;
    }
    /**
     * 获取当前模式对应的 Department AR 类名
     */
    public static function department(): string
    {
        $pattern = Yii::$app->params['devPattern'] ?? DevPatternEnum::SAAS;
        if($pattern == DevPatternEnum::SAAS||$pattern == DevPatternEnum::B2C)
            return \common\models\backend\Department::class;
        elseif($pattern == DevPatternEnum::B2B2C&&\yiiframe\plugs\common\AddonHelper::isInstall('Merchants'))
            return \addons\Merchants\common\models\Department::class;
        else return \common\models\backend\Department::class;
    }
    //获取用户对象
    public function getMember($id)
    {
        return static::member()::findOne($id);
    }
    /**
     * 构造用户下拉数组 (id ⇒ title)
     */
    public static function getMap(): array
    {
        /** @var \yii\db\ActiveRecord $class */
        $class = static::member();
        return \common\helpers\ArrayHelper::map(
            $class::find()
                ->where(['status' => StatusEnum::ENABLED])
                ->asArray()
                ->all(),
            'id',
            'title'
        );
    }

    //获取用户名
    public function getName($id)
    {
        return static::member()::findOne($id)->title;
    }
    //获取多个用户名
    public function getNames($ids = [])
    {
        $str = '';
        foreach ($ids as $id){
            $member =  static::member()::findOne($id);
            if($member) $str .= $member->title.' ';
        }
        return $str;
    }
    //获取角色名称
    public function getRoleTitle($id)
    {
        return AuthRole::findOne($id)->title;
    }    
    //根据角色获取用户ID
    public function getMemberIDByRoleId($member_id,$role_id)
    {
        $department_id = static::member()::findOne($member_id)->department_id;
        return static::member()::find()->where(['department_id'=>$department_id,'role_id'=>$role_id])->scalar();
    }
    //根据部门获取负责人ID
    public function getLeaderIDByMemberId($member_id)
    {
        return static::member()::findOne($member_id)->department['department_leader'];
    }
    public function findByIdWithAssignment($id)
    {
        return static::member()::find()
            ->where(['id' => $id])
            ->with('assignment')
            ->one();
    }
    public function findById($id)
    {
        return static::member()::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->one();
    }
}