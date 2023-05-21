<?php

namespace Ecommage\ShopeePay\Cron;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class OrderExpired
{
    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * PrepareContent constructor.
     *
     * @param CollectionFactory $orderCollectionFactory
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory

    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
    }

    /**
     * job chạy để cập nhập lại trạng thái đơn hàng quá thời gian xử lý (quá 20 phút)
     *
     * @inheritdoc
     */
    public function execute()
    {
        $orderCollection = $this->orderCollectionFactory->create();
        $orderCollection->join(
            ['payment' => $orderCollection->getTable('sales_order_payment')],
            'payment.parent_id = main_table.entity_id',
            ['method' => 'payment.method']
        );
        $orderCollection->addFieldToFilter('payment.method', 'shopeepay');
        $orderCollection->addFieldToFilter('main_table.state', Order::STATE_NEW);
        $orderCollection->getSelect()->where(
            'main_table.created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)'
        );
        /** @var Order $order */
        foreach ($orderCollection as $order) {
            $order->addCommentToStatusHistory(__('Overdue orders are not processed'));
            $order->cancel()->save();
        }
    }
}
