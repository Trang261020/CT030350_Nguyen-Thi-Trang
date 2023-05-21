<?php

namespace Ecommage\CustomCatalogPriceRules\Cron;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 *
 */
class CronUpdateDiscountPriceProduct
{
    /**
     * @var array
     */
    protected $_options = [] ;

    /**
     * @var \Ecommage\CatalogWidget\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulation;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $attribute;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param \Ecommage\CatalogWidget\Helper\Data                            $helper
     * @param \Magento\Store\Model\App\Emulation                             $emulation
     * @param \Psr\Log\LoggerInterface                                       $logger
     * @param AttributeRepositoryInterface                                   $attributeRepository
     * @param \Magento\Framework\App\ResourceConnection                      $resourceConnection
     * @param \Magento\Eav\Model\Config                                      $eavConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param PriceCurrencyInterface                                         $priceCurrency
     */
    public function __construct
    (
        \Ecommage\CatalogWidget\Helper\Data $helper,
        \Magento\Store\Model\App\Emulation $emulation,
        \Psr\Log\LoggerInterface $logger,
        AttributeRepositoryInterface $attributeRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        PriceCurrencyInterface $priceCurrency
    )
    {
        $this->helper = $helper;
        $this->emulation = $emulation;
        $this->logger = $logger;
        $this->attributeRepository = $attributeRepository;
        $this->resource = $resourceConnection;
        $this->attribute = $eavConfig;
        $this->priceCurrency = $priceCurrency;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->emulation->startEnvironmentEmulation(1, \Magento\Framework\App\Area::AREA_ADMINHTML, true); // You can set store id and area

        $collection = $this->collectionFactory->create()
                                              ->addAttributeToSelect('*')
                                              ->addAttributeToFilter('status', Status::STATUS_ENABLED);

        $attribute = $this->attributeRepository->get(Product::ENTITY, 'discount_price_range');
        foreach ($collection as $product) {
            $finalPrice = $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
            $price = $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
            if ($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $price = $this->priceCurrency->convertAndRound($price);
            }

            if (!empty($price) && !empty($finalPrice)){
                $discount  = $product->getData('discount_price_range');
                // $sales = $this->helper->displayDiscountPercent($product) ?? '-0%';
                $sales = $this->setDiscountPrice($price,$finalPrice);
                $tableName = "catalog_product_entity_int";
                if (!$discount) {
                    try {
                        $data = [
                            "attribute_id" => $attribute->getAttributeId(),
                            "store_id"     => 0,
                            "entity_id"    => $product->getEntityId(),
                            "value"        => $this->setValueOption($this->setParma($sales))
                        ];

                        $connection = $this->resource->getConnection();
                        $connection->insertOnDuplicate($tableName, $data);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                } else {
                    try {
                        $connection = $this->resource->getConnection();
                        $connection->update($tableName,
                                            [
                                                'value' => $this->setValueOption($this->setParma($sales))
                                            ],
                                            [
                                                'entity_id IN (?)' => $product->getEntityId(),
                                                'attribute_id IN (?)' => $attribute->getAttributeId()
                                            ]
                        );
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }
        $this->emulation->stopEnvironmentEmulation();
        shell_exec('php bin/magento indexer:reset');
        shell_exec('php bin/magento indexer:reindex');

    }

    /**
     * @param $param
     *
     * @return string|null
     */
    public function setParma($param)
    {
        $chane = null;
        if ($param)
        {
            $chane = substr(str_replace('%','',$param),1);
        }
        return $chane;
    }

    /**
     * @param $price
     * @param $finalPrice
     *
     * @return float|int
     */
    protected function setDiscountPrice($price, $finalPrice)
    {
        $sale = 0 ;
        if ($finalPrice && $price){
            if ($finalPrice < $price){
                $sale = ($price - $finalPrice) / $price * 100 ;
            }
        }
        return '-'.round($sale).'%';
    }

    /**
     * @param $id
     *
     * @return array
     */
    protected function setSql($id)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('catalog_product_index_price');
        $sql = $connection->select()->distinct(true)->from(array('catalog' => $tableName), array('*'));
        $sql->where('entity_id = ?',$id);

        return $connection->fetchRow($sql);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOptionAttribute()
    {
        $options = $this->attribute->getAttribute('catalog_product', 'discount_price_range');
        $optionLabel =  $options->getSource()->getAllOptions();
        foreach ($optionLabel as $option){
            if ($option['value'] != ""){
                $this->_options[] = $option;
            }
        }
        return $this->_options;
    }

    /**
     * @param $sale
     *
     * @return mixed|null
     */
    public function setValueOption(int $sale)
    {
        $values = 0 ;
        if ($this->getOptionAttribute()){
            foreach ($this->getOptionAttribute() as $value){
                if (array_key_exists('label',$value))
                {
                    $param = str_replace('%','',$value['label']);
                    $data = explode('-',$param);
                    if  ($sale >= (int) $data[0] && $sale <= (int) $data[1]){
                        return (int) $value['value'];
                    }
                }

            }
        }
        return $values;
    }
}
