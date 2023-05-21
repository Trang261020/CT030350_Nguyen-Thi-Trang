<?php

namespace Ecommage\ProductIndexFreeGift\Indexer;

use Magento\Framework\Mview\ActionInterface;

class FreeGiftIndex implements \Magento\Framework\Indexer\ActionInterface, ActionInterface
{
    const INDEXER_ID = 'product_free_gifts';
    /**
     * @var \Ecommage\ProductIndexFreeGift\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * FreeGiftIndex constructor.
     *
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Ecommage\ProductIndexFreeGift\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Ecommage\ProductIndexFreeGift\Helper\FreeGift $helper
    ) {
        $this->helper          = $helper;
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids)
    {
        $this->helper->debug("Start execute", $ids);
        $indexer = $this->indexerRegistry->get(self::INDEXER_ID);
        if ($indexer->isInvalid()) {
            $this->helper->debug("execute isInvalid");
            return;
        }

        $this->helper->executeList($ids);
        $this->helper->debug("End execute");
    }

    /**
     * Will take all of the data and reindex
     * Will run when reindex via command line
     */
    public function executeFull()
    {
        $this->helper->debug("Start executeFull");
        $this->helper->reIndexAll();
    }

    /**
     * Works with a set of entity changed (may be massaction)
     */
    public function executeList(array $ids)
    {
        $this->helper->debug("Start executeList", $ids);
        $this->helper->reIndexProductIds($ids);
        $this->helper->debug("End executeList");
    }

    /**
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($id)
    {
        $this->helper->debug("Start executeRow", [$id]);
        $indexer = $this->indexerRegistry->get(self::INDEXER_ID);
        if ($indexer->isScheduled()) {
            $this->helper->debug("executeRow isScheduled");
            return;
        }

        $this->helper->reIndexProductIds([$id]);
        $this->helper->debug("End executeRow");
    }
}
