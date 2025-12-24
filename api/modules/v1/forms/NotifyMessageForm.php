<?php 

namespace api\modules\v1\forms;

use Yii;
use yii\base\Model;

/**
 * 站内通知发送表单
 *
 * Class NotifyMessageForm
 * @package api\modules\v1\forms
 * @author jianyan74 <751393839@qq.com>
 */
class NotifyMessageForm extends Model
{
    public $content;
    public $toManagerId;
    public $data;

    /**
     * 初始化：载入可选接收者列表，并剔除自己
     */
    public function init()
    {
        parent::init();
        $this->data = Yii::$app->services->devPattern->getMap();
        unset($this->data[Yii::$app->user->identity->member_id]);
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['content', 'toManagerId'], 'required'],
            ['content', 'string', 'max' => 500],
            ['toManagerId', 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'content'     => '内容',
            'toManagerId' => '发送对象',
        ];
    }
}