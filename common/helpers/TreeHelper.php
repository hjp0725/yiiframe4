<?php 

namespace common\helpers;

/**
 * Class TreeHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
final class TreeHelper
{
    /**
     * 生成节点前缀 key
     */
    public static function prefixTreeKey(int $id): string
    {
        return "tr_$id ";
    }

    /**
     * 默认根节点 key
     */
    public static function defaultTreeKey(): string
    {
        return 'tr_0 ';
    }
}