<?php
/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */

/* @var $generator yii\gii\generators\crud\Generator */

use yii\helpers\Html;

echo $form->field($generator, 'modelClass');
// echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'indexWidgetType')->dropDownList([
    'grid' => 'GridView',
    // 'list' => 'ListView',
]);
echo $form->field($generator, 'enableI18N')->checkbox();
// echo $form->field($generator, 'enablePjax')->checkbox();
echo $form->field($generator, 'messageCategory');

if (Yii::$app->request->isPost) {
    $table_s = $generator->getTableSchema();

    if (empty($table_s)) {
        return;
    }

    $columns = $table_s->columns;
    $cols = [];
    foreach ($columns as $key => $val) {
        $cols[$key] = $val->name;
    }
    $cols = array_diff($cols, ['id','merchant_id','member_id','status','updated_at']);
    echo $form->field($generator, 'listFields')->checkboxList($cols);
    if (empty($generator->inputType)) {
        foreach ($columns as $name => $val) {
            $generator->inputType[$name] = 1;
        }
    }
    echo "<div form-group'>";
    echo '<label control-label help" data-original-title title>'.Yii::t('app', '表单字段').'</label>';
    echo "<div  class='row'>";
    $drop = ['id','merchant_id', 'member_id','created_at', 'updated_at'];
    $columns = array_diff_key($columns, array_flip($drop));
    foreach ($columns as $name => $val) {
        $checked = '';
        if (!empty($generator->formFields) && in_array($name, array_values($generator->formFields))) {
            $checked = 'checked="checked"';
        }

        echo '<div class="col-lg-9"><input type="checkbox" name="Generator[formFields][]" value="' . $name . '" ' . $checked . '> <label control-label">' . $name . '</label></div>';
        echo '<div class="col-lg-3">' . Html::dropDownList("Generator[inputType][$name]", $generator->inputType[$name],
                $generator->fieldTypes(), ['class' => 'form-control']) . '</div>';
    }
    echo "</div></div>";
}