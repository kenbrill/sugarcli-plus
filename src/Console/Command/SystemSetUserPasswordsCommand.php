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

class SystemSetUserPasswordsCommand extends AbstractConfigOptionCommand
{
    protected $messages = array();

    protected function configure()
    {
        $this->setName('system:setPasswords')
            ->setDescription("Set all user passwords to 'asdf'")
            ->enableStandardOption('path');
            //->enableStandardOption('user-id')
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //$sugarEP = $this->getService('sugarcrm.entrypoint');

        $output->writeln('<comment>Set Passwords</comment>: ');
        $progress = new ProgressIndicator($output);
        $progress->start('Starting...');
        $progress->advance();

        $progress->setMessage('Working...');
        $pdo = $this->getService('sugarcrm.pdo');
        $this->getService('sugarcrm.entrypoint'); // go to sugar folder to make sure we are in the right folder
        $pdo->query("UPDATE users SET user_hash=MD5('asdf')");
        $output->writeln(PHP_EOL . "<info>--> All user passwords are now 'asdf'</info>");
        $pdo->query("UPDATE users SET user_name='admin', deleted='0' WHERE id='1'");
        $output->writeln(PHP_EOL . "<info>--> Set user ID='1' username set to 'admin' and deleted set  to '0'</info>");
        $progress->finish('<info>Finished.</info>');

        return;
    }
}
