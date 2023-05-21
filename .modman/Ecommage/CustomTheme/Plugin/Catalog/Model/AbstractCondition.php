<?php

namespace Ecommage\CustomTheme\Plugin\Catalog\Model;

class AbstractCondition
{
    public function beforeValidateAttribute(\Magento\Rule\Model\Condition\AbstractCondition $subject , $validatedValue)
    {
        if (empty($validatedValue))
        {
            $validatedValue = '';
        }

        return [$validatedValue];
    }
}