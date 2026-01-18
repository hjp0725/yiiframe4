<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();

echo "<?php\n";
?>

use kartik\daterange\DateRangePicker;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;
use common\helpers\Html;
use common\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = <?= $generator->generateString($generator->getTableComment()) ?>;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-sm-12">
        <div class="box">
            <div class="box-header">
                <h2 style="font-size: 18px;padding-top: 0;margin-top: 0">
                    <i class="icon ion-android-apps"></i>
                    <?= "<?= " ?>Html::encode($this->title) ?>
                </h2>
                <div class="box-tools">
                    <?= "<?= " ?> Html::create(['create'], [],Yii::t('app','创建')); ?>
                    <?= "<?= " ?> Html::export(['export']+Yii::$app->request->queryParams, [], Yii::t('app','导出')); ?>
                    <?= "<?= " ?> Html::a(Yii::t('app','批量删除'), "javascript:void(0);",
                        [
                            'class' => 'btn btn-danger btn-xs delete-all',
                            'onclick' =>"rfDelete(this);return false;"
                        ]); 
                    ?>
                </div>
            </div>
            <div class="box-body table-responsive">
<?php if ($generator->indexWidgetType === 'grid'): ?>
    <?= "<?= " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => [
            'class' => 'table table-hover rf-table',
            //'fixedNumber' => 1,
            //'fixedRightNumber' => 1,
        ],
        'options' => [
            'id' => 'grid',
        ],
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n        'columns' => [\n" : "'columns' => [\n"; ?>
            [
                'class' => 'yii\grid\CheckboxColumn',
                'checkboxOptions' => function ($model, $key, $index, $column) {
                    return ['value' => $model->id];
                },
            ],
            [
                'class' => 'yii\grid\SerialColumn',
                'visible' => false,
            ],

<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "            '" . $name . "',\n";
        } else {
            echo "            //'" . $name . "',\n";
        }
    }
} else {
    $listFields = !empty($generator->listFields) ? $generator->listFields : [];
    /* 需要下拉/单选/复选筛选的类型 */
    $mapable = ['dropDownList', 'radioList', 'checkboxList'];

    foreach ($tableSchema->columns as $column) {
        /* ====== 如果用户把 created_at 列勾进列表，就走日期范围筛选 ====== */
        if ($column->name === 'created_at' && in_array('created_at', $listFields, true)) {
            echo "            [
                'attribute' => 'created_at',
                'filter' => DateRangePicker::widget([
                    'name' => 'SearchModel[dateRange]',
                    'value' => Yii::\$app->request->get('SearchModel')['dateRange'],
                    'convertFormat' => true,
                    'pluginOptions' => [
                        'locale' => [
                            'format' => 'Y-m-d',
                            'separator' => '/',
                        ],
                        'opens'  => 'left',
                    ]
                ]),
                'value' => function (\$model) {
                    return date('Y-m-d H:i:s', \$model->created_at);
                },
            ],\n";
            continue;   // 已经输出完毕，直接跳过后面默认逻辑
        }elseif($column->name=='member_id' && in_array('member_id', $listFields, true)){
            echo "            [
                'attribute' => 'member_id',
                'value' => function (\$model) {
                    return \yii\helpers\ArrayHelper::getValue(Yii::\$app->services->devPattern->member()::dropDown('id','title'),\$model->member_id,'未知');
                },
                'filter' => Html::activeDropDownList(\$searchModel, 'member_id', Yii::\$app->services->devPattern->member()::dropDown('id','title'),['prompt' => Yii::t('addon','全部'), 'class' => 'form-control']
                ),
            ],\n";
            continue;   // 已经输出完毕，直接跳过后面默认逻辑
        }
        $format = $generator->generateColumnFormat($column);

        // 只在列表字段里显示
        if (!in_array($column->name, $listFields)) {
            echo "            //'" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
            continue;
        }

        /* 1. 控件类型是否可下拉 */
        /* 先拆出控件类型 */
        list($type,$linkTable) = array_pad(explode('|', $generator->inputType[$column->name] ?? ''), 2, '');
        /* ======  新增：Select2 多选列  ====== */
        if ($type === 'Select2') {
            // 1. 取关联表（或会员模型）做下拉选项
            if (!empty($linkTable)) {
                $modelClass = $generator->getRelationModelClass($linkTable);
                $itemsCode  = "{$modelClass}::dropDown()";
            } else {
                // 1-2 无关联表 → 解析备注
                $map = $generator->parseCommentToMap($column->comment);
                if (!empty($map)) {
                    $itemsCode = '[' . implode(', ', array_map(
                        function ($k, $v) { return "'{$k}' => '{$v}'"; },
                        array_keys($map),
                        $map
                    )) . ']';
                } else {
                    // 1-3 没备注 → 默认「是/否」
                    $itemsCode = "['1' => '选项1', '2' => '选项2']";
                }
            }

            // 2. 输出 GridView 列
            echo "            [
                'attribute' => '".$column->name."',
                'value' => function (\$model) {
                    \$ids = is_array(\$model->$column->name) 
                    ? \$model->$column->name 
                    : json_decode(\$model->$column->name, true);
                    
                    if (!is_array(\$ids) || !\$ids) return Yii::t('addon','未知');

                    \$map   = $itemsCode;
                    \$names = array_filter(array_map(function (\$id) use (\$map) {
                        return \$map[\$id] ?? '';
                    }, \$ids));
                    
                    return \$names ? implode(', ', \$names) : Yii::t('addon','未知');
                },
                'filter' => Html::dropDownList(
                    '".$column->name."',
                    Yii::\$app->request->get('".$column->name."'), 
                    $itemsCode, 
                    ['prompt' => Yii::t('addon','全部'), 'class' => 'form-control', 'onchange' => 'this.form.submit()']
                ),
            ],\n";
            continue;   // 已经处理完，直接下一轮
        }
        if (in_array($type, $mapable)) {
            // 1-1 先查是否指定了关联表
            if (!empty($linkTable)) {
                // 走模型
                $modelClass = $generator->getRelationModelClass($linkTable);
                $itemsCode  = "{$modelClass}::dropDown()";
            } else {
                // 1-2 无关联表 → 解析备注
                $map = $generator->parseCommentToMap($column->comment);
                if (!empty($map)) {
                    $itemsCode = '[' . implode(', ', array_map(
                        function ($k, $v) { return "'{$k}' => '{$v}'"; },
                        array_keys($map),
                        $map
                    )) . ']';
                } else {
                    // 1-3 没备注 → 默认「是/否」
                    $itemsCode = "['1' => '选项1', '2' => '选项2']";
                }
            }

            // 输出下拉列
            echo "            [
                'attribute' => '".$column->name."',
                'value' => function(\$model) {
                    return \yii\helpers\ArrayHelper::getValue(
                        $itemsCode,
                        \$model->$column->name,
                        Yii::t('addon','未知')
                    );
                },
                'filter' => \yii\helpers\Html::activeDropDownList(
                    \$searchModel,
                    '".$column->name."',
                    $itemsCode,
                   ['prompt' => Yii::t('addon','全部'), 'class' => 'form-control']
                ),
            ],\n";
            continue;
        }

        /* 2. 默认文本框 */
        echo "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
    }
}
?>
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => Yii::t('app', '操作'),
                'template' => '{status} {edit} {delete}',
                'buttons' => [
                    'status' => function($url, $model, $key){
                            return Html::status($model['status']);
                    },
                    'edit' => function($url, $model, $key){
                        return Html::edit(['edit', 'id' => $model->id]);
                    },
                    'delete' => function($url, $model, $key){
                            return Html::delete(['delete', 'id' => $model->id]);
                    },
                ]
            ]
    ]
    ]); ?>
<?php else: ?>
    <?= "<?= " ?>ListView::widget([
        'dataProvider' => $dataProvider,
        'itemOptions' => ['class' => 'item'],
        'itemView' => function ($model, $key, $index, $widget) {
            return Html::a(Html::encode($model-><?= $nameAttribute ?>), ['view', <?= $urlParams ?>]);
        },
    ]) ?>
<?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    let url = '';
    // 删除全部
    $(".delete-all").on("click", function () {
        url = "<?= "<?= " ?> Url::to(['delete-all']) ?>";
        appConfirm("您确定要删除这些记录吗?", '请谨慎操作', function (value) {
            switch (value) {
                case "defeat":
                    sendData(url);
                    break;
                default:
            }
        })
        
    });
    function sendData(url, ids = []) {
        if (ids.length === 0) {
            ids = $("#grid").yiiGridView("getSelectedRows");
        }

        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {ids: ids},
            success: function (data) {
                if (parseInt(data.code) === 200) {
                    location.reload();
                } else {
                    rfWarning(data.message);
                }
            }
        });
    }
</script>