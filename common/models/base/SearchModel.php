<?php

namespace common\models\base;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\db\ActiveQuery;
use yii\validators\Validator;
use yii\web\NotFoundHttpException;
use Yii;
/**
 * // 示例一
 *
 * ```php
 * $searchModel = new SearchModel(
 * [
 *      'model' => Topic::class,
 *      'scenario' => 'default',
 * ]
 * );
 *
 * $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
 *
 * return $this->render('index', [
 *      'dataProvider' => $dataProvider,
 * ]);
 * ```
 *
 * // 示例二
 *
 *```php
 * $searchModel = new SearchModel(
 * [
 *      'defaultOrder' => ['id' => SORT_DESC],
 *      'model' => Topic::class,
 *      'scenario' => 'default',
 *      'relations' => ['comment' => []], // 关联表（可以是Model里面的关联）
 *      'partialMatchAttributes' => ['title'], // 模糊查询
 *      'pageSize' => 15
 * ]
 * );
 *
 * $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
 * $dataProvider->query->andWhere([Topic::tableName() . '.user_id' => 23, Comment::tableName() . '.status' => 1]);
 *
 * return $this->render('index', [
 *      'dataProvider' => $dataProvider,
 * ]);
 * ```
 *
 * Class SearchModel
 * @package common\components
 * @property \yii\db\ActiveRecord|\yii\base\Model $model
 */
class SearchModel extends Model
{
    private $attributes;
    private $attributeLabels;
    private $internalRelations;
    private $model;
    private $modelClassName;
    private $relationAttributes = [];
    private $rules;
    private $scenarios;

    /**
     * @var string 默认排序
     */
    public $defaultOrder;

    /**
     * @var string 分组
     */
    public $groupBy;

    /**
     * @var int 每页大小
     */
    public $pageSize = 10;

    /**
     * @var array 模糊查询
     */
    public $partialMatchAttributes = [];

    /**
     * @var array
     */
    public $relations = [];

    public $dateRange;
    /**
     * SearchModel constructor.
     * @param $params
     * @throws NotFoundHttpException
     */
    public function __construct($params)
    {
        $this->scenario = 'search';
        parent::__construct($params);

        if ($this->model === null) {
            throw new NotFoundHttpException('Param "model" cannot be empty');
        }

        $this->rules        = $this->model->rules();
        $this->scenarios    = $this->model->scenarios();
        $this->attributeLabels = $this->model->attributeLabels();

        foreach ($this->safeAttributes() as $attribute) {
            $this->attributes[$attribute] = '';
        }

    }

    /**
     * @param ActiveQuery $query
     * @param string $attribute
     * @param bool $partialMath
     */
    private function addCondition($query, $attribute, $partialMath = false)
    {
        if (isset($this->relationAttributes[$attribute])) {
            $attributeName = $this->relationAttributes[$attribute];
        } else {
            $attributeName = call_user_func([$this->modelClassName, 'tableName']) . '.' . $attribute;
        }

        $value = $this->$attribute;
        if ($value === '') {
            return;
        }

        if ($partialMath) {
            $query->andWhere(['like', $attributeName, trim($value)]);
        } else {
            $query->andWhere($this->conditionTrans($attributeName, $value));
        }
    }

    /**
     * 可以查询大于小于和IN
     *
     * @param $attributeName
     * @param $value
     * @return array
     */
    private function conditionTrans($attributeName, $value)
    {
        switch (true) {
            case is_array($value):
                return [$attributeName => $value];
                break;
            case stripos($value, '>=') !== false:
                return ['>=', $attributeName, substr($value, 2)];
                break;
            case stripos($value, '<=') !== false:
                return ['<=', $attributeName, substr($value, 2)];
                break;
            case stripos($value, '<') !== false:
                return ['<', $attributeName, substr($value, 1)];
                break;
            case stripos($value, '>') !== false:
                return ['>', $attributeName, substr($value, 1)];
                break;
            case stripos($value, ',') !== false:
                return [$attributeName => explode(',', $value)];
                break;
            default:
                return [$attributeName => $value];
                break;
        }
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $value
     */
    public function setModel($value)
    {
        if ($value instanceof Model) {
            $this->model = $value;
            $this->scenario = $this->model->scenario;
            $this->modelClassName = get_class($value);
        } else {
            $this->model = new $value;
            $this->modelClassName = $value;
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return $this->attributeLabels;
    }

    /**
     * @return array
     */
    public function scenarios()
    {
        return $this->scenarios;
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = call_user_func([$this->modelClassName, 'find']);
        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'forcePageParam' => false,
                'pageSize'       => $this->pageSize,
            ],
        ]);

        // 1. 处理关联
        if (is_array($this->relations)) {
            foreach ($this->relations as $relation => $attributes) {
                $pieces = explode('.', $relation);
                $path = '';
                foreach ($pieces as $i => $piece) {
                    $path .= ($i ? '.' : '') . $piece;
                    if (!isset($this->internalRelations[$path])) {
                        $rel = $i === 0
                            ? call_user_func([$this->model, 'get' . $piece])
                            : call_user_func([new $this->internalRelations[substr($path, 0, strrpos($path, '.'))]['className'], 'get' . $piece]);
                        $this->internalRelations[$path] = [
                            'className' => $rel->modelClass,
                            'tableName' => $rel->modelClass::tableName(),
                        ];
                    }
                }
                foreach ((array)$attributes as $attribute) {
                    $attrName = "$relation.$attribute";
                    $tblAttr  = $this->internalRelations[$relation]['tableName'] . ".$attribute";
                    $this->rules[] = [$attrName, 'safe'];
                    $this->scenarios[$this->scenario][] = $attrName;
                    $this->attributes[$attrName] = '';
                    $this->relationAttributes[$attrName] = $tblAttr;
                    $dataProvider->sort->attributes[$attrName] = [
                        'asc'  => [$tblAttr => SORT_ASC],
                        'desc' => [$tblAttr => SORT_DESC],
                    ];
                }
            }
            // 重新组合 rule, 移除自定义的验证器
            $rules = [];
            $builtInValidators = Validator::$builtInValidators;
            $validRule = array_keys($builtInValidators);
            foreach ($this->rules as $rule) {
                if (isset($rule[1]) && in_array($rule[1], $validRule)) {
                    $rules[] = $rule;
                }
            }
            $this->rules = $rules;
            $query->joinWith(array_keys($this->relations));
        }

        // 2. 排序 & 分组
        if (is_array($this->defaultOrder)) {
            $dataProvider->sort->defaultOrder = $this->defaultOrder;
        }
        if (is_array($this->groupBy)) {
            $query->addGroupBy($this->groupBy);
        }

        // 3. 加载参数并处理通用条件
        $this->load($params);

        // if (isset($params['SearchModel']['dateRange'])) {
        //     $this->dateRange = $params['SearchModel']['dateRange'];
        // }
        foreach ($this->attributes as $name => $value) {
            $this->addCondition($query, $name, in_array($name, $this->partialMatchAttributes));
        }

        // 4. 日期区间搜索 - 使用公共属性 $this->dateRange
        if (!empty($this->dateRange)) {
            
            // 处理多种分隔符
            if (strpos($this->dateRange, '/') !== false) {
                $sep = '/';
            } elseif (strpos($this->dateRange, ' - ') !== false) {
                $sep = ' - ';
            } else {
                return $dataProvider;
            }
            
            if (strpos($this->dateRange, $sep) === false) {
                return $dataProvider;
            }
            
            list($startStr, $endStr) = explode($sep, $this->dateRange);
            
            // 去除可能的空格
            $startStr = trim($startStr);
            $endStr = trim($endStr);
            
            // 将日期字符串转换为时间戳
            $start = strtotime($startStr . ' 00:00:00');
            $end   = strtotime($endStr . ' 23:59:59');
            
            if ($start && $end && $start <= $end) {
                $tableName = call_user_func([$this->modelClassName, 'tableName']);
                $query->andWhere(['between', $tableName . '.created_at', $start, $end]);
            }
        }
        /* ===== 固定状态过滤（含 NULL 视为正常） ===== */
        $tableName = call_user_func([$this->modelClassName, 'tableName']);
        $query->andWhere([
            'or',
            ['>=', $tableName . '.status', \common\enums\StatusEnum::DISABLED],
            [$tableName . '.status' => null]
        ]);
        /* ===== 当前登录会员只能看自己的记录（仅当表存在 member_id 时生效） ===== */
        // if (!empty(Yii::$app->user->id)) {
        //     $tableName = call_user_func([$this->modelClassName, 'tableName']);
        //     if (Yii::$app->db->getTableSchema($tableName)->getColumn('member_id')) {
        //         // 超级管理员角色 ID 可配置，这里用 1 示例
        //         if (!Yii::$app->services->auth->isSuperAdmin()) {
        //             $query->andFilterWhere([$tableName . '.member_id' => Yii::$app->user->id]);
        //         }
        //     }
        // }
        return $dataProvider;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws \yii\base\UnknownPropertyException
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return parent::__get($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \yii\base\UnknownPropertyException
     */
    public function __set($name, $value)
    {
        if (isset($this->attributes[$name])) {
            $this->attributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }
    /**
     * 重写 load 方法，确保自定义属性被正确加载
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        
        // 手动加载自定义属性
        $scope = $formName === null ? $this->formName() : $formName;
        if ($scope === '' && !empty($data)) {
            if (isset($data['dateRange'])) {
                $this->dateRange = $data['dateRange'];
            }
        } elseif (isset($data[$scope])) {
            if (isset($data[$scope]['dateRange'])) {
                $this->dateRange = $data[$scope]['dateRange'];
            }
        }
        
        return $result;
    }
}
