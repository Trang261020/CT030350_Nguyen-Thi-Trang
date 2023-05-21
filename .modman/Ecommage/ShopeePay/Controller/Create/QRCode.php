<?php

namespace Ecommage\ShopeePay\Controller\Create;

use Ecommage\ShopeePay\Helper\Data;
use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\ShopeePay\Gateway\Http\RequestQr;
use Ecommage\ShopeePay\Controller\AbstractAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Throwable;

class QRCode extends AbstractAction
{
    /**
     * @var RequestQr
     */
    private $requestQr;
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * QRCode constructor.
     *
     * @param Context                  $context
     * @param OrderFactory             $orderFactory
     * @param PageFactory              $resultPageFactory
     * @param OrderManagementInterface $orderManagement
     * @param StoreManagerInterface    $storeManager
     * @param Session                  $checkoutSession
     * @param RequestQr                $requestQr
     * @param DateTime                 $dateTime
     * @param Data                     $helperData
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        RequestQr $requestQr,
        DateTime $dateTime,
        Data $helperData
    ) {
        $this->dateTime  = $dateTime;
        $this->requestQr = $requestQr;
        parent::__construct($context, $orderFactory, $resultPageFactory, $orderManagement, $storeManager, $checkoutSession, $helperData);
    }

    /**
     * @return ResultInterface
     * @throws Throwable
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->validate();
            $order = $this->getOrder();
            $names = $prices = $qty = [];
            foreach ($order->getItems() as $item) {
                $names[]  = sprintf('%s(%s)', $item->getName(), $item->getSku());
                $prices[] = $item->getPrice();
                $qty[]    = $item->getQtyOrdered();
            }

            $additionalInfo = '';
            if (!empty($names)) {
                $additionalInfo = json_encode(
                    [
                        'product' => implode(', ', $names),
                        'price'   => implode(', ', $prices),
                        'qty'     => implode(', ', $qty)
                    ]
                );
            }

            $currentTime = $this->dateTime->gmtTimestamp();
            $expiredTime = strtotime('+20 minutes', $currentTime);
            $response    = $this->requestQr->sendData(
                [
                    'request_id'           => $order->getId(),
                    'currency'             => $order->getOrderCurrencyCode(),
                    'payment_reference_id' => $order->getIncrementId(),
                    'amount'               => (float)$order->getGrandTotal(),
                    'promo_ids'            => $order->getAppliedRuleIds(),
                    'additional_info'      => $additionalInfo,
                    'expiry_time'          => $expiredTime,
                ]
            );

            $resultData = [
                'errcode'  => -3,
                'message'  => __('Sorry, but something went wrong.'),
                'redirect' => $this->getUrl('checkout/onepage/failure')
            ];
            if ($response->getStatusCode() == 200 && $response->getBody()) {
                $body      = json_decode($response->getBody(), true);
                $errorCode = $body['errcode'] ?? -3;
                $message   = $this->getMessageError($errorCode);
                if ($errorCode == 0) {
                    $resultData['qr_url']       = $body['qr_url'] ?? null;
                    $resultData['store_name']   = $body['store_name'] ?? null;
                    $resultData['qr_content']   = $body['qr_content'] ?? null;
                    $resultData['expired_time'] = $expiredTime - $currentTime;
                }

                //translate message
                $resultData['errcode'] = $errorCode;
                $resultData['message'] = __($message);
                if (!$errorCode) {
                    $this->cancelOrder($message);
                }

                return $resultJson->setData($resultData);
            }

            return $resultJson->setData($resultData);
        } catch (Exception $exception) {
            $this->cancelOrder($exception->getMessage());
            return $resultJson->setData(
                [
                    'errcode'  => $exception->getCode(),
                    'message'  => $exception->getMessage(),
                    'redirect' => $this->getUrl('checkout/onepage/failure')
                ]
            );
        }
    }
}
