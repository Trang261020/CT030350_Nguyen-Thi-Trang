<?php

namespace Ecommage\ShopeePay\Model;

use Ecommage\ShopeePay\Model\Payment\ShopeePay;
use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Configuration provider for GiftMessage rendering on "Shipping Method" step of checkout.
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ShopeePay
     */
    protected $shopeePay;

    /**
     * ConfigProvider constructor.
     *
     * @param ShopeePay $shopeePay
     */
    public function __construct(
        ShopeePay $shopeePay
    ) {
        $this->shopeePay = $shopeePay;
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'plat_form' => $this->shopeePay->getPlatform()
        ];
    }

}
