<?php

namespace Ecommage\CatalogWidget\Helper;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Helper\Context;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\State;
use Magento\Framework\Registry;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class PrepareContent
 *
 * @package Ecommage\CatalogWidget\Helper
 */
class PrepareData extends AbstractHelper
{
    const CONFIG_PREPARE_CONTENT   = 'config_prepare_content';
    const CONFIG_PREPARE_TEMPLATES = 'template_static_blocks';
    /**
     * @var Emulation
     */
    protected $emulation;
    /**
     * @var State
     */
    protected $appState;
    /**
     * @var FilterProvider
     */
    protected $filterProvider;
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;
    /**
     * @var PageFactory
     */
    private $pageFactory;
    /**
     * @var null
     */
    protected $output = null;
    /**
     * @var bool
     */
    private $isPreview = false;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;
    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * PrepareData constructor.
     *
     * @param ThemeProviderInterface $themeProvider
     * @param StoreManagerInterface  $storeManager
     * @param FilterProvider         $filterProvider
     * @param PageFactory            $pageFactory
     * @param Registry               $coreRegistry
     * @param Emulation              $emulation
     * @param DesignInterface        $design
     * @param State                  $appState
     * @param Context                $context
     */
    public function __construct(
        ThemeProviderInterface $themeProvider,
        StoreManagerInterface $storeManager,
        FilterProvider $filterProvider,
        PageFactory $pageFactory,
        Registry $coreRegistry,
        Emulation $emulation,
        DesignInterface $design,
        State $appState,
        Context $context
    ) {
        $this->design         = $design;
        $this->appState       = $appState;
        $this->emulation      = $emulation;
        $this->pageFactory    = $pageFactory;
        $this->coreRegistry   = $coreRegistry;
        $this->themeProvider  = $themeProvider;
        $this->filterProvider = $filterProvider;
        $this->storeManager   = $storeManager;
        parent::__construct($context);
    }

    /**
     * Retrieve the area in which the preview needs to be ran in
     *
     * @return string
     */
    public function getPreviewArea(): string
    {
        return Area::AREA_FRONTEND;
    }

    /**
     * Returns store id by default store view or store id from the available store if default store view is null
     *
     * @return int|null
     */
    private function getStoreId(): ?int
    {
        $storeId = null;
        $store   = $this->storeManager->getDefaultStoreView();
        if ($store) {
            $storeId = (int)$store->getId();
        } else {
            $stores = $this->storeManager->getStores();
            if (count($stores)) {
                $store   = array_shift($stores);
                $storeId = (int)$store->getId();
            }
        }

        return $storeId;
    }

    /**
     * Start Page Builder preview mode and emulate store front
     *
     * @param callable $callback
     * @param int      $storeId
     *
     * @return mixed
     * @throws Exception
     */
    public function startPreviewMode($callback, $storeId = null)
    {
        $this->isPreview = true;
        if (!$storeId) {
            $storeId = $this->getStoreId();
        }
        $this->emulation->startEnvironmentEmulation($storeId);

        return $this->appState->emulateAreaCode(
            Area::AREA_FRONTEND,
            function () use ($callback) {
                $themeId = $this->scopeConfig->getValue(
                    'design/theme/theme_id',
                    ScopeInterface::SCOPE_STORE
                );
                $theme   = $this->themeProvider->getThemeById($themeId);
                $this->design->setDesignTheme($theme, $this->getPreviewArea());

                try {
                    $result = $callback();
                } catch (Exception $e) {
                    $this->isPreview = false;
                    throw $e;
                }

                $this->emulation->stopEnvironmentEmulation();
                return $result;
            }
        );
    }

    /**
     * @throws Exception
     */
    public function prepareContent()
    {
        $this->startPreviewMode([$this, 'processContent']);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function processContent()
    {
        $this->coreRegistry->register(self::CONFIG_PREPARE_CONTENT, true);
        $homePageIdentifier = $this->scopeConfig->getValue(
            'web/default/cms_home_page',
            ScopeInterface::SCOPE_STORE
        );

        $page = $this->pageFactory->create();
        $page->load($homePageIdentifier);
        $html      = $this->filterProvider->getPageFilter()->filter($page->getContent());
        $templates = $this->coreRegistry->registry(self::CONFIG_PREPARE_TEMPLATES);
        $this->showMessage($templates);
        return $templates;
    }

    /**
     * @param null $output
     *
     * @return $this
     */
    public function setOutput($output = null)
    {
        if (!$output) {
            $output = $this->_logger;
        }

        $this->output = $output;
        return $this;
    }

    /**
     * @param $msg
     *
     * @return $this
     */
    public function showMessage($msg)
    {
        if (is_array($msg)) {
            $msg = implode(', ', $msg);
        }


        if ($this->output instanceof OutputInterface) {
            $this->output->writeln($msg);
        }

        if ($this->output instanceof LoggerInterface) {
            $this->output->debug($msg);
        }

        return $this;
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public function getAllFileHtml(string $dir): array
    {
        $files = [];
        foreach ((new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS))) as $file) {
            /** @var \SplFileInfo $file */
            if ($file->getExtension() === 'html') {
                $files[] = substr($file->getPathname(), strpos($file->getPathname(), 'pub/static/app/code/'));
            }
        }

        return $files;
    }
}
