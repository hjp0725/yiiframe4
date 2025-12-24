<?php
namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use common\helpers\StringHelper;

/**
 * 仅对 backend_member 表生效：
 * 1. 插入时自动写 member_id / merchant_id
 * 2. 空 username 且存在 mobile 时自动生成
 * 3. 空 auth_key 时自动生成
 */
class MemberAutoFillBehavior extends Behavior
{
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }

    public function beforeInsert()
    {
        $user = Yii::$app->user->identity ?? null;
        if (!$user) {
            return;
        }

        // 1. 会员 & 企业
        if ($this->owner->hasAttribute('member_id')) {
            $this->owner->member_id = $user->member_id ?? $user->id;
        }
        if ($this->owner->hasAttribute('merchant_id')) {
            $this->owner->merchant_id = $user->merchant_id ?? 0;
        }

        // 2. auth_key
        if ($this->owner->hasAttribute('auth_key') && empty($this->owner->auth_key)) {
            $this->owner->auth_key = Yii::$app->security->generateRandomString();
        }

        // 3. username 生成（mobile 存在且 username 为空）
        if ($this->owner->hasAttribute('username')
            && $this->owner->hasAttribute('mobile')
            && empty($this->owner->username)
            && !empty($this->owner->mobile)) {
            $this->owner->username = StringHelper::random(5) . '_' . substr($this->owner->mobile, -4);
        }
    }
}