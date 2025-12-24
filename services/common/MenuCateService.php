<?php 

namespace services\common;

use Yii;
use common\helpers\Auth;
use common\helpers\ArrayHelper;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\components\Service;
use common\models\common\MenuCate;

/**
 * 菜单分类业务层
 *
 * @package services\common
 * @author  jianyan74 <751393839@qq.com>
 */
class MenuCateService extends Service
{
    /**
     * 根据插件名删除分类
     * @param string $addons_name
     * @return int
     */
    public function delByAddonsName(string $addons_name): int
    {
        return MenuCate::deleteAll(['addons_name' => $addons_name]);
    }

    /**
     * 为插件创建菜单分类
     * @param string $appId
     * @param array $info
     * @param string $icon
     * @return MenuCate
     */
    public function createByAddons(string $appId, array $info, string $icon): MenuCate
    {
        MenuCate::deleteAll(['app_id' => $appId, 'addons_name' => $info['name']]);

        $model = new MenuCate();
        $model->app_id      = $appId;
        $model->addons_name = $info['name'];
        $model->is_addon    = WhetherEnum::ENABLED;
        $model->title       = $info['title'];
        $model->icon        = $icon;
        $model->save(false);

        return $model;
    }

    /**
     * 获取当前用户有权限的启用的分类（含插件）
     * @return MenuCate[]
     */
    public function getOnAuthList(): array
    {
        $models = $this->findAll();
        foreach ($models as $k => $m) {
            // 原生菜单：校验 cate:id 权限
            if ($m['is_addon'] == WhetherEnum::DISABLED
                && !Auth::verify('cate:' . $m['id'])
            ) {
                unset($models[$k]);
                continue;
            }
            // 插件菜单：校验插件权限
            if ($m['is_addon'] == WhetherEnum::ENABLED
                && !Auth::verify($m['addons_name'])
            ) {
                unset($models[$k]);
                continue;
            }
            // B2B2C 商户端隐藏 Flow 插件
            if ($m['app_id'] === 'merchant'
                && $m['addons_name'] === 'Flow'
                && Yii::$app->services->devPattern->isB2B2C()
            ) {
                unset($models[$k]);
            }
        }

        return array_values($models);
    }

    /**
     * 获取指定应用下「非插件中心」分类下拉
     * @param string $app_id
     * @return array
     */
    public function getDefaultMap(string $app_id): array
    {
        return ArrayHelper::map($this->findDefault($app_id), 'id', 'title');
    }

    /**
     * 获取指定应用下「非插件中心」且启用的分类
     * @param string $app_id
     * @return MenuCate[]
     */
    public function findDefault(string $app_id): array
    {
        return MenuCate::find()
            ->where(['addon_centre' => StatusEnum::DISABLED])
            ->andWhere(['app_id' => $app_id])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->orderBy('sort asc')
            ->asArray()
            ->all();
    }

    /**
     * 获取当前应用下启用的分类（含插件）
     * 商户端仅返回已安装的插件菜单
     * @return MenuCate[]
     */
    public function findAll(): array
    {
        $cates = MenuCate::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => Yii::$app->id])
            ->orderBy('sort asc, id asc')
            ->asArray()
            ->all();

        // 商户端：只保留已安装的插件菜单 + 固定三个系统菜单
        if (Yii::$app->id === 'merchant') {
            $installed = \addons\Merchants\common\models\Addons::find()
                ->select(['name'])
                ->where(['merchant_id' => Yii::$app->user->identity->merchant_id])
                ->column();

            $menus  = [];
            $system = []; // 缓存系统菜单顺序

            foreach ($cates as $cate) {
                // 已安装插件
                if ($cate['is_addon'] && in_array($cate['addons_name'], $installed, true)) {
                    $menus[] = $cate;
                    continue;
                }
                // 系统菜单缓存
                if (in_array($cate['addons_name'], ['Merchants', 'TinyShop', ''], true)) {
                    $system[$cate['addons_name']] = $cate;
                }
            }

            // 固定顺序追加系统菜单
            foreach (['Merchants', 'TinyShop', ''] as $key) {
                if (isset($system[$key])) {
                    $menus[] = $system[$key];
                }
            }

            return $menus;
        }

        return $cates;
    }

    /**
     * 根据主键查找模型
     * @param int $id
     * @return MenuCate|null
     */
    public function findById(int $id): ?MenuCate
    {
        return MenuCate::findOne($id);
    }
    /**
     * 根据appId和name查找模型
     * @param int $id
     * @return MenuCate|null
     */
    public function findByAddon(string $appId,string $name): ?MenuCate
    {
        return MenuCate::find()
            ->where(['app_id'=>$appId,'addons_name'=>$name])
            ->one();
    }
    /**
     * 获取指定应用下第一个启用的分类 ID
     * @param string $app_id
     * @return int|null
     */
    public function findFirstId(string $app_id): ?int
    {
        return (int) MenuCate::find()
            ->select(['id'])
            ->where(['status' => StatusEnum::ENABLED])
            ->andWhere(['app_id' => $app_id])
            ->orderBy('sort asc')
            ->scalar();
    }
}