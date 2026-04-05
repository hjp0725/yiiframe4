<?php
namespace common\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\web\UnauthorizedHttpException;
use yiiframe\plugs\services\AddonsService;
use yiiframe\plugs\common\AddonHelper;
use common\models\backend\Member;
use common\helpers\StringHelper;
use common\enums\AppEnum;
use common\helpers\ArrayHelper;

/**
 * 系统初始化（PHP 7.3 + MySQL 5.7 兼容）
 * 保持原有功能零侵入
 */
class Init implements BootstrapInterface
{
    /** 应用ID缓存 */
    private $_appId;

    /** 模块注册缓存（一次反射，重复利用） */
    private static $_moduleCache = [];

    /** IP 黑名单状态缓存（避免重复查询） */
    private static $_ipBlackChecked = false;

    /* --------------------------------------------------
     * 入口：BootstrapInterface 实现
     * -------------------------------------------------- */
    public function bootstrap($application)
    {
        // 生成全局唯一标识
        Yii::$app->params['uuid'] = StringHelper::uuid('uniqid');

        $this->_appId = $application->id;

        // 统一加载入口
        $this->loadByApp($this->_appId);
    }

    /* --------------------------------------------------
     * 统一加载逻辑（按应用类型分发）
     * -------------------------------------------------- */
    private function loadByApp($appId)
    {
        $merchantId = $this->resolveMerchantId($appId);

        try {
            Yii::$app->services->merchant->setId($merchantId);
            // 注册插件模块（带缓存）
            Yii::$app->setModules($this->getModulesByAddons());
        } catch (\Exception $e) {
            Yii::info('Init loadByApp exception: ' . $e->getMessage(), 'application');
        }

        // IP 黑名单检查（仅一次）
        $this->checkIpBlacklist($merchantId);
    }

    /* --------------------------------------------------
     * 解析当前商户ID（零魔法字符串）
     * -------------------------------------------------- */
    private function resolveMerchantId($appId)
    {
        // 控制台 & 总后台：空商户
        if (in_array($appId, [AppEnum::CONSOLE, AppEnum::BACKEND], true)) {
            return '';
        }

        // 商户端 & 商户接口：读登录身份
        if (in_array($appId, [AppEnum::MERCHANT, AppEnum::MER_API], true)) {
            /** @var Member $identity */
            $identity = Yii::$app->user->identity;
            return $identity->merchant_id ?? '';
        }

        // 其余：Header > Get 参数
        $id = Yii::$app->request->headers->get('merchant-id', '');
        if ($id === '') {
            $id = Yii::$app->request->get('merchant_id', '');
        }
        return (string)$id;
    }

    /* --------------------------------------------------
     * IP 黑名单检查（惰性加载 + 一次查询）
     * -------------------------------------------------- */
    private function checkIpBlacklist($merchantId)
    {
        if (self::$_ipBlackChecked) {
            return;
        }
        self::$_ipBlackChecked = true;

        // 控制台免检查
        if (Yii::$app->id === AppEnum::CONSOLE) {
            return;
        }

        // 插件未安装直接返回
        if (!AddonHelper::isInstall('IpBlack')) {
            return;
        }

        $config = Yii::$app->debris->addonConfig('IpBlack', true, true);
        if (!($config['sys_ip_blacklist_open'] ?? false)) {
            return;
        }

        $ips = Yii::$app->ipBlackService->ipBlacklist->findIps();
        if (ArrayHelper::ipInArray(Yii::$app->request->userIP, $ips)) {
            throw new UnauthorizedHttpException('您的 IP 已被禁止访问');
        }
    }

    /* --------------------------------------------------
     * 插件模块注册（带缓存 + 常量防错）
     * -------------------------------------------------- */
    private function getModulesByAddons()
    {
        $cacheKey = 'init:modules:' . $this->_appId;
        if (isset(self::$_moduleCache[$cacheKey])) {
            return self::$_moduleCache[$cacheKey];
        }

        $addons = AddonsService::findAllNames();
        $modules = [];

        foreach ($addons as $addon) {
            $name     = $addon['name'];
            $appId    = $this->resolveAddonAppId($addon);

            // 模块注册
            $modules[StringHelper::toUnderScore($name)] = [
                'class' => 'yiiframe\plugs\components\BaseAddonModule',
                'name'  => $name,
                'app_id'=> $appId,
            ];

            // 动态注入服务（一次反射）
            if (!empty($addon['service'])) {
                Yii::$app->set(lcfirst($name) . 'Service', [
                    'class' => $addon['service'],
                ]);
            }
        }

        self::$_moduleCache[$cacheKey] = $modules;
        return $modules;
    }

    /**
     * 解析插件应在哪个 app 下生效
     */
    private function resolveAddonAppId(array $addon)
    {
        // 后台 + 开启商户路由映射 → 走商户路由
        if ($this->_appId === AppEnum::BACKEND && ($addon['is_merchant_route_map'] ?? false)) {
            return AppEnum::MERCHANT;
        }
        return $this->_appId;
    }
}