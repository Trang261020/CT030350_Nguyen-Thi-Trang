<?php

namespace Ecommage\ShopeePay\Controller\Create;

use Exception;
use Ecommage\ShopeePay\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\ShopeePay\Gateway\Http\CreateOrder;
use Ecommage\ShopeePay\Controller\AbstractAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

class Order extends AbstractAction
{
    /**
     * @var CreateOrder
     */
    private $createOrder;

    /**
     * Order constructor.
     *
     * @param Context                  $context
     * @param OrderFactory             $orderFactory
     * @param PageFactory              $resultPageFactory
     * @param OrderManagementInterface $orderManagement
     * @param StoreManagerInterface    $storeManager
     * @param Session                  $checkoutSession
     * @param CreateOrder              $createOrder
     * @param Data                     $helperData
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        CreateOrder $createOrder,
        Data $helperData
    ) {
        $this->createOrder = $createOrder;
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
                'payment_reference_id' => $order->getIncrementId(),
                'currency'             => $order->getOrderCurrencyCode(),
                'amount'               => (float)$order->getGrandTotal(),
                'promo_ids'            => $order->getAppliedRuleIds(),
                'return_url'           => $this->getUrl('shopeepay/notify_transaction/status'),
                'additional_info'      => $additionalInfo,
            ];

            $response              = $this->createOrder->sendData($requestData);
            $body                  = json_decode($response->getBody(), true);
            $errcode               = $body['errcode'] ?? -3;
            $resultData['redirect'] = $this->getUrl('checkout/onepage/failure');
            $resultData['message'] = $this->getMessageError($errcode);
            $resultData['errcode'] = $errcode;
            if ($errcode === 0) {
                $resultData['redirect'] = $body['redirect_url_http'];
            } else {
                $this->cancelOrder($resultData['message']);
            }

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
