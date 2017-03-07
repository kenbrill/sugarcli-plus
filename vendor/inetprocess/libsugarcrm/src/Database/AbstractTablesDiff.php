<?php
/**
 * SugarCRM Tools
 *
 * PHP Version 5.3 -> 5.6
 * SugarCRM Versions 6.5 - 7.6
 *
 * @author Rémi Sauvat
 * @copyright 2005-2015 iNet Process
 *
 * @package inetprocess/sugarcrm
 *
 * @license Apache License 2.0
 *
 * @link http://www.inetprocess.com
 */

namespace Inet\SugarCRM\Database;

use PDO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Inet\SugarCRM\Exception\SugarException;

/**
 * Manage any table to make diffs
 */
abstract class AbstractTablesDiff
{
    const ADD = 0;
    const DEL = 1;
    const UPDATE = 2;

    const BASE = 0;
    const MODIFIED = 1;

    const DIFF_NONE = 0;
    const DIFF_ADD = 1;
    const DIFF_DEL = 2;
    const DIFF_UPDATE = 4;
    const DIFF_ALL = 7;

    protected $tableName = null;

    /**
     * Logger
     * @var
     */
    protected $logger;

    /**
     * Database instance
     * @var
     */
    protected $pdo;

    /**
     * Query Factory
     * @var
     */
    protected $query_factory;

    /**
     * Path of relationships definition file.
     * @var
     */
    protected $defFile;

    public function __construct(LoggerInterface $logger, PDO $pdo, $defFile = null)
    {
        $this->logger = $logger;
        $this->pdo = $pdo;
        $this->defFile = $defFile;

        $this->query_factory = new QueryFactory($this->getPdo());
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function getQueryFactory()
    {
        return $this->query_factory;
    }

    public function setDefFile($defFile)
    {
        $this->defFile = $defFile;

        return $this;
    }

    abstract public function loadFromDb();

    abstract public function loadFromFile();

    /**
     * Compute the difference between two metadata arrays.
     * @param $base Base or old array.
     * @param $new New array with new definitions.
     * @param $add If true find new fields. Default: true
     * @param $del If true find fields to delete. Default: true
     * @param $update if true find modified fields; Default: true
     * @param $field_ids Array for field name to filter the results.
     * @return array Return a 3-row array for add, del and update fields.
     */
    public function diff($base, $new, $mode = self::DIFF_ALL, array $field_ids = array())
    {
        if (!empty($field_ids)) {
            $field_ids = array_flip($field_ids);
            $base = array_intersect_key($base, $field_ids);
            $new = array_intersect_key($new, $field_ids);
        }
        $res = array(
            self::ADD => array(),
            self::DEL => array(),
            self::UPDATE => array()
        );
        if ($mode & self::DIFF_ADD) {
            $res[self::ADD] = array_diff_key($new, $base);
        }
        if ($mode & self::DIFF_DEL) {
            $res[self::DEL] = array_diff_key($base, $new);
        }
        if ($mode & self::DIFF_UPDATE) {
            // Update array will have common fields with different data.
            $common = array_intersect_key($new, $base);
            foreach ($common as $key => $newData) {
                $new_data = array_diff_assoc($newData, $base[$key]);
                if (!empty($new_data)) {
                    $res[self::UPDATE][$key][self::BASE] = $base[$key];
                    $res[self::UPDATE][$key][self::MODIFIED] = $new_data;
                }
            }
        }

        return $res;
    }

    /**
     * Build Query for add field
     */
    public function createAddQuery(array $field_data)
    {
        return $this->getQueryFactory()
                    ->createInsertQuery($this->tableName, $field_data);
    }

    /**
     * Build query for fields to delete
     */
    public function createDeleteQuery(array $field_data)
    {
        return $this->getQueryFactory()
                    ->createDeleteQuery($this->tableName, $field_data['id']);
    }

    /**
     *  Build query to update fields.
     */
    public function createUpdateQuery(array $field_data)
    {
        return $this->getQueryFactory()
                    ->createUpdateQuery($this->tableName, $field_data[self::BASE]['id'], $field_data[self::MODIFIED]);
    }

    /**
     * Get the sql query string for a query.
     */
    public function getSqlQuery(Query $query)
    {
        return $query->getRawSql();
    }

    /**
     * Get the queries for a diff result
     */
    public function generateQueries(array $diff_res)
    {
        $queries = array();
        foreach ($diff_res[self::ADD] as $new_field) {
            $queries[] = $this->createAddQuery($new_field);
        }
        foreach ($diff_res[self::DEL] as $del_field) {
            $queries[] = $this->createDeleteQuery($del_field);
        }
        foreach ($diff_res[self::UPDATE] as $mod_field) {
            $queries[] = $this->createUpdateQuery($mod_field);
        }
        return $queries;
    }

    /**
     * Get the all the sql queries for a diff result.
     */
    public function generateSqlQueries(array $diff_res)
    {
        $queries = $this->generateQueries($diff_res);
        $sql = '';
        foreach ($queries as $query) {
            $sql .= $this->getSqlQuery($query) . ";\n";
        }
        return $sql;
    }

    /**
     * Execute DB queries for a diff result
     */
    public function executeQueries(array $diff_res)
    {
        $this->getLogger()->debug('Running sql queries.');
        $queries = $this->generateQueries($diff_res);
        foreach ($queries as $query) {
            $query->execute();
        }
    }

    /**
     * Merge base metadata array with modifications from diff result
     */
    public function getMergedData(array $base, array $diff_res)
    {
        $res = $base + $diff_res[self::ADD];
        $res = array_diff_key($res, $diff_res[self::DEL]);
        foreach ($diff_res[self::UPDATE] as $field_id => $values) {
            $new_values = array_merge($values[self::BASE], $values[self::MODIFIED]);
            $res[$field_id] = $new_values;
        }
        return $res;
    }

    /**
     * Write to file
     */
    public function writeFile(array $diff_res)
    {
        $base = array();
        if (is_readable($this->defFile)) {
            $base = $this->loadFromFile();
        }
        $merged_data = $this->getMergedData($base, $diff_res);
        ksort($merged_data);
        $yaml = Yaml::dump(array_values($merged_data));
        if (@file_put_contents($this->defFile, $yaml) === false) {
            throw new SugarException("Unable to dump metadata file to {$this->defFile}.");
        }
    }
}
