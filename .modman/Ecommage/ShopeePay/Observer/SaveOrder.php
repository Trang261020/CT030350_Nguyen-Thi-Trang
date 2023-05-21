<?php

namespace Ecommage\ShopeePay\Observer;

use Ecommage\ShopeePay\Model\Payment\ShopeePay;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class SaveOrder implements ObserverInterface
{
    /**
     * Execute observer.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order   = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        if ($payment && $payment->getMethod() === 'shopeepay') {
            /** @var ShopeePay $shopeepay */
            $shopeepay  = $payment->getMethodInstance();
            $orderStats = $shopeepay->getConfigData('order_status');
            $order->setStatus($orderStats);
            $order->setCanSendNewEmailFlag(false);
        }
    }
}
