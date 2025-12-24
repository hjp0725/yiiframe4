<?php 

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UnauthorizedHttpException;
use common\traits\BaseAction;
use common\helpers\Auth;

/**
 * 后台基类控制器
 *
 * @package backend\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class BaseController extends Controller
{
    use BaseAction;

    /**
     * 行为配置
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // 仅登录用户
                    ],
                ],
            ],
        ];
    }

    /**
     * 前置拦截
     *
     * @param Action $action
     * @return bool
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws UnauthorizedHttpException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 每页数量限制
        $this->pageSize = (int)Yii::$app->request->get('per-page', 20);
        if ($this->pageSize > 50) {
            $this->pageSize = 50;
        }

        // 权限名：模块/控制器/动作
        $permissionName = '/' . Yii::$app->controller->route;

        // 忽略名单
        if (in_array($permissionName, Yii::$app->params['noAuthRoute'] ?? [], true)) {
            return true;
        }

        // 权限校验
        if (!Auth::verify($permissionName)) {
            throw new ForbiddenHttpException('对不起，您现在还没获此操作的权限');
        }

        // 记录上一页跳转
        $this->setReferrer($action->id);

        return true;
    }
}