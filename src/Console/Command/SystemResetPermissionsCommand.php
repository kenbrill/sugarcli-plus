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

//use Inet\SugarCRM\System as SugarSystem;

class SystemResetPermissionsCommand extends AbstractConfigOptionCommand
{
    protected $messages = array();

    protected function configure()
    {
        $this->setName('system:resetPerms')
            ->setDescription('Reset all file permissions.')
            ->enableStandardOption('path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sugarEP = $this->getService('sugarcrm.entrypoint');

        $output->writeln('<comment>Set Permissions</comment>: ');
        $progress = new ProgressIndicator($output);
        $progress->start('Starting...');
        $progress->advance();
        //$sugarSystem = new SugarSystem($sugarEP);
        $progress->setMessage('Working...');

        exec("sudo chmod -Rf 777 " . basename($input->getOption('path')), $commandOutput);
        exec("sudo chown -Rf apache:apache " . basename($input->getOption('path')), $commandOutput);

        $changed = 0;
        $retained = 0;
        foreach ($commandOutput as $line) {
            if (stristr($line, 'changed')) {
                $changed++;
            } else {
                $retained++;
            }
        }

        $progress->finish('<info>Permissions set.</info>');

        if ($output->isVerbose()) {
            $output->writeln(PHP_EOL . '<comment>General Messages</comment>: ');
            $output->writeln(implode(PHP_EOL, $commandOutput));
        }

        return;
    }
}
