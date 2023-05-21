<?php
namespace Ecommage\ProductIndexFreeGift\Model\ResourceModel;

/**
 * Class ProductGift
 */
class ProductGift extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init('product_free_gifts', 'entity_id');
    }
}
