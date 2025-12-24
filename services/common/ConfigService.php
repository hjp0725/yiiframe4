<?php 

namespace services\common;

use Yii;
use yii\helpers\Json;
use yii\db\ActiveQuery;
use common\enums\StatusEnum;
use common\components\Service;
use common\models\common\Config;
use common\models\common\ConfigValue;
use common\enums\AppEnum;

/**
 * Class ConfigService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class ConfigService extends Service
{
    /**
     * 批量更新配置
     */
    public function updateAll(string $app_id, array $data): void
    {
        $merchant_id = Yii::$app->services->merchant->getNotNullId();

        $configs = Config::find()
            ->where(['in', 'name', array_keys($data)])
            ->andWhere(['app_id' => $app_id])
            ->with([
                'value' => function (ActiveQuery $q) use ($merchant_id, $app_id) {
                    $q->andWhere(['app_id' => $app_id])
                      ->andFilterWhere(['merchant_id' => $merchant_id]);
                },
            ])
            ->all();

        foreach ($configs as $cfg) {
            $val   = $data[$cfg['name']] ?? '';
            $model = $cfg->value ?? new ConfigValue();
            $model->setAttributes([
                'merchant_id' => $merchant_id,
                'config_id'   => $cfg->id,
                'app_id'      => $cfg->app_id,
                'data'        => is_array($val) ? Json::encode($val) : $val,
            ], false);
            $model->save(false);
        }

        // 刷新缓存
        if ($app_id === AppEnum::BACKEND) {
            Yii::$app->debris->backendConfigAll(true);
        } else {
            Yii::$app->debris->merchantConfigAll(true, $merchant_id);
        }
    }

    /**
     * 带配置值列表
     */
    public function findAllWithValue(string $app_id,  $merchant_id): array
    {
        return Config::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => $app_id])
            ->with([
                'value' => function (ActiveQuery $q) use ($merchant_id, $app_id) {
                    $q->andWhere(['app_id' => $app_id])
                      ->andFilterWhere(['merchant_id' => $merchant_id]);
                },
            ])
            ->asArray()
            ->all();
    }
}