<?php

namespace Ecommage\CatalogWidget\Command;

use Ecommage\CatalogWidget\Helper\PrepareData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Cache\Manager;

class PrepareContent extends Command
{
    /**
     * @var PrepareContent
     */
    protected $helper;
    /**
     * @var Manager
     */
    protected $cacheManager;

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName("content:prepare:widget");
        $this->setDescription("A command the programmer was too lazy to enter a description for.");
        parent::configure();
    }

    /**
     * PrepareContent constructor.
     *
     * @param Manager     $cacheManager
     * @param PrepareData $helper
     * @param string|null $name
     */
    public function __construct(
        Manager $cacheManager,
        PrepareData $helper,
        string $name = null
    ) {
        $this->helper            = $helper;
        $this->cacheManager     = $cacheManager;
        parent::__construct($name);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->helper->setOutput($output)
                     ->prepareContent();
        $this->cleanCache();
        $output->writeln("Rebuild content data done.");
    }

    /**
     * @return $this
     */
    public function cleanCache()
    {
        $types = $this->cacheManager->getAvailableTypes();
        $this->cacheManager->clean($types);;
        return $this;
    }
}
