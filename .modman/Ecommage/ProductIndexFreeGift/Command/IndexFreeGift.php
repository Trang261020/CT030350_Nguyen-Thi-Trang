<?php

namespace Ecommage\ProductIndexFreeGift\Command;

use Ecommage\ProductIndexFreeGift\Helper\Data;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexFreeGift extends Command
{
    /**
     * @var \Ecommage\ProductIndexFreeGift\Helper\Data
     */
    protected $helper;

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName("product:free-gift:index");
        $this->setDescription("A command the programmer was too lazy to enter a description for.");
        parent::configure();
    }

    /**
     * IndexFreeGift constructor.
     *
     * @param Data        $helper
     * @param string|null $name
     */
    public function __construct(
        Data $helper,
        string $name = null
    ) {
        $this->helper = $helper;
        parent::__construct($name);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->helper->setOutput($output)
            ->reIndexProductFreeGift();
        $output->writeln("Rebuild product free gift done.");
    }
}
