<?php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => [
            'class' => 'yii\gii\Module',
            // 'allowedIPs' => ['127.0.0.1', '::1', '192.168.1.*'],
            
            'generators' => [
                'model' => [
                    'class' => \common\components\gii\model\Generator::class,
                    'templates' => [
                        'yiiframe' => '@common/components/gii/model/yiiframe',
                        'export' => '@common/components/gii/model/export',
                        'default' => '@vendor/yii2framework/yiiframe-gii/src/generators/model/default',
                    ]
                ],
                'crud' => [
                    'class' => \common\components\gii\crud\Generator::class,
                    'templates' => [
                        'yiiframe' => '@common/components/gii/crud/yiiframe',
                        'default' => '@vendor/yii2framework/yiiframe-gii/src/generators/crud/default',
                    ]
                ],
                'api' => [
                    'class' => \common\components\gii\api\Generator::class,
                    'templates' => [
                        'yiiframe' => '@common/components/gii/api/yiiframe',
                        'default' => '@common/components/gii/api/default',
                    ]
                ],
            ],
        ],
        
    ],
];
