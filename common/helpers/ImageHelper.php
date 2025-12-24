<?php 

namespace common\helpers;

use Yii;
use yii\helpers\Html;

/**
 * Class ImageHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
final class ImageHelper
{
    /* ------------------------------------------------------------------
     * 默认图片 / 头像
     * ------------------------------------------------------------------ */
    public static function default($imgSrc, string $defaultImgSrc = '/resources/img/error.png'): string
    {
        return $imgSrc ?: Yii::getAlias('@web') . $defaultImgSrc;
    }

    public static function defaultHeaderPortrait($imgSrc, string $defaultImgSrc = '/resources/img/profile_small.jpg'): string
    {
        return $imgSrc ?: Yii::getAlias('@web') . $defaultImgSrc;
    }

    /* ------------------------------------------------------------------
     * 单图点击放大（fancyBox）
     * ------------------------------------------------------------------ */
    public static function fancyBox(string $imgSrc, int $width = 45, int $height = 45): string
    {
        $img = Html::img($imgSrc, ['width' => $width, 'height' => $height]);
        return Html::a($img, $imgSrc, ['data-fancybox' => 'gallery']);
    }

    /* ------------------------------------------------------------------
     * 多图点击放大
     * ------------------------------------------------------------------ */
    public static function fancyBoxs($covers, int $width = 45, int $height = 45): string
    {
        if (empty($covers)) {
            return '';
        }

        !is_array($covers) && $covers = json_decode($covers, true) ?: [];

        $html = '';
        foreach ($covers as $src) {
            $html .= Html::tag('span', self::fancyBox($src, $width, $height), [
                'style' => 'padding-right:5px;padding-bottom:5px',
            ]);
        }

        return $html;
    }

    /* ------------------------------------------------------------------
     * 判断是否图片地址
     * ------------------------------------------------------------------ */
    public static function isImg(string $imgSrc): bool
    {
        if ($imgSrc === '') {
            return false;
        }

        // 微信头像特例
        if (strpos($imgSrc, 'http://wx.qlogo.cn') !== false || strpos($imgSrc, 'https://wx.qlogo.cn') !== false) {
            return true;
        }

        $ext = strtolower(StringHelper::clipping($imgSrc, '.', 1));
        $imgExt = [
            'bmp', 'jpg', 'jpeg', 'jpe', 'gif', 'png', 'jif', 'dib', 'rle',
            'emf', 'pcx', 'dcx', 'pic', 'tga', 'tif', 'tiff', 'xif', 'wmf', 'jfif',
        ];

        return in_array($ext, $imgExt, true);
    }
}