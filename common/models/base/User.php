<?php
namespace common\models\base;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use common\models\rbac\AuthAssignment;

/**
 * User model  (PHP 7.3 + MySQL 5.7)
 *
 * @property int $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    /** 状态常量 */
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE  = 1;

    /** 角色常量 */
    const ROLE_USER  = 10;
    const ROLE_ADMIN = 20;

    /** 场景常量 */
    const SCENARIO_LOGIN = 'login';
    const SCENARIO_RESET = 'reset';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * 行为：时间戳（保持原样）
     */
    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    /**
     * 规则：用常量代替魔法数字
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            ['email', 'email'],
            ['username', 'string', 'max' => 20],
            ['password_hash', 'string', 'max' => 255],
        ];
    }

    /* --------------------------------------------------
     * 静态工厂：IDE 友好 + 链式 + 复用查询片段
     * -------------------------------------------------- */

    /**
     * 公共活跃查询片段
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * 通过用户名查找
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findActive()->andWhere(['username' => $username])->one();
    }

    /**
     * 通过手机号查找
     * @param string $mobile
     * @return static|null
     */
    public static function findByMobile($mobile)
    {
        return static::findActive()->andWhere(['mobile' => $mobile])->one();
    }

    /**
     * 通过邮箱查找
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findActive()->andWhere(['email' => $email])->one();
    }

    /**
     * 通过密码重置令牌查找
     * @param string $token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findActive()->andWhere(['password_reset_token' => $token])->one();
    }

    /**
     * 令牌是否有效（读配置，可后台改）
     * @param string $token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        return $timestamp + $expire >= time();
    }

    /* --------------------------------------------------
     * IdentityInterface 实现（保持原样）
     * -------------------------------------------------- */

    public static function findIdentity($id)
    {
        return static::findActive()->andWhere(['id' => $id])->one();
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /* --------------------------------------------------
     * 密码工具（保持原样）
     * -------------------------------------------------- */

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /* --------------------------------------------------
     * 关联（保持原样）
     * -------------------------------------------------- */

    public function getAssignment()
    {
        return $this->hasOne(AuthAssignment::class, ['user_id' => 'id'])
            ->where(['app_id' => Yii::$app->id]);
    }
}