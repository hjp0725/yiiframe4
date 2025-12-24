<?php 

namespace common\helpers;

use common\enums\AppEnum;
use Yii;
use yii\web\Response;

/**
 * 格式化数据返回
 * Class ResultHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
final class ResultHelper
{
    /**
     * 统一出口：非 API 返回数组，API 返回设置好的 Response
     *
     * @param int $code
     * @param string $message
     * @param array|object $data
     * @return array|mixed
     */
    public static function json(int $code = 404, string $message = '未知错误', $data = [])
    {
        if (in_array(Yii::$app->id, AppEnum::api(), true)) {
            return static::api($code, $message, $data);
        }

        return static::baseJson($code, $message, $data);
    }

    /**
     * 普通场景：返回标准数组（会被 Yii 自动序列化 JSON）
     */
    protected static function baseJson(int $code, string $message, $data): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            'code'    => (string)$code,
            'message' => trim($message),
            'data'    => $data ? ArrayHelper::toArray($data) : [],
        ];
    }

    /**
     * API 场景：直接设置 HTTP 状态码 & data
     */
    protected static function api(int $code, string $message, $data)
    {
        $response = Yii::$app->response;
        $response->setStatusCode($code, $message);
        $response->data = $data ? ArrayHelper::toArray($data) : [];

        return $response->data;
    }
}