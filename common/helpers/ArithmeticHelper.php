<?php 
/**
 * 算法辅助类
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
namespace common\helpers;

class ArithmeticHelper
{
    /* ====================== 红包算法 ====================== */

    /**
     * 生成红包（金额单位：元）
     *
     * @param float $money 红包总金额
     * @param int   $num   红包个数
     * @param float $min   最小金额
     * @param float $max   最大金额
     * @return float[]
     */
    public static function getRedPackage(float $money, int $num, float $min, float $max): array
    {
        // 边界预检查
        if ($min * $num > $money || $max * $num < $money) {
            return [];
        }

        $packages = [];
        while ($num >= 1) {
            --$num;

            $kmix = max($min, $money - $num * $max);
            $kmax = min($max, $money - $num * $min);
            $kAvg = $money / ($num + 1);

            // 浮动区间
            $kDis = min($kAvg - $kmix, $kmax - $kAvg);
            $r    = (random_int(0, 10000) / 10000 - 0.5) * $kDis * 2;
            $k    = round($kAvg + $r, 2);

            $money    -= $k;
            $packages[] = $k;
        }

        shuffle($packages);
        return $packages;
    }

    /* ====================== 抽奖算法 ====================== */

    /**
     * 非必中抽奖（总概率 1-1000）
     *
     * @param array  $awards 奖品列表
     * @param string $prob   概率字段名
     * @param string $key    返回字段名
     * @return mixed|false
     */
    public static function drawRandom(array $awards = [], string $prob = 'prob', string $key = 'id')
    {
        if (!$awards) {
            return false;
        }

        $rand  = random_int(1, 1000);
        $accum = 0;

        foreach ($awards as $award) {
            $accum += $award[$prob];
            if ($rand <= $accum) {
                return $award[$key];
            }
        }

        return false;
    }

    /**
     * 必中抽奖
     *
     * @param array  $awards 奖品列表
     * @param string $prob   概率字段名
     * @return mixed|false
     */
    public static function drawBitslap(array $awards = [], string $prob = 'prob')
    {
        if (!$awards) {
            return false;
        }

        $proArr = [];
        foreach ($awards as $k => $v) {
            $proArr[$k] = $v[$prob];
        }

        $hit = self::getDrawRand($proArr);
        return $awards[$hit]['id'] ?? false;
    }

    /**
     * 经典权重随机（必中）
     *
     * @param int[] $proArr 权重数组
     * @return int|string    命中 key
     */
    public static function getDrawRand(array $proArr)
    {
        $proSum = array_sum($proArr);

        foreach ($proArr as $key => $proCur) {
            $rand = random_int(1, $proSum);
            if ($rand <= $proCur) {
                return $key;
            }
            $proSum -= $proCur;
        }

        // 防御性返回
        return array_key_first($proArr);
    }
}