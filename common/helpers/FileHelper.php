<?php 

namespace common\helpers;

use yii\helpers\BaseFileHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class FileHelper
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
class FileHelper extends BaseFileHelper
{
    /* ------------------------------------------------------------------
     * 递归创建目录（0777）
     * ------------------------------------------------------------------ */
    public static function mkdirs(string $catalogue): bool
    {
        if (!file_exists($catalogue)) {
            // PHP 自带递归创建，省去自己写
            mkdir($catalogue, 0777, true);
        }
        return true;
    }

    /* ------------------------------------------------------------------
     * 写日志（自动建目录）
     * ------------------------------------------------------------------ */
    public static function writeLog(string $path, string $content): int
    {
        self::mkdirs(dirname($path));
        return file_put_contents($path, "\r\n" . $content, FILE_APPEND | LOCK_EX);
    }

    /* ------------------------------------------------------------------
     * 统计目录大小（字节）
     * ------------------------------------------------------------------ */
    public static function getDirSize(string $dir): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /* ------------------------------------------------------------------
     * 基于数组批量创建目录或空文件
     * ------------------------------------------------------------------ */
    public static function createDirOrFiles(array $files): void
    {
        foreach ($files as $value) {
            if (substr($value, -1) === '/') {
                self::mkdirs($value);
            } else {
                self::mkdirs(dirname($value));
                file_put_contents($value, '');
            }
        }
    }

    /* ------------------------------------------------------------------
     * 软著代码收集：递归提取指定后缀文件内容（去注释）并合并
     * ------------------------------------------------------------------ */
    public static function getDirFileContent(string $dir, string $savePath, array $suffix = ['php']): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $suffix, true)) {
                $content = StringHelper::removeAnnotation(file_get_contents($file->getRealPath()));
                file_put_contents($savePath, $content, FILE_APPEND | LOCK_EX);
            }
        }
    }
}