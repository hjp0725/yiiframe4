<?php 

namespace api\rest;

use common\helpers\StringHelper;
use yiiframe\plugs\services\AddonsService;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Request;

/**
 * Class UrlRule
 * @package api\rest
 * @author jianyan74 <751393839@qq.com>
 */
class UrlRule extends \yii\rest\UrlRule
{
    /**
     * 解析请求路径
     * @throws InvalidConfigException
     */
    public function parseRequest($manager,$request)
    {
        $path_info = $request->getPathInfo();
        $path_info_list = explode('/', $path_info);

        $addons = AddonsService::findAllNames();
        $names = [];
        foreach ($addons as $addon) {
            $names[] = StringHelper::toUnderScore($addon['name']);
        }

        if (count($path_info_list) >= 3 && in_array($path_info_list[0], $names, true)) {
            return [$path_info, []];
        }

        return false;
    }
}