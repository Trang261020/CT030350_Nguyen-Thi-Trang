<?php
namespace Ecommage\ProductIndexFreeGift\Model;

/**
 * Class ProductGift
 */
class ProductGift extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'product_free_gifts';

    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(\Ecommage\ProductIndexFreeGift\Model\ResourceModel\ProductGift::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}
