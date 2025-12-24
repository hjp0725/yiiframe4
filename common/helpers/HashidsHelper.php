<?php 

namespace common\helpers;

use Hashids\Hashids;
use yii\web\UnprocessableEntityHttpException;

/**
 * ID 加密辅助类
 *
 * @package common\helpers
 * @author  jianyan74 <751393839@qq.com>
 */
class HashidsHelper
{
    /** @var int 密文长度 */
    public static $length = 10;

    /** @var string 盐值（建议.env中覆盖） */
    public static $secretKey = '';

    /** @var Hashids 单例 */
    protected static $hashids;

    /* ====================== 生命周期 ====================== */

    /**
     * 加密
     *
     * @param int ...$numbers
     * @return string
     */
    public static function encode(int ...$numbers): string
    {
        return self::getHashids()->encode(...$numbers);
    }

    /**
     * 解密
     *
     * @param string $hash
     * @return int|int[]
     * @throws UnprocessableEntityHttpException
     */
    public static function decode(string $hash)
    {
        $data = self::getHashids()->decode($hash);
        if ($data === []) {
            throw new UnprocessableEntityHttpException('解密失败');
        }

        return count($data) === 1 ? $data[0] : $data;
    }

    /* ====================== 内部 ====================== */

    /**
     * 单例获取 Hashids 实例
     * @return Hashids
     */
    private static function getHashids(): Hashids
    {
        if (!self::$hashids instanceof Hashids) {
            // 默认盐随机生成，支持外部覆盖
            $salt = self::$secretKey ?: bin2hex(random_bytes(16));
            self::$hashids = new Hashids($salt, self::$length);
        }

        return self::$hashids;
    }
}