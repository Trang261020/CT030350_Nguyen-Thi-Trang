<?php

namespace Ecommage\CustomerFullName\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class MobilePhone extends Column
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct
    (
        LoggerInterface                                   $logger,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        ContextInterface                                  $context,
        UiComponentFactory                                $uiComponentFactory,
        array                                             $components = [],
        array                                             $data = []
    )
    {
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as $key => &$item) {
                try {
                    $customer = $this->customerRepository->getById($item['entity_id']);
                    $attribute = $customer->getCustomAttribute('mobile_number');
                    if (!empty($attribute)) {
                        $item[$this->getData('name')] = $customer->getCustomAttribute('mobile_number')->getValue();
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        }

        return $dataSource;
    }
}
