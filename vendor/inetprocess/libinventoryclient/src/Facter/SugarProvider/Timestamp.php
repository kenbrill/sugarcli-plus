<?php
/**
 * Inventory
 *
 * PHP Version 5.3 -> 5.4
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/inventory
 *
 * @license GNU General Public License v2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\Inventory\Facter\SugarProvider;

use Inet\Inventory\Facter\AbstractSugarProvider;

class Timestamp extends AbstractSugarProvider
{
    public function getFacts()
    {
        $date = new \DateTime();

        return array('facts_timestamp' => $date->getTimestamp());
    }
}
