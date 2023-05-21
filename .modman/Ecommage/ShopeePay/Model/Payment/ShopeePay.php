<?php

namespace Ecommage\ShopeePay\Model\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\AbstractMethod;

class ShopeePay extends AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'shopeepay';

    /**
     * @param null $storeId
     *
     * @return int
     */
    public function isLive($storeId = null): int
    {
        return (int)$this->getConfigData('is_live', $storeId);
    }

    /**
     * @return string
     */
    public function getPlatform($storeId = null)
    {
        return $this->getConfigData('platform_type', $storeId);
    }

    /**
     * @param      $data
     * @param null $storeId
     *
     * @return string
     * @throws LocalizedException
     */
    public function generateSignature($data, $storeId = null)
    {
        $secretKey = $this->getSecretKey($storeId);
        return base64_encode(hash_hmac('sha256', $data, $secretKey, true));
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMerchantExtId($storeId = null)
    {
        return $this->getConfigValue('merchant_ext_id', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getStoreExtId($storeId = null)
    {
        return $this->getConfigValue('store_ext_id', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return string
     * @throws LocalizedException
     */
    public function getSecretKey($storeId = null)
    {

        return $this->getConfigEnvironment('secret_key', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return string
     * @throws LocalizedException
     */
    public function getClientId($storeId = null)
    {
        return $this->getConfigEnvironment('client_id', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return string
     */
    public function getEndpoint($storeId = null): string
    {
        return $this->getConfigValue('end_point', $storeId);
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return string
     */
    public function getConfigValue($field, $storeId = null)
    {
        $field = sprintf('test_%s', $field);
        if ($this->isLive($storeId)) {
            $field = sprintf('live_%s', $field);
        }

        return $this->getConfigData($field, $storeId);
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return string
     */
    public function getConfigEnvironment($field, $storeId = null)
    {
        $type = $this->getPlatform($storeId);
        $field = sprintf('%s_%s', $type, $field);
        return $this->getConfigValue($field, $storeId);
    }
}
