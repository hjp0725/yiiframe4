<?php 
namespace backend\controllers;

use Yii;
use common\helpers\FileHelper;
use common\helpers\ResultHelper;
use yiiframe\plugs\common\AddonHelper;
use backend\controllers\BaseController;

/**
 * 主控制器
 *
 * Class MainController
 * @package backend\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class MainController extends BaseController
{
    /* ---------- 视图 ---------- */
    public function actionIndex(): string
    {
        // 设置为 AJAX 关闭掉 DEBUG 显示
        YII_DEBUG && Yii::$app->request->headers->set('X-Requested-With', 'XMLHttpRequest');
        return $this->renderPartial($this->action->id, [

        ]);
    }

    /**
     * 子框架默认主页
     *
     * @return string
     */
    public function actionSystem()
    {
        $mid = Yii::$app->services->merchant->getId();

        $attachment = AddonHelper::isInstall('Webuploader')
            ? Yii::$app->services->backendReport->getAttachment($mid)
            : 0;

        $behavior = AddonHelper::isInstall('Monitoring')
            ? Yii::$app->services->backendReport->getActionBehavior($mid)
            : 0;

        $logCount = AddonHelper::isInstall('Log')
            ? Yii::$app->services->backendReport->getLog()
            : 0;

        return $this->render($this->action->id, [
            'member'         => Yii::$app->services->backendReport->getMember($mid) ?: 0,
            'attachmentSize' => round(FileHelper::getDirSize(Yii::getAlias('@attachment')) / 1024 / 1024),
            'mysql_size'     => Yii::$app->formatter->asShortSize(Yii::$app->services->backendReport->getDefaultDbSize()),
            'attachment'     => $attachment,
            'behavior'       => $behavior,
            'logCount'       => $logCount,
        ]);
    }

    /* ---------- Ajax 统计 ---------- */
    public function actionLoginCount(string $type): array
    {
        $data = AddonHelper::isInstall('Monitoring')
            ? Yii::$app->services->backendReport->getLogin($type)
            : ['xAxisData' => [],'fieldsName'=> [],'seriesData'=> []];

        return ResultHelper::json(200, '获取成功', $data);
    }

    public function actionMemberCount(string $type): array
    {
        return ResultHelper::json(200, '获取成功', Yii::$app->services->backendReport->getMemberCountStat($type));
    }

    public function actionLogCount(string $type): array
    {
        $data = AddonHelper::isInstall('Log')
            ? Yii::$app->services->backendReport->getLogCountStat($type)
            : ['seriesData'=>[["name"=>"暂无数据","value"=> 0]],'fieldsName'=>[]];

        return ResultHelper::json(200, '获取成功', $data);
    }

    /* ---------- 清理缓存 ---------- */
    public function actionClearCache()
    {
        return Yii::$app->cache->flush()
            ? $this->message(Yii::t('app', '缓存清理成功'), $this->redirect(['/main/system']))
            : $this->message(Yii::t('app', '缓存清理失败'), $this->redirect(['/main/system']));
    }
}