<?php
namespace backend\modules\common\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\db\Exception as DbException;
use common\helpers\FileHelper;
use yiiframe\plugs\services\UpdateService;
use backend\controllers\BaseController;
use Exception;

/**
 * Class SystemController
 * @package backend\modules\base\controllers
 * @author jianyan74 <751393839@qq.com>
 */
class SystemController extends BaseController
{
    /**
     * 系统信息页面
     * 
     * @return string
     * @throws DbException
     */
    public function actionInfo(): string
    {
        // 禁用函数
        $disableFunctions = ini_get('disable_functions');
        $disableFunctions = !empty($disableFunctions) ? explode(',', $disableFunctions) : '未禁用';
        
        // 附件大小
        $attachmentSize = FileHelper::getDirSize(Yii::getAlias('@attachment'));
        
        // 更新信息
        if (!Yii::$app->debris->backendConfig('sys_dev')) {
            $updateInfo = [
                'info' => '已经是最新版本',
                'time' => ''
            ];
        } else {
            $updateInfo = UpdateService::Version();
        }

        return $this->render('info', [
            'mysql_size' => Yii::$app->services->backendReport->getDefaultDbSize(),
            'attachment_size' => $attachmentSize ?? 0,
            'disable_functions' => $disableFunctions,
            'updateinfo' => $updateInfo['info'],
            'domain_time' => $updateInfo['time'],
        ]);
    }

    /**
     * 系统更新操作
     * 
     * @return mixed
     */
    public function actionUpdate()
    {
        // 检查会话令牌
        if (!Yii::$app->session->get('token')) {
            return $this->message(
                '请先绑定会员账号！', 
                $this->redirect(Yii::$app->request->referrer), 
                'warning'
            );
        }

        try {
            // 下载更新包
            $version = UpdateService::download();
            
            // 解压更新包
            UpdateService::unzip();
            
            // 安装更新
            if (!UpdateService::install($version)) {
                return $this->message(
                    '更新文件失败,请手动将/backend/runtime/update/下对应版本的文件覆盖到站点相应目录，注意：升级前请先对站点做好备份！', 
                    $this->redirect(['info']), 
                    'error'
                );
            }
            
            return $this->message(
                '更新成功，请手动刷新网页查看系统版本号', 
                $this->redirect(['info'])
            );
            
        } catch (Exception $e) {
            return $this->message(
                $e->getMessage() ?: '更新文件失败,请手动将/backend/runtime/update/下对应版本的文件覆盖到站点相应目录，注意：升级前请先对站点做好备份！', 
                $this->redirect(['info']), 
                'error'
            );
        }
    }
}