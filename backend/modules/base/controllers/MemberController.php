<?php 

namespace backend\modules\base\controllers;

use Yii;
use yii\web\Response;
use common\enums\MemberAuthEnum;
use common\enums\StatusEnum;
use common\enums\AppEnum;
use common\helpers\HashidsHelper;
use common\helpers\ResultHelper;
use common\helpers\Url;
use common\models\base\SearchModel;
use common\models\backend\Member;
use common\traits\Curd;
use backend\controllers\BaseController;
use backend\modules\base\forms\MemberForm;
use backend\modules\base\forms\PasswdForm;

/**
 * 后台用户管理
 *
 * @package backend\modules\base\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class MemberController extends BaseController
{
    use Curd;

    /** @var string AR 类名 */
    public $modelClass = Member::class;

    /* -------------------- 列表 -------------------- */

    /**
     * 用户列表
     * @return string
     */
    public function actionIndex(): string
    {
        // 当前用户可见的 uid（不含超管）
        $ids = Yii::$app->services->rbacAuthAssignment->getChildIds(AppEnum::BACKEND);

        $searchModel = new SearchModel([
            'model'                  => $this->modelClass,
            'scenario'               => 'default',
            'relations'              => ['department' => ['title']],
            'partialMatchAttributes' => ['username', 'mobile', 'title', 'department.title'],
            'defaultOrder'           => ['type' => SORT_DESC, 'id' => SORT_DESC],
            'pageSize'               => $this->pageSize,
        ]);

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andFilterWhere(['in', Member::tableName() . '.id', $ids])
            ->andWhere(['>=', Member::tableName() . '.status', StatusEnum::DISABLED])
            ->with('assignment');

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel'  => $searchModel,
        ]);
    }

    /* -------------------- 新增/编辑 -------------------- */

    /**
     * AJAX 新增/编辑
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $request = Yii::$app->request;
        $model   = new MemberForm();
        $model->id = $request->get('id');
        $model->loadData();
        // 非超管走通用场景
        if ((int)$model->id !== (int)Yii::$app->params['adminAccount']) {
            $model->scenario = 'generalAdmin';
        }

        $this->activeFormValidate($model);
        if ($model->load($request->post())) {
            return $model->save()
                ? $this->redirect(['index'])
                : $this->message($this->getError($model), $this->redirect(['index']), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model'       => $model,
            'departments' => Yii::$app->services->backendDepartment->getMapList(),
            'roles'       => Yii::$app->services->rbacAuthRole->getDropDown(AppEnum::BACKEND, true),
        ]);
    }

    /* -------------------- 个人中心 -------------------- */

    /**
     * 个人资料
     * @return string|\yii\web\Response
     */
    public function actionPersonal()
    {
        $model = $this->findModel((string)Yii::$app->user->id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->message(Yii::t('app', '修改成功'), $this->redirect(['personal']));
        }

        return $this->render($this->action->id, ['model' => $model]);
    }

    /* -------------------- 密码 -------------------- */

    /**
     * 修改密码
     * @return array|string
     * @throws \yii\base\Exception
     */
    public function actionUpPassword()
    {
        $model = new PasswdForm();
        if ($model->load(Yii::$app->request->post())) {
            if (!$model->validate()) {
                return ResultHelper::json(404, $this->getError($model));
            }

            /* @var \common\models\backend\Member $member */
            $member = Yii::$app->user->identity;
            $member->password_hash = Yii::$app->security->generatePasswordHash($model->passwd_new);

            if ($member->save()) {
                Yii::$app->user->logout();
                return ResultHelper::json(200, Yii::t('app', '修改成功'));
            }
            return ResultHelper::json(404, $this->analyErr($member->getFirstErrors()));
        }

        return $this->render($this->action->id, ['model' => $model]);
    }

    /* -------------------- 微信绑定/解绑 -------------------- */

    /**
     * 生成绑定二维码
     * @param int $id   会员主键
     * @param int $type 绑定类型
     * @return Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionBinding(int $id, int $type): Response
    {
        if ($type !== MemberAuthEnum::WECHAT) {
            throw new \yii\web\BadRequestHttpException('仅支持微信绑定');
        }

        $uuid = HashidsHelper::encode($id);
        $url  = Url::toHtml5(['binding-wechat/index', 'uuid' => $uuid]);

        $qr = Yii::$app->get('qr');
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', $qr->getContentType());

        return $qr->setText($url)
            ->setSize(200)
            ->setMargin(7)
            ->writeString();
    }

    /**
     * 解绑
     * @param int $type      类型
     * @param int $member_id 会员主键
     * @return \yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionUnBind(int $type, int $member_id): \yii\web\Response
    {
        Yii::$app->services->backendMemberAuth->unBind($type, $member_id);
        return $this->message('解绑成功', $this->redirect(['index']));
    }
}