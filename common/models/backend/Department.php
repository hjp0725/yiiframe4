<?php

namespace common\models\backend;
use Yii;
use common\traits\Tree;
use common\traits\DropDownTrait;

class Department extends \common\models\base\BaseModel
{
    use Tree,DropDownTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%backend_department}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['merchant_id', 'department_id', 'sort', 'level', 'pid', 'status', 'created_at', 'updated_at'], 'integer'],
            [['tree'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['cover'], 'string', 'max' => 255],
            [['department_leader'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'merchant_id' => Yii::t('app', '企业'),
            'department_id' => Yii::t('app', '部门ID'),
            'department_leader' => Yii::t('app', '主管'),
            'title' => Yii::t('app', '部门'),
            'cover' => Yii::t('app', '封面'),
            'sort' => Yii::t('app', '排序'),
            'level' => Yii::t('app', '级别'),
            'tree' => Yii::t('app', '树'),
            'pid' => Yii::t('app', '父级'),
            'status' => Yii::t('app', '状态'),
            'created_at' => Yii::t('app', '创建时间'),
            'updated_at' => Yii::t('app', '更新时间'),
        ];
    }
    public function getMember()
    {
        return $this->hasOne(Yii::$app->services->devPattern->member(), ['id' => 'department_leader']);
    }
}
