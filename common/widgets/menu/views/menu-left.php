<?php if (!empty($menus)) { ?>
    <?= $this->render('menu-tree', [
        'menus' => $menus,
        'level' => 1,
    ]) ?>
<?php } ?>

<!--扩展插件模块信息-->
<?php foreach ($addons as $key => $addon) { ?>
    <li class="nav-item rfLeftMenu rfLeftMenu-2 hide ">
        <a href="#" class="nav-link ">
            <i class="nav-icon rf-i <?= \yiiframe\plugs\enums\GroupEnum::getMap()[$key]['icon']; ?>"></i>
           
            <p class="">
                <?= Yii::t('addon',\yiiframe\plugs\enums\GroupEnum::getMap()[$key]['title']); ?>
                <i class="right fas fa-angle-left"></i>
            </p>
            
        </a>

        <ul class="nav nav-treeview">
            <?php foreach ($addon as $value) { ?>
                <li>
                    <a class="nav-link J_menuItem" href="<?= $value['menuUrl']; ?>">
                        <i class="fa fa-angle-right ml-4" ></i>
                        <p class="">
                            <?= Yii::t('addon',$value['title']); ?>
                        </p>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </li>
<?php } ?>
