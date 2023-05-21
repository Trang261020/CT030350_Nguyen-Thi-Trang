<?php
namespace Ecommage\CatalogWidget\Block\Adminhtml;

/**
 * Class RebuildBlock
 *
 * @package Ecommage\CatalogWidget\Block\Adminhtml
 */
class RebuildBlock extends \Magento\Backend\Block\Template
{
    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('rebuild_block/index/index');
    }
}

