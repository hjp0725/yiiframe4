<?php

namespace backend\assets;

use Yii;
use yii\web\AssetBundle;
/**
 * Class AppAsset
 * @package backend\assets
 * @author jianyan74 <751393839@qq.com>
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web/resources';

    public $css = [
        'css/yiiframe3.css',
        'css/yiiframe.css',
        'css/yiiframe.widgets.css',
        'css/iconfont/iconfont.css',    
    ];

    public $js = [
        'js/template.js',
        'js/yiiframe.js',
        'js/yiiframe.widgets.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
        'yiiframe\adminlte3\AdminLetAsset',
    ];

}
