<?php

namespace Ecommage\CustomTheme\Model\ResourceModel\Method;

class Name extends \Amasty\Sorting\Model\ResourceModel\Method\AbstractMethod
{
    /**
     * {@inheritdoc}
     */
    public function apply($collection, $direction)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAlias()
    {
        return 'name';
    }

    /**
     * @inheritdoc
     */
    public function getIndexedValues(int $storeId, ?array $entityIds = [])
    {
        return [];
    }
}
