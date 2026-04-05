<?php 

namespace services\common;
use Yii;
use common\components\Service;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\models\common\ConfigCate;
use yii\db\ActiveQuery;

/**
 * Class ConfigCateService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class ConfigCateService extends Service
{
    /**
     * 下拉树（一维）
     */
    public function getDropDown(string $app_id): array
    {
        $models = ArrayHelper::itemsMerge($this->findAll($app_id));
        return ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');
    }

    /**
     * 编辑用下拉（带“顶级分类”）
     */
    public function getDropDownForEdit(string $app_id,  $id = 0): array
    {
        $list = ConfigCate::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['app_id' => $app_id])
            ->andFilterWhere(['<>', 'id', $id])
            ->select(['id', 'title', 'pid', 'level'])
            ->orderBy('sort asc')
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($list);
        $data   = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        return ArrayHelper::merge([0 => Yii::t('app', '顶级分类')], $data);
    }

    /**
     * 带配置项的树（递归）
     */
    public function getItemsMergeForConfig(string $app_id): array
    {
        return ArrayHelper::itemsMerge($this->findAllWithConfig($app_id));
    }

    /**
     * 取某分类及其所有子级 ID
     */
    public function getChildIds(string $app_id, int $cate_id): array
    {
        $cates   = $this->findAll($app_id);
        $cateIds = ArrayHelper::getChildIds($cates, $cate_id);
        $cateIds[] = $cate_id;
        return $cateIds;
    }

    /**
     * 关联配置项的列表
     */
    public function findAllWithConfig(string $app_id): array
    {
        return ConfigCate::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => $app_id])
            ->orderBy('sort asc')
            ->with([
                'config' => function (ActiveQuery $query) use ($app_id) {
                    $query->andWhere(['app_id' => $app_id])
                          ->with([
                              'value' => function (ActiveQuery $q) {
                                  $q->andWhere(['merchant_id' => Yii::$app->user->identity->merchant_id]);
                              }
                          ]);
                }
            ])
            ->asArray()
            ->all();
    }

    /**
     * 所有启用的分类（二维数组）
     */
    public function findAll(string $app_id): array
    {
        return ConfigCate::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => $app_id])
            ->asArray()
            ->all();
    }
}