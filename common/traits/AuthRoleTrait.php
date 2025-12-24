<?php 
namespace common\traits;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\helpers\ResultHelper;
use common\models\rbac\AuthRole;

/**
 * Trait AuthRoleTrait
 * @package common\traits
 * @property \yii\db\ActiveRecord|\yii\base\Model $modelClass
 * @property string $appId 应用id
 * @property bool $sourceAuthChild 权限来源(false:所有权限，true：当前角色)
 * @property string $viewPrefix 加载视图
 * @author jianyan74 <751393839@qq.com>
 */
trait AuthRoleTrait
{
    /* ---------- 生命周期 ---------- */
    public function init(): void
    {
        parent::init();

        if ($this->modelClass === null) {
            throw new InvalidConfigException('"modelClass" 属性必须设置.');
        }
        if ($this->appId === null) {
            throw new InvalidConfigException('"appId" 属性必须设置.');
        }
        if ($this->sourceAuthChild === null) {
            throw new InvalidConfigException('"sourceAuthChild" 属性必须设置.');
        }
        if ($this->viewPrefix === null) {
            throw new InvalidConfigException('"viewPrefix" 属性必须设置.');
        }
    }

    /* ---------- 列表 ---------- */
    public function actionIndex()
    {
        $query = AuthRole::find()
            ->where(['app_id' => $this->appId])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => Yii::$app->services->merchant->getId()])
            ->andFilterWhere(Yii::$app->services->rbacAuthRole->roleCondition($this->sourceAuthChild))
            ->orderBy('sort asc, created_at asc');
        $dataProvider = new ActiveDataProvider([
            'query'      => $query->asArray(),
            'pagination' => false,
        ]);

        return $this->render(
            $this->viewPrefix . $this->action->id,
            [
                'dataProvider' => $dataProvider,
                'merchant_id'  => $this->merchant_id,
                'role'         => $this->sourceAuthChild ? Yii::$app->services->rbacAuthRole->getRole() : [],
            ]
        );
    }

    /* ---------- 新增/编辑 ---------- */
    public function actionEdit()
    {
        $id          = (int)Yii::$app->request->get('id');
        $merchant_id = Yii::$app->services->merchant->getNotNullId();

        /** @var AuthRole $model */
        $model = $this->findModel($id);
        $model->pid   = Yii::$app->request->get('pid') ?? $model->pid;
        $model->app_id = $this->appId;

        // POST 保存
        if (Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $model->attributes  = $post;
            $model->merchant_id = $merchant_id ?? 0;

            // 非超管且开启权限继承时自动写入父级
            if (
                $this->sourceAuthChild &&
                !Yii::$app->services->auth->isSuperAdmin() &&
                empty($model->pid)
            ) {
                $role         = Yii::$app->services->rbacAuthRole->getRole();
                $model->pid   = $role['id'];
            }

            if (!$model->save()) {
                return ResultHelper::json(422, $this->getError($model));
            }

            // 写入权限
            $userIds = $post['userTreeIds'] ?? [];
            $plugIds = $post['plugTreeIds'] ?? [];
            Yii::$app->services->rbacAuthItemChild->accredit($model->id, $userIds, WhetherEnum::DISABLED, $this->appId);
            Yii::$app->services->rbacAuthItemChild->accredit($model->id, $plugIds, WhetherEnum::ENABLED,  $this->appId);

            return ResultHelper::json(200, Yii::t('app','提交成功'));
        }

        // 获取权限树数据
        if ($this->sourceAuthChild === true && !Yii::$app->services->auth->isSuperAdmin()) {
            $role    = Yii::$app->services->rbacAuthRole->getRole();
            $allAuth = Yii::$app->services->rbacAuthItemChild->findItemByRoleId((int)$role['id']);
        } else {
            $allAuth = Yii::$app->services->rbacAuthItem->findAll($this->appId);
        }

        list($defaultFormAuth, $defaultCheckIds, $addonsFormAuth, $addonsCheckIds) =
            Yii::$app->services->rbacAuthRole->getJsTreeData($id, $allAuth);

        return $this->render(
            $this->viewPrefix . $this->action->id,
            [
                'model'            => $model,
                'defaultFormAuth'  => $defaultFormAuth,
                'defaultCheckIds'  => $defaultCheckIds,
                'addonsFormAuth'   => $addonsFormAuth,
                'addonsCheckIds'   => $addonsCheckIds,
                'dropDownList'     => Yii::$app->services->rbacAuthRole->getDropDownForEdit($this->appId, $id),
                'merchant_id'      => $merchant_id,
            ]
        );
    }

    /* ---------- 删除 ---------- */
    public function actionDelete($id)
    {
        if ($this->findModel($id)->delete()) {
            return $this->message('删除成功', $this->redirect(['index']));
        }
        return $this->message('删除失败', $this->redirect(['index']), 'error');
    }

    /* ---------- Ajax 更新排序/状态 ---------- */
    public function actionAjaxUpdate($id): array
    {
        $model = $this->modelClass::findOne($id);
        if (!$model) {
            return ResultHelper::json(404, '找不到数据');
        }

        $model->attributes = ArrayHelper::filter(Yii::$app->request->get(), ['sort', 'status']);
        if (!$model->save()) {
            return ResultHelper::json(422, $this->getError($model));
        }

        return ResultHelper::json(200, Yii::t('app','修改成功'));
    }

    /* ---------- 查找模型 ---------- */
    protected function findModel($id)
    {
        /* @var $model \yii\db\ActiveRecord */
        if (empty($id) || empty(($model = $this->modelClass::findOne($id)))) {
            $model = new $this->modelClass;
            $model->loadDefaultValues();
        }
        return $model;
    }
}