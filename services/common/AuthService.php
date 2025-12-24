<?php 

namespace services\common;

use Yii;
use common\enums\AppEnum;
use common\components\Service;

/**
 * 通用权限业务层
 *
 * @package services\common
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthService extends Service
{
    /**
     * 是否超级管理员
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        if (!in_array(Yii::$app->id, [AppEnum::BACKEND, AppEnum::MERCHANT, AppEnum::FRONTEND], true)) {
            return false;
        }

        return (int) Yii::$app->user->id === (int) Yii::$app->params['adminAccount'];
    }
    /**
     * 是否超级管理员
     * @return bool
     */
    public function isSystemAdmin(): bool
    {
        if (!in_array(Yii::$app->id, [AppEnum::BACKEND, AppEnum::MERCHANT, AppEnum::FRONTEND], true)) {
            return false;
        }

        return (int) Yii::$app->user->identity->type === 10;
    }
}