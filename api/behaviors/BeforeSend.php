<?php 
namespace api\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Response;
use yiiframe\plugs\common\AddonHelper;

/**
 * API 响应统一格式化
 *
 * @package api\behaviors
 * @author jianyan74 <751393839@qq.com>
 */
class BeforeSend extends Behavior
{
    /**
     * @return string[]
     */
    public function events(): array
    {
        return ['beforeSend' => 'beforeSend'];
    }

    /**
     * 统一格式化返回
     * @param \yii\base\Event $event
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeSend($event): void
    {
        $response = $event->sender;

        // debug 模块原样输出
        if (YII_DEBUG && Yii::$app->controller->module->id === 'debug') {
            return;
        }

        // 全局关闭格式化
        if (Yii::$app->params['triggerBeforeSend'] === false) {
            $response->format = Response::FORMAT_JSON;
            $response->statusCode = 200;
            return;
        }

        // 基础结构
        $response->data = [
            'code'      => $response->statusCode,
            'message'   => $response->statusText,
            'data'      => $response->data,
            'timestamp' => time(),
        ];

        // 日志记录
        $errData = AddonHelper::isInstall('Log')
            ? Yii::$app->logService->log->record($response, true)
            : '';

        // 5xx 统一提示
        if ($response->statusCode >= 500) {
            $response->data['data'] = YII_DEBUG ? $errData : '服务器打瞌睡了~';
        }

        // 3xx-4xx 提取友好提示
        if ($response->statusCode >= 300 && $response->statusCode <= 499) {
            if (isset($response->data['data']['message'], $response->data['data']['status'])) {
                $response->data['message'] = $response->data['data']['message'];
            }
            if (isset($errData['errorMessage'])) {
                $response->data['message'] = $errData['errorMessage'];
                // 避免 data 与 message 重复
                if ($response->data['message'] === $response->data['data']) {
                    $response->data['data'] = [];
                }
            }
        }

        // 频率限制自动拉黑
        if ($response->statusCode === 429
            && AddonHelper::isInstall('IpBlack')
        ) {
            Yii::$app->ipBlackService->ipBlacklist->create(
                Yii::$app->request->userIP,
                '请求频率过高'
            );
        }

        // 统一 JSON 输出 & 前端友好 200
        $response->format = Response::FORMAT_JSON;
        $response->statusCode = 200;
    }
}