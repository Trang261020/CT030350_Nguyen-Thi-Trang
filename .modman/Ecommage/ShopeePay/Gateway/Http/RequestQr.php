<?php

namespace Ecommage\ShopeePay\Gateway\Http;

class RequestQr extends AbstractRequest
{
    const API_EXTENSION = '/v3/merchant-host/qr/create';

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
    public function getPaymentReferenceId(): string
    {
        return $this->getRequestData('payment_reference_id');
    }

    /**
     * @return string
     */
    public function getAdditionalInfo(): string
    {
        return $this->getRequestData('additional_info');
    }

    /**
     * @param $data
     *
     * @return \Magento\Framework\HTTP\AsyncClient\Response
     * @throws \Magento\Framework\Exception\LocalizedException
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
