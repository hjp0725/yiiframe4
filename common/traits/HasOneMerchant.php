<?php 

namespace common\traits;

use addons\Merchants\common\models\Merchant;
use yii\db\ActiveQuery;

/**
 * Trait HasOneMerchant
 *
 * @package common\traits
 * @author  jianyan74 <751393839@qq.com>
 */
trait HasOneMerchant
{
    /**
     * 关联企业
     */
    public function getMerchant(): ActiveQuery
    {
        return $this->hasOne(Merchant::class, ['id' => 'merchant_id'])
            ->select([
                'id',
                'title',
                'cover',
                'address_name',
                'address_details',
                'longitude',
                'latitude',
                'collect_num',
            ])
            ->cache(60);
    }
}