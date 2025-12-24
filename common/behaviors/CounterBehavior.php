<?php 

namespace common\behaviors;

use common\helpers\DateHelper;
use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\TooManyRequestsHttpException;

/**
 * 计数器 - 限流
 * Class CounterBehavior
 * @package common\behaviors
 * @author jianyan74 <751393839@qq.com>
 */
class CounterBehavior extends Behavior
{
    /**
     * 请求方法
     * @var string
     */
    public $action = '*';

    /**
     * 规定时间内最大请求数量
     * @var int
     */
    public $maxCount = 1000;

    /**
     * 用户id
     * @var int
     */
    public $userId = 0;

    /**
     * 时间窗口（毫秒）
     * @var int
     */
    public $period = 60 * 1000;

    /**
     * 注册事件
     */
    public function events(): array
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * 限流检查
     * @param \yii\base\ActionEvent $event
     * @throws TooManyRequestsHttpException
     */
    public function beforeAction($event): void
    {
        $redis = Yii::$app->redis;
        $key   = sprintf('hist:%s:%s', $this->userId, Yii::$app->controller->route);
        $now   = DateHelper::microtime(); // 毫秒时间戳

        // 记录当前请求
        $redis->zadd($key, $now, $now);
        // 清理时间窗口之前的数据
        $redis->zremrangebyscore($key, 0, $now - $this->period);

        if ($redis->zcard($key) > $this->maxCount) {
            throw new TooManyRequestsHttpException('服务器繁忙');
        }
    }
}