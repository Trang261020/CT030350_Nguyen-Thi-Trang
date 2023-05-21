<?php

namespace Ecommage\ShopeePay\Controller;

use Ecommage\ShopeePay\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\Phrase;

/**
 * Class AbstractAction
 *
 * @package Ecommage\ShopeePay\Controller
 */
abstract class AbstractAction extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var Data
     */
    protected $helperData;
    /**
     * @var null
     */
    protected $order = null;

    /**
     * AbstractAction constructor.
     *
     * @param Context                  $context
     * @param OrderFactory             $orderFactory
     * @param PageFactory              $resultPageFactory
     * @param OrderManagementInterface $orderManagement
     * @param StoreManagerInterface    $storeManager
     * @param Session                  $checkoutSession
     * @param Data                     $helperData
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        StoreManagerInterface $storeManager,
        Session $checkoutSession,
        Data $helperData
    ) {
        parent::__construct($context);
        $this->orderFactory      = $orderFactory;
        $this->storeManager      = $storeManager;
        $this->checkoutSession   = $checkoutSession;
        $this->orderManagement   = $orderManagement;
        $this->resultPageFactory = $resultPageFactory;
        $this->helperData        = $helperData;
    }

    /**
     * @return int
     */
    protected function getOrderId()
    {
        $id = $this->getRequest()->getParam('order_id');
        $id = $id ? $id : $this->checkoutSession->getLastOrderId();
        return $id;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            $orderId     = $this->getOrderId();
            $this->order = $this->orderFactory->create()->load($orderId);
        }

        return $this->order;
    }

    /**
     * @param null $comment
     *
     * @return Order|null
     * @throws LocalizedException
     */
    public function cancelOrder($comment = null)
    {
        if (!$comment) {
            $comment = $this->getMessageCancel();
        }

        $order = $this->getOrder();
        /** Canceling order to reproduce test case */
        $order->addCommentToStatusHistory($comment);
        $order->cancel()->save();
        return $order;
    }

    /**
     * @return Phrase
     */
    public function getMessageCancel()
    {
        return __('Overdue orders are not processed');
    }

    /**
     * @throws LocalizedException
     */
    public function validate()
    {
        if (!$this->order) {
            $this->getOrder();
        }

        if (
            !$this->order ||
            !$this->order->getId() ||
            $this->order->getState() != Order::STATE_NEW
        ) {
            throw new LocalizedException(__('Order is not correct'));
        }
    }

    /**
     * @param string $route
     * @param array  $params
     *
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->helperData->getUrl($route, $params);
    }

    /**
     * @param $message
     *
     * @return Data
     */
    public function log($message)
    {
        return $this->helperData->log($message);
    }

    /**
     * @param $code
     *
     * @return Phrase
     */
    public function getMessageError($code)
    {
        return $this->helperData->getMessage($code);
    }
}
