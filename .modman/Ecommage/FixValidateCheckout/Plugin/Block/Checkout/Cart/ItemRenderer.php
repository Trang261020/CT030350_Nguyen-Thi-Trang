<?php
/**
 * Created By : Rohan Hapani
 */
namespace Ecommage\FixValidateCheckout\Plugin\Block\Checkout\Cart;

use Magento\Checkout\Block\Cart\Item\Renderer\Actions;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class ItemRenderer
{
    /**
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $subject
     * @param callable $proceed
     * @param AbstractItem $item
     * @SuppressWarnings(PHPMD)
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @return string
     */
    public function aroundGetActions(\Magento\Checkout\Block\Cart\Item\Renderer $subject, callable $proceed, AbstractItem $item)
    {
        /** @var \Amasty\Promo\Helper\Item $helperPromo */
        $helperPromo = ObjectManager::getInstance()->get(\Amasty\Promo\Helper\Item::class);
        $isFree = $helperPromo->isPromoItem($item);
        /** @var Actions $block */
        $block = $subject->getChildBlock('actions');
        if ($block instanceof Actions && !$isFree) {
            $block->setItem($item);
            return $block->toHtml();
        } else {
            return '';
        }
    }
}
