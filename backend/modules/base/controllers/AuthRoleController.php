<?php 

namespace backend\modules\base\controllers;

use common\traits\AuthRoleTrait;
use common\models\rbac\AuthRole;
use common\enums\AppEnum;
use backend\controllers\BaseController;

/**
 * 后台角色管理
 *
 * @package backend\modules\base\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthRoleController extends BaseController
{
    use AuthRoleTrait;

    /** @var string AR 类名 */
    public $modelClass = AuthRole::class;

    /** @var string 默认应用 */
    public $appId = AppEnum::BACKEND;

    /** @var bool 权限来源 false=所有权限 true=当前角色 */
    public $sourceAuthChild = true;

    /** @var string 视图前缀 */
    public $viewPrefix = '@backend/modules/base/views/auth-role/';
}