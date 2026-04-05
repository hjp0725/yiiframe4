<?php

namespace backend\modules\common\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\Response;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\common\Config;
use common\helpers\ResultHelper;
use common\traits\Curd;
use common\enums\ConfigTypeEnum;
use common\enums\AppEnum;
use backend\controllers\BaseController;

/**
 * Class ConfigController
 * @package backend\modules\common\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class ConfigController extends BaseController
{
    use Curd;

    /**
     * @var string
     */
    public  $modelClass = Config::class;

    /**
     * 首页
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionIndex(): string
    {
        $searchModel = new SearchModel([
            'model' => Config::class,
            'scenario' => 'default',
            'partialMatchAttributes' => ['title', 'name'], // 模糊查询
            'defaultOrder' => [
                'cate_id' => SORT_ASC,
                'sort' => SORT_ASC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['app_id' => AppEnum::BACKEND])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'cateDropDownList' => Yii::$app->services->configCate->getDropDown(AppEnum::BACKEND),
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return string|Response
     * @throws ExitException
     */
    public function actionAjaxEdit()
    {
        $request = Yii::$app->request;
        $id = $request->get('id');
        $model = $this->findModel($id);
        $model->app_id = AppEnum::BACKEND;

        // ajax 校验
        $this->activeFormValidate($model);
        
        if ($model->load($request->post())) {
            return $model->save()
                ? $this->redirect($request->referrer)
                : $this->message($this->getError($model), $this->redirect($request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'configTypeList' => ConfigTypeEnum::getMap(),
            'cateDropDownList' => Yii::$app->services->configCate->getDropDown(AppEnum::BACKEND),
        ]);
    }

    /**
     * 网站设置
     *
     * @return string
     */
    public function actionEditAll(): string
    {
        return $this->render($this->action->id, [
            'cates' => Yii::$app->services->configCate->getItemsMergeForConfig(AppEnum::BACKEND),
        ]);
    }

    /**
     * ajax批量更新数据
     *
     * @return array
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionUpdateInfo(): array
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            $config = $request->post('config', []);
            Yii::$app->services->config->updateAll(Yii::$app->id, $config);
            return ResultHelper::json(200, Yii::t('app', '修改成功'));
        }

        throw new NotFoundHttpException(Yii::t('app', '请求出错'));
    }
}