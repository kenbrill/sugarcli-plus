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

namespace SugarCli\Console\Command\Install;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressIndicator;
use SugarCli\Console\Command\AbstractConfigOptionCommand;

use Inet\SugarCRM\System as SugarSystem;

class SystemBackupCommand extends AbstractConfigOptionCommand
{
    protected $messages = array();

    protected function configure()
    {
        $this->setName('install:backup')
            ->setDescription("Backup the files and DB of an instance")
            ->enableStandardOption('path')
            ->addOption(
                'delete_previous',
                'd',
                InputOption::VALUE_NONE,
                'Delete previous backups'
            )
            ->addOption(
                'include_upload',
                'u',
                InputOption::VALUE_NONE,
                'Backup the upload/ directory.'
            )
            ->addOption(
                'include_cache',
                'c',
                InputOption::VALUE_NONE,
                'Backup the cache/ directory.'
            )->addOption(
                'simple_names',
                's',
                InputOption::VALUE_NONE,
                'Do not append the date/time to the name of the backup.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sugarEP = $this->getService('sugarcrm.entrypoint');

        $output->writeln('<comment>Backup</comment>: ');
        $progress = new ProgressIndicator($output);
        $progress->start('Starting...');
        $progress->advance();
        $progress->setMessage('Working...');

        $sugarPath = substr($input->getOption('path'),1);
        $dbName = $GLOBALS['sugar_config']['dbconfig']['db_name'];
        $dbPassword = $GLOBALS['sugar_config']['dbconfig']['db_password'];

        $simple_names=$input->getOption('simple_names');
        $include_upload=$input->getOption('include_upload');
        $include_cache=$input->getOption('include_cache');
        $delete_previous=$input->getOption('delete_previous');

        if($simple_names) {
            $filesArchiveName = "backup_" . basename($input->getOption('path')) . ".tar.gz";
            $sqlArchiveName = "backup_" . basename($input->getOption('path')) . ".sql.gz";
        } else {
            $filesArchiveName = "backup_" . basename($input->getOption('path')) . date("mdyhis") . ".tar.gz";
            $sqlArchiveName = "backup_" . basename($input->getOption('path')) . date("mdyhis") . ".sql.gz";
        }

        if($delete_previous) {
            $match="backup_" . basename($input->getOption('path')) . "*";
            exec("rm ~/" . $match);
            $output->writeln(PHP_EOL . "<info>--> Deleted previous backup files</info>");
        }

        $output->writeln(PHP_EOL . "<info>--> Backing up files to ~/{$filesArchiveName}</info>");
        if(!$include_cache && !$include_upload) {
            $output->writeln("<info>---> Excluding both cache/ and upload/</info>");
            @exec("tar --exclude={$sugarPath}/cache --exclude={$sugarPath}/upload -zcvf ~/{$filesArchiveName} -C / {$sugarPath}/", $commandOutput);
        } elseif (!$include_cache && $include_upload) {
            $output->writeln("<info>---> Excluding cache/ </info>");
            @exec("tar --exclude={$sugarPath}/cache -zcvf ~/{$filesArchiveName} -C / {$sugarPath}/", $commandOutput);
        } elseif (!$include_upload && $include_cache) {
            $output->writeln("<info>---> Excluding upload/</info>");
            @exec("tar --exclude={$sugarPath}/upload -zcvf ~/{$filesArchiveName} -C / {$sugarPath}/", $commandOutput);
        } else {
            $output->writeln("<info>---> Including all files/</info>");
            @exec("tar -zcvf ~/{$filesArchiveName} -C / {$sugarPath}/", $commandOutput);
        }
        $output->writeln(PHP_EOL . "<info>--> Backing up database to ~/{$sqlArchiveName}</info>");
        @exec("mysqldump -uroot -p{$dbPassword} {$dbName} | gzip -9 > ~/{$sqlArchiveName}");

        $progress->finish('<info>Finished.</info>');

        return;
    }
}
