<?php 

namespace common\traits;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;
use common\enums\StatusEnum;

/**
 * 通用增删改查（Curd）复用逻辑
 *
 * @property string $modelClass  AR 类名
 * @property int    $pageSize    分页大小
 *
 * @package common\traits
 */
trait Curd
{
    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if ($this->modelClass === null) {
            throw new InvalidConfigException('"modelClass" 属性必须设置.');
        }
    }

    /* -------------------- 列表 -------------------- */

    /**
     * 首页
     */
    public function actionIndex()
    {
        $searchModel = new \common\models\base\SearchModel([
            'model'                  => $this->modelClass,
            'scenario'               => 'default',
            'relations' => ['member' => ['title']], 
            'partialMatchAttributes' => ['title','member.title'],
            'defaultOrder'           => ['id' => SORT_DESC],
            'pageSize'               => $this->pageSize,
        ]);
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andFilterWhere([$this->modelClass::tableName() .'.merchant_id'=>Yii::$app->user->identity->merchant_id]);
        if (!Yii::$app->services->auth->isSuperAdmin()&&!Yii::$app->services->auth->isSystemAdmin()) {
            $dataProvider->query->andWhere([$this->modelClass::tableName(). '.member_id' => Yii::$app->user->id]);
        }
        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel'  => $searchModel,
        ]);
    }

    /* -------------------- 新增/编辑 -------------------- */

    /**
     * 创建
     */
    public function actionCreate()
    {
        $model = $this->findModel(Yii::$app->request->get('id'));
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->referrer();
        }
        return $this->render($this->action->id, ['model' => $model]);
    }

    /**
     * 编辑
     */
    public function actionEdit()
    {
        $model = $this->findModel(Yii::$app->request->get('id'));
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->referrer();
        }
        return $this->render($this->action->id, ['model' => $model]);
    }

    /* -------------------- 删除 -------------------- */

    /**
     * 伪删除（状态改为 DELETE）
     */
    public function actionDestroy(string $id)
    {
        $model = $this->findModel($id);
        $model->status = StatusEnum::DELETE;
        return $model->save()
            ? $this->message('删除成功', $this->redirect(Yii::$app->request->referrer))
            : $this->message('删除失败', $this->redirect(Yii::$app->request->referrer), 'error');
    }

    /**
     * 物理删除
     */
    public function actionDelete(string $id)
    {
        return $this->findModel($id)->delete()
            ? $this->message('删除成功', $this->redirect(Yii::$app->request->referrer))
            : $this->message('删除失败', $this->redirect(Yii::$app->request->referrer), 'error');
    }

    /**
     * 批量物理删除
     */
    public function actionDeleteAll()
    {
        $ids = Yii::$app->request->post('ids', []);
        if (!$ids) {
            return ResultHelper::json(422, '请至少选择一条记录');
        }
        $this->modelClass::deleteAll(['in', 'id', $ids]);
        return ResultHelper::json(200, '批量操作成功');
    }

    /* -------------------- AJAX 快捷修改 -------------------- */

    /**
     * 排序/状态一键修改
     */
    public function actionAjaxUpdate(string $id)
    {
        $model = $this->findModel($id);
        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status']);
        return $model->save()
            ? ResultHelper::json(200, '修改成功')
            : ResultHelper::json(422, $this->getError($model));
    }

    /**
     * AJAX 新增/编辑
     */
    public function actionAjaxEdit()
    {
        $model = $this->findModel(Yii::$app->request->get('id'));
        $this->activeFormValidate($model);

        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->redirect(Yii::$app->request->referrer)
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, ['model' => $model]);
    }
    /**
     * 导出
     */
    public function actionExport()
    {
        $result = Yii::createObject($this->modelClass.'Export'::class)->export(Yii::$app->request->get());
        if ($result === false) {
            return $this->message('没有可导出的数据', $this->redirect(Yii::$app->request->referrer));
        }
    }
    /* -------------------- 辅助 -------------------- */

    /**
     * 返回模型
     */
    protected function findModel(?string $id): ActiveRecord
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if ($id === null || ($model = $class::findOne($id)) === null) {
            $model = new $class();
            $model->loadDefaultValues();
        }
        return $model;
    }
}