<?php
/**
 * This is the template for generating a controller class file.
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\controller\Generator */

echo "<?php\n";
?>

namespace <?= $generator->getControllerNamespace() ?>;
use Yii;
use <?= ltrim($generator->modelClass, '\\') ?>;
use common\helpers\ArrayHelper;

class <?= StringHelper::basename($generator->controllerClass) ?> extends \api\controllers\OnAuthController
{
    public $modelClass = <?= StringHelper::basename($generator->modelClass) ?>::class;
    protected $authOptional = ['index'];

<?php
/* =====  根据控件类型自动生成下拉/单选/复选接口  ===== */
$inputType  = $generator->inputType ?? [];
$tableSchema= $generator->getTableSchema();
foreach ($inputType as $fieldName => $ctrlType) {
    if (!in_array($ctrlType, ['dropDownList','radioList','checkboxList'], true)) {
        continue;
    }
    if ($fieldName=='status') {
        continue;
    }
    $column = $tableSchema->columns[$fieldName] ?? null;
    if (!$column) {
        continue;
    }
    /* 解析可选项：优先 ENUM，其次备注 */
    /* 1. 优先用「备注」解析  1=事假,2=病假,3=休假 */
    $map = $generator->parseCommentToMap($column->comment);
    /* 2. 输出 actionXxx() */
    $actionId = 'action' . Inflector::camelize($fieldName);

    /* 紧凑 [] 格式 */
    if (empty($map)) {
        $phpArr = "['1' => '选项1', '2' => '选项2']";
    } else {
        $items  = [];
        foreach ($map as $k => $v) {
            $items[] = "'" . addslashes($k) . "' => '" . addslashes($v) . "'";
        }
        $phpArr = '[' . implode(', ', $items) . ']';
    }
?>

    /**
     * 获取 <?= $fieldName ?> 可选项
     * @return array
     */
    public function <?= $actionId ?>()
    {
        return ArrayHelper::arrayToArrays(<?= $phpArr ?>);
    }

<?php } // endforeach ?>
}