<?php

namespace Ecommage\ShopeePay\Controller\Notify\Transaction;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Ecommage\ShopeePay\Gateway\Http\TransactionCheck;
use Ecommage\ShopeePay\Controller\AbstractAction;
use Ecommage\ShopeePay\Helper\PaymentOrder;
use Ecommage\ShopeePay\Helper\Data;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Exception;

class Status extends AbstractAction
{
    /**
     * @var TransactionCheck
     */
    protected $transactionCheck;
    /**
     * @var PaymentOrder
     */
    protected $paymentOrder;

    /**
     * Status constructor.
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
     * @return Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            $incrementId = $this->getRequest()->getParam('reference_id');
            $this->order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->order;
    }

    /**
     * @return mixed
     * @throws LocalizedException
     */
    public function execute()
    {
        $resultReject = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $this->validate();
            $order       = $this->getOrder();
            $transaction = $this->getRequest()->getParams();
            $transInfo   = new DataObject($transaction);
            $code        = $transInfo->getData('result_code');
            $message     = $this->helperData->getResultCode($code);
            if ($transInfo->getData('result_code') != 100) {
                $this->cancelOrder($message);
                $this->messageManager->addErrorMessage($message);
                return $resultReject->setPath('checkout/onepage/failure');
            }

            //Không dùng redirect return_url ở Bước 3 để đánh giá giao dịch thành công hay không.
            //Chỉ nên tin cậy trạng thái giao dịch qua thông báo từ phía server ShopeePay (Bước 4).
            $response  = $this->transactionCheck->sendData(
                [
                    'request_id'   => $order->getId(),
                    'reference_id' => $order->getIncrementId(),
                    'amount'       => (float)$order->getGrandTotal(),
                    'currency'     => $order->getOrderCurrencyCode(),
                ]
            );
            $body      = json_decode($response->getBody(), true);
            $errcode   = $body['errcode'] ?? -3;
            $trans     = $body['transaction'] ?? [];
            $transInfo = new DataObject($trans);
            if ($errcode == 0 && $transInfo->getData('status') == 3) {
                //$this->messageManager->addSuccessMessage($message);
                $this->paymentOrder->invoiceOrder($order, 0, $transInfo);
                //fix lỗi mất session khi chuyển sang trang thanh toán thành công
                $this->reCheckAndUpdateSession($order);
                return $resultReject->setPath('checkout/onepage/success');
            }

            $message = $this->getMessageError($errcode);
            $this->cancelOrder($message);
            $this->messageManager->addErrorMessage($message);
            return $resultReject->setPath('checkout/onepage/failure');
        } catch (Exception $exception) {
            $this->cancelOrder($exception->getMessage());
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $resultReject->setPath('checkout/onepage/failure');
        }
    }

    /**
     * @param $order
     */
    public function reCheckAndUpdateSession($order)
    {
        if (!$this->checkoutSession->getLastQuoteId()) {
            if ($order && $order->getId()) {
                $this->checkoutSession->setLastQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
                $this->checkoutSession->setLastOrderId($order->getId());
                $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
                $this->checkoutSession->setLastOrderStatus($order->getStatus());
            }
        }
    }

    /**
     * @throws LocalizedException
     */
    public function validate()
    {
        $data = $this->getRequest()->getParams();
        if (!isset($data['reference_id'])) {
            throw new Exception(__('The reference_id is required but missing.'));
        }

        if (!isset($data['signature'])) {
            throw new Exception(__('The signature is required but missing.'));
        }

        $signature = $data['signature'];
        unset($data['signature']);
        $rawHash    = http_build_query($data);
        $cSignature = $this->transactionCheck
            ->computeSignature($rawHash);
        $cSignature = $this->encodeSignature($cSignature);
        if ($signature != $cSignature) {
            throw new Exception(__('Signature is incorrect.'));
        }

        parent::validate();
    }

    /**
     * @param $cSignature
     *
     * @return string
     */
    private function encodeSignature($cSignature)
    {
        return str_replace(['/', '+'], ['_', '-'], $cSignature);
    }
}
