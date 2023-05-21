<?php

namespace Ecommage\ShopeePay\Controller\Order;

use Ecommage\ShopeePay\Controller\AbstractAction;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Cancel extends AbstractAction
{
    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->validate();
            $this->cancelOrder();
            return $resultJson->setData(
                [
                    'errcode' => 0,
                    'message' => 'success'
                ]
            );
        } catch (\Exception $exception) {
            return $resultJson->setData(
                [
                    'errcode' => $exception->getCode(),
                    'message' => $exception->getMessage()
                ]
            );
        }
    }
}
