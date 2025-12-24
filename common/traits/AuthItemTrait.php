<?php 

namespace common\traits;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;

/**
 * 权限项（AuthItem）复用逻辑
 *
 * @property string $modelClass  AR 类名
 * @property string $appId       应用 id
 * @property string $viewPrefix  视图前缀
 *
 * @package common\traits
 * @author  jianyan74 <751393839@qq.com>
 */
trait AuthItemTrait
{
    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        foreach (['modelClass', 'appId', 'viewPrefix'] as $attr) {
            if ($this->$attr === null) {
                throw new InvalidConfigException("\"{$attr}\" 属性必须设置.");
            }
        }
    }

    /* -------------------- 列表 -------------------- */

    /**
     * 树形列表
     * @return string
     */
    public function actionIndex(): string
    {
        $query = $this->modelClass::find()
            ->where([
                'app_id'  => $this->appId,
                'is_addon'=> WhetherEnum::DISABLED,
            ])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => false,
        ]);

        return $this->render($this->viewPrefix . $this->action->id, [
            'dataProvider' => $dataProvider,
        ]);
    }

    /* -------------------- 编辑 -------------------- */

    /**
     * 新增/修改
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $id    = Yii::$app->request->get('id', '');
        /** @var ActiveRecord $model */
        $model = $this->findModel($id);

        $model->pid      = Yii::$app->request->get('pid') ?: $model->pid;
        $model->app_id   = $this->appId;
        $model->is_addon = WhetherEnum::DISABLED;

        // AJAX 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->renderAjax($this->viewPrefix . $this->action->id, [
            'model'        => $model,
            'dropDownList' => Yii::$app->services->rbacAuthItem
                ->getDropDownForEdit($this->appId, $id),
        ]);
    }

    /* -------------------- 删除 -------------------- */

    /**
     * 删除
     * @param string $id
     * @return mixed
     */
    public function actionDelete(string $id)
    {
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->message('删除成功', $this->redirect(['index']));
        }
        return $this->message('删除失败', $this->redirect(['index']), 'error');
    }

    /* -------------------- AJAX 更新 -------------------- */

    /**
     * 排序/状态快速修改
     * @param string $id
     * @return array
     */
    public function actionAjaxUpdate(string $id): array
    {
        /** @var ActiveRecord|null $model */
        $model = $this->modelClass::findOne($id);
        if ($model === null) {
            return ResultHelper::json(404, '找不到数据');
        }

        $model->attributes = ArrayHelper::filter(
            Yii::$app->request->get(),
            ['sort', 'status']
        );

        return $model->save()
            ? ResultHelper::json(200, '修改成功')
            : ResultHelper::json(422, $this->getError($model));
    }

    /* -------------------- 辅助 -------------------- */

    /**
     * 返回模型
     * @param string $id
     * @return ActiveRecord
     */
    protected function findModel(string $id): ActiveRecord
    {
        /** @var ActiveRecord $class */
        $class = $this->modelClass;
        if ($id === '' || ($model = $class::findOne($id)) === null) {
            $model = new $class();
            $model->loadDefaultValues();
        }
        return $model;
    }
}