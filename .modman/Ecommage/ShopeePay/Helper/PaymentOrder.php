<?php

namespace Ecommage\ShopeePay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\StoreManagerInterface;
use Exception;

class PaymentOrder extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var BuilderInterface
     */
    protected $builder;

    /**
     * @var DateTime
     */
    protected $date;
    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * PaymentOrder constructor.
     *
     * @param Context               $context
     * @param OrderSender           $orderSender
     * @param TransactionFactory    $transactionFactory
     * @param StoreManagerInterface $storeManager
     * @param InvoiceService        $invoiceService
     * @param InvoiceSender         $invoiceSender
     * @param BuilderInterface      $builder
     * @param Data                  $helperData
     * @param DateTime              $date
     */
    public function __construct(
        Context $context,
        OrderSender $orderSender,
        TransactionFactory $transactionFactory,
        StoreManagerInterface $storeManager,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        BuilderInterface $builder,
        Data $helperData,
        DateTime $date
    ) {
        $this->date               = $date;
        $this->helperData         = $helperData;
        $this->storeManager       = $storeManager;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceService     = $invoiceService;
        $this->invoiceSender      = $invoiceSender;
        $this->orderSender        = $orderSender;
        $this->builder            = $builder;
        parent::__construct($context);
    }

    /**
     * @param Order $order
     * @param       $errorCode
     * @param       $transInfo
     *
     * @return Order
     * @throws LocalizedException
     */
    public function invoiceOrder(Order $order, $errorCode, $transInfo)
    {
        //send email new order
        $this->sendNewEmailNewOrder($order);
        if ($order->canInvoice() && !$order->hasInvoices()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->pay();
            $invoice->save();
            $invoice->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
            $payment = $order->getPayment();
            $payment->setLastTransactionId($transInfo->getData('transaction_sn'));
            $payment->setTransactionId($transInfo->getData('transaction_sn'));
            $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $transInfo->getData()]);
            $message     = __('The authorized amount is %1.', $transInfo->getData('amount'));
            $transaction = $this->builder->setPayment($payment)
                                         ->setOrder($order)
                                         ->setTransactionId($transInfo->getData('transaction_sn'))
                                         ->setAdditionalInformation([Transaction::RAW_DETAILS => $transInfo->getData()])
                                         ->setFailSafe(true)
                                         ->build(Transaction::TYPE_CAPTURE);
            $payment->addTransactionCommentsToOrder($transaction, $message);
            $payment->save();
            $transaction->save();
            $order->save();
            if ($invoice && !$invoice->getEmailSent()) {
                $invoiceSender = $this->invoiceSender;
                $invoiceSender->send($invoice);
                $order->addRelatedObject($invoice);
                $message = $this->getMessage($errorCode);
                $order->addStatusHistoryComment(
                    __('ShoppePay message: %1. Your Invoice for Order ID #%2.', $message, $order->getIncrementId())
                )->setIsCustomerNotified(true);
            }
        } elseif ($order->hasInvoices()) {
            $this->createTransactionPayment($order, $transInfo);
        }

        return $order;
    }

    /**
     * @param $order
     */
    protected function sendNewEmailNewOrder($order)
    {
        try {
            $this->orderSender->send($order);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param $errorCode
     *
     * @return Phrase
     */
    public function getMessage($errorCode)
    {
        return $this->helperData->getMessage($errorCode);
    }

    /**
     * @param $order
     * @param $transInfo
     *
     * @throws Exception
     */
    public function createTransactionPayment($order, $transInfo)
    {
        $payment = $order->getPayment();
        $payment->setLastTransactionId($transInfo->getData('transaction_sn'));
        $payment->setTransactionId($transInfo->getData('transaction_sn'));
        $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $transInfo->getData()]);
        $message     = __('The authorized amount is %1.', $transInfo->getData('amount'));
        $transaction = $this->builder->setPayment($payment)
                                     ->setOrder($order)
                                     ->setTransactionId($transInfo->getData('transaction_sn'))
                                     ->setAdditionalInformation([Transaction::RAW_DETAILS => $transInfo->getData()])
                                     ->setFailSafe(true)
                                     ->build(Transaction::TYPE_ORDER);
        $payment->addTransactionCommentsToOrder($transaction, $message);
        $payment->save();
        $transaction->save();
        $order->save();
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function log($message)
    {
        $this->helperData->log($message);
        return $this;
    }
}
