<?php 

namespace common\helpers;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\db\MigrationInterface;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;

/**
 * MigrateHelper（仅内部调整形参，外部旧调用无需改）
 * PHP 7.3 完全兼容
 */
final class MigrateHelper
{
    private const MAX_NAME_LENGTH = 180;

    private static $compact = false;
    private static $migrationPath = [];
    private static $migrationNs = [];   // 内部仍用复数存储
    private static $info = [];

    /* ------------------------------------------------------------------
     * 四个入口：形参统一用复数，保持与旧调用一致
     * ------------------------------------------------------------------ */
    public static function upByPath(array $namespaces, bool $compact = false): array
    {
        return self::run('up', $namespaces, [], $compact);
    }

    public static function upByNamespaces(array $namespaces, bool $compact = false): array
    {
        return self::run('up', [], $namespaces, $compact);
    }

    public static function downByPath(array $namespaces, bool $compact = false): array
    {
        return self::run('down', $namespaces, [], $compact);
    }

    public static function downByNamespaces(array $namespaces, bool $compact = false): array
    {
        return self::run('down', [], $namespaces, $compact);
    }

    /* ------------------------------------------------------------------
     * 统一调度
     * ------------------------------------------------------------------ */
    private static function run(string $direction, array $path, array $ns, bool $compact): array
    {
        // 每次调用都先清空上次残留
        self::$compact   = $compact;
        self::$migrationPath = array_map([Yii::class, 'getAlias'], $path);
        self::$migrationNs   = array_map('trim', $ns);

        if ($path && !self::$migrationPath) {
            throw new InvalidConfigException('At least one of `migrationPath` should be specified.');
        }
        if ($ns && !self::$migrationNs) {
            throw new InvalidConfigException('At least one of `migrationNamespaces` should be specified.');
        }

        return $direction === 'up' ? self::up() : self::down();
    }

    /* ------------------------------------------------------------------
     * 具体 up / down 逻辑（未改动）
     * ------------------------------------------------------------------ */
    private static function up(int $limit = 0): array
    {
        $migrations = self::getNewMigrations();
        if (!$migrations) {
            throw new NotFoundHttpException('找不到可用的数据迁移');
        }
        if ($limit > 0) {
            $migrations = array_slice($migrations, 0, $limit);
        }
        foreach ($migrations as $m) {
            if (strlen($m) > self::MAX_NAME_LENGTH) {
                throw new UnprocessableEntityHttpException("The migration name '$m' is too long. Its not possible to apply this migration.");
            }
        }
        foreach ($migrations as $m) {
            if (!self::migrateOne($m, 'up')) {
                throw new UnprocessableEntityHttpException("$m 迁移失败了。其余的迁移被取消");
            }
        }
        return self::$info;
    }

    private static function down(): array
    {
        $migrations = self::getNewMigrations();
        if (!$migrations) {
            throw new NotFoundHttpException('找不到可用的数据迁移');
        }
        foreach ($migrations as $m) {
            if (!self::migrateOne($m, 'down')) {
                throw new UnprocessableEntityHttpException("$m 迁移失败了。其余的迁移被取消");
            }
        }
        self::$info[] = 'Migrated down successfully.';
        return self::$info;
    }

    /* ------------------------------------------------------------------
     * 其余私有方法均保持原样（仅贴关键片段）
     * ------------------------------------------------------------------ */
    private static function migrateOne(string $class, string $method): bool
    {
        self::$info[] = "*** {$method}ing $class";
        $start = microtime(true);
        ob_start();
        $migration = self::createMigration($class);
        $result    = $migration->$method();
        $raw       = ob_get_clean();
        if ($result !== false) {
            foreach (explode('>', $raw) as $l) {
                if (($l = trim($l)) !== '') {
                    self::$info[] = $l;
                }
            }
            self::$info[] = sprintf('*** %sed %s (time: %.3fs)', $method, $class, microtime(true) - $start);
            return true;
        }
        self::$info[] = sprintf('*** failed to %s %s (time: %.3fs)', $method, $class, microtime(true) - $start);
        return false;
    }

    private static function createMigration(string $class): MigrationInterface
    {
        self::includeMigrationFile($class);
        /** @var MigrationInterface $m */
        $m = Yii::createObject($class);
        if ($m instanceof BaseObject && $m->canSetProperty('compact')) {
            $m->compact = self::$compact;
        }
        return $m;
    }

    private static function includeMigrationFile(string $class): void
    {
        if (strpos($class, '\\') !== false) {
            return;
        }
        foreach (self::$migrationPath as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR . $class . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }

    private static function getNewMigrations(): array
    {
        $scan = [];
        // 目录
        foreach (self::$migrationPath as $path) {
            if ($path && is_dir($path)) {
                $scan[] = [$path, ''];
            }
        }
        // 命名空间
        foreach (self::$migrationNs as $ns) {
            $scan[] = [self::nsToPath($ns), $ns];
        }

        $found = [];
        foreach ($scan as [$dir, $ns]) {
            if (!is_dir($dir)) {
                continue;
            }
            $handle = opendir($dir);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_?\d{6})\D.*?)\.php$/i', $file, $m) && is_file($path)) {
                    $class = $ns === '' ? $m[1] : $ns . '\\' . $m[1];
                    $time  = str_replace('_', '', $m[2]);
                    $found[$time . '\\' . $class] = $class;
                }
            }
            closedir($handle);
        }

        ksort($found);
        return array_values($found);
    }

    private static function nsToPath(string $ns): string
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $ns));
    }
}