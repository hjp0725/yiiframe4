<?php 

namespace services\merchant;

use common\components\Service;
use common\enums\MerchantStateEnum;
use common\enums\StatusEnum;
use addons\Merchants\common\models\Merchant;
use yii\web\UnprocessableEntityHttpException;

/**
 * 商户
 * Class MerchantService
 * @package services\merchant
 * @author jianyan74 <751393839@qq.com>
 */
class MerchantService extends Service
{
    /**
     * @var int
     */
    protected $merchant_id = 0;

    /* -------------------- 读 -------------------- */
    public function getId()
    {
        return  $this->merchant_id;
    }

    public function setId($merchant_id): void
    {
        $this->merchant_id = $merchant_id;
    }

    public function getNotNullId(): int
    {
        return !empty($this->merchant_id) ? $this->merchant_id : 0;
    }

    public function addId($merchant_id): void
    {
        if (!$this->merchant_id) {
            $this->merchant_id = $merchant_id;
        }
    }

    /**
     * 查询并验证
     * @throws UnprocessableEntityHttpException
     */
    public function findVerifyPerfect( $merchant_id): void
    {
        $merchant = $this->findById($merchant_id);
        $this->verifyPerfect($merchant);
    }

    /**
     * 验证商户信息
     * @throws UnprocessableEntityHttpException
     */
    public function verifyPerfect(?array $merchant): void
    {
        if (empty($merchant)) {
            throw new UnprocessableEntityHttpException('找不到企业');
        }

        if ((int)$merchant['state'] === StatusEnum::DELETE) {
            throw new UnprocessableEntityHttpException('企业已被关闭');
        }

        if ((int)$merchant['status'] === StatusEnum::DISABLED) {
            throw new UnprocessableEntityHttpException('请先完善企业信息');
        }
    }

    /**
     * 统计数量
     */
    public function getCount( $merchant_id = ''): int
    {
        return (int)Merchant::find()
            ->select('id')
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['state' => StatusEnum::ENABLED])
            ->andFilterWhere(['id' => $merchant_id])
            ->count();
    }

    public function getApplyCount( $merchant_id = ''): int
    {
        return (int)Merchant::find()
            ->select('id')
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['in', 'state', [MerchantStateEnum::AUDIT]])
            ->andFilterWhere(['id' => $merchant_id])
            ->count();
    }

    public function findByLogin()
    {
        return $this->findById($this->getId());
    }

    public function findById( $id)
    {
        return Merchant::find()
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['id' => $id])
            ->one();
    }

    public function findBaseById( $id): ?array
    {
        return Merchant::find()
            ->select([
                'id', 'title', 'cover', 'address_name',
                'address_details', 'longitude', 'latitude', 'collect_num',
            ])
            ->where(['id' => $id])
            ->andWhere(['status' => StatusEnum::ENABLED])
            ->asArray()
            ->one();
    }

    /**
     * @param int[] $ids
     * @return array
     */
    public function findBaseByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        return Merchant::find()
            ->select([
                'id', 'title', 'cover', 'address_name',
                'address_details', 'longitude', 'latitude',
            ])
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['in', 'id', $ids])
            ->asArray()
            ->all();
    }

    public function findBaseAll(): array
    {
        return Merchant::find()
            ->select([
                'id', 'title', 'cover', 'address_name',
                'address_details', 'longitude', 'latitude',
            ])
            ->where(['>=', 'status', StatusEnum::DISABLED])
            ->asArray()
            ->all();
    }
}