<?php
namespace common\traits;

use Yii;
/**
 * Trait HasOneMember
 * @package common\traits
 */
trait HasOneMember
{
    /**
     * 用户信息
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBaseMember()
    {
        
        return $this->hasOne(Yii::$app->services->devPattern->member(), ['id' => 'department_leader'])->select(['id', 'title', 'mobile', 'head_portrait']);
        
    }
}