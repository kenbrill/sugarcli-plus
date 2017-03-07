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

class UsersInfo extends AbstractSugarProvider
{
    public function getFacts()
    {
        $queries = array(
            'active' => 'SELECT count(*) FROM users WHERE deleted = 0 AND status = "Active"',
            'admin' => 'SELECT count(*) FROM users WHERE deleted = 0 AND status = "Active" AND is_admin = 1',
            'last_session' => 'SELECT MAX(date_end) FROM tracker_sessions',
        );
        $facts = array();
        foreach ($queries as $key => $sql) {
            $stmt = $this->getPdo()->prepare($sql);
            $facts[$key] = $this->queryOne($stmt);
        }
        $facts['active'] = intval($facts['active']);
        $facts['admin'] = intval($facts['admin']);

        return array('users' => $facts);
    }
}
