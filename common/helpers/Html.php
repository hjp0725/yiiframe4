<?php 

namespace common\helpers;

use common\enums\MethodEnum;
use common\enums\StatusEnum;
use common\enums\WhetherEnum;
use common\enums\MessageLevelEnum;
use Yii;
use yii\helpers\BaseHtml;

/**
 * Class Html
 * @package common\helpers
 * @author jianyan74 <751393839@qq.com>
 */
class Html extends BaseHtml
{
    /* -------------------- 基础按钮 -------------------- */
    public static function create(array $url, array $options = [], string $content = '创建'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-success btn-xs', 'ion-plus');
    }

    public static function edit(array $url, array $options = [], string $content = '编辑'): string
    {
        return self::a(Yii::t('app', $content), $url, array_merge(['class' => 'btn btn-primary btn-sm'], $options));
    }

    public static function delete(array $url, array $options = [], string $content = '删除'): string
    {
        return self::a(Yii::t('app', $content), $url, array_merge([
            'class' => 'btn btn-danger btn-sm',
            'onclick' => 'rfDelete(this);return false;',
        ], $options));
    }

    public static function reset(array $url, array $options = [], string $content = '重置'): string
    {
        return self::a($content, $url, array_merge([
            'class' => 'btn btn-danger btn-sm',
            'onclick' => 'rfDelete(this);return false;',
        ], $options));
    }

    public static function view(array $url, array $options = [], string $content = '查看'): string
    {
        return self::a(Yii::t('app', $content), $url, array_merge(['class' => 'btn btn-primary btn-sm'], $options));
    }

    public static function export(array $url, array $options = [], string $content = '导出'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-info btn-xs', '');
    }

    public static function import(array $url, array $options = [], string $content = '导入'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-warning btn-xs', 'ion-plus');
    }

    public static function upload(array $url, array $options = [], string $content = '上传'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-primary btn-xs', 'ion-plus');
    }

    public static function download(array $url, array $options = [], string $content = '下载'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-primary btn-xs', 'ion-plus');
    }

    public static function cate(array $url, array $options = [], string $content = '分类'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-primary btn-xs', 'ion-plus');
    }

    public static function sync(array $url, array $options = [], string $content = '同步'): string
    {
        return self::buttonTemplate($url, $options, $content, 'btn btn-primary btn-xs', 'ion-plus');
    }

    public static function linkButton(array $url, string $content, array $options = []): string
    {
        return self::a($content, $url, array_merge(['class' => 'btn btn-white btn-sm'], $options));
    }

    /* -------------------- 状态 / 审核 / 是否 / 级别 / 方法 标签 -------------------- */
    public static function status(int $status = 1, array $options = []): string
    {
        if (!self::beforVerify('ajax-update')) {
            return '';
        }

        $map = [
            StatusEnum::DISABLED => ['禁用', 'btn-default'],
            StatusEnum::ENABLED  => ['启用', 'btn-success'],
        ];

        list($text, $class) = $map[$status] ?? ['', ''];
        return self::tag('span', Yii::t('app', $text), array_merge([
            'class' => "btn btn-sm $class",
            'data-toggle' => 'tooltip',
            'data-original-title' => Yii::t('app', $text),
            'onclick' => 'rfStatus(this)',
        ], $options));
    }

    public static function audit(int $status = 1, array $options = []): string
    {
        if (!self::beforVerify('ajax-update')) {
            return '';
        }

        $map = [
            StatusEnum::DISABLED => ['已拒绝', 'btn-success'],
            StatusEnum::ENABLED  => ['已审核', 'btn-default'],
            StatusEnum::DELETE   => ['待审核', 'btn-default'],
        ];

        list($text, $class) = $map[$status] ?? ['', ''];
        return self::tag('span', Yii::t('app', $text), array_merge([
            'class' => "btn btn-sm $class",
            'data-toggle' => 'tooltip',
            'data-original-title' => Yii::t('app', $text),
            'onclick' => 'rfStatus(this)',
        ], $options));
    }

    public static function whether(int $status = 1): string
    {
        $map = [
            WhetherEnum::ENABLED  => ['是', 'label-primary'],
            WhetherEnum::DISABLED => ['否', 'label-default'],
        ];

        list($text, $class) = $map[$status] ?? ['', ''];
        return self::tag('span', Yii::t('app', $text), ['class' => "label label-sm $class"]);
    }

    public static function messageLevel(int $level): string
    {
        $map = [
            MessageLevelEnum::INFO    => ['信息', 'label-info'],
            MessageLevelEnum::WARNING => ['警告', 'label-warning'],
            MessageLevelEnum::ERROR   => ['错误', 'label-danger'],
        ];

        list($text, $class) = $map[$level] ?? ['', ''];
        return self::tag('span', $text, ['class' => "label label-sm $class"]);
    }

    public static function method(int $method): string
    {
        $map = [
            MethodEnum::GET    => ['GET', 'label-success'],
            MethodEnum::POST   => ['POST', 'label-info'],
            MethodEnum::PUT    => ['PUT', 'label-primary'],
            MethodEnum::DELETE => ['DELETE', 'label-danger'],
            MethodEnum::ALL    => ['ALL', 'label-warning'],
        ];

        list($text, $class) = $map[$method] ?? ['', ''];
        return self::tag('span', $text, ['class' => "label label-sm $class"]);
    }

    /* -------------------- 工具 -------------------- */
    public static function sort($value, array $options = []): string
    {
        if (!self::beforVerify('ajax-update')) {
            return (string)$value;
        }

        return self::input('text', 'sort', $value, array_merge([
            'class' => 'form-control',
            'onblur' => 'rfSort(this)',
            'style' => 'min-width:55px',
        ], $options));
    }

    public static function textNewLine(string $string, int $num = 36, int $cycle = 3): string
    {
        if ($string === '') {
            return '';
        }
        return self::tag('span', implode('<br>', StringHelper::textNewLine($string, $num, $cycle)), [
            'title' => $string,
        ]);
    }

    public static function timeStatus(int $start, int $end): string
    {
        $now = time();
        if ($start > $end) {
            return "<span class='label label-danger'>有效期错误</span>";
        }
        if ($start > $now) {
            return "<span class='label label-default'>未开始</span>";
        }
        if ($start < $now && $end > $now) {
            return "<span class='label label-primary'>进行中</span>";
        }
        if ($end < $now) {
            return "<span class='label label-default'>已结束</span>";
        }
        return '';
    }

    public static function modelBaseCss(): void
    {
        echo self::cssFile(Yii::getAlias('@web') . '/resources/css/yiiframe.css?v=' . time());

        Yii::$app->controller->view->registerCss(<<<CSS
.modal{z-index:999;}
.modal-backdrop{z-index:998;}
CSS
        );
    }

    /* -------------------- 重写 A 标签，带权限校验 -------------------- */
    public static function a($text, $url = null, $options = []): string
    {
        if ($url !== null && !self::beforVerify($url)) {
            return '';
        }

        if ($url !== null) {
            $options['href'] = Url::to($url);
        }

        return parent::a($text, null, $options);
    }

    /* -------------------- 私有 -------------------- */
    private static function buttonTemplate(array $url, array $options, string $text, string $class, string $icon): string
    {
        $content = '<i class="icon ' . $icon . '"></i> ' . Yii::t('app', $text);
        return self::a($content, $url, array_merge(['class' => $class], $options));
    }

    private static function beforVerify($route): bool
    {
        if (Yii::$app->user->isGuest) {
            return true;
        }

        is_array($route) && $route = $route[0];
        $route = Url::getAuthUrl($route);
        substr($route, 0, 1) !== '/' && $route = '/' . $route;

        if (Yii::$app->params['inAddon'] ?? false) {
            $route = StringHelper::replace('/addons/', '', $route);
        }

        return Auth::verify($route);
    }
}