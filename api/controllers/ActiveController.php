<?php 

namespace api\controllers;
use Yii;
use common\behaviors\HttpSignAuth;
use common\traits\BaseAction;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpHeaderAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\Cors;
use yii\filters\RateLimiter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Class ActiveController
 * @package api\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class ActiveController extends \yii\rest\ActiveController
{
    use BaseAction;

    /**
     * 不用进行登录验证的方法
     * @var string[]
     */
    protected $authOptional = [];

    /**
     * 不用进行签名验证的方法
     * @var string[]
     */
    protected $signOptional = [];

    /* -------------------- 行为 -------------------- */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // 移除父类默认的 authenticator，避免重复配置
        unset($behaviors['authenticator']);

        // 签名验证（开关由参数控制）
        if (($this->module->params['user.httpSignValidity'] ?? false) == true) {
            $behaviors['signTokenValidate'] = [
                'class'   => HttpSignAuth::class,
                'optional'=> $this->signOptional,
            ];
        }

        // 复合认证
        $behaviors['authenticator'] = [
            'class'       => CompositeAuth::class,
            'authMethods' => [
                HttpHeaderAuth::class,
                [
                    'class'      => QueryParamAuth::class,
                    'tokenParam' => 'access-token',
                ],
            ],
            'optional'    => $this->authOptional,
        ];

        // 速率限制
        $behaviors['rateLimiter'] = [
            'class'                  => RateLimiter::class,
            'enableRateLimitHeaders' => true,
        ];

        // CORS 如果需要，可在此追加
        // $behaviors['cors'] = ['class' => Cors::class];

        return $behaviors;
    }

    /* -------------------- 动作动词 -------------------- */
    public function verbs(): array
    {
        return [
            'index'  => ['GET', 'HEAD', 'OPTIONS'],
            'view'   => ['GET', 'HEAD', 'OPTIONS'],
            'create' => ['POST', 'OPTIONS'],
            'update' => ['PUT', 'PATCH', 'OPTIONS'],
            'delete' => ['DELETE', 'OPTIONS'],
        ];
    }

    /* -------------------- 前置钩子 -------------------- */
    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // 权限检查（如用 RBAC 可注释）
        $this->checkAccess($action->id, $this->modelClass, Yii::$app->request->get());

        // 每页数量限制
        $this->pageSize = (int)Yii::$app->request->get('per-page', 10);
        if ($this->pageSize > 50) {
            $this->pageSize = 50;
        }

        return true;
    }
}