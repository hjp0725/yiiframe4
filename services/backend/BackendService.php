<?php 

namespace services\backend;

use common\enums\AppEnum;
use common\components\Service;
use Yii;

/**
 * Class BackendService
 * @package services\backend
 * @author jianyan74 <751393839@qq.com>
 */
class BackendService extends Service
{
    /**
     * 根据应用ID返回用户展示字符串
     * @param object $model
     * @return string
     */
    public function getUserName($model): string
    {
        if (empty($model->member)) {
            return '游客';
        }

        $id   = $model->member->id;
        $user = $model->member->username;
        $name = $model->member->title;

        switch ($model->app_id) {
            case AppEnum::BACKEND:
                return implode('<br>', [
                    'ID：' . $id,
                    Yii::t('app', '账号') . '：' . $user,
                    Yii::t('app', '姓名') . '：' . $name,
                ]);

            case AppEnum::MERCHANT:
                return implode('<br>', [
                    'ID：' . $id,
                    '账号：' . $user,
                    '姓名：' . $name,
                ]);

            case AppEnum::OAUTH2:
                $nick = $model->member->nickname ?? '';
                return implode('<br>', [
                    'ID：' . $id,
                    '账号：' . $user,
                    '昵称：' . $nick,
                    '姓名：' . $name,
                ]);

            default:
                return implode('<br>', [
                    'ID：' . $id,
                    '账号：' . $user,
                    '姓名：' . $name,
                ]);
        }
    }
}