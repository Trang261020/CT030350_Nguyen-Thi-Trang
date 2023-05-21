<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2022 Amasty (https://www.amasty.com)
 * @package Pre Order Base for Magento 2
 */

namespace Ecommage\ProductIndexFreeGift\Plugin\InventorySales\Plugin\StockState\CheckQuoteItemQtyPlugin;

use Amasty\Preorder\Model\ConfigProvider;
use Amasty\Preorder\Model\Product\Constants;
use Closure;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\StockRegistry;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventorySales\Plugin\StockState\CheckQuoteItemQtyPlugin;
use Magento\Catalog\Api\ProductRepositoryInterface;

class CheckQuoteItemQty
{
    /**
     * @var StockRegistry
     */
    private $stockRegistry;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var FormatInterface
     */
    private $localeFormat;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        StockRegistry $stockRegistry,
        ConfigProvider $configProvider,
        FormatInterface $localeFormat,
        ProductRepositoryInterface $productRepository
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->configProvider = $configProvider;
        $this->localeFormat = $localeFormat;
        $this->productRepository = $productRepository;
    }

    /**
     * @param CheckQuoteItemQtyPlugin $subject
     * @param DataObject $result
     * @param StockStateInterface $prevSubject
     * @param Closure $proceed
     * @param int|string $productId
     * @param string|float|int|null $itemQty
     * @param string|float|int|null $qtyToCheck
     * @param string|float|int|null $origQty
     * @param null|int $scopeId
     * @return DataObject
     */
    public function afterAroundCheckQuoteItemQty(
        CheckQuoteItemQtyPlugin $subject,
        DataObject $result,
        StockStateInterface $prevSubject,
        Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId = null
    ): DataObject {
        $stockStatus = $this->stockRegistry->getStockStatus($productId, $scopeId);
        $qty = max($this->getNumber($itemQty), $this->getNumber($qtyToCheck));
        $product = $this->productRepository->getById($productId);
        if ($stockStatus->getStockItem()->getBackorders() == Constants::BACKORDERS_PREORDER_OPTION
            && $stockStatus->getQty() > 0
            && $qty > $stockStatus->getQty()
        ) {
            if (!$this->configProvider->isAllowEmpty()) {
                $message = $this->getResultMessage(
                    $this->configProvider->getBelowZeroMessage(),
                    $product->getName(),
                    (float) $stockStatus->getQty()
                );
                $result->setHasError(true);
                $result->setMessage($message);
                $result->setQuoteMessage($message);
                $result->setQuoteMessageIndex('qty');
            } elseif ($this->configProvider->isDisableForPositiveQty()) {
                $backorderedQty = $qty - $stockStatus->getQty();
                $result->setMessage(
                    $this->getResultMessage(
                        $this->configProvider->getCartMessage(),
                        $product->getName(),
                        (float) $backorderedQty
                    )
                );
            }
        }

        return $result;
    }

    private function getResultMessage(string $message, string $productName, float $qty): string
    {
        return sprintf(
            $message,
            $productName,
            $qty
        );
    }

    /**
     * @param string|float|int|null $qty
     * @return float|null
     */
    private function getNumber($qty): ?float
    {
        if (!is_numeric($qty)) {
            return $this->localeFormat->getNumber($qty);
        }

        return $qty;
    }
}
