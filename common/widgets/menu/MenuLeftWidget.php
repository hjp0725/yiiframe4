<?php

namespace common\widgets\menu;

use Yii;
use yii\base\Widget;
use common\helpers\ArrayHelper;
use yiiframe\plugs\services\AddonsService;

/**
 * 左边菜单
 *
 * Class MenuLeftWidget
 * @package common\widgets\menu
 * @author jianyan74 <751393839@qq.com>
 */
class MenuLeftWidget extends Widget
{
    /**
     * @var string
     */
    public $app_id;

    /**
     * @return string
     */
    public function run()
    {
        empty($this->app_id) && $this->app_id = Yii::$app->id;

        return $this->render('menu-left', [
            'menus' => Yii::$app->services->menu->getOnAuthList(),
            'addons' => AddonsService::getMenus(),

        ]);
    }
}
