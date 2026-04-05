<?php 
namespace backend\modules\base\controllers;

use backend\controllers\BaseController;
use common\helpers\ResultHelper;
use common\traits\Curd;
use common\models\backend\Department;
use Yii;

/**
 * 企业分类
 * Class DepartmentController
 * @package backend\modules\base\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class DepartmentController extends BaseController
{
    use Curd;

    /**
     * @var string
     */
    public $modelClass = Department::class;

    /**
     * 列表
     */
    public function actionIndex(): string
    {
        $query = $this->modelClass::find()
            ->andWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->with('member')
            ->orderBy('sort asc, created_at asc');

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query'      => $query,
            'pagination' => false,
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * 新增/编辑
     */
    public function actionAjaxEdit()
    {
        $request = Yii::$app->request;
        $id      = $request->get('id');
        $model   = $this->findModel($id);

        $model->pid         = $request->get('pid') ?? $model->pid;
        $model->merchant_id = Yii::$app->user->identity->merchant_id;

        $this->activeFormValidate($model);

        if ($model->load($request->post())) {
            return $model->save()
                ? $this->redirect(['index'])
                : $this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model'    => $model,
            'dropDown' => Yii::$app->services->backendDepartment->getDropDownForEdit(),
            'members'  => Yii::$app->services->devPattern->getMap(),
        ]);
    }

    /**
     * 获取全部 id
     */
    public function actionSyncAllDepartmentid(): array
    {
        $nextId = Yii::$app->request->get('next_departmentid', '');
        try {
            list($total, $count, $nextDepartmentid) = Yii::$app->services->backendDepartment->syncAllDepartmentid((int)$nextId);
            return ResultHelper::json(200, '同步部门 id 完成', [
                'total'             => $total,
                'count'             => $count,
                'next_departmentid' => $nextDepartmentid,
            ]);
        } catch (\Exception $e) {
            return ResultHelper::json(422, $e->getMessage());
        }
    }

    /**
     * 开始同步数据
     */
    public function actionSyncDepartment(): array
    {
        $request = Yii::$app->request;
        $type    = $request->post('type', 'all');
        $page    = (int)$request->post('page', 0);

        // 全部同步
        if ($type === 'all' && ($models = Yii::$app->services->backendDepartment->getFollowListByPage($page))) {
            foreach ($models as $dept) {
                Yii::$app->services->backendDepartment->syncByDepartmentid((int)$dept['department_id']);
            }
            return ResultHelper::json(200, '同步完成', ['page' => $page + 1]);
        }

        return ResultHelper::json(200, '同步完成');
    }
}