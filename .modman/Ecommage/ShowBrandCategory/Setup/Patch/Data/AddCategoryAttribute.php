<?php

namespace Ecommage\ShowBrandCategory\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Ecommage\ShowBrandCategory\Model\Config\Source\Brands;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Catalog\Model\Category;


class AddCategoryAttribute implements DataPatchInterface
{

    protected $_moduleDataSetup;
    protected $_eavSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->_moduleDataSetup = $moduleDataSetup;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $this->_moduleDataSetup]);

        $eavSetup->addAttribute(Category::ENTITY, 'category_brands', [
            'type' => 'text',
            'label' => 'Choose brands',
            'backend' => \Ecommage\ShowBrandCategory\Model\Entity\Attribute\Backend\ArrayBackend::class,
//            'frontend' => \Ecommage\ShowBrandCategory\Model\Entity\Attribute\Backend\ArrayBackend::class,
            'input' => 'multiselect',
            'source' => Brands::class,
            'default' => '',
            'sort_order' => 3,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'General Information',
            'visible_on_front' => true
        ]);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
