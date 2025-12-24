<?php 

namespace common\helpers;

use Yii;

/**
 * Class Auth
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
final class Auth
{
    /** @var string[] */
    private static $auth = [];

    /* —— 入口 —— */
    public static function verify(string $route, array $defaultAuth = []): bool
    {
        if (self::isRoot()) {
            return true;
        }

        $route = trim($route);
        $auth  = $defaultAuth ?: self::getAuth();

        if (
            in_array('/*', $auth, true) ||
            in_array('*', $auth, true)  ||
            in_array($route, $auth, true) ||
            in_array(Url::to([$route]), $auth, true)
        ) {
            return true;
        }

        return self::multistageCheck($route, $auth);
    }

    /**
     * 批量过滤，返回用户实际拥有的路由
     */
    public static function verifyBatch(array $route): array
    {
        return self::isRoot() ? $route : array_intersect(self::getAuth(), $route);
    }

    /* —— 通配符检测 —— */
    public static function multistageCheck(string $route, array $auth, string $sep = '/'): bool
    {
        $parts = explode($sep, $route);
        $key   = $sep;

        foreach ($parts as $v) {
            if ($v === '') {
                continue;
            }
            $key .= $v . $sep;
            if (in_array($key . '*', $auth, true)) {
                return true;
            }
        }
        return false;
    }

    /* —— 私有 —— */
    private static function getAuth(): array
    {
        if (self::$auth !== []) {
            return self::$auth;
        }

        $role       = Yii::$app->services->rbacAuthRole->getRole();
        self::$auth = Yii::$app->services->rbacAuthItemChild->getAuthByRole($role, Yii::$app->id);

        return self::$auth;
    }

    private static function isRoot(): bool
    {
        return Yii::$app->services->auth->isSuperAdmin();
    }
}