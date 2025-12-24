<?php 
namespace common\components;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use common\enums\StatusEnum;

class GridView extends \yii\grid\GridView
{
    public $layout = '{items}
    <div class="row">
        <div class="col-sm-6">
            <div class="dataTables_info" id="dynamic-table_info" role="status" aria-live="polite">
            {summary}
            </div>
        </div>
        <div class="col-sm-6">
            <div class="dataTables_paginate paging_simple_numbers" id="dynamic-table_paginate">{pager}</div>
        </div>
    </div>
    ';

    public $pager = [
        'options' => [
            'class' => 'pagination'
        ]
    ];

    public $tableOptions = [
        'class' => 'table table-striped table-bordered table-hover'
    ];

    /**
     * 统计配置
     * @var array
     */
    public $summaryConfig = [
        'enabled' => false, // 是否启用统计
        'modelClass' => null, // 统计的模型类
        'sumField' => 'point', // 求和的字段
        'sumFieldAlias' => 'point', // 求和字段的别名（在summary中使用的变量名）
        'condition' => [], // 附加查询条件
        'with' => [], // 关联查询
        'format' => 'decimal', // 格式化方式：decimal, currency, percent 等
        'defaultValue' => '0', // 默认值
    ];

    public $summary = '第{begin}-{end}条，共{totalCount}条数据';

    public function init()
    {
        parent::init();
        
        // 如果启用了统计且有统计字段，修改summary模板
        if ($this->summaryConfig['enabled'] && !empty($this->summaryConfig['sumField'])) {
            $fieldName = $this->summaryConfig['sumFieldAlias'] ?? $this->summaryConfig['sumField'];
            $this->summary .= '，合计{' . $fieldName . '}';
        }
    }
    
    public function renderSummary()
    {
        $count = $this->dataProvider->getCount();
        if ($count <= 0) {
            return '';
        }
        
        $summaryOptions = $this->summaryOptions;
        $tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
        
        if (($pagination = $this->dataProvider->getPagination()) !== false) {
            $totalCount = $this->dataProvider->getTotalCount();
            $begin = $pagination->getPage() * $pagination->pageSize + 1;
            $end = $begin + $count - 1;
            if ($begin > $end) {
                $begin = $end;
            }
            $page = $pagination->getPage() + 1;
            $pageCount = $pagination->pageCount;
            if (($summaryContent = $this->summary) === null) {
                return Html::tag($tag, Yii::t('yii', 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.', [
                        'begin' => $begin,
                        'end' => $end,
                        'count' => $count,
                        'totalCount' => $totalCount,
                        'page' => $page,
                        'pageCount' => $pageCount,
                    ]), $summaryOptions);
            }
        } else {
            $begin = $page = $pageCount = 1;
            $end = $totalCount = $count;
            if (($summaryContent = $this->summary) === null) {
                return Html::tag($tag, Yii::t('yii', 'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.', [
                    'begin' => $begin,
                    'end' => $end,
                    'count' => $count,
                    'totalCount' => $totalCount,
                    'page' => $page,
                    'pageCount' => $pageCount,
                ]), $summaryOptions);
            }
        }

        if ($summaryContent === '') {
            return '';
        }

        // 准备替换数据 - 关键修改：在键名中添加大括号
        $replaceData = [
            '{begin}' => $begin,
            '{end}' => $end,
            '{count}' => $count,
            '{totalCount}' => $totalCount,
            '{page}' => $page,
            '{pageCount}' => $pageCount,
        ];

        // 如果启用了统计，计算总和
        if ($this->summaryConfig['enabled'] && !empty($this->summaryConfig['modelClass'])) {
            $sumValue = $this->calculateSum();
            $fieldName = $this->summaryConfig['sumFieldAlias'] ?? $this->summaryConfig['sumField'];
            // 关键修改：在统计字段的键名中也添加大括号
            $replaceData['{' . $fieldName . '}'] = $sumValue;
        }

        return Html::tag($tag, strtr($summaryContent, $replaceData), $summaryOptions);
    }

    /**
     * 计算总和
     */
    protected function calculateSum()
    {
        try {
            $modelClass = $this->summaryConfig['modelClass'];
            $sumField   = $this->summaryConfig['sumField'];
            $mainTable  = $modelClass::tableName();          // 主表真实表名

            $query = $modelClass::find()
                ->select([$sumField => "SUM($mainTable.$sumField)"])
                ->from("$mainTable")
                ->where(["$mainTable.status" => StatusEnum::ENABLED]);

            $searchParams = Yii::$app->request->get('SearchModel', []);

            $query->andFilterWhere($searchParams);   // 剩余主表字段

            $sql = $query->createCommand()->rawSql;

            $result = $query->asArray()->one();
            $sum = $result[$sumField] ?? 0;

            return $this->formatSumValue($sum);
        } catch (\Exception $e) {
            Yii::error('计算总和失败：' . $e->getMessage(), 'GridView');
            return $this->summaryConfig['defaultValue'] ?? '0';
        }
    }

    /**
     * 格式化统计值
     */
    protected function formatSumValue($value)
    {
        if (empty($value)) {
            return $this->summaryConfig['defaultValue'];
        }

        $format = $this->summaryConfig['format'] ?? 'decimal';
        
        switch ($format) {
            case 'currency':
                return Yii::$app->formatter->asCurrency($value);
            case 'percent':
                return Yii::$app->formatter->asPercent($value);
            case 'integer':
                return Yii::$app->formatter->asInteger($value);
            case 'decimal':
            default:
                return Yii::$app->formatter->asDecimal($value);
        }
    }
}