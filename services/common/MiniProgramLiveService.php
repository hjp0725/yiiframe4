<?php 

namespace services\common;

use Yii;
use yii\helpers\Json;
use common\components\Service;
use linslin\yii2\curl\Curl;

/**
 * Class MiniProgramLiveService
 * @package services\common
 * @author jianyan74 <751393839@qq.com>
 */
class MiniProgramLiveService extends Service
{
    /**
     * 直播接口地址
     */
    const LIVE_URL = 'https://api.weixin.qq.com/wxa/business/getliveinfo';

    /**
     * 同步直播房间列表
     * @param int $start 起始拉取房间
     * @param int $limit 每次拉取条数
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function syncRoom(int $start = 0, int $limit = 20): array
    {
        $token = Yii::$app->wechat->miniProgram->access_token->getToken(true);
        $curl  = new Curl();

        $response = $curl->setGetParams([
            'access_token' => $token['access_token'] ?? '',
        ])->setRequestBody(Json::encode([
            'start' => $start,
            'limit' => $limit,
        ]))->post(self::LIVE_URL);

        $data = Json::decode($response);
        Yii::$app->debris->getWechatError($data);

        return $data;
    }

    /**
     * 同步直播回放
     * @param int $room_id 直播间ID
     * @param int $start   起始拉取回放
     * @param int $limit   每次拉取条数
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function syncReplay(int $room_id, int $start = 0, int $limit = 20): array
    {
        $token = Yii::$app->wechat->miniProgram->access_token->getToken();
        $curl  = new Curl();

        $response = $curl->setGetParams([
            'access_token' => $token['access_token'] ?? '',
        ])->setRequestBody(Json::encode([
            'action'  => 'get_replay',
            'room_id' => $room_id,
            'start'   => $start,
            'limit'   => $limit,
        ]))->post(self::LIVE_URL);

        $data = Json::decode($response);
        Yii::$app->debris->getWechatError($data);

        return $data;
    }
}