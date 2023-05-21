<?php

namespace Ecommage\FixPriceCore\Plugin\Model;

/**
 * Class Currency
 */
class Currency
{
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;
    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * Currency constructor.
     *
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Locale\FormatInterface   $localeFormat
     */
    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat
    ) {
        $this->_localeFormat   = $localeFormat;
        $this->_localeCurrency = $localeCurrency;
    }

    /**
     * @param          $subject
     * @param callable $proceed
     * @param          $price
     * @param array    $options
     *
     * @return string
     * @throws \Zend_Currency_Exception
     */
    public function aroundFormatTxt($subject, callable $proceed, $price, $options = [])
    {
        if (!is_numeric($price)) {
            $price = $this->_localeFormat->getNumber($price);
        }
        /**
         * Fix problem with 12 000 000, 1 200 000
         *
         * %f - the argument is treated as a float, and presented as a floating-point number (locale aware).
         * %F - the argument is treated as a float, and presented as a floating-point number (non-locale aware).
         */
        $price = sprintf("%F", $price);
        return $this->_localeCurrency->getCurrency($subject->getCode())->toCurrency($price, $options);
    }
}
