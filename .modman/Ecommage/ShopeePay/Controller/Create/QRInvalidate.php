<?php

namespace Ecommage\ShopeePay\Controller\Create;

use Ecommage\ShopeePay\Helper\Data;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\ShopeePay\Gateway\Http\InvalidateQr;
use Ecommage\ShopeePay\Controller\AbstractAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Exception;
use Throwable;

class QRInvalidate extends AbstractAction
{
    /**
     * @var InvalidateQr
     */
    private $invalidateQr;

    /**
     * QRInvalidate constructor.
     *
     * @param Context                  $context
     * @param OrderFactory             $orderFactory
     * @param PageFactory              $resultPageFactory
     * @param OrderManagementInterface $orderManagement
     * @param StoreManagerInterface    $storeManager
     * @param InvalidateQr             $invalidateQr
     * @param Session                  $checkoutSession
     * @param Data                     $helperData
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        StoreManagerInterface $storeManager,
        InvalidateQr $invalidateQr,
        Session $checkoutSession,
        Data $helperData
    ) {
        $this->invalidateQr = $invalidateQr;
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

            $requestData = [
                'request_id'           => $order->getId(),
                'currency'             => $order->getOrderCurrencyCode(),
                'payment_reference_id' => $order->getIncrementId(),
                'amount'               => (float)$order->getGrandTotal(),
                'promo_ids'            => $order->getAppliedRuleIds(),
                'additional_info'      => $additionalInfo,
            ];

            $response    = $this->invalidateQr->sendData($requestData);
            $body = json_decode($response->getBody(), true);
            $errorCode = $body['errcode'] ?? -3;
            $message   = $this->getMessageError($errorCode);
            $resultData['message'] = $message;
            return $resultJson->setData($resultData);
        } catch (Exception $exception) {
            return $resultJson->setData(
                [
                    'errcode' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ]
            );
        }
    }
}
