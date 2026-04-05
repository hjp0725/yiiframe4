<?php 

namespace api\controllers;

use common\enums\StatusEnum;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

/**
 * 个人信息访问基类
 * 注意：适用于个人中心
 *
 * Class UserAuthController
 * @package api\controllers
 * @property \yii\db\ActiveRecord|\yii\base\Model $modelClass
 * @author jianyan74 <751393839@qq.com>
 */
class UserAuthController extends OnAuthController
{
    /**
     * 首页
     */
    public function actionIndex(): ActiveDataProvider
    {
        $user = Yii::$app->user->identity;

        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where([
                    'status'     => StatusEnum::ENABLED,
                    'member_id'  => $user->member_id,
                ])
                ->andFilterWhere(['merchant_id' => $user->merchant_id])
                ->orderBy('id desc')
                ->asArray(),
            'pagination' => [
                'pageSize'     => $this->pageSize,
                'validatePage' => false, // 超出分页不返回 data
            ],
        ]);
    }

    /**
     * 查找模型（带个人权限校验）
     * @throws NotFoundHttpException
     */
    protected function findModel($id): \yii\db\ActiveRecord
    {
        $user = Yii::$app->user->identity;

        $model = $this->modelClass::find()
            ->where([
                'id'        => $id,
                'status'    => StatusEnum::ENABLED,
                'member_id' => $user->member_id,
            ])
            ->andFilterWhere(['merchant_id' => $user->merchant_id])
            ->one();

        if (empty($model)) {
            throw new NotFoundHttpException('请求的数据不存在或您的权限不足.');
        }

        return $model;
    }
}