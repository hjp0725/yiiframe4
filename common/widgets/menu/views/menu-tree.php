<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;
use common\enums\ThemeLayoutEnum;
?>
<?php foreach ($menus as $item) { ?>
    <li class="nav-item hide rfLeftMenu rfLeftMenu-<?= $item['cate_id']; ?> <?= (isset($item['cate']['is_default_show']) && $item['cate']['is_default_show'] == StatusEnum::ENABLED) ? 'is_default_show' : ''; ?>">
        <?php if (!empty($item['-'])) { ?>
            <a href="#" class="nav-link ">
                <i class="nav-icon rf-i fa <?= $level == 1 ? $item['icon'] : ''; ?>"></i>
                <p class="">
                    <?= Yii::t('app',$item['title']); ?>
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview">
                <?= $this->render('menu-tree', [
                    'menus' => $item['-'],
                    'level' => $level + 1,
                ]) ?>
            </ul>
        <?php } else { ?>
            <a href="<?= $item['fullUrl'] == '#' ? '' : Url::to($item['fullUrl']); ?>" class="nav-link J_menuItem">
                <i class="rf-i nav-icon fa <?= $level == 1 ? $item['icon'] : ''; ?> <?= $level > 2 ? 'ml-4' : ''; ?>"></i>
                <p class=""><?= Yii::t('app',$item['title']); ?></p>
            </a>
        <?php } ?>
    </li>
<?php } ?>

