<?php

namespace Ecommage\AddCmsPayments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Configuration provider for GiftMessage rendering on "Shipping Method" step of checkout.
 */
class ConfigProvider implements ConfigProviderInterface
{
    const XML_PATH_PAYMENT = 'payment';
    /**
     * @var LayoutInterface
     */
    protected $layout;
    /**
     * @var StoreInterface
     */
    protected $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var PaymentMethodListInterface
     */
    protected $paymentMethodList;

    /**
     * ConfigProvider constructor.
     *
     * @param PaymentMethodListInterface $paymentMethodList
     * @param StoreManagerInterface      $storeManager
     * @param ScopeConfigInterface       $scopeConfig
     * @param LayoutInterface            $layout
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        PaymentMethodListInterface $paymentMethodList,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LayoutInterface $layout
    ) {
        $this->layout            = $layout;
        $this->scopeConfig       = $scopeConfig;
        $this->storeManager      = $storeManager;
        $this->paymentMethodList = $paymentMethodList;
    }

    /**
     * @param $xmlPathConfig
     *
     * @return mixed
     */
    protected function getConfigValue($xmlPathConfig)
    {
        return $this->scopeConfig->getValue(
            $xmlPathConfig,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $blockId
     *
     * @return string
     */
    protected function getCmsContent($blockId)
    {
        if (empty($blockId)) {
            return '';
        }

        return $this->layout->createBlock('Magento\Cms\Block\Block')->setBlockId($blockId)->toHtml();
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        return [
            'cms_payments' => $this->getCmsPayments()
        ];
    }

    /**
     * @return array
     */
    public function getCmsPayments()
    {
        $cmsPayments = [];
        $storeId     = $this->storeManager->getStore()->getId();
        $payments    = $this->paymentMethodList->getActiveList($storeId);
        if (is_array($payments) && !empty($payments)) {
            /** @var \Magento\Payment\Api\Data\PaymentMethodInterface $payment */
            foreach ($payments as $payment) {
                $code               = $payment->getCode();
                $blockId            = $this->getBlockId($code);
                $cmsPayments[$code] = $this->getCmsContent($blockId);
            }
        }

        return $cmsPayments;
    }

    /**
     * @param $methodCode
     *
     * @return mixed
     */
    protected function getBlockId($methodCode)
    {
        $xmlPathConfig = $this->getPathCmsBlock($methodCode);
        return $this->getConfigValue($xmlPathConfig);
    }

    /**
     * @param $methodCode
     *
     * @return string
     */
    private function getPathCmsBlock($methodCode)
    {
        return sprintf('payment/%s/block_id', $methodCode);
    }
}
