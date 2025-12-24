<?php

namespace backend\modules\common\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\base\ExitException;
use yii\web\Response;
use yii\db\ActiveRecord;
use Throwable;
use yii\db\StaleObjectException;
use common\traits\Curd;
use common\models\common\Menu;
use common\enums\AppEnum;
use backend\controllers\BaseController;

/**
 * Class MenuController
 * @package backend\modules\base\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class MenuController extends BaseController
{
    use Curd;

    /**
     * @var ActiveRecord|string
     */
    public  $modelClass = Menu::class;

    /**
     * Lists all Tree models.
     * 
     * @return string
     */
    public function actionIndex(): string
    {
        $cateId = Yii::$app->request->get('cate_id', Yii::$app->services->menuCate->findFirstId(AppEnum::BACKEND));

        $query = $this->modelClass::find()
            ->orderBy('sort ASC, id ASC')
            ->filterWhere(['cate_id' => $cateId])
            ->andWhere(['app_id' => AppEnum::BACKEND]);
            
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'cates' => Yii::$app->services->menuCate->findDefault(AppEnum::BACKEND),
            'cate_id' => $cateId,
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
        $id = $request->get('id', '');
        $model = $this->findModel($id);
        $model->pid = $request->get('pid') ?? $model->pid; // 父id
        $model->cate_id = $request->get('cate_id') ?? $model->cate_id; // 分类id

        // ajax 校验
        $this->activeFormValidate($model);
        
        if ($model->load($request->post())) {
            return $model->save()
                ? $this->redirect(['index', 'cate_id' => $model->cate_id])
                : $this->message($this->getError($model), $this->redirect(['index', 'cate_id' => $model->cate_id]), 'error');
        }

        if ($model->isNewRecord && $model->parent) {
            $model->cate_id = $model->parent->cate_id;
        }

        $menuCate = Yii::$app->services->menuCate->findById($model->cate_id);

        return $this->renderAjax('ajax-edit', [
            'model' => $model,
            'cates' => Yii::$app->services->menuCate->getDefaultMap(AppEnum::BACKEND),
            'menuDropDownList' => Yii::$app->services->menu->getDropDown($menuCate, AppEnum::BACKEND, (int)$id),
        ]);
    }

    /**
     * 删除
     *
     * @param int|string $id
     * @return Response
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id): Response
    {
        $model = $this->findModel($id);
        
        if ($model->delete()) {
            return $this->message('删除成功', $this->redirect(['index', 'cate_id' => $model->cate_id]));
        }

        return $this->message('删除失败', $this->redirect(['index', 'cate_id' => $model->cate_id]), 'error');
    }
}