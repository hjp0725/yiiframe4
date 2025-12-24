<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap4\ActiveForm */

/* @var $model LoginForm */

use yii\bootstrap4\ActiveForm;
use yii\bootstrap4\Html;
use yii\captcha\Captcha;
use backend\assets\AppAsset;
use yii\helpers\Url;

AppAsset::register($this);

$this->title = Yii::$app->params['adminTitle'];

?>

<style>
    .login-box {
        width: 360px;
        margin: 7% auto;
    }

    .wechat-qr-box {
        height: 178px;
        width: 178px;
        top: 10px
    }

    .wechat-qr-img {
        height: 178px;
        width: 178px;
        padding: 10px;
        border: 1px solid #e8eaec;
        margin-left: 80px
    }

    .wechat-qr-shade {
        background-color: rgba(0, 0, 0, 0.7);
        width: 100%;
        height: 100%;
        left: 80px;
        overflow: hidden;
        position: absolute;
        right: -2px;
        top: -2px;
        z-index: 10;
    }

    .wechat-qr-shade-loading-text,
    .wechat-qr-shade-lose-text {
        position: relative;
        top: 70px;
        color: #d2d2d2
    }
</style>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="renderer" content="webkit">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="login-page">
<?php $this->beginBody() ?>
<div class="login-box">
    <div class="login-logo">
        <?= Html::encode(Yii::$app->params['adminTitle']); ?>
    </div>
    <!-- /.login-logo -->
    <div class="card card-primary card-outline card-outline-tabs">
        
        <div class="card-body login-card-body">
            <div class="tab-content" id="custom-tabs-four-tabContent">
                <div class="tab-pane fade active show" id="custom-1">
                    <p class="login-box-msg <?= empty($hasWechat) ? '' : 'hide'; ?>"><?=Yii::t('app','欢迎登陆');?></p>
                    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                    <?= $form->field($model, 'username')->textInput(['placeholder' => '登录账号'])->label(false); ?>
                    <?= $form->field($model, 'password')->passwordInput(['placeholder' => '登录密码'])->label(false); ?>
                    <?php if ($model->scenario == 'captchaRequired') { ?>
                        <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
                            'template' => '<div class="row"><div class="col-sm-7">{input}</div><div class="col-sm-5">{image}</div></div>',
                            'imageOptions' => [
                                'alt' => '点击换图',
                                'title' => '点击换图',
                                'style' => 'cursor:pointer',
                            ],
                            'options' => [
                                'class' => 'form-control',
                                'placeholder' => '验证码',
                            ],
                        ])->label(false); ?>
                    <?php } ?>
                    <?= $form->field($model, 'rememberMe')->checkbox() ?>
                    <div class="form-group">
                        <?= Html::submitButton('立即登录',
                            ['class' => 'btn btn-primary btn-block', 'name' => 'login-button']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
                
            </div>
            <div class="social-auth-links text-center">
                <p><?= Html::encode(Yii::$app->debris->backendConfig('web_copyright')); ?></p>
            </div>
        </div>
        <!-- /.login-card-body -->
    </div>
</div>

<script>
    //判断是否存在父窗口
    if (window.parent !== this.window) {
        parent.location.reload();
    }

</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
