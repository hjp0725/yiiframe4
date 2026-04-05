<?php 

namespace common\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\helpers\Json;

/**
 * Class DebrisHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
final class DebrisHelper
{
    /* ------------------------------------------------------------------
     *  URL 相关
     * ------------------------------------------------------------------ */
    public static function getUrl(): string
    {
        $url       = explode('?', Yii::$app->request->getUrl(), 2)[0];
        $prefix    = '/' . Yii::$app->id . '/';
        $prefixLen = strlen($prefix);

        if (substr($url, 0, $prefixLen) === $prefix) {
            $url = substr($url, $prefixLen);
        }

        return ltrim($url, '/');
    }

    /* ------------------------------------------------------------------
     *  HTML 批量转义（支持多维数组）
     * ------------------------------------------------------------------ */
    public static function htmlEncode($value)
    {
        if (empty($value)) {
            return $value;
        }

        try {
            $data = is_array($value) ? $value : Json::decode($value);
        } catch (\Throwable $e) {
            return $value;
        }

        foreach ($data as $k => $v) {
            $data[$k] = is_array($v) ? self::htmlEncode($v) : Html::encode($v);
        }

        return $data;
    }

    /* ------------------------------------------------------------------
     *  生成分页跳转 URL（去掉 page / per-page）
     * ------------------------------------------------------------------ */
    public static function getPageSkipUrl(): array
    {
        $fullUrl = Yii::$app->request->getHostInfo() . Yii::$app->request->url;
        $parts   = explode('?', $fullUrl, 2);
        $base    = $parts[0];
        $query   = isset($parts[1]) ? urldecode($parts[1]) : '';

        $params = array_filter(explode('&', $query), function (string $v): bool {
            return $v !== '' && strpos($v, 'page=') !== 0 && strpos($v, 'per-page=') !== 0;
        });

        $conn = empty($params) ? '?' : '&';
        $url  = $base . ($params ? '?' . implode('&', $params) : '');

        return [HtmlPurifier::process($url), $conn];
    }

    /* ------------------------------------------------------------------
     *  IP 处理
     * ------------------------------------------------------------------ */
    public static function long2ip($ip): string
    {
        try {
            return long2ip((int)$ip);
        } catch (\Throwable $e) {
            return (string)$ip;
        }
    }

    public static function analysisIp($ip, bool $long = true)
    {
        if (empty($ip)) {
            return false;
        }

        if ((int)$ip === ip2long('127.0.0.1')) {
            return '本地';
        }

        if ($long === true) {
            $ip = self::long2ip($ip);
            if (is_numeric($ip) && $ip > 1000) {
                return '无法解析';
            }
        }

        $data = \Zhuzhichao\IpLocationZh\Ip::find($ip);
        $tmp  = array_filter([$data[0] ?? '', $data[1] ?? '', $data[2] ?? '']);
        return $tmp ? implode(' · ', $tmp) : false;
    }

    /* ------------------------------------------------------------------
     *  debug_backtrace 美化
     * ------------------------------------------------------------------ */
    public static function debug(bool $reverse = false): array
    {
        $stack = debug_backtrace();
        $out   = [];
        foreach ($stack as $e) {
            $out[] = sprintf(
                '%s(%s),%s::%s()',
                $e['file'] ?? 'null file',
                $e['line'] ?? 'null',
                $e['class'] ?? 'null class',
                $e['function'] ?? 'null function'
            );
        }
        return $reverse ? array_reverse($out) : $out;
    }

    /* ------------------------------------------------------------------
     *  根据经纬度计算两点距离（米）
     * ------------------------------------------------------------------ */
    public static function getDistance($lat1, $lng1, $lat2, $lng2): int
    {
        if (empty($lat1) || empty($lng1) || empty($lat2) || empty($lng2)) {
            return 0;
        }

        $radius = 6367000;
        $rad    = M_PI / 180;

        $lat1 = $lat1 * $rad;
        $lng1 = $lng1 * $rad;
        $lat2 = $lat2 * $rad;
        $lng2 = $lng2 * $rad;

        $dlng = $lng2 - $lng1;
        $dlat = $lat2 - $lat1;

        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlng / 2) ** 2;
        $c = 2 * asin(min(1, sqrt($a)));

        return (int)round($radius * $c);
    }

    /* ------------------------------------------------------------------
     *  生成水印坐标
     * ------------------------------------------------------------------ */
    public static function getWatermarkLocation(string $imgUrl, string $watermarkImgUrl, int $point): ?array
    {
        if (
            !file_exists($imgUrl) ||
            !file_exists($watermarkImgUrl) ||
            ($imgSize = getimagesize($imgUrl)) === false ||
            ($wmSize = getimagesize($watermarkImgUrl)) === false
        ) {
            return null;
        }

        [$imgW, $imgH] = [$imgSize[0], $imgSize[1]];
        [$wmW, $wmH]   = [$wmSize[0], $wmSize[1]];

        $map = [
            1 => [20, 20],
            2 => [self::mid($imgW, $wmW), 20],
            3 => [$imgW - $wmW - 20, 20],
            4 => [20, self::mid($imgH, $wmH)],
            5 => [self::mid($imgW, $wmW), self::mid($imgH, $wmH)],
            6 => [$imgW - $wmW - 20, self::mid($imgH, $wmH)],
            7 => [20, $imgH - $wmH - 20],
            8 => [self::mid($imgW, $wmW), $imgH - $wmH - 20],
            9 => [$imgW - $wmW - 20, $imgH - $wmH - 20],
        ];

        if (!isset($map[$point])) {
            return [0, 0];
        }

        [$x, $y] = $map[$point];
        if ($x < 0 || $y < 0 || $x + $wmW > $imgW || $y + $wmH > $imgH) {
            return null;
        }

        return [$x, $y];
    }

    /* 小工具 */
    private static function mid(int $c, int $b): int
    {
        return (int)floor(($c - $b) / 2);
    }
}