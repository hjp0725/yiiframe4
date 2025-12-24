<?php 

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\UnprocessableEntityHttpException;
use common\helpers\EncryptionHelper;
use common\models\forms\SignAuthForm;

/**
 * HTTP 签名验证行为
 *
 * @package common\behaviors
 * @author  jianyan74 <751393839@qq.com>
 */
class HttpSignAuth extends Behavior
{
    /** @var string[] 方法白名单 */
    public $optional = [];

    /**
     * @return string[]
     */
    public function events(): array
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * @param \yii\base\ActionEvent $event
     * @return bool
     * @throws UnprocessableEntityHttpException
     */
    public function beforeAction($event): bool
    {
        $actionId = Yii::$app->controller->action->id;
        if (in_array($actionId, $this->optional, true)) {
            return true;
        }

        $params = Yii::$app->request->get();
        $model  = new SignAuthForm();
        $model->setAttributes($params);

        if (!$model->validate()) {
            throw new UnprocessableEntityHttpException(
                Yii::$app->debris->analyErr($model->getFirstErrors())
            );
        }

        // 校验签名
        return EncryptionHelper::decodeUrlParam($params, $model->appSecret);
    }
}