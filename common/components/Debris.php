<?php
namespace common\components;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use common\enums\AppEnum;
use common\helpers\ArrayHelper;
use yiiframe\plugs\common\AddonHelper;

/**
 * 配置 & 工具集合（PHP 7.3 + MySQL 5.7）
 * 零侵入，保持原有功能
 */
class Debris
{
    /** 缓存前缀（避免冲突） */
    const CACHE_PREFIX = 'debris:config:';

    /** 微信 Token 刷新标识（防止并发） */
    private static $_tokenRefreshing = false;

    /** 本地缓存池（惰性加载） */
    private static $_configCache = [];

    /* --------------------------------------------------
     * 公共配置读取（带标签缓存）
     * -------------------------------------------------- */

    /**
     * 单值配置
     * @param string $name
     * @param bool $noCache
     * @param string $merchant_id
     * @return string|null
     */
    public function config($name, $noCache = false, $merchant_id = '')
    {
        $all = $this->configAll($noCache, $merchant_id);
        return isset($all[$name]) ? trim($all[$name]) : null;
    }

    /**
     * 全部配置（标签缓存 + 惰性加载）
     * @param bool $noCache
     * @param string $merchant_id
     * @return array
     */
    public function configAll($noCache = false, $merchant_id = '')
    {
        !$merchant_id && $merchant_id = Yii::$app->services->merchant->getId();
        $app_id = $merchant_id ? AppEnum::MERCHANT : AppEnum::BACKEND;
        $key    = self::CACHE_PREFIX . $app_id . ':' . $merchant_id;

        // 本地池命中
        if (!$noCache && isset(self::$_configCache[$key])) {
            return self::$_configCache[$key];
        }

        // 缓存标签（支持批量失效）
        $cache = Yii::$app->cache;
        if ($noCache || !($data = $cache->get($key))) {
            $rows = Yii::$app->services->config->findAllWithValue($app_id, $merchant_id);
            $data = [];
            foreach ($rows as $row) {
                $data[$row['name']] = $row['value']['data'] ?? $row['default_value'];
            }
            $cache->set($key, $data, 3600, new \yii\caching\TagDependency(['tags' => 'config:' . $app_id]));
        }

        self::$_configCache[$key] = $data;
        return $data;
    }

    /**
     * 后台配置快捷入口
     */
    public function backendConfig($name, $noCache = false)
    {
        return $this->config($name, $noCache, '');
    }

    public function backendConfigAll($noCache = false)
    {
        return $this->configAll($noCache, '');
    }

    /**
     * 商户配置快捷入口
     */
    public function merchantConfig($name, $noCache = false, $merchant_id = '')
    {
        return $this->config($name, $noCache, $merchant_id);
    }

    public function merchantConfigAll($noCache = false, $merchant_id = '')
    {
        return $this->configAll($noCache, $merchant_id);
    }

    /**
     * 插件配置（保持原样）
     */
    public function addonConfig(string $name = '', bool $noCache = true, bool $backend = false): array
    {
        // 1. 先拿到原始值
        $rawId = Yii::$app->services->merchant->getId();   // 可能 null/0
        // 2. 如果是后台，或者原始值就是 0，则统一按后台处理
        $merchant_id = ($backend || !$rawId) ? 0 : $rawId;
        $app_id      = ($backend || !$rawId) ? AppEnum::BACKEND : AppEnum::MERCHANT;

        return AddonHelper::findConfig($noCache, $merchant_id, $name, $app_id) ?: [];
    }
    public function getAllInfo($noCache, $app_id, $merchant_id = '')
    {
        // 获取缓存信息
        $cacheKey = 'config:' . $merchant_id . $app_id;
        if ($noCache == false && !empty($this->config[$cacheKey])) {
            return $this->config[$cacheKey];
        }

        if ($noCache == true || !($this->config[$cacheKey] = Yii::$app->cache->get($cacheKey))) {
            $config = Yii::$app->services->config->findAllWithValue($app_id, $merchant_id);
            $this->config[$cacheKey] = [];

            foreach ($config as $row) {
                $this->config[$cacheKey][$row['name']] = $row['value']['data'] ?? $row['default_value'];
            }

            Yii::$app->cache->set($cacheKey, $this->config[$cacheKey], 60 * 60);
        }

        return $this->config[$cacheKey];
    }
    /* --------------------------------------------------
     * 工具函数
     * -------------------------------------------------- */

    /**
     * 打印变量（保持原样）
     */
    public function p(...$array)
    {
        echo '<pre>';
        count($array) === 1 ? print_r($array[0]) : print_r($array);
        echo '</pre>';
    }

    /**
     * 系统异常解析（增强上下文）
     */
    public function getSysError(\Exception $e)
    {
        return [
            'errorMessage' => $e->getMessage(),
            'type'         => get_class($e),
            'file'         => $e->getFile(),
            'line'         => $e->getLine(),
            'stack-trace'  => explode("\n", $e->getTraceAsString()),
        ];
    }

    /**
     * 模型首错误提取（保持原样）
     */
    public function analyErr($firstErrors)
    {
        if (!is_array($firstErrors) || empty($firstErrors)) {
            return '未捕获到错误信息';
        }
        return (string) reset($firstErrors);
    }

    /**
     * 检测客户端版本（保持原样）
     */
    public function detectVersion()
    {
        $detect = Yii::$app->mobileDetect;
        if ($detect->isMobile()) {
            foreach ($detect->getOperatingSystems() as $key => $val) {
                if ($detect->is($key)) {
                    return $key . $detect->version($key);
                }
            }
        }
        return $detect->getUserAgent();
    }

    /**
     * 微信错误解析（自动刷新 token 一次）
     */
    public function getWechatError($message, $direct = true)
    {
        if (!isset($message['errcode']) || $message['errcode'] == 0) {
            return false;
        }

        // 40001 过期：仅刷新一次
        if ($message['errcode'] == 40001 && !self::$_tokenRefreshing) {
            self::$_tokenRefreshing = true;
            Yii::$app->wechat->app->access_token->getToken(true);
            self::$_tokenRefreshing = false;
            return false; // 让调用方重试
        }

        if ($direct) {
            throw new UnprocessableEntityHttpException($message['errmsg']);
        }

        return $message['errmsg'];
    }

    /**
     * 当前版本号（保持原样）
     */
    public function version()
    {
        $file = Yii::getAlias('@common') . '/config/version.php';
        if (!file_exists($file)) {
            throw new NotFoundHttpException('找不到版本号文件');
        }
        return require $file;
    }
}