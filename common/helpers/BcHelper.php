<?php 

namespace common\helpers;

/**
 * BC 高精度数学助手
 * 四舍六入(银行家舍入)请使用 round(1.2849, 2, PHP_ROUND_HALF_EVEN);
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class BcHelper
{
    /* -------------------- 基础运算 -------------------- */

    public static function div(string $dividend, string $divisor, ?int $scale = 2): ?string
    {
        return bcdiv($dividend, $divisor, $scale);
    }

    public static function mul(string $multiplier, string $multiplicand, ?int $scale = 2): ?string
    {
        return bcmul($multiplier, $multiplicand, $scale);
    }

    public static function mod(string $dividend, string $modulus, ?int $scale = 2): ?string
    {
        return bcmod($dividend, $modulus, $scale);
    }

    public static function add(string $left, string $right, ?int $scale = 2): string
    {
        return bcadd($left, $right, $scale);
    }

    public static function sub(string $left, string $right, ?int $scale = 2): string
    {
        return bcsub($left, $right, $scale);
    }

    public static function comp(string $left, string $right, ?int $scale = 2): int
    {
        return bccomp($left, $right, $scale);
    }

    public static function pow(string $base, string $exponent, ?int $scale = 2): string
    {
        return bcpow($base, $exponent, $scale);
    }

    public static function sqrt(string $operand, ?int $scale = null): string
    {
        return bcsqrt($operand, $scale);
    }

    /* -------------------- 环境设置 -------------------- */

    public static function scale(int $scale): bool
    {
        return bcscale($scale);
    }

    /**
     * 四舍五入
     *
     * @param $num
     * @param $scale
     * @return float
     */
    private static function round($num, $scale)
    {
        return round($num, $scale);
    }
}