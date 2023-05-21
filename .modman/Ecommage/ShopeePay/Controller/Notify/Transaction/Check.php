<?php

namespace Ecommage\ShopeePay\Controller\Notify\Transaction;

use Ecommage\ShopeePay\Helper\Data;
use Ecommage\ShopeePay\Helper\PaymentOrder;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\ShopeePay\Gateway\Http\TransactionCheck;
use Ecommage\ShopeePay\Controller\AbstractAction;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Exception;

class Check extends AbstractAction
{
    /**
     * @var TransactionCheck
     */
    private $transactionCheck;
    /**
     * @var PaymentOrder
     */
    private $paymentOrder;

    /**
     * Check constructor.
     *
     * @param Context                  $context
     * @param OrderFactory             $orderFactory
     * @param PageFactory              $resultPageFactory
     * @param OrderManagementInterface $orderManagement
     * @param StoreManagerInterface    $storeManager
     * @param TransactionCheck         $transactionCheck
     * @param PaymentOrder             $paymentOrder
     * @param Session                  $checkoutSession
     * @param Data                     $helperData
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        StoreManagerInterface $storeManager,
        TransactionCheck $transactionCheck,
        PaymentOrder $paymentOrder,
        Session $checkoutSession,
        Data $helperData
    ) {
        $this->paymentOrder     = $paymentOrder;
        $this->transactionCheck = $transactionCheck;
        parent::__construct($context, $orderFactory, $resultPageFactory, $orderManagement, $storeManager, $checkoutSession, $helperData);
    }

    /**
     * @return ResultInterface
     * @throws \Throwable
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->validate();
            $order    = $this->getOrder();
            $response = $this->transactionCheck->sendData(
                [
                    'request_id'    => $order->getId(),
                    'reference_id'  => $order->getIncrementId(),
                    'amount'        => (float)$order->getGrandTotal(),
                    'currency'   => $order->getOrderCurrencyCode(),
                ]
            );

            $body                  = json_decode($response->getBody(), true);
            $errcode               = $body['errcode'] ?? 0;
            $transaction           = $body['transaction'] ?? [];
            $transInfo             = new DataObject($transaction);
            $resultData['message'] = $this->getMessageError($errcode);
            $resultData['status']  = $transInfo->getData('status');
            $resultData['errcode'] = $errcode;
            if ($errcode == 0 && $transInfo->getData('status') == 3) {
                $this->paymentOrder->invoiceOrder($order, $errcode, $transInfo);
                return $resultJson->setData(
                    [
                        'errcode'        => $errcode,
                        'state'          => $order->getState(),
                        'status'         => $transInfo->getData('status'),
                        'order_id'       => $transInfo->getData('reference_id'),
                        'transaction_sn' => $transInfo->getData('transaction_sn')
                    ]
                );
            } elseif ($errcode == 0 && $transInfo->getData('status') != 3) {
                $statusCode            = $transInfo->getData('status');
                $resultData['message'] = $this->helperData->getTransactionStatus($statusCode);
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
