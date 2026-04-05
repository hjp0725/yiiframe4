<?php

use yii\widgets\ActiveForm;
use common\helpers\Url;
use common\enums\StatusEnum;
use kartik\select2\Select2;

$form = ActiveForm::begin([
    'id' => $model->formName(),
    'enableAjaxValidation' => true,
    'validationUrl' => Url::to(['ajax-edit', 'id' => $model['id']]),
    'fieldConfig' => [
        // 'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}\n{hint}\n{error}</div>",
    ],
]);

?>

    <div class="modal-header">
        <h4 class="modal-title"><?=Yii::t('app', '编辑');?></h4>
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span><span class="sr-only">关闭</span></button>
    </div>
    <div class="modal-body">
        <?= $form->field($model, 'pid')->dropDownList($dropDown) ?>
        <?= $form->field($model, 'title')->textInput(); ?>
        <?= $form->field($model, 'sort')->textInput(); ?>
        <?= $form->field($model, 'department_leader')->widget(Select2::class, [
            'data' => $members,
            'options' => ['placeholder' => Yii::t('app','请选择')],
            'pluginOptions' => [
            'allowClear' => true
            ],
        ]);?>
        <?= $form->field($model, 'status')->radioList(StatusEnum::getMap()); ?>
    </div>
    <div class="modal-footer">
        <button class="btn btn-primary" type="submit"><?=Yii::t('app','保存');?></button>
        <button type="button" class="btn btn-white" data-dismiss="modal"><?=Yii::t('app','关闭');?></button>

    </div>
<?php ActiveForm::end(); ?>