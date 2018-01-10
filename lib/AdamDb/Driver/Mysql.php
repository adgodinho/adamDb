<?php

require_once dirname(__FILE__).'/Base.php';

/**
 * Mysql Driver
 *
 * @package PicoDb\Driver
 * @author  Frederic Guillot
 */
class Mysql extends Base
{
    /**
     * Table to store the schema version
     *
     * @access private
     * @var array
     */
    private $schemaTable = 'schema_version';

    /**
     * Create a new ADOdb connection
     *
     * @access public
     * @param  array   $settings
     */
    public function createConnection($db)
    {
        $this->adodb = $db;
    }

    /**
     * Build connection DSN
     *
     * @access protected
     * @param  array $settings
     * @return string
     */
    protected function buildDsn(array $settings)
    {
        $charset = empty($settings['charset']) ? 'utf8' : $settings['charset'];
        $dsn = 'mysql:host='.$settings['hostname'].';dbname='.$settings['database'].';charset='.$charset;

        if (! empty($settings['port'])) {
            $dsn .= ';port='.$settings['port'];
        }

        return $dsn;
    }

    /**
     * Build connection options
     *
     * @access protected
     * @param  array $settings
     * @return array
     */
    protected function buildOptions(array $settings)
    {
        $options = array(
            ADOdb::MYSQL_ATTR_INIT_COMMAND => 'SET sql_mode = STRICT_ALL_TABLES',
        );

        if (! empty($settings['ssl_key'])) {
            $options[ADOdb::MYSQL_ATTR_SSL_KEY] = $settings['ssl_key'];
        }

        if (! empty($settings['ssl_cert'])) {
            $options[ADOdb::MYSQL_ATTR_SSL_CERT] = $settings['ssl_cert'];
        }

        if (! empty($settings['ssl_ca'])) {
            $options[ADOdb::MYSQL_ATTR_SSL_CA] = $settings['ssl_ca'];
        }

        if (! empty($settings['persistent'])) {
            $options[ADOdb::ATTR_PERSISTENT] = $settings['persistent'];
        }

        return $options;
    }

    /**
     * Enable foreign keys
     *
     * @access public
     */
    public function enableForeignKeys()
    {
        $this->adodb->execute('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Disable foreign keys
     *
     * @access public
     */
    public function disableForeignKeys()
    {
        $this->adodb->execute('SET FOREIGN_KEY_CHECKS=0');
    }

    /**
     * Return true if the error code is a duplicate key
     *
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    public function isDuplicateKeyError($code)
    {
        return $code == 23000;
    }

    /**
     * Escape identifier
     *
     * @access public
     * @param  string  $identifier
     * @return string
     */
    public function escape($identifier)
    {
        return $identifier;
    }

    /**
     * Cast value
     *
     * @access public
     * @param  string  $value
     * @param  string  $type
     * @return string
     */
    public function cast($value, $type, $option = NULL)
    {
        switch ($type) {
            default:
                return $value;
                break;
        }
    }

    /**
     * Current date value
     *
     * @access public
     * @return string
     */
    public function date()
    {
        return 'CURDATE()';
    }
    
    /**
     * Current timestamp value
     *
     * @access public
     * @return string
     */
    public function timestamp()
    {
        return 'CURRENT_TIMESTAMP()';
    }

    /**
     * Date difference
     *
     * @access public
     * @param  string  $diff
     * @param  string  $date1
     * @param  string  $date2
     * @return string
     */
    public function datediff($diff, $date1, $date2)
    {
        return '';
    }

    /**
     * Get non standard operator
     *
     * @access public
     * @param  string  $operator
     * @return string
     */
    public function getOperator($operator)
    {
        if ($operator === 'LIKE') {
            return 'LIKE BINARY';
        }
        else if ($operator === 'ILIKE') {
            return 'LIKE';
        }

        return '';
    }

    /**
     * Get last inserted id
     *
     * @access public
     * @return integer
     */
    public function getLastId()
    {
        return $this->adodb->lastInsertId();
    }

    /**
     * Get current schema version
     *
     * @access public
     * @return integer
     */
    public function getSchemaVersion()
    {
        $this->adodb->execute("CREATE TABLE IF NOT EXISTS `".$this->schemaTable."` (`version` INT DEFAULT '0') ENGINE=InnoDB CHARSET=utf8");

        $rq = $this->adodb->prepare('SELECT `version` FROM `'.$this->schemaTable.'`');
        $rq->execute();
        $result = $rq->fetchColumn();

        if ($result !== false) {
            return (int) $result;
        }
        else {
            $this->adodb->execute('INSERT INTO `'.$this->schemaTable.'` VALUES(0)');
        }

        return 0;
    }

    /**
     * Set current schema version
     *
     * @access public
     * @param  integer  $version
     */
    public function setSchemaVersion($version)
    {
        $rq = $this->adodb->prepare('UPDATE `'.$this->schemaTable.'` SET `version`=?');
        $rq->execute(array($version));
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

            $sql = sprintf(
                'REPLACE INTO %s (%s, %s) VALUES %s',
                $this->escape($table),
                $this->escape($keyColumn),
                $this->escape($valueColumn),
                implode(', ', array_fill(0, count($dictionary), '(?, ?)'))
            );

            $values = array();

            foreach ($dictionary as $key => $value) {
                $values[] = $key;
                $values[] = $value;
            }

            $rq = $this->adodb->prepare($sql);
            $rq->execute($values);

            return true;
        }
        catch (ADODB_Exception $e) {
            return false;
        }
    }
}
