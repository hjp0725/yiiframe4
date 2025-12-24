<?php 

namespace backend\modules\base\controllers;

use common\enums\AppEnum;
use common\models\rbac\AuthItem;
use common\traits\AuthItemTrait;
use backend\controllers\BaseController;

/**
 * Class AuthItemController
 *
 * @package backend\modules\base\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class AuthItemController extends BaseController
{
    use AuthItemTrait;

    /** @var string 对应模型类 */
    public  $modelClass = AuthItem::class;

    /** @var string 默认应用 */
    public  $appId = AppEnum::BACKEND;

    /** @var string 视图前缀 */
    public  $viewPrefix = '@backend/modules/base/views/auth-item/';
}