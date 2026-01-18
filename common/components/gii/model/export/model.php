<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $queryClassName string query class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $properties array list of properties (property => [type, name. comment]) */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */
// echo 之前先取注释
$tableComment = '';
$cmd = Yii::$app->db->createCommand(
    'SELECT table_comment FROM information_schema.tables '
  . 'WHERE table_schema = DATABASE() AND table_name = :table',
    [':table' => $tableName]
);
$tableComment = $cmd->queryScalar() ?: $tableName;
echo "<?php\n";
?>

namespace <?= $generator->ns ?>;

use yii\db\ActiveQuery;

/**
 * This is the export class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($properties as $property => $data): ?>
 * @property <?= "{$data['type']} \${$property}"  . ($data['comment'] ? ' ' . strtr($data['comment'], ["\n" => ' ']) : '') . "\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends \services\common\ExportService
{
    protected function modelClass(): string
    {
        return <?= preg_replace('/Export$/', '', $className) ?>::class;
    }
    protected function relations(): array
    {
        return [
            'member'      => ['title'],
        ];
    }

    protected function fileName(): string
    {
        return '<?= $tableComment ?: $tableName ?>';
    }

    /**
     * 表头、字段、回调 统一放配置，告别硬编码
     */
    protected function config(): array
    {
        <?php
        $listFields = !empty($generator->listFields) ? $generator->listFields : [];
        $listFields     = array_flip($listFields); 
        $labels     = array_intersect_key($labels, $listFields);

        $mapable = ['dropDownList', 'radioList', 'checkboxList'];

        ?>
        
        return [
<?php foreach ($labels as $name => $label):
    list($type, $linkTable) = array_pad(explode('|', $generator->inputType[$name] ?? ''), 2, '');
    $mapable = ['dropDownList','radioList','checkboxList'];
?>
<?php   if (in_array($type, $mapable)):
        // 先拼好 items 字符串（单行）
        if (!empty($linkTable)):
            $items = $generator->getRelationModelClass($linkTable) . '::dropDown()';
        else:
            $map = $generator->parseCommentToMap($properties[$name]['comment']);
            if (!empty($map)):
                $items = '[' . implode(',', array_map(
                        function ($k, $v) { return "'$k'=>'$v'"; },
                        array_keys($map), $map
                )) . ']';
            else:
                $items = "['1'=>'选项1','2'=>'选项2']";
            endif;
        endif; ?>
            ['<?= $label ?>', '<?= $name ?>', 'selectd', <?= $items ?>],
<?php   elseif ($type === 'Select2'): ?>
            ['<?= $label ?>', '', 'function', function ($model) {
                $ids = is_array($model-><?= $name ?>) 
                    ? $model-><?= $name ?> 
                    : json_decode($model-><?= $name ?>, true);
                
                if (!is_array($ids) || !$ids) return '未知';

                $map = <?= $items ?>;
                $names = array_filter(array_map(function ($id) use ($map) {
                    return $map[$id] ?? '';
                }, $ids));
                
                return implode(', ', $names) ?: '未知';
            }],
<?php   elseif ($name === 'member_id'): ?>
            ['用户', 'member.title'],
<?php   elseif ($name === 'created_at'): ?>
            ['创建时间', 'created_at', 'date', 'Y-m-d H:i:s'],
<?php   else: ?>
            ['<?= $label ?>', '<?= $name ?>'],
<?php   endif; ?>
<?php endforeach; ?>
        ];
    }

    /**
     * 追加当前模块特有的过滤
     */
    protected function applyCustomFilter(ActiveQuery $query, array $get): void
    {
        /* —— 当前模块特有的过滤 —— */
        $query->andWhere([<?= preg_replace('/Export$/', '', $className) ?>::tableName() .'.status'=>\common\enums\StatusEnum::ENABLED]);

        /* —— 其余普通字段用 SearchModel 已自动处理 —— */
    }
}
