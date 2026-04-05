<?php 

namespace common\replaces;

/**
 * 数据库连接断开异常重试
 * errorInfo = ['HY000',2006,'错误信息']
 *
 * @package common\replaces
 * @author  jianyan74 <751393839@qq.com>
 */
class Command extends \yii\db\Command
{
    /** @var bool 是否重试 */
    public $retry = false;

    /**
     * 执行写操作（INSERT/UPDATE/DELETE）
     * @return int
     * @throws \yii\db\Exception
     */
    public function execute(): int
    {
        try {
            return parent::execute();
        } catch (\yii\db\Exception $e) {
            if ($this->handleException($e)) {
                return parent::execute();
            }
            throw $e;
        }
    }

    /**
     * 执行读操作（SELECT）
     * @param string $method
     * @param int|null $fetchMode
     * @return mixed
     * @throws \yii\db\Exception
     */
    protected function queryInternal(string $method, ?int $fetchMode = null)
    {
        try {
            return parent::queryInternal($method, $fetchMode);
        } catch (\yii\db\Exception $e) {
            if ($this->handleException($e)) {
                return parent::queryInternal($method, $fetchMode);
            }
            throw $e;
        }
    }

    /**
     * 判断是否需要重试
     * @param \yii\db\Exception $e
     * @return bool
     * @throws \yii\db\Exception
     */
    private function handleException(\yii\db\Exception $e): bool
    {
        $msg = $e->getMessage();
        $need = str_contains($msg, 'MySQL server has gone away') ||
                str_contains($msg, 'Error while sending QUERY packet') ||
                str_contains($msg, 'SQLSTATE[HY000]: General error') ||
                (!empty($e->errorInfo) && in_array($e->errorInfo[1] ?? 0, [2006, 2013], true));

        if ($need) {
            $this->retry = true;
            $this->pdoStatement = null;
            $this->db->close();
            $this->db->open();
            return true;
        }

        return false;
    }

    /**
     * 重连后重新绑定参数
     */
    protected function bindPendingParams(): void
    {
        if ($this->retry) {
            $this->retry = false;
            $this->bindValues($this->params);
        }
        parent::bindPendingParams();
    }
}