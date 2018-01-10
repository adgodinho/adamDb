<?php

/**
 * Base Driver class
 *
 * @package PicoDb\Driver
 * @author  Frederic Guillot
 */
abstract class Base
{
    /**
     * List of required settings options
     *
     * @access protected
     * @var array
     */
    protected $requiredAttributes = array();

    /**
     * ADOdb connection
     *
     * @access protected
     * @var ADOdb
     */
    protected $adodb = null;

    /**
     * Create a new ADOdb connection
     *
     * @abstract
     * @access public
     * @param  array   $settings
     */
    abstract public function createConnection($db);

    /**
     * Enable foreign keys
     *
     * @abstract
     * @access public
     */
    abstract public function enableForeignKeys();

    /**
     * Disable foreign keys
     *
     * @abstract
     * @access public
     */
    abstract public function disableForeignKeys();

    /**
     * Return true if the error code is a duplicate key
     *
     * @abstract
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    abstract public function isDuplicateKeyError($code);

    /**
     * Escape identifier
     *
     * @abstract
     * @access public
     * @param  string  $identifier
     * @return string
     */
    abstract public function escape($identifier);

    /**
     * Cast value
     *
     * @abstract
     * @access public
     * @param  string  $value
     * @param  string  $type
     * @return string
     */
    abstract public function cast($value, $type, $option = NULL);

    /**
     * Current date value
     *
     * @abstract
     * @access public
     * @return string
     */
    abstract public function date();

    /**
     * Date difference
     *
     * @abstract
     * @access public
     * @param  string  $diff
     * @param  string  $date1
     * @param  string  $date2
     * @return string
     */
    abstract public function datediff($diff, $date1, $date2);

    /**
     * Current timestamp value
     *
     * @abstract
     * @access public
     * @return string
     */
    abstract public function timestamp();

    /**
     * Get non standard operator
     *
     * @abstract
     * @access public
     * @param  string  $operator
     * @return string
     */
    abstract public function getOperator($operator);

    /**
     * Get last inserted id
     *
     * @abstract
     * @access public
     * @return integer
     */
    abstract public function getLastId();

    /**
     * Get current schema version
     *
     * @abstract
     * @access public
     * @return integer
     */
    abstract public function getSchemaVersion();

    /**
     * Set current schema version
     *
     * @abstract
     * @access public
     * @param  integer  $version
     */
    abstract public function setSchemaVersion($version);

    /**
     * Constructor
     *
     * @access public
     * @param  array   $settings
     */
    public function __construct($db)
    {
        $this->createConnection($db);
        // $this->adodb->setAttribute(ADOdb::ATTR_ERRMODE, ADOdb::ERRMODE_EXCEPTION);
    }

    /**
     * Get the ADOdb connection
     *
     * @access public
     * @return ADOdb
     */
    public function getConnection()
    {
        return $this->adodb;
    }

    /**
     * Release the ADOdb connection
     *
     * @access public
     */
    public function closeConnection()
    {
        $this->adodb = null;
    }

    /**
     * Upsert for a key/value variable
     *
     * @access public
     * @param  string  $table
     * @param  string  $keyColumn
     * @param  string  $valueColumn
     * @param  array   $dictionary
     * @return bool    False on failure
     */
    public function upsert($table, $keyColumn, $valueColumn, array $dictionary)
    {
        try {
            $this->adodb->beginTransaction();

            foreach ($dictionary as $key => $value) {

                $rq = $this->adodb->prepare('SELECT 1 FROM '.$this->escape($table).' WHERE '.$this->escape($keyColumn).'=?');
                $rq->execute(array($key));

                if ($rq->fetchColumn()) {
                    $rq = $this->adodb->prepare('UPDATE '.$this->escape($table).' SET '.$this->escape($valueColumn).'=? WHERE '.$this->escape($keyColumn).'=?');
                    $rq->execute(array($value, $key));
                }
                else {
                    $rq = $this->adodb->prepare('INSERT INTO '.$this->escape($table).' ('.$this->escape($keyColumn).', '.$this->escape($valueColumn).') VALUES (?, ?)');
                    $rq->execute(array($key, $value));
                }
            }

            $this->adodb->commit();

            return true;
        }
        catch (ADODB_Exception $e) {
            $this->adodb->rollBack();
            return false;
        }
    }

    /**
     * Run EXPLAIN command
     *
     * @access public
     * @param  string $sql
     * @param  array  $values
     * @return array
     */
    public function explain($sql, array $values)
    {
        return $this->getConnection()->query('EXPLAIN '.$this->getSqlFromPreparedStatement($sql, $values))->fetchAll(ADOdb::FETCH_ASSOC);
    }

    /**
     * Replace placeholder with values in prepared statement
     *
     * @access protected
     * @param  string $sql
     * @param  array  $values
     * @return string
     */
    protected function getSqlFromPreparedStatement($sql, array $values)
    {
        foreach ($values as $value) {
            $sql = substr_replace($sql, "'$value'", strpos($sql, '?'), 1);
        }

        return $sql;
    }

    /**
     * Get database version
     *
     * @access public
     * @return array
     */
    public function getDatabaseVersion()
    {
        return $this->getConnection()->query('SELECT VERSION()')->fetchColumn();
    }
}
