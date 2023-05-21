<?php

namespace Ecommage\FixValidateCheckout\Plugin\Model;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class ShippingInformationManagement
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * ShippingInformationManagement constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $shippingInformationManagement
     * @param                                                       $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $shippingInformationManagement,
                                                              $cartId,
        ShippingInformationInterface                          $addressInformation
    )
    {
        /** @var Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $addressInformation->getShippingAddress()->getExtensionAttributes()
            ->setWard($quote->getShippingAddress()->getWard())
            ->setWardId($quote->getShippingAddress()->getWardId());

        $addressInformation->getShippingAddress()
            ->setRegion($quote->getShippingAddress()->getRegion())
            ->setRegionId($quote->getShippingAddress()->getRegionId());
    }
}
