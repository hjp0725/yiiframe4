<?php

use common\helpers\ImageHelper;
use common\widgets\menu\MenuLeftWidget;
use common\enums\ThemeLayoutEnum;

?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar main-sidebar-custom sidebar-dark-white elevation-2">
    <!-- Brand Logo -->
    
    <a href="" class="brand-link">
        <img src="<?= ImageHelper::defaultHeaderPortrait(Yii::$app->debris->backendConfig('web_logo'), '/resources/img/logo.png'); ?>" alt="AdminLTE Logo" class="brand-image img-circle elevation-2">
            <span class="brand-text font-weight-light"><?= Yii::$app->params['adminTitle']; ?></span>
    </a>
   
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false" style="padding-bottom: 60px">
                <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
                <li class="nav-header"><?=Yii::t('app','系统菜单');?></li>
                <?= MenuLeftWidget::widget(); ?>
                <?php if (!empty(Yii::$app->debris->backendConfig('sys_related_links'))){ ?>
                    <!-- 相关链接 -->
                    <li class="nav-header"><?=Yii::t('app','相关链接');?></li>
                    <li class="nav-item">
                        <a href="http://www.yiiframe.com" class="nav-link" onclick="window.open('http://www.yiiframe.com')">
                            <i class="nav-icon far fa-bookmark text-danger"></i>
                            <p class="text"><?=Yii::t('app','官网');?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="http://doc.yiiframe.com" class="nav-link" onclick="window.open('http://doc.yiiframe.com')">
                            <i class="nav-icon far fa-circle text-warning"></i>
                            <p><?=Yii::t('app','文档');?></p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="" class="nav-link" >
                            <i class="nav-icon far fa-comment text-info"></i>
                            <p>21931118</p>
                        </a>
                    </li>
                   
                <?php } ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
</aside>
