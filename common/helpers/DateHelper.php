<?php 

namespace common\helpers;

/**
 * 日期数据格式返回
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class DateHelper
{
    /* ====================== 自然日 ====================== */

    /**
     * 今日起止时间戳
     * @return int[]
     */
    public static function today(): array
    {
        $d = new \DateTimeImmutable('today');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('+1 day')->getTimestamp() - 1,
        ];
    }

    /**
     * 昨日
     * @return int[]
     */
    public static function yesterday(): array
    {
        $d = new \DateTimeImmutable('yesterday');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('+1 day')->getTimestamp() - 1,
        ];
    }

    /* ====================== 自然周 ====================== */

    /**
     * 本周（周一 00:00 ～ 周日 23:59）
     * @return int[]
     */
    public static function thisWeek(): array
    {
        $d = new \DateTimeImmutable('this week monday');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('+6 days 23:59:59')->getTimestamp(),
        ];
    }

    /**
     * 上周
     * @return int[]
     */
    public static function lastWeek(): array
    {
        $d = new \DateTimeImmutable('last week monday');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('+6 days 23:59:59')->getTimestamp(),
        ];
    }

    /* ====================== 自然月 ====================== */

    /**
     * 本月
     * @return int[]
     */
    public static function thisMonth(): array
    {
        $d = new \DateTimeImmutable('first day of this month');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('last day of this month 23:59:59')->getTimestamp(),
        ];
    }

    /**
     * 上月
     * @return int[]
     */
    public static function lastMonth(): array
    {
        $d = new \DateTimeImmutable('first day of last month');
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('last day of last month 23:59:59')->getTimestamp(),
        ];
    }

    /**
     * N 个月前
     * @param int $month
     * @return int[]
     */
    public static function monthsAgo(int $month): array
    {
        $d = new \DateTimeImmutable("first day of -$month months");
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('last day of this month 23:59:59')->getTimestamp(),
        ];
    }

    /* ====================== 指定年/月 ====================== */

    /**
     * 某年
     * @param int $year
     * @return int[]
     */
    public static function aYear(int $year): array
    {
        $d = new \DateTimeImmutable("$year-01-01 00:00:00");
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('Dec 31 23:59:59')->getTimestamp(),
        ];
    }

    /**
     * 某月
     * @param int $year
     * @param int $month
     * @return int[]
     */
    public static function aMonth(int $year = 0, int $month = 0): array
    {
        $year  = $year  ?: (int) date('Y');
        $month = $month ?: (int) date('m');
        $d     = new \DateImmutable("$year-$month-01 00:00:00");
        return [
            'start' => $d->getTimestamp(),
            'end'   => $d->modify('last day of this month 23:59:59')->getTimestamp(),
        ];
    }

    /* ====================== 周/小时/秒 友好格式 ====================== */

    /**
     * 星期几文字
     * @param int    $time
     * @param string $format
     * @return string
     */
    public static function getWeekName(int $time, string $format = '周'): string
    {
        $map = ['日', '一', '二', '三', '四', '五', '六'];
        return $format . ($map[date('w', $time)] ?? '');
    }

    /**
     * 秒数 → 小时:分钟 数组
     * @param int[] $hours
     * @return string[]
     */
    public static function formatHours(array $hours): array
    {
        return array_map(static function (int $s): string {
            return $s === 86400 ? '24:00' : date('H:i', $s);
        }, $hours);
    }

    /**
     * 单小时格式化
     * @param int $hour 秒数
     * @return string
     */
    public static function formatHoursByInt(int $hour): string
    {
        return $hour === 86400 ? '24:00' : date('H:i', $hour);
    }

    /**
     * 秒级时长 → 中文友好
     * @param int $time 秒
     * @return string
     */
    public static function formatTimestamp(int $time): string
    {
        $day  = floor($time / 86400);
        $hour = floor(($time % 86400) / 3600);
        $min  = floor(($time % 3600) / 60);

        return "{$day} 天 {$hour} 小时 {$min} 分钟";
    }

    /* ====================== 高精度毫秒 ====================== */

    /**
     * 毫秒/微秒时间戳
     * @param int $accuracy 1000=毫秒 1000000=微秒
     * @return int
     */
    public static function microtime(int $accuracy = 1000): int
    {
        return (int) (microtime(true) * $accuracy);
    }
}