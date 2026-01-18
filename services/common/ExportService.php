<?php
namespace services\common;

use Yii;
use yii\db\ActiveQuery;
use common\models\base\SearchModel;
use common\helpers\ExcelHelper;

/**
 * 通用导出抽象
 *  - 子类只须覆写 config()/queryCallback()/fileName()
 */
abstract class ExportService
{
    /**
     * 返回配置数组
     * @return array 格式同 ExcelHelper::exportData 的 $header
     */
    abstract protected function config(): array;

    /**
     * 可选：对 Query 再加工（join、特殊 where）
     * @param ActiveQuery $query
     */
    protected function queryCallback(ActiveQuery $query): void {}

    /**
     * 文件名（不含后缀）
     * @return string
     */
    abstract protected function fileName(): string;

    /**
     * 主入口
     * @param array $get 通常为 Yii::$app->request->get()
     * @return mixed ExcelHelper::exportData() 直接输出
     */
    public function export(array $get)
    {
        /* —— 1. 组装 SearchModel —— */
        $searchModel = new SearchModel($this->searchDefinitions());

        /* —— 2. 应用过滤条件 —— */
        $dataProvider = $searchModel->search($get);
        $query        = $dataProvider->query;          // 已带 relations + 默认排序
        $this->queryCallback($query);

        /* —— 3. 附加当前模块特有的过滤 —— */
        $this->applyCustomFilter($query, $get);

        $list = $query->all();
        if (empty($list)) {
            return false;
        }

        /* —— 4. 导出 —— */
        return ExcelHelper::exportData(
            $list,
            $this->config(),
            $this->fileName() . '_' . date('YmdHis'),
            'xlsx'
        );
    }

    /* -------------------- 可覆写或复用的 protected 方法 -------------------- */

    /**
     * SearchModel 初始化参数
     * 子类可按需覆写
     */
    protected function searchDefinitions(): array
    {
        return [
            'model'                  => $this->modelClass(),
            'scenario'               => 'default',
            'relations'              => $this->relations(),
            'partialMatchAttributes' => ['title'],
            'defaultOrder'           => ['id' => SORT_DESC],
            'pageSize'               => -1,
        ];
    }

    /**
     * 模型类名
     * @return string
     */
    abstract protected function modelClass(): string;

    /**
     * 关联关系
     * @return array
     */
    abstract protected function relations(): array;

    /**
     * 留给子类“追加特殊条件”的钩子
     * @param ActiveQuery $query
     * @param array $get
     */
    protected function applyCustomFilter(ActiveQuery $query, array $get): void {}
}