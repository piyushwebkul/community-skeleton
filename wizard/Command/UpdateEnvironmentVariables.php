<?php

namespace Webkul\UVDesk\Wizard\Command;

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateEnvironmentVariables extends Command
{
    private $path;
    private $conf;
    private $envvars;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('uvdesk-wizard:update:envvars');
        $this->setDescription('Makes changes to .env located in project root to update environment variables.');

        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, "Name of the environment variable")
            ->addOption('value', null, InputOption::VALUE_REQUIRED, "Value to set for the evironment variable");
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->path = $this->container->get('kernel')->getProjectDir() . '/.env';
        
        $this->conf = file_get_contents($this->path);
        $this->envvars = (new Dotenv())->parse($this->conf);
        $this->envvars[strtoupper($input->getOption('name'))] = $input->getOption('value');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ('prod' == $this->container->get('kernel')->getEnvironment()) {
            $output->writeln("\nThis command is disabled to work on production mode.");

            exit(0);
        }

        $stream = array_map(function($line) {
            if (trim($line) && trim($line)[0] != '#' && strpos(trim($line), '=') > 0) {
                try {
                    list($var, $value) = explode("=", trim($line));
    
                    if (isset($this->envvars[strtoupper($var)])) {
                        return strtoupper($var) . "=" . $this->envvars[strtoupper($var)];
                    }
                } catch (\Exception $e) {
                    // Do nothing
                }
            }

            return $line;
        }, file($this->path, FILE_IGNORE_NEW_LINES));

        $stream = implode("\n", $stream) . "\n";

        // Proceed only if there are changes in configuration file
        if (trim($this->conf) == trim($stream)) {
            $output->writeln("\nNothing to update.");

            return;
        }

        file_put_contents($this->path, $stream);

        try {
            $this->getApplication()->find('cache:clear')->run(new ArrayInput([]), new NullOutput());
        } catch (\Exception $e) {
            $output->writeln([
                "\n<comment>Warning: </comment>",
                "\n<comment>Failed to clear cache. Please clear your cache to reflect updates to your .env file.\n"
            ]);

            return;
        }
    }
}