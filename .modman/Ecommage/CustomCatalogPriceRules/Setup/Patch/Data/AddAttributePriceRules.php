<?php
declare(strict_types=1);
namespace Ecommage\CustomCatalogPriceRules\Setup\Patch\Data;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class AddAttributePriceRules implements DataPatchInterface, PatchRevertableInterface

{
    /**
     * ModuleDataSetupInterface
     *
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * EavSetupFactory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * AddProductAttribute constructor.
     *
     * @param ModuleDataSetupInterface  $moduleDataSetup
     * @param EavSetupFactory           $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $eavSetup->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
            'discount_price_range',
            [
                'type' => 'int',
                'label' => __('Discount Price Range'),
                'input' => 'select',
                'class' => '',
                'source' => '',
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'is_visible' => true,
                'searchable' => false,
                'user_defined' => false,
                'filterable' => true,
                'comparable' => false,
                'is_required' => false,
                'visible_on_front' => false,
                'is_user_defined' => true,
                'used_in_product_listing' => false,
                'is_filterable' => true,
                'option' => ['values' =>
                                 [
                                     '0% - 10%',
                                     '10% - 20%',
                                     '20% - 30%',
                                     '30% - 40%',
                                     '40% - 50%',
                                     '50% - 60%',
                                     '60% - 70%',
                                     '70% - 80%',
                                     '80% - 90%',
                                     '90% - 100%'
                                 ]
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    public function revert()
    {
        // TODO: Implement revert() method.
    }
}
