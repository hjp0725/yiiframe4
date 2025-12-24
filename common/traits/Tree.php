<?php 

namespace common\traits;

use common\helpers\ArrayHelper;
use common\helpers\TreeHelper;

/**
 * Trait Tree
 *
 * 注意：必须带有
 * id、pid、level、tree 字段
 *
 * 选择使用
 * public function getParent()
 * {
 *      return $this->hasOne(self::class, ['id' => 'pid']);
 * }
 *
 * @package common\traits
 */
trait Tree
{
    /**
     * 关联父级
     */
    public function getParent(): \yii\db\ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'pid']);
    }

    /**
     * 新建/更新时自动维护树路径
     */
    public function beforeSave($insert): bool
    {
        $this->autoUpdateTree();
        return parent::beforeSave($insert);
    }

    /**
     * 删除时自动清掉所有下级
     */
    public function beforeDelete(): bool
    {
        $this->autoDeleteTree();
        return parent::beforeDelete();
    }

    /* -------------------- 私有 -------------------- */
    private function autoDeleteTree(): void
    {
        self::deleteAll(
            ['like', 'tree', $this->tree . TreeHelper::prefixTreeKey($this->id) . '%', false]
        );
    }

    private function autoUpdateTree(): void
    {
        /* 新增场景 */
        if ($this->isNewRecord) {
            if ((int)$this->pid === 0) {
                $this->tree  = TreeHelper::defaultTreeKey();
                $this->level = 1;
            } else {
                list($level, $tree) = $this->getParentData();
                $this->level = $level;
                $this->tree  = $tree;
            }
            return;
        }

        /* 修改父级场景 */
        $oldPid = isset($this->oldAttributes['pid']) ? (int)$this->oldAttributes['pid'] : 0;
        if ($oldPid !== (int)$this->pid && (int)$this->pid !== (int)$this->id) {
            list($newLevel, $newTree) = $this->getParentData();

            $list = self::find()
                ->where(['like', 'tree', $this->tree . TreeHelper::prefixTreeKey($this->id) . '%', false])
                ->select(['id', 'level', 'tree', 'pid'])
                ->asArray()
                ->all();

            $distance = $newLevel - $this->level;
            $merge    = ArrayHelper::itemsMerge($list, $this->id);
            $this->recursionUpdate($merge, $distance, $newTree);

            $this->level = $newLevel;
            $this->tree  = $newTree;
        }
    }

    /**
     * 递归批量更新子级 level / tree
     */
    private function recursionUpdate(array $data, int $distance, string $newParentTree): void
    {
        $ids  = [];
        $level = '';
        $tree  = '';

        foreach ($data as $item) {
            $ids[] = (int)$item['id'];
            if ($level === '') {
                $level = (int)$item['level'] + $distance;
            }
            if ($tree === '') {
                $tree = str_replace($this->tree, $newParentTree, $item['tree']);
            }

            if (!empty($item['-'])) {
                $this->recursionUpdate($item['-'], $distance, $newParentTree);
            }
        }

        if ($ids) {
            self::updateAll(['level' => $level, 'tree' => $tree], ['in', 'id', $ids]);
        }
    }

    /**
     * 取得父级 level + tree
     */
    private function getParentData(): array
    {
        $parent = $this->parent;
        if (!$parent) {
            return [1, TreeHelper::defaultTreeKey()];
        }

        $level = (int)$parent->level + 1;
        $tree  = $parent->tree . TreeHelper::prefixTreeKey((int)$parent->id);

        return [$level, $tree];
    }
}