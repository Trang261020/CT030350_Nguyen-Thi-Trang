<?php
//ini_set('display_errors', 1);
// @codingStandardsIgnoreFile
use Magento\Framework\App\Bootstrap;
use Magento\Framework\DB\Adapter\AdapterInterface;
require __DIR__ . '/../../app/bootstrap.php';
//@SuppressWarnings(PHPMD.CyclomaticComplexity)
$bootstrap = Bootstrap::create(BP, $_SERVER); // @codingStandardsIgnoreLine
$obj   = $bootstrap->getObjectManager();
$state = $obj->get(Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');
$resource = $obj->get('Magento\Framework\App\ResourceConnection');
/** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
$connection = $resource->getConnection();
$tableConnect = $connection->getTableName('catalog_product_entity_varchar');
$connection->update($tableConnect,['value'=>'1column'], 'attribute_id = 104');
