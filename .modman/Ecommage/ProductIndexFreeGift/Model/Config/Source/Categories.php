<?php

namespace Ecommage\ProductIndexFreeGift\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

class Categories implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_categoryColFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Category constructor.
     *
     * @param CollectionFactory     $categoryColFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory     $categoryColFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->_categoryColFactory = $categoryColFactory;
        $this->_storeManager       = $storeManager;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function toOptionArray()
    {
        return $this->getTree();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getTree()
    {
        $rootId     = $this->_storeManager->getStore(0)->getRootCategoryId();
        $tree       = [];
        $collection = $this->_categoryColFactory->create()->addNameToResult();

        $pos = [];
        foreach ($collection as $cat) {
            $path = explode('/', $cat->getPath());
            if ((!$rootId || in_array($rootId, $path)) && $cat->getLevel()) {
                $tree[$cat->getId()] = [
                    'label' => str_repeat('--', $cat->getLevel()) . $cat->getName(),
                    'value' => $cat->getId(),
                    'path'  => $path,
                ];
            }
            $pos[$cat->getId()] = $cat->getPosition();
        }

        foreach ($tree as $catId => $cat) {
            $order = [];
            foreach ($cat['path'] as $id) {
                $order[] = isset($pos[$id]) ? $pos[$id] : 0;
            }
            $tree[$catId]['order'] = $order;
        }

        usort($tree, [$this, 'compare']);
        array_unshift($tree, ['value' => '', 'label' => '']);

        return $tree;
    }

    /**
     * Compares category data. Must be public as used as a callback value
     *
     * @param array $a
     * @param array $b
     *
     * @return int 0, 1 , or -1
     */
    public function compare($a, $b)
    {
        foreach ($a['path'] as $i => $id) {
            if (!isset($b['path'][$i])) {
                return 1;
            }
            if ($id != $b['path'][$i]) {
                $p  = isset($a['order'][$i]) ? $a['order'][$i] : 0;
                $p2 = isset($b['order'][$i]) ? $b['order'][$i] : 0;
                return ($p < $p2) ? -1 : 1;
            }
        }
        return ($a['value'] == $b['value']) ? 0 : -1;
    }

}
