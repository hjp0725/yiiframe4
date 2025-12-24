<?php
namespace api\modules\v1\controllers;

use common\enums\AppEnum;
use Yii;
use api\controllers\OnAuthController;
use yiiframe\plugs\common\AddonHelper;

/**
 * 公用配置
 *
 * Class ConfigController
 * @package api\modules\v1\controllers\common
 * @author jianyan74 <751393839@qq.com>
 */
class ConfigController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 不用进行登录验证的方法
     *
     * 例如： ['index', 'update', 'create', 'view', 'delete']
     * 默认全部需要验证
     *
     * @var array
     */
    protected $authOptional = ['index','setting'];

    /**
     * @return array|\yii\data\ActiveDataProvider
     */
    public function actionIndex()
    {
        if (Yii::$app->services->devPattern->isB2B2C())
            return Yii::$app->debris->getAllInfo(true, AppEnum::MERCHANT,  Yii::$app->user->identity->merchant_id)[Yii::$app->request->get('field')];
        else
            return Yii::$app->debris->backendConfigAll(true)[Yii::$app->request->get('field')];
    }
    public function actionSetting()
    {
        if (Yii::$app->services->devPattern->isB2B2C()){
            return Yii::$app->debris->addonConfig('Merchants');
        }
    }
}