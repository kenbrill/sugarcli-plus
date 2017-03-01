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
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace SugarCli\Console\Command\Database;

use SugarCli\Console\Command\AbstractConfigOptionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CopyTableCommand extends AbstractConfigOptionCommand
{

    protected function configure()
    {
        $this->setName('database:copyTable')
            ->setDescription('Copy the structure and data of an exiting table to a new table')
            ->enableStandardOption('path')
            ->addOption(
                'from',
                null,
                InputOption::VALUE_REQUIRED,
                'Existinng table (repeat for multiple values)'
            )->addOption(
                'to',
                null,
                InputOption::VALUE_REQUIRED,
                'New table (repeat for multiple values)'
            )->addOption(
                'delete',
                null,
                InputOption::VALUE_NONE,
                "Delete the destination table if it exists"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pdo = $this->getService('sugarcrm.pdo');
        $this->getService('sugarcrm.entrypoint'); // go to sugar folder to make sure we are in the right folder

        if ($input->getOption('from') === false || $input->getOption('to') === false) {
            $msg = 'You need to set a --FROM and --TO table';
            throw new \InvalidArgumentException($msg);
        }

        $from = $input->getOption('from');
        $to = $input->getOption('to');
        $delete = $input->getOption('delete');

        if ($this->tableExists($pdo, $from) === false) {
            $msg = "{$from} does not exist";
            throw new \InvalidArgumentException($msg);
        }

        if ($this->tableExists($pdo, $to) === true) {
            if(!$delete) {
                $msg = "{$to} already exists";
                throw new \InvalidArgumentException($msg);
            } else {
                $output->writeln("<info>Dropping {$to}...</info>");
                $pdo->query("DROP TABLE {$to}");
            }
        }

        $output->writeln("<info>Creating {$to}...</info>");
        $output->writeln("<info>Copying data...</info>");
        $pdo->query("CREATE TABLE {$to} SELECT * FROM {$from}");
        $output->writeln("<info>Finished...</info>");

    }

    /**
     * Check if the table exists
     * @param  PDO    $pdo
     * @param  string $table
     * @return bool
     */
    protected function tableExists(\PDO $pdo, $table)
    {
        $db = $this->getDb($pdo);
        $data = $pdo->query($sql = "SHOW TABLES WHERE `tables_in_{$db}` LIKE '{$table}'");
        if ($data === false) {
            throw new \PDOException("Can't run the query to get the table: " . PHP_EOL . $sql);
        }

        return $data->rowCount() === 1 ? true : false;
    }

    /**
     * Get the current DB Name
     *
     * @param \PDO $pdo
     *
     * @return string
     */
    protected function getDb(\PDO $pdo)
    {
        return $pdo->query('SELECT DATABASE()')->fetchColumn();
    }
}
