<?php
namespace Ecommage\ProductIndexFreeGift\Model\ResourceModel\ProductGift;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(
            \Ecommage\ProductIndexFreeGift\Model\ProductGift::class,
            \Ecommage\ProductIndexFreeGift\Model\ResourceModel\ProductGift::class
        );
    }
}
