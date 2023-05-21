<?php

namespace Ecommage\CatalogWidget\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutInterface;

/**
 * Class Data
 *
 * @package Ecommage\CustomTheme\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @param LayoutInterface $layout
     * @param Context $context
     */
    public function __construct
    (
        LayoutInterface $layout,
        Context         $context
    )
    {
        $this->layout = $layout;
        parent::__construct($context);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @return false|float|int
     */
    public function displayDiscountPercent($product)
    {
        if ($product->getTypeId() == 'configurable' && !$product->isAvailable()) {
            return null;
        }

        $simplePrices = [];
        $_savingPercent = 0;
        if ($product->getTypeId() == "simple") {
            $simplePrices[] = $product->getPrice();
        } else {
            $_children = $product->getTypeInstance()->getUsedProducts($product);
            foreach ($_children as $child) {
                $simplePrices[] = $child->getPrice();
            }
        }

        $_finalPrice = $product->getFinalPrice();
        $_price = min($simplePrices);
        if ($_finalPrice < $_price) {
            $_savingPercent = 100 - round(($_finalPrice / $_price) * 100);
        }
        if ($_savingPercent !== 0) {
            return '-' . $_savingPercent . '%';
        }

        return null;
    }

    public function getBlock()
    {
        return $this->layout->createBlock(\Ecommage\CatalogPromoCountdown\Block\Widgets\Countdown::class);
    }

}
