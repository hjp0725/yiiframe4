<?php

use common\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use backend\assets\AppAsset;

AppAsset::register($this);                // 确保 AdminLTE3 资源已加载
$this->title = '会员注册';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="hold-transition register-page">
<?php $this->beginBody() ?>

<div class="register-box">
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <a href="" class="h2" style="color:#000"><b><?= $this->title ?></b></a>
        </div>
        <div class="card-body">

            <?php $form = ActiveForm::begin([
                'id' => 'register-form',
                'enableClientValidation' => false,
                'fieldConfig' => [
                    'options' => ['class' => 'input-group mb-3'],
                    'inputOptions' => ['class' => 'form-control'],
                    'errorOptions' => ['class' => 'invalid-feedback d-block'],
                    'template' => "{input}\n{hint}\n{error}"
                ]
            ]); ?>

            <!-- 真实姓名 -->
            <div class="input-group mb-3">
                <?= Html::activeTextInput($model, 'title', [
                    'class' => 'form-control',
                    'placeholder' => '真实姓名'
                ]) ?>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-user"></span>
                    </div>
                </div>
                <?= Html::error($model, 'title', ['class' => 'invalid-feedback d-block']) ?>
            </div>

            <!-- 手机号 -->
            <div class="input-group mb-3">
                <?= Html::activeTextInput($model, 'mobile', [
                    'class' => 'form-control',
                    'placeholder' => '手机号码'
                ]) ?>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-phone"></span>
                    </div>
                </div>
                <?= Html::error($model, 'mobile', ['class' => 'invalid-feedback d-block']) ?>
            </div>

            <!-- 密码 -->
            <div class="input-group mb-3">
                <?= Html::activePasswordInput($model, 'password', [
                    'class' => 'form-control',
                    'placeholder' => '密码'
                ]) ?>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
                <?= Html::error($model, 'password', ['class' => 'invalid-feedback d-block']) ?>
            </div>

            <!-- 确认密码 -->
            <div class="input-group mb-3">
                <?= Html::activePasswordInput($model, 'password_repetition', [
                    'class' => 'form-control',
                    'placeholder' => '确认密码'
                ]) ?>
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-redo"></span>
                    </div>
                </div>
                <?= Html::error($model, 'password_repetition', ['class' => 'invalid-feedback d-block']) ?>
            </div>

            <!-- 用户协议 -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="icheck-primary">
                        <?= Html::activeCheckbox($model, 'rememberMe', [
                            'id'    => 'rememberMe',
                            'label' => '我同意 ' . Html::a('《用户协议》', ['protocol'], ['target' => '_blank'])
                        ]) ?>
                        <?= Html::error($model, 'rememberMe', ['class' => 'invalid-feedback d-block']) ?>
                    </div>
                </div>
            </div>

            <!-- 提交 -->
            <div class="row">
                <div class="col-12">
                    <?= Html::submitButton('注册', ['class' => 'btn btn-primary btn-block']) ?>
                </div>
            </div>

            <?php ActiveForm::end(); ?>

            <div class="text-center mt-2">
                <span style="color:#000">已有帐号？</span><?= Html::a('立即登录', ['login']) ?>
            </div>
        </div>
        <!-- /.card-body -->
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>