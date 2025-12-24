<?php 

namespace common\helpers;

/**
 * 顶级域名提取助手
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class TopDomainhuo
{
    /** @var string[] 双后缀列表 */
    private const DOUBLE_SUFFIX = [
        'com.cn',
        'net.cn',
        'org.cn',
        'gov.cn',
    ];

    /**
     * 获取当前请求的顶级域名（含双后缀）
     * @return string
     */
    public static function getTopDomainhuo(): string
    {
        // 优先 HTTP_HOST，兜底 SERVER_NAME
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $host = strtolower($host);

        $parts = explode('.', $host);
        $count = count($parts);

        // 双后缀判断
        if ($count >= 3) {
            $lastTwo = implode('.', array_slice($parts, -2));
            if (in_array($lastTwo, static::DOUBLE_SUFFIX, true)) {
                return implode('.', array_slice($parts, -3));
            }
        }

        // 常规二级
        return implode('.', array_slice($parts, -2));
    }
}