<?php
namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * 自动填充 member_id / merchant_id
 *
 * 用法：
 *   public function behaviors()
 *   {
 *       return [
 *           AutoFillMemberBehavior::class,
 *       ];
 *   }
 */
class AutoFillMemberBehavior extends Behavior
{
    /** @var string 对应表字段 */
    public $memberAttribute = 'member_id';
    public $merchantAttribute = 'merchant_id';

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }

    public function beforeInsert()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            return;           // 未登录不处理
        }

        // 1. 会员 ID
        if ($this->owner->hasAttribute($this->memberAttribute)) {
            // 优先取 member_id，没有再取 id
            $this->owner->{$this->memberAttribute} = $user->member_id ?? $user->id;
        }

        // 2. 企业 ID
        if ($this->owner->hasAttribute($this->merchantAttribute)) {
            $this->owner->{$this->merchantAttribute} = $user->merchant_id ?? 0;   // B2C 兜底 1
        }
    }
}