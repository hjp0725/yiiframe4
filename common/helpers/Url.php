<?php

namespace common\helpers;

use Yii;
use yii\helpers\BaseUrl;
use common\enums\AppEnum;

/**
 * Class Url
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
class Url extends BaseUrl
{
    /**
     * 生成模块Url
     *
     * @param string|array $url
     * @param bool|string $scheme
     * @return string
     */
    public static function to($url = '', $scheme = false): string
    {
        if (is_array($url) && !in_array(Yii::$app->id, [AppEnum::BACKEND, AppEnum::MERCHANT], true)) {
            $url = static::isMerchant($url);
        }

        return parent::to($url, $scheme);
    }

    /**
     * 生成前台链接
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function toFront(array $url, bool $absolute = false, $scheme = false): string
    {
        return static::create($url, $absolute, $scheme, Yii::getAlias('@frontendUrl'), '', 'urlManagerFront');
    }

    /**
     * 生成微信链接
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function toHtml5(array $url, bool $absolute = false, $scheme = false): string
    {
        return static::create($url, $absolute, $scheme, Yii::getAlias('@html5Url'), '/html5', 'urlManagerHtml5');
    }

    /**
     * 生成 oauth2 链接
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function toOAuth2(array $url, bool $absolute = false, $scheme = false): string
    {
        return static::create($url, $absolute, $scheme, Yii::getAlias('@oauth2Url'), '/oauth2', 'urlManagerOAuth2');
    }

    /**
     * 生成 storage 链接
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function toStorage(array $url, bool $absolute = false, $scheme = false): string
    {
        return static::create($url, $absolute, $scheme, Yii::getAlias('@storageUrl'), '/storage', 'urlManagerStorage');
    }

    /**
     * 生成 Api 链接
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public static function toApi(array $url, bool $absolute = false, $scheme = false): string
    {
        return static::create($url, $absolute, $scheme, Yii::getAlias('@apiUrl'), '/api', 'urlManagerApi');
    }

    /**
     * 获取权限所需的 url
     *
     * @param string|array $url
     * @return string
     */
    public static function getAuthUrl($url): string
    {
        return static::normalizeRoute($url);
    }

    /**
     * 创建支付回调专门 Url
     *
     * @param string $action
     * @param array $url
     * @param bool|string $scheme
     * @return string
     */
    public static function removeMerchantIdUrl(string $action, array $url, $scheme = false): string
    {
        $realAppId = Yii::$app->params['realAppId'];
        Yii::$app->params['realAppId'] = AppEnum::BACKEND;

        $url = self::$action($url, $scheme);

        Yii::$app->params['realAppId'] = $realAppId;

        return $url;
    }

    /**
     * 统一的 url 创建逻辑
     *
     * @param array $url
     * @param bool $absolute
     * @param bool|string $scheme
     * @param string $domainName
     * @param string $appId
     * @param string $key
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    protected static function create(array $url, bool $absolute, $scheme, string $domainName, string $appId, string $key): string
    {
        $url = static::isMerchant($url);
        if (Yii::$app->params['inAddon'] ?? false) {
            $url = static::regroupUrl($url);
        }

        if (!Yii::$app->has($key)) {
            Yii::$app->set($key, [
                'class' => \yii\web\UrlManager::class,
                'hostInfo' => $domainName ?: Yii::$app->request->hostInfo . $appId,
                'scriptUrl' => '',
                'enablePrettyUrl' => true,
                'showScriptName' => true,
                'suffix' => '',
            ]);
        }

        return urldecode(Yii::$app->get($key)->createAbsoluteUrl($url, $scheme));
    }

    /**
     * 重组 url（插件场景）
     *
     * @param array $url
     * @return array
     */
    protected static function regroupUrl(array $url): array
    {
        $url[0] = (Yii::$app->params['addonName'] ?? '') . '/' . $url[0];
        return $url;
    }

    /**
     * 自动追加商户 ID
     *
     * @param array $url
     * @return array
     */
    protected static function isMerchant(array $url): array
    {
        $merchantId = Yii::$app->services->merchant->getId();
        if (
            Yii::$app->params['realAppId'] !== AppEnum::BACKEND
            && $merchantId
        ) {
            $url = ArrayHelper::merge(['merchant_id' => $merchantId], $url);
        }

        return $url;
    }
}