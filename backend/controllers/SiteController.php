<?php 

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use backend\forms\LoginForm;
use backend\forms\SignUpForm;

/**
 * Class SiteController
 *
 * @package backend\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class SiteController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'register', 'protocol', 'error', 'captcha'],
                        'allow'   => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs'  => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error'   => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class'           => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
                'maxLength'       => 6,
                'minLength'       => 6,
                'padding'         => 5,
                'height'          => 32,
                'width'           => 100,
                'offset'          => 4,
                'backColor'       => 0xffffff,
                'foreColor'       => 0x62a8ea,
            ],
        ];
    }

    /**
     * 登录
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        $model->loginCaptchaRequired();

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goHome();
        }

        $model->password = '';
        return $this->renderPartial('login', ['model' => $model]);
    }

    /**
     * 注册
     * @throws NotFoundHttpException
     */
    public function actionRegister()
    {
        if (empty(Yii::$app->debris->addonConfig('Member')['member_register_is_open'])) {
            throw new NotFoundHttpException('未开放注册，请稍后再试');
        }

        $model = new SignUpForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->register()) {
            return $this->redirect(['login']);
        }

        return $this->renderPartial($this->action->id, ['model' => $model]);
    }

    /**
     * 注册协议
     * @throws NotFoundHttpException
     */
    public function actionProtocol()
    {
        if (empty(Yii::$app->debris->addonConfig('Member')['member_register_is_open'])) {
            throw new NotFoundHttpException('找不到页面');
        }

        return $this->renderPartial($this->action->id);
    }

    /**
     * 退出
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * 站点维护
     */
    public function actionOffline()
    {
        return $this->renderPartial('offline', [
            'title' => '系统维护中...',
        ]);
    }
}