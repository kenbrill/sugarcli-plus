<?php
/**
 * Inet Data Anonymization
 *
 * PHP Version 5.3 -> 7.0
 *
 * @author Emmanuel Dyan
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/neuralyzer
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\Neuralyzer\Console;

use Symfony\Component\Console\Application as BaseApplication;

/**
 * Run console application.
 */
class Application extends BaseApplication
{
    /**
     * Init commands
     *
     * @return Command[] An array of default Command instances
     */
    public function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new \Inet\Neuralyzer\Console\Commands\AnonRunCommand();
        $commands[] = new \Inet\Neuralyzer\Console\Commands\ConfigGenerateCommand();

        return $commands;
    }
}
