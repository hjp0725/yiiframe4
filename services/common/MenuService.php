<?php 

namespace services\common;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\Json;
use common\components\Service;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\helpers\ArrayHelper;
use common\helpers\Auth;
use common\helpers\StringHelper;
use common\helpers\TreeHelper;
use common\models\common\Menu;
use common\models\common\MenuCate;


/**
 * Class MenuService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class MenuService extends Service
{
    /* -------------------- 写操作 -------------------- */
    public function delByAddonsName(string $addons_name): void
    {
        Menu::deleteAll(['addons_name' => $addons_name]);
    }

    public function delByCate(MenuCate $cate): void
    {
        Menu::deleteAll(['app_id' => $cate->app_id, 'addons_name' => $cate->addons_name]);
    }

    /**
     * 根据插件配置批量生成菜单
     */
    public function createByAddons(array $menus, MenuCate $cate, int $pid = 0, int $level = 1, ?Menu $parent = null): void
    {
        $menus = ArrayHelper::regroupMapToArr($menus);

        foreach ($menus as $menu) {
            $model = new Menu();
            $model->attributes = $menu;

            if ($parent !== null) {
                $model->setParent($parent);
            }

            if (!empty($model->params) && is_array($model->params)) {
                $params = [];
                foreach ($model->params as $k => $v) {
                    $params[] = ['key' => $k, 'value' => $v];
                }
                $model->params = $params;
            }

            $model->url         = '/' . StringHelper::toUnderScore($cate->addons_name) . '/' . $menu['route'];
            $model->pid         = $pid;
            $model->level       = $level;
            $model->cate_id     = $cate->id;
            $model->app_id      = $cate->app_id;
            $model->addons_name = $cate->addons_name;
            $model->is_addon    = $cate->is_addon;
            $model->save();

            if (!empty($menu['child'])) {
                $this->createByAddons($menu['child'], $cate, $model->id, $model->level + 1, $model);
            }
        }
    }

    /* -------------------- 读操作 -------------------- */
    public function getDropDown(MenuCate $menuCate, string $app_id,  $id = 0): array
    {
        $list = Menu::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['app_id' => $app_id])
            ->andWhere(['is_addon' => $menuCate->is_addon])
            ->andFilterWhere(['addons_name' => $menuCate->addons_name])
            ->andFilterWhere(['<>', 'id', $id])
            ->select(['id', 'title', 'pid', 'level'])
            ->orderBy('cate_id asc, sort asc')
            ->asArray()
            ->all();

        $models = ArrayHelper::itemsMerge($list);
        $data   = ArrayHelper::map(ArrayHelper::itemsMergeDropDown($models), 'id', 'title');

        return ArrayHelper::merge([0 => Yii::t('app', '顶级菜单')], $data);
    }

    /**
     * 返回已合并的菜单树（带权限过滤）
     */
    public function getOnAuthList(): array
    {
        $models = $this->findAll();

        foreach ($models as $key => &$model) {
            if (!empty($model['url'])) {
                $params = Json::decode($model['params']);
                if (empty($params) || !is_array($params)) {
                    $params = [];
                }

                $model['fullUrl'] = [$model['url']];
                foreach ($params as $p) {
                    if (!empty($p['key'])) {
                        $model['fullUrl'][$p['key']] = $p['value'];
                    }
                }
            } else {
                $model['fullUrl'] = '#';
            }

            // 系统菜单权限校验
            if (
                $model['is_addon'] == WhetherEnum::DISABLED &&
                Auth::verify($model['url']) === false
            ) {
                unset($models[$key]);
                continue;
            }

            // 插件菜单权限校验
            if (
                $model['is_addon'] == WhetherEnum::ENABLED &&
                Auth::verify($model['url']) === false
            ) {
                unset($models[$key]);
            }
        }

        return ArrayHelper::itemsMerge($models);
    }

    /**
     * 所有启用的菜单（含 cate 关联）
     */
    public function findAll(): array
    {
        $query = Menu::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => Yii::$app->id]);

        // 非开发模式隐藏 dev 菜单
        if (!Yii::$app->debris->backendConfig('sys_dev')) {
            $query->andWhere(['dev' => StatusEnum::DISABLED]);
        }

        $models = $query
            ->with([
                'cate' => function (ActiveQuery $q) {
                    $q->andWhere(['app_id' => Yii::$app->id]);
                },
            ])
            ->orderBy('sort asc, id asc')
            ->asArray()
            ->all();

        // 手动剔除开发工具菜单
        foreach ($models as $k => $v) {
            if (
                in_array($v['url'], ['/gii', '/plugs'], true) &&
                !Yii::$app->debris->backendConfig('sys_dev')
            ) {
                unset($models[$k]);
            }
        }

        return array_values($models);
    }

    /**
     * 根据 tree 前缀查找所有子菜单
     */
    public function findChildByID(string $tree, int $id): array
    {
        return Menu::find()
            ->where(['like', 'tree', $tree . TreeHelper::prefixTreeKey($id) . '%', false])
            ->select(['id', 'level', 'tree', 'pid'])
            ->asArray()
            ->all();
    }
}