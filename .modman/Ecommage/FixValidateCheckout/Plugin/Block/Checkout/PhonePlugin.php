<?php
/**
 * Created By : Rohan Hapani
 */
namespace Ecommage\FixValidateCheckout\Plugin\Block\Checkout;
use Magento\Checkout\Block\Checkout\AttributeMerger;
class PhonePlugin
{
    /**
     * @param AttributeMerger $subject
     * @param $result
     * @return mixed
     */
    public function afterMerge(AttributeMerger $subject, $result)
    {
        $result['telephone']['validation'] = [
            'required-entry'  => true,
            'max_text_length' => 10,
            'min_text_length' => 10,
            'validate-number' => true
        ];
        return $result;
    }
}
