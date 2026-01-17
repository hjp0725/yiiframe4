<?php

use yii\helpers\BaseUrl;
use common\helpers\Html;
use common\helpers\ArrayHelper;
use common\helpers\ImageHelper;
use common\enums\AppEnum;
use common\enums\ThemeLayoutEnum;
use common\enums\StatusEnum;

$menuCates = Yii::$app->services->menuCate->getOnAuthList();

?>

<nav class="main-header navbar navbar-expand navbar-white navbar-light rf-navbar-nav">
    <!-- Left navbar links -->
    <ul class="navbar-nav rf-navbar-nav-left">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <?php foreach ($menuCates as $cate){ ?>
            <li class="nav-item d-none d-sm-inline-block rfTopMenu" data-id="<?= $cate['id']; ?>" data-type="<?= $cate['id']; ?>" data-addon_centre="<?= $cate['addon_centre']; ?>">
                <a href="#" class="nav-link">
                    <i class="fa <?= Html::encode($cate['icon']); ?>"></i> <?= Html::encode(Yii::t('app', $cate['title'])); ?>
                </a>
            </li>
        <?php } ?>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- 自动隐藏菜单 -->
        <li class="nav-item dropdown hide">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-outdent"></i>
            </a>
            <div class=""></div>
        </li>
        
        <!-- 通知公告 -->
        <?php if (\yiiframe\plugs\common\AddonHelper::isInstall('Notify'))
         echo \addons\Notify\common\widgets\notify\Notify::widget(); ?>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <img src="<?= ImageHelper::defaultHeaderPortrait(Yii::$app->user->identity->head_portrait); ?>" class="img-circle head_portrait" width="30px">
                <?= Yii::$app->user->identity->username; ?>
            </a>
            <div class="dropdown-menu">
                <a href="<?= BaseUrl::to(['/base/member/personal'])?>" class="dropdown-item text-center J_menuItem">
                    <!-- Message Start -->
                    <div class="media">
                        <div class="media-body" onclick="$('body').click();">
                            <h4 class="text-sm">
                                <?= Yii::t('app', '个人信息') ?>
                            </h4>
                        </div>
                    </div>
                    <!-- Message End -->
                </a>
                <div class="dropdown-divider"></div>
                <a href="<?= BaseUrl::to(['/base/member/up-password'])?>" class="dropdown-item text-center J_menuItem">
                    <!-- Message Start -->
                    <div class="media">
                        <div class="media-body" onclick="$('body').click();">
                            <h4 class="text-sm">
                                <?= Yii::t('app', '密码') ?>
                            </h4>
                        </div>
                    </div>
                    <!-- Message End -->
                </a>
                <div class="dropdown-divider"></div>
                <?php if (Yii::$app->id == AppEnum::BACKEND) { ?>
                    <a href="<?= BaseUrl::to(['/main/clear-cache'])?>" class="dropdown-item text-center dropdown-footer J_menuItem">
                        <!-- Message Start -->
                        <div class="media">
                            <div class="media-body" onclick="$('body').click();">
                                <h4 class="text-sm">
                                    <?= Yii::t('app', '清理缓存') ?>
                                </h4>
                            </div>
                        </div>
                        <!-- Message End -->
                    </a>
                    <div class="dropdown-divider"></div>
                <?php } ?>
                <span href="#" class="dropdown-item dropdown-footer purple text-sm">
                    <?php if (Yii::$app->services->auth->isSuperAdmin()){ ?>
                        <?=Yii::t('app','超级管理员');?>
                    <?php }else{ ?>
                        <?= Yii::$app->services->rbacAuthRole->getTitle() ?>
                    <?php } ?>
                </span>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BaseUrl::to(['site/logout']); ?>" data-method="post" class="nav-link"><i class="iconfont icontuichu"  style="font-size:14px"></i> <?=Yii::t('app','退出');?></a>
        </li>
        <li class="nav-item hide">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>
