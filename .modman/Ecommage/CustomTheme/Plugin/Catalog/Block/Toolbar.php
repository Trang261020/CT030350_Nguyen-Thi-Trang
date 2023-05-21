<?php
namespace Ecommage\CustomTheme\Plugin\Catalog\Block;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Toolbar
{
    /**
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param \Closure                                           $proceed
     * @param                                                    $collection
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function beforeSetCollection(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject, $collection
    ) {
        $currentOrder = $subject->getCurrentOrder();
        if ($currentOrder && $currentOrder == 'saving') {
            if ($collection instanceof ProductCollection) {
                $collection->setOrder('saving', 'desc');
            }
        }
    }
}
