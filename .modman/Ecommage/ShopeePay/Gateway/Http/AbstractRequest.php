<?php

namespace Ecommage\ShopeePay\Gateway\Http;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\AsyncClient\HttpResponseDeferredInterface;
use Magento\Framework\HTTP\AsyncClient\Request;
use Magento\Framework\HTTP\AsyncClientInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Ecommage\ShopeePay\Logger\Logger;
use Ecommage\ShopeePay\Model\Payment\ShopeePay;
use Throwable;

abstract class AbstractRequest extends DataObject
{
    const RESPONSE_DATA = 'response_data';
    const REQUEST_DATA  = 'request_data';
    /**
     * @var AsyncClientInterface
     */
    protected $httpClient;
    /**
     * @var ShopeePay
     */
    protected $shopeePay;
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * AbstractRequest constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param AsyncClientInterface  $httpClient
     * @param ShopeePay             $shopeePay
     * @param Data                  $jsonHelper
     * @param Logger                $logger
     *
     * @throws LocalizedException
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        AsyncClientInterface $httpClient,
        ShopeePay $shopeePay,
        Data $jsonHelper,
        Logger $logger
    ) {
        $this->logger       = $logger;
        $this->shopeePay    = $shopeePay;
        $this->jsonHelper   = $jsonHelper;
        $this->httpClient   = $httpClient;
        $this->storeManager = $storeManager;
        $this->_construct();
    }

    /**
     * @throws LocalizedException
     */
    protected function _construct()
    {
        $this->setRequestData(
            [
                'currency'         => $this->getCurrency(),
                'platform_type'    => $this->getPlatform(),
                'store_ext_id'     => $this->getStoreExtId(),
                //'transactionId'   => $this->getTransactionType(),
                'transaction_type' => $this->getTransactionType(),
                'merchant_ext_id'  => $this->getMerchantExtId(),
            ]
        );
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return 'VND';
    }

    /**
     * @return string|null
     */
    public function getPlatform()
    {
        return $this->shopeePay->getPlatform();
    }

    public function getTransactionType(): int
    {
        return 13; // payment
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getMerchantExtId(): string
    {
        return $this->shopeePay->getMerchantExtId();
    }

    /**
     * @return string
     */
    public function getStoreExtId(): string
    {
        return $this->shopeePay->getStoreExtId();
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->shopeePay->getClientId();
    }

    /**
     * @return string
     */
    public function getSecretKey(): string
    {
        return $this->shopeePay->getSecretKey();
    }

    /**
     * @param string $rawHash
     *
     * @return string
     * @throws LocalizedException
     */
    public function computeSignature(string $rawHash): string
    {
        return $this->shopeePay->generateSignature($rawHash);
    }

    /**
     * @param $api
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getApiUrl($api)
    {
        return $this->getEndpoint() . $api;
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getEndpoint(): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->shopeePay->getEndpoint($storeId);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getRequestData($key = '')
    {
        if ('' === $key) {
            return $this->getData(self::REQUEST_DATA);
        }

        $key = sprintf('%s/%s', self::REQUEST_DATA, $key);
        return $this->getData($key);
    }

    /**
     * @param array $arr
     *
     * @return AbstractRequest
     */
    public function addRequestData(array $arr)
    {
        if ($this->_data === []) {
            $this->setRequestData($arr);
            return $this;
        }

        $data = (array)$this->getRequestData();
        $data = array_merge($data, $arr);
        ksort($data);
        $this->setRequestData($data);
        return $this;
    }

    /**
     * @param      $key
     * @param null $value
     *
     * @return AbstractRequest
     */
    public function setRequestData($key, $value = null)
    {
        if ($key === (array)$key) {
            return $this->setData(self::REQUEST_DATA, $key);
        }

        $key = sprintf('%s/%s', self::REQUEST_DATA, $key);
        return $this->setData($key, $value);
    }

    /**
     * @param $apiExtension
     *
     * @return HttpResponseDeferredInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function sendRequest($apiExtension)
    {
        $payload = $this->getRequestData();
        if (is_array($payload)) {
            $serializer = new Json();
            $payload    = $serializer->serialize($payload);
        }

        $apiUrl = $this->getApiUrl($apiExtension);
        $this->logger->info(sprintf("Api request url: %s data: %s", $apiUrl, $payload));
        return $this->httpClient->request(
            new Request(
                $apiUrl,
                Request::METHOD_POST,
                [
                    'Content-Type'      => 'application/json',
                    'X-Airpay-ClientId' => $this->getClientId(),
                    'X-Airpay-Req-H'    => $this->computeSignature($payload),
                ],
                $payload
            )
        );
    }

    /**
     * @param $apiExtension
     *
     * @return mixed|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    public function send($apiExtension)
    {
        $responseData = $this->sendRequest($apiExtension);
        $response     = $responseData->get();
        $this->setData(self::RESPONSE_DATA, $response);
        return $this->getData(self::RESPONSE_DATA);
    }

    /**
     * @param array $data
     *
     * @return array[]
     * @throws LocalizedException
     */
    protected function buildRequest($data = [])
    {
        if (!empty($data)) {
            $this->addRequestData($data);
        }

        $this->validate(
            'store_ext_id',
            'merchant_ext_id',
            'transaction_type',
            'amount',
        );
        return $this->getRequestData();
    }

    /**
     * Convert array of object data with to array with keys requested in $keys array
     *
     * @param array $keys array of required keys
     *
     * @return array
     */
    public function toArray(array $keys = [])
    {
        if (count($keys) == 1) {
            $key = reset($keys);
            return $this->_data[$key] ?? [];
        }

        return parent::toArray($keys);
    }

    /**
     * @param mixed ...$args
     *
     * @throws LocalizedException
     */
    protected function validate(...$args)
    {
        foreach ($args as $key) {
            $value = $this->getConfigEnvironment($key);
            if (
                null === $value &&
                !$this->getRequestData($key)
            ) {
                throw new LocalizedException(
                    new Phrase('The %1 parameter is required.', [$key])
                );
            }
        }
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function getConfigEnvironment(string $field)
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $method  = 'get' . ($field !== null ? str_replace('_', '', ucwords($field, '_')) : '');
            return $this->shopeePay->{$method}($storeId);
        } catch (Exception $exception) {
            $this->logger->info($exception->getMessage());
        }

        return '';
    }
}
