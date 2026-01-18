<?php

namespace common\components\gii\crud;

use Yii;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;

/**
 * Class Generator
 * @package backend\components\gii\crud
 * @author jianyan74 <751393839@qq.com>
 */
class Generator extends \yiiframe\gii\generators\crud\Generator
{
    public $listFields;
    public $formFields;
    public $inputType;

    public function beforeValidate()
    {
        parent::beforeValidate();

        /* 如果是字符串（CLI），就解码 */
        if (is_string($this->listFields)) {
            $this->listFields = json_decode($this->listFields, true) ?: [];
        }
        if (is_string($this->formFields)) {
            $this->formFields = json_decode($this->formFields, true) ?: [];
        }
        if (is_string($this->inputType)) {
            $this->inputType  = json_decode($this->inputType, true)  ?: [];
        }

        /* 防止 null */
        $this->listFields = (array)$this->listFields;
        $this->formFields = (array)$this->formFields;
        $this->inputType  = (array)$this->inputType;

        return true;
    }
    /**
     * @return array
     */
    public function fieldTypes()
    {
        return [
            'text' => Yii::t('app','文本框'),
            'textarea' => Yii::t('app','文本域'),
            'time' => Yii::t('app','时间'),
            'date' => Yii::t('app','日期'),
            'datetime' => Yii::t('app','日期时间'),
            'color' => Yii::t('app','颜色'),
            'dropDownList' => Yii::t('app','下拉框'),
            'Select2' => Yii::t('app','多选下拉框'),
            'multipleInput' => Yii::t('app','Input组'),
            'radioList' => Yii::t('app','单选按钮'),
            'checkboxList' => Yii::t('app','复选框'),
            'baiduUEditor' => Yii::t('app','百度编辑器'),
            'image' => Yii::t('app','图片上传'),
            'images' => Yii::t('app','多图上传'),
            'file' => Yii::t('app','文件上传'),
            'files' => Yii::t('app','多文件上传'),
            'cropper' => Yii::t('app','裁剪上传'),
            'latLngSelection' => Yii::t('app','经纬度选择'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['listFields', 'formFields', 'inputType'], 'safe'],
        ]);
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'listFields' => Yii::t('app', '列表字段'),
        ];
    }
    /**
     * 公共：把 "类型:1=体育新闻,2=娱乐新闻" 解析成 [1=>'体育新闻',...]
     * 失败返回空数组
     */
    public function parseCommentToMap($comment)
    {
        if (preg_match('/^([^:]+):(.+)$/', $comment, $m)) {
            $map = [];
            foreach (explode(',', $m[2]) as $pair) {
                if (strpos($pair, '=') === false) {
                    continue;
                }
                list($key, $val) = explode('=', trim($pair));
                $map[trim($key)] = trim($val);
            }
            return $map;
        }
        return [];
    }
    /**
     * 根据「linkTable 值」反推模型完整类名
     * 例：addon_ceshi_cate → \addons\Ceshi\common\models\CeshiCate
     * 系统表：yii_backend_member → \common\models\backend\Member
     */
    public function getRelationModelClass($linkTable)
    {
        $prefix      = \Yii::$app->db->tablePrefix;
        $addonPrefix = $prefix . 'addon_';

        /* ========== 1. 系统表分支 ========== */
        if (strpos($linkTable, 'yii_backend_') === 0) {
            // 完整表名 → 类名映射表（可继续追加）
            $map = [
                'yii_backend_member'      => '\common\models\backend\Member',
                'yii_backend_department'  => '\common\models\backend\Department',
                'yii_member'        => '\addons\Member\common\models\Member',
                'yii_merchant_member'        => '\addons\Merchants\common\models\Member',
                'yii_merchant_department'         => '\addons\Merchants\common\models\Department',
            ];
            return $map[$linkTable] ?? '';
        }

        /* ========== 2. 原插件分支（无改动） ========== */
        $rawName    = preg_replace('#^' . preg_quote($addonPrefix, '#') . '#', '', $linkTable);
        $pluginName = ucfirst(explode('_', $rawName)[0]);
        $modelName  = str_replace(' ', '', ucwords(str_replace('_', ' ', $rawName)));
        return "\\addons\\{$pluginName}\\common\\models\\{$modelName}";
    }
    /**
     * 取当前模型对应的真实表注释
     * @return string
     */
    public function getTableComment()
    {
        $class     = $this->modelClass;
        $db        = $class::getDb();
        $rawTable  = $db->getSchema()->getRawTableName($class::tableName());

        $comment = $db->createCommand("
            SELECT table_comment
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_name   = :table
        ", [':table' => $rawTable])->queryScalar();

        return $comment
            ?: Inflector::pluralize(Inflector::camel2words(StringHelper::basename($this->modelClass)));
    }
    /**
     * Generates code for active field
     * @param string $attribute
     * @return string
     */
    public function generateActiveField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        list($type,$linkTable) = array_pad(explode('|',$this->inputType[$attribute] ?? ''),2,'');

        // $type = $this->inputType[$attribute] ?? '';
        $column = $tableSchema->columns[$attribute];
        switch ($type) {
            case 'text':
                return parent::generateActiveField($attribute);
                break;
            case 'number':
                return "\$form->field(\$model, '$attribute')->input('number')"; 
            case 'password':
                return "\$form->field(\$model, '$attribute')->passwordInput()";   
            case 'textarea':
                return "\$form->field(\$model, '$attribute')->textarea()";
                break;
            case 'dropDownList':
            case 'radioList':
            case 'checkboxList':
            case 'Select2':
                /* 1. 先读注释 */
                $tableSchema = $this->getTableSchema();
                $column      = $tableSchema->columns[$attribute];
                $comment     = $column->comment ?? '';

                /* 2. 如果指定了关联表，统一走模型 */
                list($type, $linkTable) = array_pad(explode('|', $this->inputType[$attribute] ?? ''), 2, '');
                if (!empty($linkTable)) {
                    $fullClass = $this->getRelationModelClass($linkTable);
                    if ($fullClass === '') {          // 系统表映射里没命中，也允许插件表解析失败
                        $mapCode = '';
                    } else {
                        $mapCode = "{$fullClass}::dropDown()";
                    }
                } else {
                    /* 3. 无关联表 → 用备注解析 */
                    // 无关联表 → 用备注解析
                    $map = $this->parseCommentToMap($comment);
                    if (empty($map)) {
                        // 默认兜底：紧凑 [] 格式
                        $mapCode = "['1' => '选项1', '2' => '选项2']";
                    } else {
                        // 手工拼 PHP 数组字串，键带引号，用 [] 包裹
                        $items = [];
                        foreach ($map as $k => $v) {
                            $items[] = "'{$k}' => '{$v}'";
                        }
                        $mapCode = '[' . implode(', ', $items) . ']';
                    }
                }

                /* 4. 根据控件类型返回对应代码 */
                switch ($type) {
                    case 'dropDownList':
                        return "\$form->field(\$model, '$attribute')->dropDownList({$mapCode}, ['prompt' => '请选择'])";
                    case 'radioList':
                        return "\$form->field(\$model, '$attribute')->radioList({$mapCode})";
                    case 'checkboxList':
                        return "\$form->field(\$model, '$attribute')->checkboxList({$mapCode})";
                    case 'Select2':
                        return "\$form->field(\$model, '$attribute')->widget(Select2::class, [
                            'data' => {$mapCode},
                            'options' => ['placeholder' => Yii::t('app','请选择'),'multiple' => true],
                            'pluginOptions' => [
                                'allowClear' => true
                            ],
                        ])";  
                }
                break;

            case 'baiduUEditor':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Ueditor'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Ueditor\common\widgets\ueditor\UEditor::class, [])";
                else 
                return "\$form->field(\$model, '$attribute')->textarea()";
                break;
            case 'color':
                return "\$form->field(\$model, '$attribute')->widget(\kartik\color\ColorInput::class, [
                            'options' => ['placeholder' => '请选择颜色'],
                    ]);";
                break;
            case 'time':
                return "\$form->field(\$model, '$attribute')->widget(kartik\\time\TimePicker::class, [
                        'language' => 'zh-CN',
                        'pluginOptions' => [
                            'showSeconds' => true
                        ]
                    ])";
                break;
            case 'date':
                return "\$form->field(\$model, '$attribute')->widget(kartik\date\DatePicker::class, [
                        'language' => 'zh-CN',
                        'layout'=>'{picker}{input}',
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true, // 今日高亮
                            'autoclose' => true, // 选择后自动关闭
                            'todayBtn' => true, // 今日按钮显示
                        ],
                        'options'=>[
                            'class' => 'form-control no_bor',
                            'value' => \$model->isNewRecord ? date('Y-m-d') : date('Y-m-d',strtotime(\$model->$attribute)),
                        ]
                    ])";
                break;
            case 'datetime':
                return "\$form->field(\$model, '$attribute')->widget(kartik\datetime\DateTimePicker::class, [
                        'language' => 'zh-CN',
                        'options' => [
                            'value' => \$model->isNewRecord ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s',strtotime(\$model->$attribute)),
                        ],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd hh:ii',
                            'todayHighlight' => true, // 今日高亮
                            'autoclose' => true, // 选择后自动关闭
                            'todayBtn' => true, // 今日按钮显示
                        ]
                    ])";
                break;
            
            case 'cropper':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Webuploader')&&\yiiframe\plugs\common\AddonHelper::isInstall('Cropper'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Cropper\common\widgets\cropper\Cropper::class, [
                            'config' => [
                                  // 可设置自己的上传地址, 不设置则默认地址
                                  // 'server' => '',
                             ],
                            'formData' => [
                                // 'drive' => 'local',// 默认本地 支持 qiniu/oss/cos 上传
                            ],
                    ]);";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            case 'latLngSelection':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Map'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Map\common\widgets\selectmap\Map::class, [
                            'type' => 'amap', // amap高德;tencent:腾讯;baidu:百度
                    ])->hint('点击地图某处才会获取到经纬度，否则默认北京')";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            case 'image':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Webuploader'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Webuploader\common\widgets\webuploader\Files::class, [
                            'type' => 'images',
                            'theme' => 'default',
                            'themeConfig' => [],
                            'config' => [
                                // 可设置自己的上传地址, 不设置则默认地址
                                // 'server' => '',
                                'pick' => [
                                    'multiple' => false,
                                ],
                            ]
                    ]);";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            case 'images':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Webuploader'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Webuploader\common\widgets\webuploader\Files::class, [
                            'type' => 'images',
                            'theme' => 'default',
                            'themeConfig' => [],
                            'config' => [
                                // 可设置自己的上传地址, 不设置则默认地址
                                // 'server' => '',
                                'pick' => [
                                    'multiple' => true,
                                ],
                            ]
                    ]);";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            case 'file':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Webuploader'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Webuploader\common\widgets\webuploader\Files::class, [
                            'type' => 'files',
                            'theme' => 'default',
                            'themeConfig' => [],
                            'config' => [
                                // 可设置自己的上传地址, 不设置则默认地址
                                // 'server' => '',
                                'pick' => [
                                    'multiple' => false,
                                ],
                            ]
                    ]);";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            case 'files':
                if (\yiiframe\plugs\common\AddonHelper::isInstall('Webuploader'))
                return "\$form->field(\$model, '$attribute')->widget(\addons\Webuploader\common\widgets\webuploader\Files::class, [
                            'type' => 'files',
                            'theme' => 'default',
                            'themeConfig' => [],
                            'config' => [
                                // 可设置自己的上传地址, 不设置则默认地址
                                // 'server' => '',
                                'pick' => [
                                    'multiple' => true,
                                ],
                            ]
                    ]);";
                else
                return "\$form->field(\$model, '$attribute')->textInput()";
                break;
            default:
                return parent::generateActiveField($attribute);
                break;
        }
    }
}