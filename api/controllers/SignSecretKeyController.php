<?php 

namespace api\controllers;

use Yii;
use common\helpers\EncryptionHelper;
use common\helpers\StringHelper;

/**
 * 签名加密控制器 - Test
 *
 * @package api\controllers
 * @author  jianyan74 <751393839@qq.com>
 */
class SignSecretKeyController extends OnAuthController
{
    /** @var string 模型类名（当前无表） */
    public $modelClass = '';

    /** @var string 应用公钥 */
    protected $appId = 'doormen';

    /** @var string 应用密钥 */
    protected $appSecret = 'e3de3825cfbf';

    /** @var array 免登录动作 */
    protected $optional = ['index', 'create'];

    /* -------------------- 生成带签名 URL -------------------- */

    /**
     * 生成测试带签名秘钥的 url
     * @return array
     */
    public function actionIndex(): array
    {
        $params = EncryptionHelper::createUrlParam([
            'appId'    => $this->appId,
            'time'     => time(),
            'nonceStr' => StringHelper::random(32),
            'mobile'   => '15888888888',
        ], $this->appSecret);

        return [
            'url'     => Yii::$app->request->hostInfo . '/api/sign-secret-key?' . $params,
            'method'  => 'post',
            'explain' => '请用 post 请求该链接进行测试带签名验证',
        ];
    }

    /* -------------------- 校验签名 -------------------- */

    /**
     * 校验签名是否正确
     * @return bool
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate(): bool
    {
        return EncryptionHelper::decodeUrlParam(Yii::$app->request->get(), $this->appSecret);
    }
}