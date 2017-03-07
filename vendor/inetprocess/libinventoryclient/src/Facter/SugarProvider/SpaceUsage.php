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
use Inet\Inventory\Utils;

class SpaceUsage extends AbstractSugarProvider
{
    protected function getDiskSpaceUsage()
    {
        $du_output = rtrim($this->exec('/usr/bin/du -B1 -s .', $this->getPath()));
        $matches = array();
        if (preg_match('/(\d+)\s+\.$/', $du_output, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    protected function getDBSpaceUsage()
    {
        $sql = 'SELECT ROUND(SUM( data_length + index_length), 2) As size';
        $sql .= ' FROM information_schema.TABLES';
        $sql .= ' WHERE table_schema = ?';
        $sql .= ' GROUP BY table_schema';
        $stmt = $this->getPdo()->prepare($sql);
        $sugar_config = $this->getApplication()->getSugarConfig();
        $stmt->bindValue(1, $sugar_config['dbconfig']['db_name']);

        return $this->queryOne($stmt);
    }

    public function getFacts()
    {
        $disk_used = $this->getDiskSpaceUsage();
        $db_used = $this->getDBSpaceUsage();
        $facts = array(
            'disk_used_mb' => round($disk_used / (1024*1024), 2),
            'disk_used' => Utils::humanize($disk_used),
            'db_used_mb' => round($db_used / (1024*1024), 2),
            'db_used' => Utils::humanize($db_used),
        );

        return $facts;
    }
}
