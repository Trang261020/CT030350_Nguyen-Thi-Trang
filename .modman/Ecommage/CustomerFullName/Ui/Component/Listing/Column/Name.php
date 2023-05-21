<?php

namespace Ecommage\CustomerFullName\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Name extends Column
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    public function __construct
    (
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as $key => & $item) {
               $customer = $this->customerRepository->getById($item['entity_id']);
               if ($customer->getLastname() == $customer->getFirstname())
               {
                   $item[$this->getData('name')] = $customer->getLastname();
               }
            }
        }

        return $dataSource;
    }

}