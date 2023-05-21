<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ecommage\ShowBrandCategory\Model\Entity\Attribute\Backend;

/**
 * Backend model for attribute with multiple values
 *
 * @api
 * @since 100.0.2
 */
class ArrayBackend extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    /**
     * @param $object
     *
     * @return ArrayBackend
     */
    public function beforeSave($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $data = $object->getData($attributeCode);
        if (is_array($data)) {
            $data = array_filter($data, function ($value) {
                return $value === '0' || !empty($value);
            });
            $object->setData($attributeCode, implode(',', $data));
        }

        return parent::beforeSave($object);
    }

    /**
     * @param $object
     *
     * @return ArrayBackend
     */
    public function afterLoad($object)
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        $data = $object->getData($attributeCode);
        if ($data) {
            $object->setData($attributeCode, explode(',', $data));
        }
        return parent::afterLoad($object);
    }

    /**
     * Implode data for validation
     *
     * @param \Magento\Catalog\Model\Product $object
     * @return bool
     */
    public function validate($object)
    {
        return true;
    }
}
