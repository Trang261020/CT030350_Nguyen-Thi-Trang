<?php

namespace Ecommage\ShopeePay\Gateway\Http;

class TransactionCheck extends AbstractRequest
{
    const API_EXTENSION = '/v3/merchant-host/transaction/check';

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->getRequestData('request_id');
    }

    /**
     * @return string
     */
    public function getAmount(): int
    {
        return (int)$this->getRequestData('amount');
    }

    /**
     * @return string
     */
    public function getReferenceId(): string
    {
        return $this->getRequestData('reference_id');
    }

    /**
     * @param $data
     *
     * @return mixed|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Throwable
     */
    public function sendData($data)
    {
        $requestData = $this->buildRequest($data);
        $requestData['amount'] = $this->getAmount() * 100;
        $this->setRequestData($requestData);
        return $this->send(self::API_EXTENSION);
    }
}
