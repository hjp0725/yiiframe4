<?php

namespace backend\modules\common\controllers;

use Yii;
use common\traits\Curd;
use common\models\base\SearchModel;
use common\models\common\MenuCate;
use common\enums\StatusEnum;
use common\enums\AppEnum;
use backend\controllers\BaseController;

class MenuCateController extends BaseController
{
    use Curd;

    public $modelClass = MenuCate::class;

    /**
     * 首页
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['title'],
            'defaultOrder' => [
                'sort' => SORT_ASC,
                'id' => SORT_ASC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['app_id' => AppEnum::BACKEND]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'cates' => Yii::$app->services->menuCate->findDefault(AppEnum::BACKEND),
        ]);
    }

    /**
     * 添加创建方法（如果需要）
     */
    public function actionCreate()
    {
        // 由于使用了 Curd trait，可能已经有基础的 CRUD 方法
        // 如果需要自定义创建逻辑，可以重写此方法
    }

    /**
     * 添加更新方法（如果需要）
     */
    public function actionUpdate($id)
    {
        // 自定义更新逻辑
    }
}