<?php 

namespace common\traits;

use Yii;
use yii\data\Pagination;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use common\helpers\ResultHelper;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;

/**
 * Trait MerchantCurd
 * @property ActiveRecord|Model $modelClass
 * @package common\traits
 */
trait MerchantCurd
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

    /**
     * 首页
     */
    public function actionIndex()
    {
        $query = $this->modelClass::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['merchant_id' => Yii::$app->user->identity->merchant_id]);

        $pages = new Pagination([
            'totalCount' => $query->count(),
            'pageSize' => $this->pageSize,
        ]);

        $models = $query->offset($pages->offset)
            ->orderBy(['id' => SORT_DESC])
            ->limit($pages->limit)
            ->all();

        return $this->render($this->action->id, [
            'models' => $models,
            'pages' => $pages,
        ]);
    }

    /**
     * 编辑/创建
     */
    public function actionEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->referrer();
        }

        return $this->render($this->action->id, ['model' => $model]);
    }

    /**
     * 伪删除
     */
    public function actionDestroy($id): \yii\web\Response
    {
        $model = $this->modelClass::findOne($id);
        if ($model === null) {
            return $this->message('找不到数据', $this->redirect(Yii::$app->request->referrer), 'error');
        }

        $model->status = StatusEnum::DELETE;
        return $model->save()
            ? $this->message('删除成功', $this->redirect(Yii::$app->request->referrer))
            : $this->message('删除失败', $this->redirect(Yii::$app->request->referrer), 'error');
    }

    /**
     * 物理删除
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id): \yii\web\Response
    {
        return $this->findModel($id)->delete()
            ? $this->message('删除成功', $this->redirect(Yii::$app->request->referrer))
            : $this->message('删除失败', $this->redirect(Yii::$app->request->referrer), 'error');
    }

    /**
     * Ajax 更新排序/状态
     */
    public function actionAjaxUpdate($id)
    {
        $model = $this->modelClass::findOne($id);
        if ($model === null) {
            return ResultHelper::json(404, '找不到数据');
        }

        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status']);
        if (!$model->save()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return ResultHelper::json(200, '修改成功');
    }

    /**
     * Ajax 编辑/创建
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        // 表单异步验证
        $this->activeFormValidate($model);

        if ($model->load(Yii::$app->request->post())) {
            return $model->save()
                ? $this->redirect(Yii::$app->request->referrer)
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, ['model' => $model]);
    }

    /**
     * 查找或实例化模型
     */
    protected function findModel($id): ActiveRecord
    {
        /* @var ActiveRecord $model */
        if ($id && ($model = $this->modelClass::findOne(['id' => $id, 'merchant_id' => Yii::$app->user->identity->merchant_id])) !== null) {
            return $model;
        }

        $model = new $this->modelClass();
        return $model->loadDefaultValues();
    }
}