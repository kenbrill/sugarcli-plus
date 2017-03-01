<?php
/**
 * SugarCLI
 *
 * PHP Version 5.3 -> 5.4
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @author Emmanuel Dyan
 * @author Kenneth Brill
 *
 * @copyright 2005-2016 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace SugarCli\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;

use Inet\SugarCRM\System as SugarSystem;

class SystemClearCacheCommand extends AbstractConfigOptionCommand
{
    protected $messages = array();

    protected function configure()
    {
        $this->setName('system:clearCache')
            ->setDescription('Clears SugarCRM Cache directory and metadata_cache table.')
            ->enableStandardOption('path');
            //->enableStandardOption('user-id')
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$sugarEP = $this->getService('sugarcrm.entrypoint');

        $output->writeln('<comment>Clear Cache</comment>: ');
        $progress = new ProgressIndicator($output);
        $progress->start('Starting...');
        $progress->advance();

        $progress->setMessage('Working...');
        exec("rm -Rfv " . $input->getOption('path') . "/cache/*", $commandOutput);

        //$sugarSystem = new SugarSystem($sugarEP);
        $pdo = $this->getService('sugarcrm.pdo');
        $this->getService('sugarcrm.entrypoint'); // go to sugar folder to make sure we are in the right folder
        $pdo->query("TRUNCATE TABLE `metadata_cache`");
        $output->writeln(PHP_EOL . "<info>--> Removed everything from 'metadata_cache'</info>");
        $progress->finish('<info>Cache Cleared.</info>');

        if ($output->isVerbose()) {
            $output->writeln(PHP_EOL . '<comment>General Messages</comment>: ');
            $output->writeln(implode(PHP_EOL, $commandOutput));
        }

        return;
    }
}
