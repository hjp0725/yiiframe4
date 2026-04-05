<?php 
namespace common\helpers;

use Yii;
use yii\web\NotFoundHttpException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

/**
 * Http请求服务
 */
class Http
{
    /**
     * 获取远程服务器
     * @return string
     */
    protected static function getServerUrl(): string
    {
        return \yiiframe\plugs\services\UpdateService::$host;
    }

    /**
     * 获取请求对象
     * @return Client
     */
    public static function getClient(): Client
    {
        static $client;
        if ($client === null) {
            $client = new Client([
                'base_uri'        => self::getServerUrl(),
                'timeout'         => 30,
                'connect_timeout' => 30,
                'verify'          => false,
                'http_errors'     => false,
                'headers'         => [
                    'X-REQUESTED-WITH' => 'XMLHttpRequest',
                    'User-Agent'       => 'FastAddon',
                ],
            ]);
        }
        return $client;
    }

    /**
     * 发送请求
     * @param string $url
     * @param array  $params
     * @param string $method
     * @return array
     * @throws NotFoundHttpException
     */
    public static function sendRequest(string $url, array $params = [], string $method = 'POST'): array
    {
        try {
            $client  = self::getClient();
            $options = strtoupper($method) === 'POST'
                ? ['form_params' => $params]
                : ['query' => $params];

            $response = $client->request($method, $url, $options);
            $content  = $response->getBody()->getContents();

            $json = json_decode($content, true);
            if (!is_array($json)) {
                throw new \RuntimeException('Invalid JSON');
            }
            return $json;
        } catch (TransferException $e) {
            throw new NotFoundHttpException('Network error: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw new NotFoundHttpException('Unknown data format: ' . $e->getMessage());
        }
    }
}