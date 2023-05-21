<?php

namespace Ecommage\CustomerFullName\Plugin\Customer\Block\Widget;

class Name
{
    /**
     * @param $subject
     * @param $result
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     */
    public function afterGetTemplate($subject, $result)
    {
        return 'Ecommage_CustomerFullName::widget/name.phtml';
    }
}
