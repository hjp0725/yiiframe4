<?php 

namespace services\backend;

use Yii;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\TreeHelper;
use common\components\Service;
use common\models\backend\Department;

/**
 * Class DepartmentService
 * @package services\backend
 */
class DepartmentService extends Service
{
    /**
     * 下拉选项（编辑用）
     */
    public function getDropDownForEdit($id = ''): array
    {
        $list = Department::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->andFilterWhere(['<>', 'id', $id])
            ->select(['id', 'title', 'pid', 'level'])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($list);
        $data   = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        return ArrayHelper::merge([0 => Yii::t('app', '顶级分类')], $data);
    }

    /**
     * 树形下拉
     */
    public function getMapList(): array
    {
        $models = ArrayHelper::itemsMerge($this->getList());
        return ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');
    }

    /**
     * 普通 map
     */
    public function getMap(): array
    {
        return ArrayHelper::map($this->findAll(), 'id', 'title');
    }

    /**
     * 启用的部门列表
     */
    public function getList(): array
    {
        return Department::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * 首页推荐区块
     */
    public function findIndexBlock(): array
    {
        return Department::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['index_block_status' => StatusEnum::ENABLED])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->cache(60)
            ->asArray()
            ->all();
    }

    /**
     * 获取某节点所有下级 ID（含自己）
     */
    public function findChildIdsById($id): array
    {
        $model = $this->findById($id);
        if (!$model) {
            return [];
        }

        $tree = $model['tree'] . TreeHelper::prefixTreeKey($id);
        $list = $this->getChilds($tree);

        return array_merge([$id], array_column($list, 'id'));
    }

    /**
     * 根据 tree 路径取所有下级
     */
    public function getChilds($tree): array
    {
        return Department::find()
            ->where(['like', 'tree', $tree . '%', false])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->asArray()
            ->all();
    }

    /**
     * 单条记录（含软删除）
     */
    public function findById($id): ?array
    {
        return Department::find()
            ->where(['id' => $id])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->asArray()
            ->one();
    }

    public function findAll(): array
    {
        return Department::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->asArray()
            ->all();
    }

    /* ---------- 企业微信同步 ---------- */

    /**
     * 批量同步部门 ID（仅 id 映射）
     */
    public function syncAllDepartmentid(int $rootParentId = 1): array
    {
        $wxDepts = Yii::$app->wechat->work->department->list()['department'] ?? [];
        // 排除根节点
        $wxDepts = array_filter($wxDepts, static function ($d) {
            return $d['id'] != 1;
        });
        $wxDepts = array_filter($wxDepts, static function ($dept) use ($rootParentId) {
            return $dept['parentid'] >= $rootParentId;
        });

        if (!$wxDepts) {
            return [0, 0, '无部门返回'];
        }

        $wxDeptIds   = array_column($wxDepts, 'id');
        $pageSize    = 500;
        $totalPage   = ceil(count($wxDeptIds) / $pageSize);
        $inserted    = 0;
        $merchantId  = Yii::$app->user->identity->merchant_id;
        $now         = time();

        for ($i = 0; $i < $totalPage; $i++) {
            $slice   = array_slice($wxDeptIds, $i * $pageSize, $pageSize);
            $exists  = $this->getListByDepartmentids($slice);
            $existsMap = array_flip(ArrayHelper::getColumn($exists, 'department_id'));

            $addList = [];
            foreach ($slice as $deptId) {
                if (!isset($existsMap[$deptId])) {
                    $addList[] = [$merchantId, $deptId, $now, $now];
                }
            }

            if ($addList) {
                Yii::$app->db->createCommand()
                    ->batchInsert(
                        Department::tableName(),
                        ['merchant_id', 'department_id', 'created_at', 'updated_at'],
                        $addList
                    )
                    ->execute();
                $inserted += count($addList);
            }
        }

        return [count($wxDeptIds), $inserted, ''];
    }

    /**
     * 单部门详情同步
     */
    public function syncByDepartmentid(int $departmentid): void
    {
        $list = Yii::$app->wechat->work->department->list()['department'] ?? [];
        $wx   = null;
        foreach ($list as $dept) {
            if ($dept['id'] == $departmentid) {
                $wx = $dept;
                break;
            }
        }
        if (!$wx) {
            return;
        }

        $localParentId = $wx['parentid'] == 0 ? 0 : ($this->findByDepartmentid($wx['parentid'])->id ?? 0);
        $leaderIds     = $wx['department_leader'] ?? $wx['leader'] ?? [];
        $leaderId      = is_array($leaderIds) ? ($leaderIds[0] ?? 0) : ($leaderIds ?: 0);

        $model = $this->findByDepartmentid($departmentid);
        if ($model) {
            $model->title            = $wx['name'];
            $model->pid              = $localParentId;
            $model->sort             = $wx['order'];
            $model->department_leader = $leaderId;
            $model->save();
        }
    }

    /* ---------- 辅助查询 ---------- */

    public function findByDepartmentid(int $departmentid): ?Department
    {
        return Department::find()
            ->where(['department_id' => $departmentid])
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->one();
    }

    public function getListByDepartmentids(array $departmentids): array
    {
        return Department::find()
            ->select('department_id')
            ->where(['in', 'department_id', $departmentids])
            ->andFilterWhere(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->asArray()
            ->all();
    }

    /**
     * 分页获取关注列表
     */
    public function getFollowListByPage(int $page = 0): array
    {
        return Department::find()
            ->where(['merchant_id' => Yii::$app->user->identity->merchant_id])
            ->orderBy(['department_id' => SORT_DESC])
            ->offset(10 * $page)
            ->limit(10)
            ->asArray()
            ->all();
    }
}