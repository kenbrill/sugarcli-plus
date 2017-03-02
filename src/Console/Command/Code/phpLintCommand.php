<?php
/**
 * SugarCLI
 *
 * PHP Version 5.3 -> 5.4
 * SugarCRM Versions 6.5 - 7.9
 *
 * @author Kenneth Brill
 * @copyright 2005-2017 Plus Consulting
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace SugarCli\Console\Command\Code;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use SugarCli\Console\Command\AbstractConfigOptionCommand;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Check all PHP files with php -l
 */
class phpLintCommand extends AbstractConfigOptionCommand
{
    private $path;

    protected function configure()
    {
        $this->setName('code:phpLint')
            ->setDescription('Check PHP code with php -l.')
            ->enableStandardOption('path')
            ->addOption(
                'custom',
                'c',
                InputOption::VALUE_NONE,
                'only process custom/ directory'
            )->addOption(
                'modules',
                'm',
                InputOption::VALUE_NONE,
                'only process modules/ directory'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->path = $input->getOption('path');
        $custom = $input->getOption('custom');
        $modules = $input->getOption('modules');

        if (!$custom && !$modules) {
            $output->writeln("Begin full scan");
            $this->fullScan($output);
        } else {
            $output->writeln("Begin scan of config files");
            $errors = $this->configScan();
            if ($custom) {
                $output->writeln("Begin scan of custom/ directory");
                $errors = $this->customScan($output, $errors);
            }
            if ($modules) {
                $output->writeln(PHP_EOL . "Begin scan of modules/ directory");
                $errors = $this->modulesScan($output, $errors);
            }
            if (!empty($errors)) {
                var_export($errors);
            } else {
                $output->writeln(PHP_EOL . "<info>No errors found</info>");
            }
        }
    }

    protected function configScan()
    {
        $errors = array();
        exec("php -l " . $this->path . DIRECTORY_SEPARATOR . 'config.php', $commandOutput);
        if (substr($commandOutput[0], 0, 25) != 'No syntax errors detected') {
            $errors[] = array('file' => $this->path . DIRECTORY_SEPARATOR . 'config.php', 'error' => $commandOutput[0]);
        }
        exec("php -l " . $this->path . DIRECTORY_SEPARATOR . 'config_override.php', $commandOutput);
        if (substr($commandOutput[0], 0, 25) != 'No syntax errors detected') {
            $errors[] = array('file' => $this->path . DIRECTORY_SEPARATOR . 'config.php', 'error' => $commandOutput[0]);
        }
        return $errors;
    }

    protected function fullScan(OutputInterface $output)
    {
        $Directory = new \RecursiveDirectoryIterator($this->path);
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $errors = array();
        $total = count(iterator_to_array($Regex));
        //http://symfony.com/doc/current/components/console/helpers/progressbar.html
        $progress = new ProgressBar($output, $total);
        $progress->setRedrawFrequency(100);
        $progress->setBarWidth(100);
        $progress->setFormat('very_verbose');
        foreach ($Regex as $index => $fileName) {
            $phpFileName = $fileName[0];
            //reset the $commandOutput array so we get back a single result each time
            $commandOutput = array();
            exec("php -l " . $phpFileName, $commandOutput);
            if (substr($commandOutput[0], 0, 25) != 'No syntax errors detected') {
                $errors[] = array('file' => $phpFileName, 'error' => $commandOutput[0]);
            }
            $progress->advance();
        }
        $progress->finish();
        if (!empty($errors)) {
            var_export($errors);
        } else {
            $output->writeln(PHP_EOL . "<info>No errors found</info>");
        }
    }

    protected function customScan(OutputInterface $output, $errors)
    {
        $Directory = new \RecursiveDirectoryIterator($this->path . DIRECTORY_SEPARATOR . 'custom');
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $total = count(iterator_to_array($Regex));
        //http://symfony.com/doc/current/components/console/helpers/progressbar.html
        $progress = new ProgressBar($output, $total);
        $progress->setRedrawFrequency(100);
        $progress->setBarWidth(100);
        $progress->setFormat('very_verbose');
        foreach ($Regex as $index => $fileName) {
            $phpFileName = $fileName[0];
            //reset the $commandOutput array so we get back a single result each time
            $commandOutput = array();
            exec("php -l " . $phpFileName, $commandOutput);
            if (substr($commandOutput[0], 0, 25) != 'No syntax errors detected') {
                $errors[] = array('file' => $phpFileName, 'error' => $commandOutput[0]);
            }
            $progress->advance();
        }
        $progress->finish();
        return $errors;
    }

    protected function modulesScan(OutputInterface $output, $errors)
    {
        $Directory = new \RecursiveDirectoryIterator($this->path . DIRECTORY_SEPARATOR . 'modules');
        $Iterator = new \RecursiveIteratorIterator($Directory);
        $Regex = new \RegexIterator($Iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        $total = count(iterator_to_array($Regex));
        //http://symfony.com/doc/current/components/console/helpers/progressbar.html
        $progress = new ProgressBar($output, $total);
        $progress->setRedrawFrequency(100);
        $progress->setBarWidth(100);
        $progress->setFormat('very_verbose');
        foreach ($Regex as $index => $fileName) {
            $phpFileName = $fileName[0];
            //reset the $commandOutput array so we get back a single result each time
            $commandOutput = array();
            exec("php -l " . $phpFileName, $commandOutput);
            if (substr($commandOutput[0], 0, 25) != 'No syntax errors detected') {
                $errors[] = array('file' => $phpFileName, 'error' => $commandOutput[0]);
            }
            $progress->advance();
        }
        $progress->finish();
        return $errors;
    }
}

