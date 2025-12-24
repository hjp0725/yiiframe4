<?php 

namespace services\backend;

use Yii;
use common\enums\StatusEnum;
use common\components\Service;
use common\models\backend\Member;
use common\helpers\EchantsHelper;
use addons\Log\common\models\Log;
use addons\Monitoring\common\models\ActionLog;
use addons\Webuploader\common\models\Attachment;

/**
 * Class ReportService
 * @package services\backend
 * @author jianyan74 <751393839@qq.com>
 */
class ReportService extends Service
{
    /* ---------- 基础计数 ---------- */

    public function getLog(): int
    {
        return (int)Log::find()
            ->where(['>', 'status', StatusEnum::DISABLED])
            ->count();
    }

    public function getActionBehavior(): int
    {
        return (int)ActionLog::find()
            ->where(['>', 'status', StatusEnum::DISABLED])
            ->count();
    }

    public function getAttachment(): int
    {
        return (int)Attachment::find()
            ->where(['>', 'status', StatusEnum::DISABLED])
            ->count();
    }

    public function getMember($merchant_id = ''): int
    {
        return (int)Member::find()
            ->where(['>', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $merchant_id ?: $this->getMerchantId()])
            ->count();
    }

    /* ---------- 数据库体积 ---------- */

    /**
     * 统计数据库大小（字节）
     * @throws \yii\db\Exception
     */
    public function getDefaultDbSize(): int
    {
        $models = Yii::$app->db->createCommand('SHOW TABLE STATUS')->queryAll();
        $size   = 0;
        foreach ($models as $m) {
            $size += (int)($m['Data_length'] ?? 0);
        }
        return $size;
    }

    /* ---------- 登录折线 ---------- */

    public function getLogin(string $type): array
    {
        $fields = [
            'success' => Yii::t('app', '成功'),
            'false'   => Yii::t('app', '失败'),
        ];
        list($time, $format) = EchantsHelper::getFormatTime($type);

        return EchantsHelper::lineOrBarInTime(
            function (int $start, int $end, string $fmt) {
                return ActionLog::find()
                    ->select([
                        'count(user_id!=0 or null) as success',
                        'count(user_id=0 or null) as false',
                        "FROM_UNIXTIME(created_at, '{$fmt}') as time",
                    ])
                    ->where(['>', 'status', StatusEnum::DISABLED])
                    ->andWhere(['between', 'created_at', $start, $end])
                    ->andWhere(['behavior' => 'login'])
                    ->groupBy(['time'])
                    ->asArray()
                    ->all();
            },
            $fields,
            $time,
            $format
        );
    }

    /* ---------- 访问排行 ---------- */

    
    public function getMemberCountStat(string $type): array
    {
        list($time, $format) = EchantsHelper::getFormatTime($type);
        return EchantsHelper::pie(function ($start_time, $end_time) {
            $result = Member::find()
            ->select(['title as name', 'SUM(visit_count) as value'])
            ->where(['>', 'status', StatusEnum::DISABLED])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->groupBy(['title'])
            ->asArray()
            ->all();
            return [$result];
        },$time,['name' => '登录次数']);
    }
    /* ---------- 日志词云 ---------- */

    public function getLogCountStat(string $type): array
    {
        list($time, $format) = EchantsHelper::getFormatTime($type);

        return EchantsHelper::wordCloud(function ($start, $end) {
            return Log::find()
            ->select(['error_code as name', 'count(error_code) as value'])
            ->groupBy(['error_code'])
            ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
            ->asArray()
            ->all();

        }, $time);
            
    }
}