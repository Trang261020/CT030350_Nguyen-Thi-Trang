<?php
declare(strict_types=1);

namespace Ecommage\CustomCatalogPriceRules\Model\Config;

class Options extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * @var null
     */
    protected $_options = null;

    /**
     * @return array[]
     */
    public function getAllOptions()
    {
        if (!$this->_options)
        {
            $this->_options = [
                ['label' => __('0% - 10%'), 'value' =>0],
                ['label' => __('10% - 20%'), 'value' =>10],
                ['label' => __('20% - 30%'), 'value' =>20],
                ['label' => __('30% - 40%'), 'value' =>30],
                ['label' => __('40% - 50%'), 'value' =>40],
                ['label' => __('50% - 60%'), 'value' =>50],
                ['label' => __('60% - 70%'), 'value' =>60],
                ['label' => __('70% - 80%'), 'value' =>70],
                ['label' => __('80% - 90%'), 'value' =>80],
                ['label' => __('90% - 100%'), 'value' =>90]
            ];
        }
        return $this->_options;
    }

    /**
     * @param $value
     *
     * @return false|mixed
     */
    public function getOptionText($value)

    {
        foreach ($this->getAllOptions() as $option) {

            if ($option['value'] == $value) {

                return $option['label'];

            }

        }

        return false;

    }

}
