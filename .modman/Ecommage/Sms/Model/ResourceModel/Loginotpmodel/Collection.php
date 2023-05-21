<?php
namespace Ecommage\Sms\Model\ResourceModel\Loginotpmodel;

/**
 * Class Collection
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Ecommage\Sms\Model\Loginotpmodel', 'Ecommage\Sms\Model\ResourceModel\Loginotpmodel');
    }


}
