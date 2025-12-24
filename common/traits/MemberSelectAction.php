<?php 

namespace common\traits;

use common\enums\StatusEnum;
use addons\Member\common\models\Member;
use Yii;
use yii\web\Response;

/**
 * Trait MemberSelectAction
 * @package common\traits
 */
trait MemberSelectAction
{
    /**
     * select2 查询
     *
     * @param string|null $q 搜索关键词
     * @param int|null $id 默认选中值
     * @return array
     */
    public function actionSelect2($q = null, $id = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $out = [
            'results' => [
                'id' => '',
                'text' => '',
            ],
        ];

        // 基础过滤条件
        $condition = ['merchant_id' => Yii::$app->user->identity->merchant_id];
        if (Yii::$app->services->devPattern->isB2B2C()) {
            $condition = [];
        }

        // 关键词搜索
        if ($q !== null) {
            $data = Member::find()
                ->select(['id', 'mobile as text'])
                ->where(['like', 'mobile', $q])
                ->andWhere(['status' => StatusEnum::ENABLED])
                ->andFilterWhere($condition)
                ->limit(10)
                ->asArray()
                ->all();

            $out['results'] = array_values($data);
            return $out;
        }

        // 单条回显
        if ($id !== null && (int)$id > 0) {
            $member = Member::findOne((int)$id);
            if ($member) {
                $out['results'] = ['id' => $member->id, 'text' => $member->mobile];
            }
        }

        return $out;
    }
}