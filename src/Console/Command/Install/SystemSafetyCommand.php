<?php
/**
 * SugarCLI
 *
 * PHP Version 5.3 -> 5.4
 * SugarCRM Versions 6.5 - 7.9
 *
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

namespace SugarCli\Console\Command\Install;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;
use SugarCli\Console\Command\AbstractConfigOptionCommand;

use Inet\SugarCRM\System as SugarSystem;

class SystemSafetyCommand extends AbstractConfigOptionCommand
{
    protected $messages = array();

    protected function configure()
    {
        $this->setName('install:safety')
            ->setDescription("Make an instance safe to use...")
            ->enableStandardOption('path')
            ->addOption(
                'email',
                'e',
                InputOption::VALUE_REQUIRED,
                'New Email address',
                'plus@email.com'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>Safety Update</comment>: ');
        $progress = new ProgressIndicator($output);
        $progress->start('Starting...');
        $progress->advance();
        $progress->setMessage('Working...');

        $email_address = $input->getOption('email');
        $email_address_caps = strtoupper($input->getOption('email'));
        $pdo = $this->getService('sugarcrm.pdo');
        $this->getService('sugarcrm.entrypoint'); // go to sugar folder to make sure we are in the right folder

        $pdo->query("UPDATE email_addresses SET email_address='{$email_address}', email_address_caps='{$email_address_caps}'");
        $output->writeln(PHP_EOL . "<info>--> All email addresses are now set to '{$email_address}'</info>");

        $pdo->query("TRUNCATE TABLE job_queue");
        $output->writeln(PHP_EOL . "<info>--> Truncated job_queue...</info>");

        $progress->finish('<info>Finished.</info>');

        return;
    }
}
