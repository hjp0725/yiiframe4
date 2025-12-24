<?php 

namespace common\helpers;

/**
 * 正则匹配验证
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class RegularHelper
{
    /* ====================== 正则常量 ====================== */

    private const MOBILE        = '/^1[3-9]\d{9}$/';
    private const EMAIL         = '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
    private const TELEPHONE     = '/^(\(\d{3,4}\)|\d{3,4}-)?\d{7,8}$/';
    private const IDENTITY_CARD = '/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/';
    private const PASSWORD      = '/^[a-zA-Z]\w{5,17}$/';
    private const URL           = '/^(http|https):\/\//i';

    /* ====================== 统一入口 ====================== */

    /**
     * 验证入口
     *
     * @param string $type  方法名 mobile|email|telephone|identityCard|password|url
     * @param string $value 待校验值
     * @return bool
     */
    public static function verify(string $type, string $value): bool
    {
        $pattern = static::{$type}();
        return $pattern !== '' && preg_match($pattern, $value) === 1;
    }

    /* ====================== 具体规则 ====================== */

    public static function mobile(): string
    {
        return self::MOBILE;
    }

    public static function email(): string
    {
        return self::EMAIL;
    }

    public static function telephone(): string
    {
        return self::TELEPHONE;
    }

    public static function identityCard(): string
    {
        return self::IDENTITY_CARD;
    }

    public static function password(): string
    {
        return self::PASSWORD;
    }

    public static function url(): string
    {
        return self::URL;
    }
}