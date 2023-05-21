<?php

namespace Ecommage\CatalogWidget\Controller\Adminhtml\Index;

use Ecommage\CatalogWidget\Helper\PrepareData;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\Shell;
use Symfony\Component\Process\PhpExecutableFinder;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Ecommage_CatalogWidget::rebuild_block';
    /**
     * @var PrepareData
     */
    protected $helper;
    /**
     * @var Shell
     */
    private $shell;
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;
    /**
     * @var PhpExecutableFinder
     */
    private $phpExecutableFinder;

    /**
     * Index constructor.
     *
     * @param PhpExecutableFinder $phpExecutableFinder
     * @param Registry            $coreRegistry
     * @param PrepareData         $helper
     * @param Context             $context
     * @param Shell               $shell
     */
    public function __construct(
        PhpExecutableFinder $phpExecutableFinder,
        Registry $coreRegistry,
        PrepareData $helper,
        Context $context,
        Shell $shell
    ) {
        $this->shell               = $shell;
        $this->helper              = $helper;
        $this->coreRegistry        = $coreRegistry;
        $this->phpExecutableFinder = $phpExecutableFinder;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->helper->setOutput()->prepareContent();
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                                                   ->getDirectoryWrite(DirectoryList::TEMPLATE_MINIFICATION_DIR);
            $mediaRootDir = $mediaDirectory->getAbsolutePath('html/');
            $templates = $this->helper->getAllFileHtml($mediaRootDir);
            $mediaDirectory->delete('html/');
            //$this->deleteFolderHtml();
            foreach ($templates as $template) {
                $mediaDirectory->delete($template);
            }

            $phpPath   = $this->phpExecutableFinder->find() ?: 'php';
            $output    = $this->shell->execute($phpPath . ' %s content:prepare:widget', [BP . '/bin/magento']);
            $templates = $this->coreRegistry->registry(PrepareData::CONFIG_PREPARE_TEMPLATES);
            $this->shell->execute($phpPath . ' %s c:f', [BP . '/bin/magento']);
            $result = [
                'status'    => 'OK',
                'templates' => $templates,
                'output'    => $output,
                'message'   => __('Rebuild blocks successful')
            ];
        } catch (Exception $exception) {
            $result = [
                'status'  => 'FAILED',
                'message' => $exception->getMessage()
            ];
        }
        $resultJson->setData($result);
        return $resultJson;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function deleteFolderHtml()
    {
        try {
            $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
                                                   ->getDirectoryWrite(DirectoryList::TEMPLATE_MINIFICATION_DIR);
            $mediaDirectory->delete('html/');
        } catch (\Exception $exception) {

        }
    }

}
