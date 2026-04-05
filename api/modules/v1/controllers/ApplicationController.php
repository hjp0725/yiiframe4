<?php

namespace api\modules\v1\controllers;

use Yii;
use yii\helpers\Json;
use yii\web\NotFoundHttpException;
use api\controllers\OnAuthController;
use common\helpers\ArrayHelper;
use common\helpers\Auth;
use common\helpers\Url;
use yiiframe\plugs\models\Addons;
use common\models\rbac\AuthItemChild;
use addons\Unioa\common\models\news\Cate;
use common\models\common\Menu;
use common\enums\StatusEnum;


/**
 * 登录接口
 *
 * Class SiteController
 * @author 古月 <21931118@qq.com>
 */
class ApplicationController extends OnAuthController
{
    public $modelClass = '';

    /**
     * 不用进行登录验证的方法
     *
     * 例如： ['index', 'update', 'create', 'view', 'delete']
     * 默认全部需要验证
     *
     * @var array
     */
    protected $authOptional = ['index', 'personal'];

    public function actionIndex()
    {
        // ---------- 1. 确定应用 ----------
        if (Yii::$app->services->devPattern->isSAAS()) {
            $appId = 'backend';
        } elseif (Yii::$app->services->devPattern->isB2B2C()) {
            $appId = 'merchant';
        } else {
            $appId = 'backend';   // 兜底
        }

        // ---------- 2. 用户有权限的 url 集合 ----------
        $roleId = Yii::$app->user->identity->role_id ?? 0;
        $authUrls = AuthItemChild::find()
            ->select('name')
            ->where(['app_id' => $appId, 'role_id' => $roleId])
            ->column();   // 一维数组

        // ---------- 3. 取菜单并过滤 ----------
        $menus = Menu::find()
            ->where([
                'status'   => StatusEnum::ENABLED,
                'app_id'   => $appId,
                'is_addon' => StatusEnum::ENABLED,
            ])
            ->orderBy('sort asc, id asc')
            ->asArray()
            ->all();

        foreach ($menus as $k => &$v) {
            // 去掉含 /unioa/personal 的菜单
            if (str_contains($v['url'], '/unioa/personal')) {
                unset($menus[$k]);
                continue;
            }
            // 权限校验
            if (!in_array($v['url'], $authUrls)) {
                unset($menus[$k]);
                continue;
            }
            // 统一加前缀
            $v['url'] = '/pages/application' . $v['url'];
        }
        unset($v);
        $menus = array_values($menus);   // 重索引

        // ---------- 4. 分支返回 ----------
        // 4.1 树形：group = 1
        if (Yii::$app->params['group']) {
            return $this->buildTreeWithAddonTop($menus, $appId);
        }

        // 4.2 平铺：group = 0 → 所有叶子（含 pid=0 的叶子）
        return $this->buildLeavesFlat($menus);
    }

    /* -------------------- 私有助手 -------------------- */

    /**
     * 树形：仅给「pid=0 且无子级」的菜单套插件壳；
     * 有子级的 pid=0 菜单直接当顶级节点返回
     */
    private function buildTreeWithAddonTop(array $menus, string $appId): array
    {
        // 1. 先把真实树拼出来（pid>0 的已经带层级）
        $realTree = ArrayHelper::itemsMerge($menus, 0, 'id', 'pid', 'list');

        // 2. 按插件归并「pid=0」的顶级菜单
        $addonTop = [];                                 // 以 addons_name 为 key
        foreach ($realTree as $top) {
            if ($top['pid'] == 0 && empty($top['list'])) {   // 无子的顶级
                $addonTop[$top['addons_name']][] = $top;
            }
        }

        // 3. 每个插件只生成一个壳，把同插件所有顶级菜单收进去
        $addonMap = ArrayHelper::map(
            Addons::find()->select(['name', 'title', 'icon'])
                ->where(['status' => StatusEnum::ENABLED])
                ->all(),
            'name', 'title'
        );

        $result = [];
        foreach ($addonTop as $aname => $items) {
            $result[] = [
                'id'          => 'addon-' . md5($aname . $appId),
                'title'       => $addonMap[$aname] ?? $aname,
                'app_id'      => $appId,
                'addons_name' => $aname,
                'is_addon'    => 1,
                'cate_id'     => 0,
                'pid'         => 0,
                'url'         => '',
                'icon'        => 'fa fa-puzzle-piece',   // 也可读插件表里的 icon
                'level'       => 1,
                'dev'         => 0,
                'sort'        => 999,
                'params'      => '[]',
                'tree'        => 'tr_0 ',
                'status'      => StatusEnum::ENABLED,
                'created_at'  => time(),
                'updated_at'  => time(),
                'list'        => $items,   // 多条顶级菜单一次性挂进来
            ];
        }

        // 4. 原来「有子级」的顶级节点直接保留
        foreach ($realTree as $top) {
            if ($top['pid'] == 0 && !empty($top['list'])) {
                $result[] = $top;
            }
        }

        return $result;
    }

    /**
     * 平铺：返回所有叶子节点（无子级即可，含 pid=0 的叶子）
     */
    private function buildLeavesFlat(array $menus): array
    {
        // 先树形化（方便判断子级）
        $tree = ArrayHelper::itemsMerge($menus, 0, 'id', 'pid', 'list');

        // 递归收叶子
        $leaves = [];
        foreach ($tree as $node) {
            $leaves = array_merge($leaves, $this->collectLeaves($node));
        }
        return $leaves;
    }

    /**
     * 递归收叶子（无子级即叶子）
     */
    private function collectLeaves(array $node): array
    {
        $leaves = [];
        // 当前节点先保存（去掉子集键）
        $self = $node;
        unset($self['list']);

        if (empty($node['list'])) {
            // 没有子级 → 叶子
            $leaves[] = $self;
        } else {
            // 有子级 → 继续递归
            foreach ($node['list'] as $child) {
                $leaves = array_merge($leaves, $this->collectLeaves($child));
            }
        }
        return $leaves;
    }
    //个人中心菜单
    public function actionPersonal()
    {
        if(Yii::$app->services->devPattern->isSAAS())
            $app_id = 'backend';
        if (Yii::$app->services->devPattern->isB2B2C())
            $app_id = 'merchant';
        $menus = Menu::find()
            ->where(['status' => StatusEnum::ENABLED,'app_id'=>$app_id,'is_addon'=>StatusEnum::ENABLED])
            ->andWhere(['like','url','/unioa/personal'])
            ->orderBy('sort asc, id asc')
            ->asArray()
            ->all();
        // 获取权限信息
        $AuthItems = AuthItemChild::find()
                ->select('name')
                ->where(['app_id'=>$app_id,'role_id'=>Yii::$app->user->identity->role_id])
                ->asArray()
                ->all();
        $items = [];
        foreach ($AuthItems as $key => $item) {
            $items[] = $item['name'];
        }
        foreach ($menus as $key => &$menu) {
            // 系统菜单校验
            if(!in_array($menu['url'], $items)){
                unset($menus[$key]);
            }
            $menu['url'] = '/pages/application' . $menu['url'];

        }
        
        return array_values(array_filter($menus,function($v) { return $v['pid'] != 0;}));
    }

    public function actionGroup(){
        return Yii::$app->params['group'];
    }
  
}
