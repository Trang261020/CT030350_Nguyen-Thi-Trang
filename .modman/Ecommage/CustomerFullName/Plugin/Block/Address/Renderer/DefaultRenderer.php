<?php

namespace Ecommage\CustomerFullName\Plugin\Block\Address\Renderer;

use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\Metadata\ElementFactory;
use Magento\Framework\Escaper;

class DefaultRenderer
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var ElementFactory
     */
    protected $_elementFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var \Magento\Customer\Api\AddressMetadataInterface
     */
    protected $_addressMetadataService;

    /**
     * @var Mapper
     */
    protected $addressMapper;

   public function __construct
   (
       \Magento\Framework\Escaper $escaper,
       \Magento\Framework\Filter\FilterManager $filterManager,
       \Magento\Directory\Model\CountryFactory $countryFactory,
       ElementFactory $elementFactory,
       \Magento\Customer\Api\AddressMetadataInterface $metadataService
   )
   {
       $this->escaper = $escaper;
       $this->filterManager = $filterManager;
       $this->_addressMetadataService = $metadataService;
       $this->_elementFactory = $elementFactory;
       $this->_countryFactory = $countryFactory;
   }

    public function aroundRenderArray(\Magento\Customer\Block\Address\Renderer\DefaultRenderer $subject, callable $proceed,$addressAttributes, $format = null)
    {
        switch ($subject->getType()->getCode()) {
            case 'html':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_HTML;
                break;
            case 'pdf':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_PDF;
                break;
            case 'oneline':
                $dataFormat = ElementFactory::OUTPUT_FORMAT_ONELINE;
                break;
            default:
                $dataFormat = ElementFactory::OUTPUT_FORMAT_TEXT;
                break;
        }

        $attributesMetadata = $this->_addressMetadataService->getAllAttributesMetadata();
        $data = [];
        foreach ($attributesMetadata as $attributeMetadata) {
            if (!$attributeMetadata->isVisible()) {
                continue;
            }
            $attributeCode = $attributeMetadata->getAttributeCode();
            if ($attributeCode == 'country_id' && isset($addressAttributes['country_id'])) {
                $data['country'] = $this->_countryFactory->create()
                                                         ->loadByCode($addressAttributes['country_id'])
                                                         ->getName($addressAttributes['locale'] ?? null);
            } elseif ($attributeCode == 'region' && isset($addressAttributes['region'])) {
                $data['region'] = (string)__($addressAttributes['region']);
            } elseif (isset($addressAttributes[$attributeCode])) {
                $value = $addressAttributes[$attributeCode];
                $dataModel = $this->_elementFactory->create($attributeMetadata, $value, 'customer_address');
                $value = $dataModel->outputValue($dataFormat);
                if ($attributeMetadata->getFrontendInput() == 'multiline') {
                    $values = $dataModel->outputValue(ElementFactory::OUTPUT_FORMAT_ARRAY);
                    // explode lines
                    foreach ($values as $k => $v) {
                        $key = sprintf('%s%d', $attributeCode, $k + 1);
                        $data[$key] = $v;
                    }
                }

                $data[$attributeCode] = $value;
            }
        }
        if ($subject->getType()->getEscapeHtml()) {
            foreach ($data as $key => $value) {
                $data[$key] =  $this->escaper->escapeHtml($value);
            }
        }
        $format = $format !== null ? $format : $subject->getFormatArray($addressAttributes);
        if (!empty($data) && $data['firstname'] == $data['lastname'])
        {
            unset($data['lastname']);
        }
        return $this->filterManager->template($format, ['variables' => $data]);
    }
}